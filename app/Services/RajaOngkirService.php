<?php

namespace App\Services;

use App\Exceptions\RajaOngkirException;
use App\Support\CaBundle;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException as CacheLockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Klien API resmi RajaOngkir (Komerce) — Docs V2.
 *
 * Base URL : https://rajaongkir.komerce.id/api/v1
 * Auth     : header "key: {RAJAONGKIR_API_KEY}" (GET & POST)
 *
 * - GET  /destination/domestic-destination?search=&limit=&offset=
 * - POST /calculate/domestic-cost (x-www-form-urlencoded:
 *        origin, destination, weight, courier)
 *
 * Kuota Starter 100 request/hari → pencarian tujuan di-cache.
 * Semua error dipetakan ke RajaOngkirException dengan HTTP status
 * 422 (request tidak valid) / 502 (upstream) / 503 (koneksi gagal).
 */
class RajaOngkirService
{
    /**
     * Kurir yang didukung API cost RajaOngkir (Komerce) untuk proyek ini.
     * Dipakai sebagai daftar default oleh validasi checkout, POST
     * /shipping/cost, dan konfigurasi kurir admin (settings).
     *
     * Hanya memuat kode yang benar-benar dikenal endpoint
     * /calculate/domestic-cost (lihat pesan 422 "the valid courier is ...").
     * Kode lama `jxpress` & `lex` tidak dikenal API sehingga memicu 422.
     */
    public const SUPPORTED_COURIERS = [
        'jne',
        'pos',
        'tiki',
        'sicepat',
        'jnt',
        'anteraja',
        'ninja',
        'lion',
        'ide',
        'sap',
        'ncs',
        'rex',
        'rpx',
        'sentral',
        'star',
        'wahana',
        'spx',
    ];

    private const CACHE_TTL_DAYS = 7;

    /**
     * Sentinel agar hasil cache "daftar kosong" tetap bisa dibedakan dari
     * cache-miss (Cache::get($key, default) tidak bisa membedakan keduanya
     * bila nilai yang disimpan memang berupa array kosong).
     */
    private const CACHE_MISS = '__MISSING__';

    private const CONNECT_TIMEOUT_SECONDS = 8;

    private const TIMEOUT_SECONDS = 15;

    private const MAX_ATTEMPTS = 2;

    private const RETRY_DELAY_MS = 250;

    /**
     * Detik tunggu saat memperebutkan lock cache ongkir (anti cache stampede).
     * Sangat singkat karena satu request upstream normalnya < 5 detik; bila
     * lock tetap tidak didapat, request tetap jalan (hasil tidak di-cache).
     */
    private const COST_LOCK_WAIT_SECONDS = 5;

    /** Detik lock ditahan sebagai kunci Cache::lock. */
    private const COST_LOCK_TTL_SECONDS = 10;

    public function __construct(private readonly ShippingConfigService $shippingConfig) {}

    public function originId(): ?string
    {
        // Sumber utama: settings (ubah-lewat-admin), fallback config/.env.
        $origin = $this->shippingConfig->originId();

        return $origin === '' ? null : $origin;
    }

    /**
     * Cari daftar tujuan (per kelurahan) berdasarkan keyword.
     *
     * @return array<int, array<string, mixed>> item {id, label, province_name, city_name, district_name, subdistrict_name, zip_code}
     *
     * @throws RajaOngkirException
     */
    public function destinations(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $cacheKey = 'rajaongkir:destinations:'.sha1(mb_strtolower($search));

        /** @var array<int, array<string, mixed>> */
        return Cache::remember($cacheKey, now()->addDays(self::CACHE_TTL_DAYS), function () use ($search) {
            $response = $this->send('GET', '/destination/domestic-destination', [
                'search' => $search,
                'limit' => 50,
                'offset' => 0,
            ]);

            return $this->extractDestinations($response);
        });
    }

    /**
     * Hitung ongkos kirim untuk satu kurir.
     *
     * @param  string  $destinationId  ID tujuan hasil endpoint destinations()
     * @param  int  $weightGram  berat total dalam gram
     * @param  string  $courier  kode kurir tunggal (jne, tiki, sicepat, ...)
     * @return array<int, array<string, mixed>> item {name, code, service, description, cost, etd}
     *
     * @throws RajaOngkirException
     */
    public function cost(string $destinationId, int $weightGram, string $courier): array
    {
        $origin = $this->requireOrigin();

        $response = $this->send('POST', '/calculate/domestic-cost', [
            'origin' => $origin,
            'destination' => $destinationId,
            'weight' => $weightGram,
            'courier' => strtolower(trim($courier)),
        ], form: true);

        return $this->extractCosts($response);
    }

    /**
     * Hitung ongkos kirim untuk BANYAK kurir sekaligus dalam SATU request
     * (parameter `courier` dipisah titik dua, sesuai Docs V2). Ini menghemat
     * kuota (1 request, bukan N) sekaligus mengisolasi kurir yang tidak
     * menyediakan tarif: hasil kurir yang ada tetap dikembalikan.
     *
     * @param  array<int, string>  $couriers  daftar kode kurir
     * @return array<int, array<string, mixed>> item {name, code, service, description, cost, etd}
     *
     * @throws RajaOngkirException hanya untuk kegagalan menyeluruh (auth/koneksi/kuota/upstream)
     */
    public function costMany(string $destinationId, int $weightGram, array $couriers): array
    {
        $origin = $this->requireOrigin();

        $codes = array_values(array_unique(array_filter(
            array_map(fn ($c) => strtolower(trim((string) $c)), $couriers),
            fn ($c) => $c !== ''
        )));

        if ($codes === []) {
            return [];
        }

        $response = $this->send('POST', '/calculate/domestic-cost', [
            'origin' => $origin,
            'destination' => $destinationId,
            'weight' => $weightGram,
            'courier' => implode(':', $codes),
        ], form: true);

        $metaCode = (int) ($response->json('meta.code') ?? 0);
        $data = $response->json('data');

        // Kurir gabungan tidak menyediakan data sama sekali (mis. semua kurir
        // tidak melayani rute) → kembalikan kosong, bukan melempar error yang
        // menghilangkan kurir lain yang valid.
        if ($metaCode === 404 || $metaCode === 400) {
            return [];
        }

        if (! is_array($data)) {
            throw new RajaOngkirException('RAJAONGKIR_INVALID_RESPONSE', 502);
        }

        return array_values($data);
    }

    /**
     * Hitung ongkos kirim untuk beberapa kurir dan kembalikan status per kurir
     * sehingga kegagalan satu kurir tidak menghapus tarif kurir lain.
     *
     * @param  array<int, string>  $couriers
     * @return array{services: array<int, array<string, mixed>>, unavailable: array<int, array{courier: string, reason: string}>}
     *
     * @throws RajaOngkirException
     */
    public function costByCourier(string $destinationId, int $weightGram, array $couriers): array
    {
        $requested = array_values(array_unique(array_filter(
            array_map(fn ($c) => strtolower(trim((string) $c)), $couriers),
            fn ($c) => $c !== ''
        )));

        $services = $this->costMany($destinationId, $weightGram, $requested);

        $availableCodes = [];
        foreach ($services as $service) {
            $code = strtolower((string) ($service['code'] ?? ''));
            if ($code !== '') {
                $availableCodes[$code] = true;
            }
        }

        $unavailable = [];
        foreach ($requested as $code) {
            if (! isset($availableCodes[$code])) {
                $unavailable[] = [
                    'courier' => $code,
                    'reason' => 'Kurir tidak menyediakan tarif untuk rute/berat ini.',
                ];
            }
        }

        return [
            'services' => array_values($services),
            'unavailable' => $unavailable,
        ];
    }

    /**
     * cekOngkir(): versi costByCourier() yang di-cache per kurir selama
     * CACHE_TTL_DAYS hari untuk menghemat kuota harian RajaOngkir.
     *
     * Strategi:
     * - Kurir yang tarifnya sudah ada di cache (hasil non-kosong) tidak
     *   diambil ulang; kurir sisanya diambil dalam SATU request costMany().
     * - Semua hasil sukses (kurir tersedia maupun "tidak melayani") di-cache
     *   7 hari. Hasil "tidak melayani" sengaja ikut di-cache karena 404/400
     *   dari API tetap terhitung kuota; kode layanan baru pada rute yang sama
     *   sangat jarang berubah dalam 7 hari.
     * - Cache hanya dipakai bila seluruh kurir yang diminta punya entry.
     *   Kurir tanpa entry (upstream gagal saat pengambilan) TIDAK di-cache
     *   agar request berikutnya bisa mencoba lagi.
     * - Cache::lock mencegah beberapa request bersamaan menembak API
     *   berkali-kali untuk key yang sama.
     *
     * @param  array<int, string>  $couriers
     * @return array{services: array<int, array<string, mixed>>, unavailable: array<int, array{courier: string, reason: string}>, cache: array{origin: string, weight: int, couriers_checked: array<int, string>, couriers_cached: array<int, string>, couriers_fetched: array<int, string>, ttl_days: int}}
     *
     * @throws RajaOngkirException
     */
    public function cekOngkir(string $destinationId, int $weightGram, array $couriers): array
    {
        $origin = $this->requireOrigin();

        $requested = array_values(array_unique(array_filter(
            array_map(fn ($c) => strtolower(trim((string) $c)), $couriers),
            fn ($c) => $c !== ''
        )));

        $cachedCouriers = [];
        $preExistingCouriers = [];
        $fetchedCouriers = [];
        $policy = [];

        // Kurir yang tarifnya sudah tersimpan di cache: pakai tanpa API call.
        foreach ($requested as $code) {
            $hit = Cache::get($this->costCacheKey($origin, $destinationId, $weightGram, $code), self::CACHE_MISS);

            if ($hit !== self::CACHE_MISS) {
                $cachedCouriers[$code] = is_array($hit) ? $hit : [];
                $preExistingCouriers[] = $code;
                $policy[$code] = true;
            }
        }

        $missing = array_values(array_diff($requested, array_keys($cachedCouriers)));

        if ($missing !== []) {
            $fetched = $this->fetchMissingCostsWithLock($origin, $destinationId, $weightGram, $missing);

            foreach ($missing as $code) {
                if (! array_key_exists($code, $fetched)) {
                    // Gagal upstream: jangan cache agar bisa dicoba lagi nanti.
                    continue;
                }

                $services = is_array($fetched[$code]) ? array_values($fetched[$code]) : [];
                $cachedCouriers[$code] = $services;
                $policy[$code] = true;
                $fetchedCouriers[] = $code;

                Cache::put(
                    $this->costCacheKey($origin, $destinationId, $weightGram, $code),
                    $services,
                    now()->addDays(self::CACHE_TTL_DAYS)
                );
            }
        }

        $services = [];

        foreach ($requested as $code) {
            if (isset($cachedCouriers[$code])) {
                $services = array_merge($services, $cachedCouriers[$code]);
            }
        }

        // Urutkan tarif termurah lebih dulu; kode kurir jadi tie-breaker agar
        // urutan stabil dan tidak bergantung urutan iterasi cache.
        usort($services, function (array $a, array $b) {
            return ($a['cost'] ?? PHP_INT_MAX) <=> ($b['cost'] ?? PHP_INT_MAX)
                ?: strcmp((string) ($a['code'] ?? ''), (string) ($b['code'] ?? ''))
                ?: strcmp((string) ($a['service'] ?? ''), (string) ($b['service'] ?? ''));
        });

        $unavailable = [];
        $availableCodes = [];

        foreach ($services as $service) {
            $code = strtolower((string) ($service['code'] ?? ''));

            if ($code !== '') {
                $availableCodes[$code] = true;
            }
        }

        foreach ($requested as $code) {
            if (! isset($availableCodes[$code]) || ($policy[$code] ?? false) === false) {
                $unavailable[] = [
                    'courier' => $code,
                    'reason' => 'Kurir tidak menyediakan tarif untuk rute/berat ini.',
                ];
            }
        }

        $cacheMeta = [
            'origin' => $origin,
            'weight' => $weightGram,
            'couriers_checked' => $requested,
            'couriers_cached' => $preExistingCouriers,
            'couriers_fetched' => $fetchedCouriers,
            'ttl_days' => self::CACHE_TTL_DAYS,
        ];

        return [
            'services' => array_values($services),
            'unavailable' => $unavailable,
            'cache' => $cacheMeta,
        ];
    }

    /**
     * Ambil tarif kurir yang belum ada di cache dalam SATU panggilan costMany()
     * (satu request upstream, satu beban kuota). Dibungkus Cache::lock supaya
     * request bersamaan tidak menembak API untuk key yang sama; lock opsional
     * (bila store cache tidak mendukung lock, tetap jalan tanpa lock).
     *
     * @param  array<int, string>  $missing
     * @return array<string, array<int, array<string, mixed>>> peta kode kurir => daftar layanan
     *
     * @throws RajaOngkirException
     */
    private function fetchMissingCostsWithLock(string $origin, string $destinationId, int $weightGram, array $missing): array
    {
        $lockKey = 'rajaongkir:cost:lock:'.sha1($origin.'|'.$destinationId.'|'.$weightGram.'|'.implode(',', $missing));
        $lock = null;

        try {
            /** @var Lock $lock */
            $lock = Cache::lock($lockKey, self::COST_LOCK_TTL_SECONDS);
        } catch (\Throwable) {
            // Driver cache tanpa dukungan lock (mis. array/database): lewati.
            return $this->groupByCourier($this->costMany($destinationId, $weightGram, $missing));
        }

        try {
            return $lock->block(self::COST_LOCK_WAIT_SECONDS, function () use ($destinationId, $weightGram, $missing, $origin) {
                $stillMissing = [];

                // Setelah dapat lock, cek ulang cache: request lain yang baru
                // selesai mungkin sudah mengisinya (double-checked locking).
                foreach ($missing as $code) {
                    if (Cache::get($this->costCacheKey($origin, $destinationId, $weightGram, $code), self::CACHE_MISS) === self::CACHE_MISS) {
                        $stillMissing[] = $code;
                    }
                }

                if ($stillMissing === []) {
                    return [];
                }

                return $this->groupByCourier($this->costMany($destinationId, $weightGram, $stillMissing));
            });
        } catch (CacheLockTimeoutException) {
            // Lock tak didapat dalam batas waktu: tetap ambil tanpa lock agar
            // pengguna tidak terlantar (kuota tetap lebih baik dari error).
            return $this->groupByCourier($this->costMany($destinationId, $weightGram, $missing));
        }
    }

    /**
     * Kelompokkan daftar layanan hasil costMany() menjadi peta kode kurir => layanan.
     *
     * @param  array<int, array<string, mixed>>  $services
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupByCourier(array $services): array
    {
        $grouped = [];

        foreach ($services as $service) {
            $code = strtolower((string) ($service['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $grouped[$code][] = $service;
        }

        return $grouped;
    }

    /**
     * Kunci cache tarif: origin + tujuan + berat + kurir + versi skema.
     * Kunci di-hash (sha1) agar panjang & karakter aman di semua driver cache.
     */
    private function costCacheKey(string $origin, string $destinationId, int $weightGram, string $courier): string
    {
        return 'rajaongkir:cost:v1:'.sha1(implode('|', [
            $origin,
            $destinationId,
            $weightGram,
            strtolower($courier),
        ]));
    }

    private function requireOrigin(): string
    {
        $origin = $this->originId();

        if ($origin === null) {
            throw new RajaOngkirException('RAJAONGKIR_NOT_CONFIGURED', 502);
        }

        return $origin;
    }

    /**
     * Kirim request dengan header auth "key" dan petakan error jadi
     * RajaOngkirException (422/502/503).
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws RajaOngkirException
     */
    private function send(string $method, string $uri, array $payload, bool $form = false): Response
    {
        $apiKey = (string) config('services.rajaongkir.api_key');
        $baseUrl = rtrim((string) config('services.rajaongkir.base_url', 'https://rajaongkir.komerce.id/api/v1'), '/');

        if ($apiKey === '') {
            throw new RajaOngkirException('RAJAONGKIR_NOT_CONFIGURED', 502);
        }

        try {
            $pending = Http::withHeaders(['key' => $apiKey])
                ->withOptions(CaBundle::verifyOptions(config('services.rajaongkir.verify')))
                ->acceptJson()
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->retry(self::MAX_ATTEMPTS, self::RETRY_DELAY_MS, self::isRetryable(...), throw: false);

            $response = $method === 'POST'
                ? ($form ? $pending->asForm()->post($baseUrl.$uri, $payload) : $pending->post($baseUrl.$uri, $payload))
                : $pending->get($baseUrl.$uri, $payload);
        } catch (ConnectionException $e) {
            throw new RajaOngkirException('RAJAONGKIR_UNAVAILABLE', 503);
        }

        $status = $response->status();

        if ($status === 401 || $status === 403) {
            throw new RajaOngkirException('RAJAONGKIR_UNAUTHORIZED', 502);
        }

        if ($status === 429) {
            throw new RajaOngkirException('RAJAONGKIR_QUOTA_EXCEEDED', 429);
        }

        if ($status >= 500) {
            throw new RajaOngkirException('RAJAONGKIR_UPSTREAM_ERROR', 502);
        }

        $meta = $response->json('meta');

        if (! is_array($meta)) {
            throw new RajaOngkirException('RAJAONGKIR_INVALID_RESPONSE', 502);
        }

        $metaCode = (int) ($meta['code'] ?? $status);

        if ($metaCode === 429) {
            throw new RajaOngkirException('RAJAONGKIR_QUOTA_EXCEEDED', 429);
        }

        if ($metaCode >= 500) {
            throw new RajaOngkirException('RAJAONGKIR_UPSTREAM_ERROR', 502);
        }

        if ($metaCode === 401 || $metaCode === 403) {
            throw new RajaOngkirException('RAJAONGKIR_UNAUTHORIZED', 502);
        }

        if ($metaCode === 422 || $metaCode === 400) {
            throw new RajaOngkirException('RAJAONGKIR_INVALID_REQUEST', 422);
        }

        return $response;
    }

    /**
     * Retry hanya untuk gangguan sementara: koneksi gagal atau upstream 5xx.
     * 4xx (mis. 422/404) tidak diulang agar kuota tidak terbuang & respons cepat.
     */
    private static function isRetryable(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        return $e instanceof RequestException
            && ($e->response?->status() ?? 0) >= 500;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractDestinations(Response $response): array
    {
        $data = $response->json('data');

        if (! is_array($data)) {
            $metaCode = (int) ($response->json('meta.code') ?? 0);

            if ($metaCode === 404) {
                return [];
            }

            throw new RajaOngkirException('RAJAONGKIR_INVALID_RESPONSE', 502);
        }

        return array_values($data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractCosts(Response $response): array
    {
        $metaCode = (int) ($response->json('meta.code') ?? 0);
        $data = $response->json('data');

        if ($metaCode === 404) {
            throw new RajaOngkirException('RAJAONGKIR_DESTINATION_NOT_FOUND', 422);
        }

        if (! is_array($data)) {
            throw new RajaOngkirException('RAJAONGKIR_INVALID_RESPONSE', 502);
        }

        if ($data === []) {
            throw new RajaOngkirException('RAJAONGKIR_NO_SERVICE', 422);
        }

        return array_values($data);
    }
}
