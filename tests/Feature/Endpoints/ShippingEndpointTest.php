<?php

namespace Tests\Feature\Endpoints;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Endpoints\Concerns\FakesRajaOngkir;

/**
 * Endpoint ongkos kirim RajaOngkir:
 * GET /api/shipping/destinations?search={keyword}
 * POST /api/shipping/cost {destination, weight, courier}
 *
 * Seluruh panggilan upstream memakai Http::fake (tanpa kuota/ jaringan asli).
 */
class ShippingEndpointTest extends EndpointTestCase
{
    use FakesRajaOngkir;

    protected static string $group = 'Ongkos Kirim';

    // ------------------------------------------------------------------
    // Akses & validasi dasar
    // ------------------------------------------------------------------

    public function test_shipping_akses_tanpa_token(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/shipping/destinations?search=solo', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan tanpa token',
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir tanpa token',
            'json' => ['destination' => 61622, 'weight' => 1000, 'courier' => 'jne'],
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_shipping_destinations_validasi(): void
    {
        $this->actingAsRole('pelanggan');

        $this->hit('GET', '/api/shipping/destinations', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan tanpa parameter search',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('search', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('GET', '/api/shipping/destinations?search=a', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan dengan search kurang dari 2 karakter',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_shipping_cost_validasi(): void
    {
        $this->actingAsRole('pelanggan');

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir tanpa field destination & weight',
            'json' => [],
            'expected' => 422,
        ]);
        $errors = $this->recorded[0]['response']['errors'] ?? [];
        $this->assertArrayHasKey('destination', $errors);
        $this->assertArrayHasKey('weight', $errors);
        // courier kini opsional (default: semua kurir aktif).

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir dengan weight 0',
            'json' => ['destination' => 61622, 'weight' => 0, 'courier' => 'jne'],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir dengan weight melebihi batas 30.000 gram',
            'json' => ['destination' => 61622, 'weight' => 30001, 'courier' => 'jne'],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir dengan courier di luar daftar yang didukung',
            'json' => ['destination' => 61622, 'weight' => 1000, 'courier' => 'kurir-ngawur'],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /shipping/destinations
    // ------------------------------------------------------------------

    public function test_shipping_destinations_sukses(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeRajaOngkirDestinations([
            [
                'id' => 61622,
                'label' => 'BANJARSARI, BANJARSARI, SURAKARTA, JAWA TENGAH, 57136',
                'province_name' => 'JAWA TENGAH',
                'city_name' => 'SURAKARTA',
                'district_name' => 'BANJARSARI',
                'subdistrict_name' => 'BANJARSARI',
                'zip_code' => '57136',
            ],
            [
                'id' => 61623,
                'label' => 'JEBRES, JEBRES, SURAKARTA, JAWA TENGAH, 57126',
                'province_name' => 'JAWA TENGAH',
                'city_name' => 'SURAKARTA',
                'district_name' => 'JEBRES',
                'subdistrict_name' => 'JEBRES',
                'zip_code' => '57126',
            ],
        ]);

        $this->hit('GET', '/api/shipping/destinations?search=surakarta', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan (sukses, data diteruskan apa adanya dari RajaOngkir)',
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $this->assertTrue($response['success'] ?? false);
        $this->assertCount(2, $response['data'] ?? []);
        $this->assertSame(61622, $response['data'][0]['id'] ?? null);
        $this->assertSame('BANJARSARI', $response['data'][0]['subdistrict_name'] ?? null);
        $this->assertSame('57136', $response['data'][0]['zip_code'] ?? null);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/destination/domestic-destination')
                && $request->header('key') !== ''
                && ($request['search'] ?? null) === 'surakarta'
                && ($request['limit'] ?? null) == 50;
        });

        $this->assertRecorded();
    }

    public function test_shipping_destinations_tidak_ditemukan(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeRajaOngkirError(404, 404, null);

        $this->hit('GET', '/api/shipping/destinations?search=zipcodetidakada', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan dengan hasil kosong dari RajaOngkir (200, data [])',
            'expected' => 200,
        ]);

        $this->assertTrue($this->recorded[0]['response']['success'] ?? false);
        $this->assertCount(0, $this->recorded[0]['response']['data'] ?? []);

        $this->assertRecorded();
    }

    public function test_shipping_destinations_upstream_error(): void
    {
        $this->actingAsRole('pelanggan');

        $this->fakeRajaOngkirError(500, 500);
        $this->hit('GET', '/api/shipping/destinations?search=surakarta', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan saat RajaOngkir upstream error (502)',
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_UPSTREAM_ERROR', $this->recorded[0]['response']['message'] ?? null);

        $this->fakeRajaOngkirConnectionError();
        $this->hit('GET', '/api/shipping/destinations?search=surakarta', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan saat koneksi RajaOngkir gagal (503)',
            'expected' => 503,
        ]);
        $this->assertSame('RAJAONGKIR_UNAVAILABLE', $this->recorded[1]['response']['message'] ?? null);

        $this->fakeRajaOngkirError(401, 401);
        $this->hit('GET', '/api/shipping/destinations?search=surakarta', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Cari tujuan saat API key ditolak (502, RAJAONGKIR_UNAUTHORIZED)',
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_UNAUTHORIZED', $this->recorded[2]['response']['message'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // POST /shipping/cost
    // ------------------------------------------------------------------

    public function test_shipping_cost_sukses(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeRajaOngkirCost(9000, 'CTC');

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir sukses (origin dari config, layanan dari RajaOngkir)',
            'json' => ['destination' => 61622, 'weight' => 1000, 'courier' => 'jne'],
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $this->assertTrue($response['success'] ?? false);
        $this->assertCount(2, $response['data'] ?? []);
        $this->assertSame('CTC', $response['data'][0]['service'] ?? null);
        $this->assertSame(9000, $response['data'][0]['cost'] ?? null);
        $this->assertSame('jne', $response['data'][0]['code'] ?? null);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/calculate/domestic-cost')
                && $request->header('key') !== ''
                && ($request['origin'] ?? null) == (string) config('services.rajaongkir.origin_id')
                && ($request['destination'] ?? null) == '61622'
                && (int) ($request['weight'] ?? 0) === 1000
                && ($request['courier'] ?? null) === 'jne';
        });

        $this->assertRecorded();
    }

    public function test_shipping_cost_gagal_respon_api(): void
    {
        $this->actingAsRole('pelanggan');
        $payload = ['destination' => 61622, 'weight' => 1000, 'courier' => 'jne'];

        $this->fakeRajaOngkirError(422, 422);
        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir saat RajaOngkir menolak request (422 RAJAONGKIR_INVALID_REQUEST)',
            'json' => $payload,
            'expected' => 422,
        ]);
        $this->assertSame('RAJAONGKIR_INVALID_REQUEST', $this->recorded[0]['response']['message'] ?? null);

        // 404 dari Komerce berarti kurir tidak menyediakan tarif untuk rute
        // ini — bukan kegagalan menyeluruh. Endpoint tetap 200 dengan data
        // kosong + daftar kurir tanpa tarif agar kurir lain tetap tampil.
        $this->fakeRajaOngkirError(404, 404);
        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir saat kurir tidak punya tarif (404 -> 200 data kosong)',
            'json' => $payload,
            'expected' => 200,
        ]);
        $response = $this->recorded[1]['response'] ?? [];
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame([], $response['data'] ?? null);
        $this->assertSame('jne', $response['meta']['kurir_tanpa_tarif'][0]['courier'] ?? null);

        $this->fakeRajaOngkir([
            'status' => 200,
            'body' => [
                'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                'data' => [],
            ],
        ]);
        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir saat tidak ada layanan (200 data kosong, kurir masuk tanpa tarif)',
            'json' => $payload,
            'expected' => 200,
        ]);
        $this->assertSame([], $this->recorded[2]['response']['data'] ?? null);
        $this->assertSame('jne', $this->recorded[2]['response']['meta']['kurir_tanpa_tarif'][0]['courier'] ?? null);

        $this->fakeRajaOngkirError(500, 500);
        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir saat upstream error (502 RAJAONGKIR_UPSTREAM_ERROR)',
            'json' => $payload,
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_UPSTREAM_ERROR', $this->recorded[3]['response']['message'] ?? null);

        $this->fakeRajaOngkirConnectionError();
        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir saat koneksi gagal (503 RAJAONGKIR_UNAVAILABLE)',
            'json' => $payload,
            'expected' => 503,
        ]);
        $this->assertSame('RAJAONGKIR_UNAVAILABLE', $this->recorded[4]['response']['message'] ?? null);

        $this->fakeRajaOngkirError(429, 429, null);
        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir saat kuota harian RajaOngkir habis (429 RAJAONGKIR_QUOTA_EXCEEDED)',
            'json' => $payload,
            'expected' => 429,
        ]);
        $this->assertSame('RAJAONGKIR_QUOTA_EXCEEDED', $this->recorded[5]['response']['message'] ?? null);
        $this->assertNotEmpty($this->recorded[5]['response']['pesan'] ?? null);

        $this->assertRecorded();
    }

    public function test_shipping_cost_konfigurasi_kosong(): void
    {
        $this->actingAsRole('pelanggan');
        config(['services.rajaongkir.api_key' => '']);

        $this->fakeRajaOngkirCost(9000);

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir saat RAJAONGKIR_API_KEY kosong (502 NOT_CONFIGURED)',
            'json' => ['destination' => 61622, 'weight' => 1000, 'courier' => 'jne'],
            'expected' => 502,
        ]);

        $this->assertSame('RAJAONGKIR_NOT_CONFIGURED', $this->recorded[0]['response']['message'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Ketahanan koneksi (retry)
    // ------------------------------------------------------------------

    public function test_shipping_retry_saat_koneksi_sesaat_gagal_lalu_sukses(): void
    {
        $this->actingAsRole('pelanggan');

        $attempts = 0;
        Http::fake([
            'rajaongkir.komerce.id/*' => function () use (&$attempts) {
                $attempts++;

                if ($attempts === 1) {
                    throw new ConnectionException('cURL error 28: Connection timed out');
                }

                return Http::response([
                    'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                    'data' => [
                        [
                            'id' => 61622,
                            'label' => 'BANJARSARI, BANJARSARI, SURAKARTA, JAWA TENGAH, 57136',
                            'province_name' => 'JAWA TENGAH',
                            'city_name' => 'SURAKARTA',
                            'district_name' => 'BANJARSARI',
                            'subdistrict_name' => 'BANJARSARI',
                            'zip_code' => '57136',
                        ],
                    ],
                ], 200);
            },
        ]);

        $this->hit('GET', '/api/shipping/destinations?search=retrysesaat', [
            'endpoint' => 'GET /api/shipping/destinations',
            'label' => 'Gangguan koneksi sesaat diulang otomatis lalu sukses (bukan 503)',
            'expected' => 200,
        ]);

        $this->assertSame(2, $attempts);
        $this->assertTrue($this->recorded[0]['response']['success'] ?? false);
        $this->assertSame(61622, $this->recorded[0]['response']['data'][0]['id'] ?? null);

        $this->assertRecorded();
    }

    public function test_shipping_tidak_retry_saat_upstream_menolak_4xx(): void
    {
        $this->actingAsRole('pelanggan');

        $attempts = 0;
        Http::fake([
            'rajaongkir.komerce.id/*' => function () use (&$attempts) {
                $attempts++;

                return Http::response([
                    'meta' => ['message' => 'Error from RajaOngkir', 'code' => 422, 'status' => 'error'],
                    'data' => null,
                ], 422);
            },
        ]);

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Upstream menolak 422 tidak diulang (hemat kuota, respons cepat)',
            'json' => ['destination' => 61622, 'weight' => 1000, 'courier' => 'jne'],
            'expected' => 422,
        ]);

        $this->assertSame(1, $attempts);
        $this->assertSame('RAJAONGKIR_INVALID_REQUEST', $this->recorded[0]['response']['message'] ?? null);

        $this->assertRecorded();
    }
}
