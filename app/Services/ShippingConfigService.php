<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Konfigurasi pengiriman yang boleh diubah lewat API admin.
 *
 * Sumber nilai: tabel `settings` (perubahan admin) lebih dulu, lalu
 * fallback ke config/.env (`services.rajaongkir.*`) dan daftar kurir
 * default RajaOngkirService::SUPPORTED_COURIERS. Kredensial (API key)
 * TIDAK pernah disimpan di settings -- tetap di .env.
 */
class ShippingConfigService
{
    public const KEY_ORIGIN = 'rajaongkir.origin_id';

    public const KEY_COURIERS = 'rajaongkir.kurir_aktif';

    private const CACHE_PREFIX = 'settings:shipping.';

    /**
     * TTL cache pembacaan settings. Lebih pendek dari sebelumnya (forever)
     * agar bila row DB diubah/dihapus di luar API, nilai basi otomatis
     * pulih sendiri tanpa perlu flush cache manual.
     */
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Sentinel untuk menandai hasil "row tidak ada" agar nilai null ikut
     * ter-cache (Cache::remember tidak menyimpan null). Mencegah nilai lama
     * tertinggal ketika row DB dihapus.
     */
    private const CACHE_MISS = '__MISSING__';

    public function originId(): string
    {
        $override = $this->read(self::KEY_ORIGIN);

        if ($override !== null) {
            return (string) $override;
        }

        $origin = config('services.rajaongkir.origin_id');

        return $origin === null || $origin === '' ? '' : (string) $origin;
    }

    public function setOriginId(string $originId): void
    {
        $this->write(self::KEY_ORIGIN, trim($originId));
    }

    /**
     * @return array<int, string> kode kurir aktif (huruf kecil)
     */
    public function activeCouriers(): array
    {
        $override = $this->read(self::KEY_COURIERS);

        if ($override !== null) {
            $decoded = json_decode((string) $override, true);

            if (is_array($decoded)) {
                return array_values(array_filter(
                    array_map(fn ($code) => strtolower((string) $code), $decoded),
                    fn ($code) => in_array($code, RajaOngkirService::SUPPORTED_COURIERS, true)
                ));
            }
        }

        return RajaOngkirService::SUPPORTED_COURIERS;
    }

    /**
     * @param  array<int, string>  $couriers  kode kurir huruf kecil
     */
    public function setActiveCouriers(array $couriers): void
    {
        $normalized = [];

        foreach ($couriers as $code) {
            $code = strtolower(trim((string) $code));

            if (in_array($code, RajaOngkirService::SUPPORTED_COURIERS, true) && ! in_array($code, $normalized, true)) {
                $normalized[] = $code;
            }
        }

        $this->write(self::KEY_COURIERS, json_encode(array_values($normalized)));
    }

    /**
     * Semua kurir yang dikenal beserta status aktifnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public function courierList(): array
    {
        $active = $this->activeCouriers();

        return array_map(
            fn (string $code) => [
                'kode' => $code,
                'aktif' => in_array($code, $active, true),
            ],
            RajaOngkirService::SUPPORTED_COURIERS
        );
    }

    public function isActiveCourier(string $code): bool
    {
        return in_array(strtolower($code), $this->activeCouriers(), true);
    }

    public function apiKeyConfigured(): bool
    {
        return (string) config('services.rajaongkir.api_key') !== '';
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.rajaongkir.base_url', 'https://rajaongkir.komerce.id/api/v1'), '/');
    }

    private function read(string $key): ?string
    {
        // Tabel settings mungkin belum ada (mis. skema uji lama) -- anggap
        // belum ada perubahan admin sehingga fallback config/.env dipakai.
        if (! Schema::hasTable('settings')) {
            return null;
        }

        $cached = Cache::remember(
            self::CACHE_PREFIX.$key,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($key) {
                $setting = Setting::where('kunci', $key)->first();

                // Sentinel dipakai agar hasil "tidak ada" ikut ter-cache,
                // bukan menyisakan nilai lama (mis. origin_id basi).
                return $setting === null ? self::CACHE_MISS : (string) $setting->nilai;
            }
        );

        return $cached === self::CACHE_MISS ? null : (string) $cached;
    }

    private function write(string $key, string $value): void
    {
        $setting = Setting::firstOrNew(['kunci' => $key]);

        if (! $setting->exists) {
            $setting->id_setting = (string) Str::uuid();
        }

        $setting->nilai = $value;
        $setting->save();

        $this->forgetCached($key);
    }

    /**
     * Bersihkan cache settings pengiriman. Tanpa argumen menghapus seluruh
     * entry (dipakai perintah artisan `shipping:clear-cache`), dengan argumen
     * hanya entry terkait.
     */
    public function forgetCached(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget(self::CACHE_PREFIX.$key);

            return;
        }

        Cache::forget(self::CACHE_PREFIX.self::KEY_ORIGIN);
        Cache::forget(self::CACHE_PREFIX.self::KEY_COURIERS);
    }
}
