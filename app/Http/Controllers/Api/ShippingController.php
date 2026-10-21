<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RajaOngkirException;
use App\Http\Controllers\Controller;
use App\Services\RajaOngkirService;
use App\Services\ShippingConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Endpoint ongkos kirim RajaOngkir.
 *
 * GET  /api/shipping/destinations?search={keyword} — cari tujuan (per kelurahan)
 * POST /api/shipping/cost {destination, weight, courier} — cek ongkir
 * POST /api/rajaongkir/check-all {destination, weight} — cek JNE/J&T/POS serentak (cache 7 hari)
 *
 * Error upstream RajaOngkir dipetakan: 422 (request tidak valid),
 * 502 (upstream/API key), 503 (koneksi gagal), 429 (kuota habis).
 */
class ShippingController extends Controller
{
    /**
     * Kurir tetap untuk endpoint check-all (sesuai permintaan: JNE, J&T, POS).
     *
     * @var array<int, string>
     */
    public const CHECK_ALL_COURIERS = ['jne', 'jnt', 'pos'];

    public function __construct(
        private readonly RajaOngkirService $rajaongkir,
        private readonly ShippingConfigService $shippingConfig
    ) {}

    public function destinations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        try {
            $destinations = $this->rajaongkir->destinations($validated['search']);
        } catch (RajaOngkirException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'pesan' => $e->userMessage(),
            ], $e->statusCode());
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar tujuan pengiriman berhasil diambil.',
            'data' => $destinations,
        ]);
    }

    public function cost(Request $request): JsonResponse
    {
        $activeCouriers = $this->shippingConfig->activeCouriers();

        $validated = $request->validate([
            'destination' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'integer', 'min:1', 'max:30000'],
            'courier' => ['nullable', 'string', Rule::in($activeCouriers)],
            'couriers' => ['nullable', 'array', 'min:1'],
            'couriers.*' => ['string', Rule::in($activeCouriers)],
        ]);

        $requested = $this->resolveRequestedCouriers($validated, $activeCouriers);

        if ($requested === []) {
            throw ValidationException::withMessages([
                'courier' => ['Kurir wajib diisi (minimal satu kurir aktif).'],
            ]);
        }

        try {
            $result = $this->rajaongkir->costByCourier(
                (string) $validated['destination'],
                (int) $validated['weight'],
                $requested,
            );
        } catch (RajaOngkirException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'pesan' => $e->userMessage(),
            ], $e->statusCode());
        }

        $services = $result['services'];
        $unavailable = $result['unavailable'];

        $meta = [
            'kurir_dicek' => $requested,
            'kurir_tersedia' => array_values(array_unique(array_map(
                fn ($s) => strtolower((string) ($s['code'] ?? '')),
                $services
            ))),
            'kurir_tanpa_tarif' => $unavailable,
        ];

        return response()->json([
            'success' => true,
            'message' => $services === []
                ? 'Tidak ada kurir yang menyediakan tarif untuk rute/berat ini.'
                : 'Biaya pengiriman berhasil dihitung.',
            'data' => $services,
            'meta' => $meta,
        ], 200);
    }

    /**
     * Cek ongkir serentak untuk JNE, J&T, dan POS dalam satu panggilan.
     * POST /api/rajaongkir/check-all {destination, weight}
     *
     * Berbeda dari /shipping/cost, endpoint ini:
     * - selalu memakai daftar kurir tetap (jne, jnt, pos) sesuai kebutuhan
     *   pembanding tarif, bukan seluruh kurir aktif;
     * - menyimpan hasil per kurir di cache 7 hari (hemat kuota) dan
     *   melaporkan mana yang berasal dari cache vs diambil dari API;
     * - tidak pernah membuat baris pengiriman (`shipments`) — murni baca.
     */
    public function checkAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination' => ['required', 'integer', 'min:1'],
            'weight' => ['required', 'integer', 'min:1', 'max:30000'],
        ]);

        $couriers = self::CHECK_ALL_COURIERS;

        try {
            $result = $this->rajaongkir->cekOngkir(
                (string) $validated['destination'],
                (int) $validated['weight'],
                $couriers,
            );
        } catch (RajaOngkirException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'pesan' => $e->userMessage(),
            ], $e->statusCode());
        }

        $services = $result['services'];
        $cache = $result['cache'];

        $meta = [
            'kurir_dicek' => $couriers,
            'kurir_tersedia' => array_values(array_unique(array_map(
                fn ($s) => strtolower((string) ($s['code'] ?? '')),
                $services
            ))),
            'kurir_tanpa_tarif' => $result['unavailable'],
            'dari_cache' => $cache['couriers_cached'],
            'diambil_dari_api' => $cache['couriers_fetched'],
            'cache_ttl_hari' => $cache['ttl_days'],
            'berat_gram' => $cache['weight'],
        ];

        return response()->json([
            'success' => true,
            'message' => $services === []
                ? 'Tidak ada kurir yang menyediakan tarif untuk rute/berat ini.'
                : 'Biaya pengiriman berhasil dihitung.',
            'data' => $services,
            // Selalu memuat SEMUA kurir yang dicek (jne, jnt, pos) sesuai urutan,
            // walau ada yang tanpa tarif (tersedia: false, layanan: []). Bentuk ini
            // melengkapi `data`, bukan menggantikannya, agar tidak memecah konsumen lama.
            'data_per_kurir' => $this->groupServicesByCourier($couriers, $services),
            'meta' => $meta,
        ], 200);
    }

    /**
     * Kelompokkan layanan hasil cek ongkir per kode kurir, mengikuti urutan
     * `$couriers`. Kurir yang tidak menyediakan tarif tetap disertakan dengan
     * `tersedia: false` dan `layanan: []`, sehingga konsumen selalu menerima
     * seluruh kurir yang dicek tanpa perlu menebak mana yang kosong.
     *
     * @param  array<int, string>  $couriers
     * @param  array<int, array<string, mixed>>  $services
     * @return array<int, array{courier: string, tersedia: bool, layanan: array<int, array<string, mixed>>}>
     */
    private function groupServicesByCourier(array $couriers, array $services): array
    {
        $grouped = [];

        foreach ($services as $service) {
            $code = strtolower((string) ($service['code'] ?? ''));

            if ($code !== '') {
                $grouped[$code][] = $service;
            }
        }

        $result = [];

        foreach ($couriers as $code) {
            $code = strtolower((string) $code);
            $items = array_values($grouped[$code] ?? []);

            $result[] = [
                'courier' => $code,
                'tersedia' => $items !== [],
                'layanan' => $items,
            ];
        }

        return $result;
    }

    /**
     * Tentukan daftar kurir yang diminta: `couriers` (array) bila ada,
     * selain itu `courier` tunggal, selain itu semua kurir aktif.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, string>  $activeCouriers
     * @return array<int, string>
     */
    private function resolveRequestedCouriers(array $validated, array $activeCouriers): array
    {
        if (! empty($validated['couriers'])) {
            return array_values(array_unique($validated['couriers']));
        }

        if (! empty($validated['courier'])) {
            return [(string) $validated['courier']];
        }

        return array_values($activeCouriers);
    }
}
