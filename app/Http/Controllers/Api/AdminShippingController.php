<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RajaOngkirException;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\RajaOngkirService;
use App\Services\ShippingConfigService;
use App\Support\CaBundle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Konfigurasi & uji coba pengiriman RajaOngkir (admin).
 *
 * GET  /api/admin/shipping/config            baca konfigurasi (origin & kurir aktif)
 * PUT  /api/admin/shipping/config            ubah origin pengiriman (disimpan ke settings)
 * GET  /api/admin/shipping/health            cek kesehatan konfigurasi TANPA memakai kuota API
 * GET  /api/admin/shipping/couriers          daftar kurir + status aktif
 * PUT  /api/admin/shipping/couriers/{code}   aktif/nonaktifkan satu kurir
 * POST /api/admin/shipping/simulate          cek ongkir multi-kurir (MENGGUNAKAN kuota RajaOngkir)
 *
 * API key tidak pernah dikembalikan maupun disimpan lewat endpoint ini;
 * perubahan origin/courier ditulis ke tabel `settings` dan menjadi sumber
 * utama bila ada, selain itu fallback ke config/.env.
 */
class AdminShippingController extends Controller
{
    public function __construct(
        private readonly ShippingConfigService $shippingConfig,
        private readonly RajaOngkirService $rajaongkir
    ) {}

    public function config(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi pengiriman berhasil diambil.',
            'data' => $this->configPayload(),
        ]);
    }

    public function updateConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_id' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
        ]);

        $this->shippingConfig->setOriginId($validated['origin_id']);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi pengiriman berhasil diperbarui.',
            'data' => $this->configPayload(),
        ]);
    }

    public function health(): JsonResponse
    {
        $baseUrl = $this->shippingConfig->baseUrl();
        $parsed = parse_url($baseUrl);
        $host = is_array($parsed) && isset($parsed['host']) ? (string) $parsed['host'] : '';

        $checks = [
            'api_key_terisi' => $this->shippingConfig->apiKeyConfigured(),
            'base_url_valid' => $host !== '' && filter_var($baseUrl, FILTER_VALIDATE_URL) !== false,
            'origin_terisi' => $this->shippingConfig->originId() !== '',
            'konektivitas_https' => $this->checkConnectivity($baseUrl),
        ];

        $requiredOk = $checks['api_key_terisi'] && $checks['base_url_valid'] && $checks['origin_terisi'];
        $status = ! $requiredOk ? 'belum_terkonfigurasi' : (collect($checks)->every(fn ($ok) => $ok) ? 'ok' : 'degraded');

        return response()->json([
            'success' => true,
            'message' => 'Kesehatan konfigurasi pengiriman berhasil diperiksa.',
            'data' => [
                'status' => $status,
                'checks' => $checks,
                'base_url' => $baseUrl,
                'origin_id' => $this->shippingConfig->originId(),
                'jumlah_kurir_aktif' => count($this->shippingConfig->activeCouriers()),
                'memakai_kuota_api' => false,
            ],
        ]);
    }

    public function couriers(): JsonResponse
    {
        $list = $this->shippingConfig->courierList();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kurir berhasil diambil.',
            'data' => $list,
            'meta' => [
                'total' => count($list),
                'aktif' => count(array_filter($list, fn ($c) => $c['aktif'])),
            ],
        ]);
    }

    public function updateCourier(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'aktif' => ['required', 'boolean'],
        ]);

        $code = strtolower($code);

        if (! in_array($code, RajaOngkirService::SUPPORTED_COURIERS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Kurir tidak ditemukan.',
            ], 404);
        }

        $active = $this->shippingConfig->activeCouriers();

        if (! $validated['aktif']) {
            $active = array_values(array_filter($active, fn ($c) => $c !== $code));

            if ($active === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimal satu kurir harus tetap aktif.',
                ], 422);
            }
        } elseif (! in_array($code, $active, true)) {
            $active[] = $code;
        }

        $this->shippingConfig->setActiveCouriers($active);

        return response()->json([
            'success' => true,
            'message' => 'Status kurir berhasil diperbarui.',
            'data' => [
                'kode' => $code,
                'aktif' => in_array($code, $this->shippingConfig->activeCouriers(), true),
                'kurir_aktif' => $this->shippingConfig->activeCouriers(),
            ],
        ]);
    }

    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'integer', 'min:1', 'max:30000'],
            'couriers' => ['nullable', 'array', 'min:1'],
            'couriers.*' => ['required', 'string', 'in:'.implode(',', RajaOngkirService::SUPPORTED_COURIERS)],
        ]);

        $origin = $this->shippingConfig->originId();

        if ($origin === '') {
            return response()->json([
                'success' => false,
                'message' => 'RAJAONGKIR_NOT_CONFIGURED',
            ], 502);
        }

        $requested = $validated['couriers'] ?? null;
        $couriers = $requested === null
            ? $this->shippingConfig->activeCouriers()
            : array_values(array_filter(
                array_map(fn ($c) => strtolower((string) $c), $requested),
                fn ($c) => $this->shippingConfig->isActiveCourier($c)
            ));

        if ($couriers === []) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kurir aktif yang diminta.',
            ], 422);
        }

        $results = [];

        foreach ($couriers as $courier) {
            try {
                $services = $this->rajaongkir->cost(
                    (string) $validated['destination'],
                    (int) $validated['weight'],
                    $courier
                );

                $results[] = [
                    'kurir' => $courier,
                    'berhasil' => true,
                    'error' => null,
                    'layanan' => $services,
                ];
            } catch (RajaOngkirException $e) {
                $results[] = [
                    'kurir' => $courier,
                    'berhasil' => false,
                    'error' => $e->getMessage(),
                    'pesan' => $e->userMessage(),
                    'layanan' => [],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulasi ongkos kirim selesai.',
            'data' => [
                'origin_id' => $origin,
                'destination' => (int) $validated['destination'],
                'weight' => (int) $validated['weight'],
                'memakai_kuota_api' => count($couriers),
                'hasil' => $results,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function configPayload(): array
    {
        $originOverride = Setting::where('kunci', ShippingConfigService::KEY_ORIGIN)->exists();

        return [
            'origin_id' => $this->shippingConfig->originId(),
            'sumber_origin' => $originOverride ? 'settings' : 'env',
            'base_url' => $this->shippingConfig->baseUrl(),
            'api_key_terisi' => $this->shippingConfig->apiKeyConfigured(),
            'kurir_aktif' => $this->shippingConfig->activeCouriers(),
        ];
    }

    /**
     * Cek konektivitas HTTPS ke base URL RajaOngkir tanpa memanggil
     * endpoint API apa pun sehingga kuota harian tidak berkurang.
     * Respons apa pun (termasuk 4xx) dianggap terhubung.
     */
    private function checkConnectivity(string $baseUrl): bool
    {
        try {
            Http::withOptions(CaBundle::verifyOptions(config('services.rajaongkir.verify')))
                ->connectTimeout(5)
                ->timeout(5)
                ->get($baseUrl);
        } catch (ConnectionException) {
            return false;
        } catch (Throwable) {
            return false;
        }

        return true;
    }
}
