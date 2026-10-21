<?php

namespace Tests\Feature\Endpoints;

use App\Models\Setting;
use App\Services\RajaOngkirService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Konfigurasi & uji coba pengiriman RajaOngkir (admin):
 * GET  /api/admin/shipping/config
 * PUT  /api/admin/shipping/config        {origin_id}
 * GET  /api/admin/shipping/health
 * GET  /api/admin/shipping/couriers
 * PUT  /api/admin/shipping/couriers/{code} {aktif}
 * POST /api/admin/shipping/simulate      {destination, weight, couriers?}
 *
 * Semua panggilan upstream memakai Http::fake (tanpa kuota/ jaringan asli).
 */
class AdminShippingConfigEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Konfigurasi Pengiriman';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_konfigurasi_pengiriman_akses(): void
    {
        $this->asGuest();
        $this->hit('GET', '/api/admin/shipping/config', [
            'endpoint' => 'GET /api/admin/shipping/config',
            'label' => 'Konfigurasi pengiriman tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('PUT', '/api/admin/shipping/config', [
            'endpoint' => 'PUT /api/admin/shipping/config',
            'label' => 'Ubah konfigurasi pengiriman tanpa token (401)',
            'json' => ['origin_id' => '12345'],
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/admin/shipping/health', [
            'endpoint' => 'GET /api/admin/shipping/health',
            'label' => 'Health check pengiriman tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/admin/shipping/couriers', [
            'endpoint' => 'GET /api/admin/shipping/couriers',
            'label' => 'Daftar kurir tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('PUT', '/api/admin/shipping/couriers/jne', [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Ubah status kurir tanpa token (401)',
            'json' => ['aktif' => false],
            'expected' => 401,
        ]);
        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi ongkir tanpa token (401)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/shipping/config', [
            'endpoint' => 'GET /api/admin/shipping/config',
            'label' => 'Konfigurasi pengiriman sebagai pelanggan (403)',
            'expected' => 403,
        ]);
        $this->hit('PUT', '/api/admin/shipping/config', [
            'endpoint' => 'PUT /api/admin/shipping/config',
            'label' => 'Ubah konfigurasi pengiriman sebagai pelanggan (403)',
            'json' => ['origin_id' => '12345'],
            'expected' => 403,
        ]);
        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi ongkir sebagai pelanggan (403)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 403,
        ]);
        $this->hit('PUT', '/api/admin/shipping/couriers/jne', [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Ubah status kurir sebagai pelanggan (403)',
            'json' => ['aktif' => false],
            'expected' => 403,
        ]);

        $this->assertSame(0, Setting::count(), 'Akses ditolak tidak boleh menulis settings.');
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET / PUT /api/admin/shipping/config
    // ------------------------------------------------------------------

    public function test_config_baca_dari_env(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/shipping/config', [
            'endpoint' => 'GET /api/admin/shipping/config',
            'label' => 'Baca konfigurasi pengiriman (fallback config/.env, API key tidak dibalikkan)',
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $data = $response['data'] ?? [];

        $this->assertSame((string) config('services.rajaongkir.origin_id'), $data['origin_id'] ?? null);
        $this->assertSame('env', $data['sumber_origin'] ?? null);
        $this->assertTrue($data['api_key_terisi'] ?? false);
        $this->assertSame(config('services.rajaongkir.base_url'), $data['base_url'] ?? null);
        $this->assertCount(count(RajaOngkirService::SUPPORTED_COURIERS), $data['kurir_aktif'] ?? []);
        $this->assertArrayNotHasKey('api_key', $data);

        $apiKey = (string) config('services.rajaongkir.api_key');
        if ($apiKey !== '') {
            $this->assertStringNotContainsString(
                $apiKey,
                json_encode($response),
                'API key tidak boleh pernah muncul di respons API.'
            );
        }

        $this->assertRecorded();
    }

    public function test_config_ubah_origin(): void
    {
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/admin/shipping/config', [
            'endpoint' => 'PUT /api/admin/shipping/config',
            'label' => 'Ubah origin pengiriman (tersimpan ke tabel settings)',
            'json' => ['origin_id' => '99999'],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('99999', $data['origin_id'] ?? null);
        $this->assertSame('settings', $data['sumber_origin'] ?? null);
        $this->assertArrayHasKey('settings', $this->recorded[0]['db_effect'] ?? []);
        $this->assertSame(
            '99999',
            Setting::where('kunci', 'rajaongkir.origin_id')->value('nilai'),
            'Origin harus benar-benar tersimpan ke database.'
        );

        $this->hit('GET', '/api/admin/shipping/config', [
            'endpoint' => 'GET /api/admin/shipping/config',
            'label' => 'Baca ulang konfigurasi setelah diubah (perubahan bertahan)',
            'expected' => 200,
        ]);
        $this->assertSame('99999', $this->recorded[1]['response']['data']['origin_id'] ?? null);
        $this->assertSame('settings', $this->recorded[1]['response']['data']['sumber_origin'] ?? null);

        // Perubahan admin dipakai oleh perhitungan ongkir sungguhan.
        Http::fake([
            'rajaongkir.komerce.id/*' => fn (Request $request) => Http::response([
                'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 9000, 'etd' => '2-3'],
                ],
            ], 200),
        ]);

        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Ongkir memakai origin hasil konfigurasi admin (bukan env lama)',
            'json' => ['destination' => 61622, 'weight' => 1000, 'courier' => 'jne'],
            'expected' => 200,
        ]);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/calculate/domestic-cost')
            && ($request['origin'] ?? null) === '99999');

        $this->assertRecorded();
    }

    public function test_config_ubah_origin_validasi(): void
    {
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/admin/shipping/config', [
            'endpoint' => 'PUT /api/admin/shipping/config',
            'label' => 'Ubah origin tanpa body (422)',
            'json' => [],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('origin_id', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('PUT', '/api/admin/shipping/config', [
            'endpoint' => 'PUT /api/admin/shipping/config',
            'label' => 'Ubah origin dengan nilai bukan angka (422)',
            'json' => ['origin_id' => 'yogyakarta'],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/admin/shipping/config', [
            'endpoint' => 'PUT /api/admin/shipping/config',
            'label' => 'Ubah origin terlalu panjang (422)',
            'json' => ['origin_id' => str_repeat('1', 25)],
            'expected' => 422,
        ]);

        $this->assertSame(0, Setting::count(), 'Validasi gagal tidak boleh menulis settings.');
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET / PUT /api/admin/shipping/couriers
    // ------------------------------------------------------------------

    public function test_couriers_aktif_nonaktif(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/shipping/couriers', [
            'endpoint' => 'GET /api/admin/shipping/couriers',
            'label' => 'Daftar kurir (semua aktif secara default)',
            'expected' => 200,
        ]);
        $response = $this->recorded[0]['response'] ?? [];
        $totalCouriers = count(RajaOngkirService::SUPPORTED_COURIERS);
        $this->assertCount($totalCouriers, $response['data'] ?? []);
        $this->assertSame($totalCouriers, $response['meta']['aktif'] ?? null);
        $this->assertTrue($response['data'][0]['aktif'] ?? false);

        $this->hit('PUT', '/api/admin/shipping/couriers/pos', [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Nonaktifkan kurir pos (tersimpan ke settings)',
            'json' => ['aktif' => false],
            'expected' => 200,
        ]);
        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertFalse($data['aktif'] ?? true);
        $this->assertNotContains('pos', $data['kurir_aktif'] ?? []);
        $this->assertArrayHasKey('settings', $this->recorded[1]['db_effect'] ?? []);

        $this->hit('GET', '/api/admin/shipping/couriers', [
            'endpoint' => 'GET /api/admin/shipping/couriers',
            'label' => 'Baca daftar kurir setelah pos dinonaktifkan (satu lebih sedikit)',
            'expected' => 200,
        ]);
        $daftar = $this->recorded[2]['response']['data'] ?? [];
        $this->assertSame($totalCouriers - 1, $this->recorded[2]['response']['meta']['aktif'] ?? null);
        $pos = array_values(array_filter($daftar, fn ($c) => $c['kode'] === 'pos'))[0] ?? null;
        $this->assertFalse($pos['aktif'] ?? true);

        // Kurir nonaktif ditolak oleh validasi ongkir & checkout.
        $this->hit('POST', '/api/shipping/cost', [
            'endpoint' => 'POST /api/shipping/cost',
            'label' => 'Cek ongkir memakai kurir yang sudah dinonaktifkan (422)',
            'json' => ['destination' => 61622, 'weight' => 1000, 'courier' => 'pos'],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/admin/shipping/couriers/pos', [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Aktifkan kembali kurir pos (semua aktif kembali)',
            'json' => ['aktif' => true],
            'expected' => 200,
        ]);
        $this->assertCount($totalCouriers, $this->recorded[4]['response']['data']['kurir_aktif'] ?? []);

        $this->assertRecorded();
    }

    public function test_couriers_validasi_dan_404(): void
    {
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/admin/shipping/couriers/kurir-ngawur', [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Ubah status kurir yang tidak dikenal (404)',
            'json' => ['aktif' => true],
            'expected' => 404,
        ]);

        $this->hit('PUT', '/api/admin/shipping/couriers/jne', [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Ubah status kurir tanpa body aktif (422)',
            'json' => [],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('aktif', $this->recorded[1]['response']['errors'] ?? []);

        $this->assertSame(0, Setting::count(), 'Permintaan gagal tidak boleh menulis settings.');
        $this->assertRecorded();
    }

    public function test_couriers_tidak_boleh_mati_semua(): void
    {
        $this->actingAsRole('admin');

        $all = RajaOngkirService::SUPPORTED_COURIERS;
        $terakhir = array_pop($all);

        foreach ($all as $index => $code) {
            $this->hit('PUT', "/api/admin/shipping/couriers/{$code}", [
                'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
                'label' => 'Nonaktifkan kurir '.$code.' ('.(count($all) - $index).' kurir tersisa)',
                'json' => ['aktif' => false],
                'expected' => 200,
            ]);
        }

        // Kurir terakhir tidak boleh dinonaktifkan.
        $this->hit('PUT', "/api/admin/shipping/couriers/{$terakhir}", [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Nonaktifkan kurir terakhir yang aktif (422, aturan bisnis)',
            'json' => ['aktif' => false],
            'expected' => 422,
        ]);
        $lastIndex = count($all);
        $this->assertSame(
            'Minimal satu kurir harus tetap aktif.',
            $this->recorded[$lastIndex]['response']['message'] ?? null
        );

        $this->hit('GET', '/api/admin/shipping/couriers', [
            'endpoint' => 'GET /api/admin/shipping/couriers',
            'label' => 'Kurir terakhir tetap aktif setelah penolakan',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[$lastIndex + 1]['response']['meta']['aktif'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/shipping/health
    // ------------------------------------------------------------------

    public function test_health_ok_tanpa_kuota_api(): void
    {
        $this->actingAsRole('admin');

        Http::fake([
            'rajaongkir.komerce.id/*' => fn () => Http::response(['message' => 'not found'], 404),
        ]);

        $this->hit('GET', '/api/admin/shipping/health', [
            'endpoint' => 'GET /api/admin/shipping/health',
            'label' => 'Health check saat konfigurasi lengkap (status ok, tanpa memakai kuota API)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('ok', $data['status'] ?? null);
        $this->assertFalse($data['memakai_kuota_api'] ?? true);
        $this->assertTrue($data['checks']['api_key_terisi'] ?? false);
        $this->assertTrue($data['checks']['base_url_valid'] ?? false);
        $this->assertTrue($data['checks']['origin_terisi'] ?? false);
        $this->assertTrue($data['checks']['konektivitas_https'] ?? false);

        // Hanya 1 request (health), tidak ada panggilan endpoint API RajaOngkir.
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => ! str_contains($request->url(), '/calculate/domestic-cost')
            && ! str_contains($request->url(), '/destination/'));

        $this->assertRecorded();
    }

    public function test_health_belum_terkonfigurasi(): void
    {
        $this->actingAsRole('admin');
        $this->clearShippingSettings();

        Http::fake([
            'rajaongkir.komerce.id/*' => fn () => Http::response(['message' => 'not found'], 404),
        ]);

        $this->hit('GET', '/api/admin/shipping/health', [
            'endpoint' => 'GET /api/admin/shipping/health',
            'label' => 'Health check saat origin & API key kosong (belum_terkonfigurasi)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('belum_terkonfigurasi', $data['status'] ?? null);
        $this->assertFalse($data['checks']['origin_terisi'] ?? true);
        $this->assertFalse($data['checks']['api_key_terisi'] ?? true);
        $this->assertSame('', $data['origin_id'] ?? null);

        $this->assertRecorded();
    }

    public function test_health_degraded_koneksi_gagal(): void
    {
        $this->actingAsRole('admin');
        config(['services.rajaongkir.base_url' => 'http://127.0.0.1:9/api/v1']);

        Http::fake([
            '127.0.0.1:9/*' => function () {
                throw new ConnectionException('cURL error 7: Failed to connect to 127.0.0.1 port 9');
            },
        ]);

        $this->hit('GET', '/api/admin/shipping/health', [
            'endpoint' => 'GET /api/admin/shipping/health',
            'label' => 'Health check saat host RajaOngkir tidak terjangkau (degraded)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('degraded', $data['status'] ?? null);
        $this->assertFalse($data['checks']['konektivitas_https'] ?? true);
        $this->assertTrue($data['checks']['origin_terisi'] ?? false);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // POST /api/admin/shipping/simulate
    // ------------------------------------------------------------------

    public function test_simulate_validasi(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi tanpa body (422)',
            'json' => [],
            'expected' => 422,
        ]);
        $errors = $this->recorded[0]['response']['errors'] ?? [];
        $this->assertArrayHasKey('destination', $errors);
        $this->assertArrayHasKey('weight', $errors);

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi dengan weight 0 (422)',
            'json' => ['destination' => 61622, 'weight' => 0],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi dengan weight melebihi 30.000 gram (422)',
            'json' => ['destination' => 61622, 'weight' => 30001],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi dengan kurir di luar daftar yang didukung (422)',
            'json' => ['destination' => 61622, 'weight' => 1000, 'couriers' => ['kurir-ngawur']],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_simulate_sukses_multi_kurir(): void
    {
        $this->actingAsRole('admin');

        Http::fake([
            'rajaongkir.komerce.id/*' => function (Request $request) {
                $courier = (string) ($request['courier'] ?? 'jne');

                return Http::response([
                    'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                    'data' => [
                        [
                            'name' => strtoupper($courier),
                            'code' => $courier,
                            'service' => 'REG',
                            'description' => 'Layanan Reguler',
                            'cost' => 9000,
                            'etd' => '2-3',
                        ],
                    ],
                ], 200);
            },
        ]);

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi ongkir untuk 2 kurir (hasil per kurir, kuota tercatat)',
            'json' => [
                'destination' => 61622,
                'weight' => 1000,
                'couriers' => ['jne', 'sicepat'],
            ],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(61622, $data['destination'] ?? null);
        $this->assertSame(1000, $data['weight'] ?? null);
        $this->assertSame(2, $data['memakai_kuota_api'] ?? null);
        $this->assertCount(2, $data['hasil'] ?? []);
        $this->assertSame('jne', $data['hasil'][0]['kurir'] ?? null);
        $this->assertTrue($data['hasil'][0]['berhasil'] ?? false);
        $this->assertTrue(array_key_exists('error', $data['hasil'][0]), 'Entri sukses tetap memuat kunci error.');
        $this->assertNull($data['hasil'][0]['error']);
        $this->assertSame('sicepat', $data['hasil'][1]['kurir'] ?? null);
        $this->assertSame(9000, $data['hasil'][0]['layanan'][0]['cost'] ?? null);
        $this->assertSame('REG', $data['hasil'][0]['layanan'][0]['service'] ?? null);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => ($request['courier'] ?? null) === 'jne'
            && ($request['destination'] ?? null) == 61622
            && (int) ($request['weight'] ?? 0) === 1000);

        // Tanpa daftar kurir: memakai seluruh kurir aktif.
        $totalCouriers = count(RajaOngkirService::SUPPORTED_COURIERS);
        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi tanpa pilihan kurir (memakai semua kurir aktif)',
            'json' => ['destination' => 61622, 'weight' => 500],
            'expected' => 200,
        ]);
        $this->assertSame($totalCouriers, $this->recorded[1]['response']['data']['memakai_kuota_api'] ?? null);
        $this->assertCount($totalCouriers, $this->recorded[1]['response']['data']['hasil'] ?? []);

        $this->assertRecorded();
    }

    public function test_simulate_upstream_error_per_kurir(): void
    {
        $this->actingAsRole('admin');

        Http::fake([
            'rajaongkir.komerce.id/*' => fn () => Http::response([
                'meta' => ['message' => 'Error from RajaOngkir', 'code' => 500, 'status' => 'error'],
                'data' => null,
            ], 500),
        ]);

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi saat upstream RajaOngkir error (entri per kurir berisi error, 200)',
            'json' => [
                'destination' => 61622,
                'weight' => 1000,
                'couriers' => ['jne', 'sicepat'],
            ],
            'expected' => 200,
        ]);

        $hasil = $this->recorded[0]['response']['data']['hasil'] ?? [];
        $this->assertCount(2, $hasil);
        $this->assertFalse($hasil[0]['berhasil'] ?? true);
        $this->assertSame('RAJAONGKIR_UPSTREAM_ERROR', $hasil[0]['error'] ?? null);
        $this->assertSame([], $hasil[0]['layanan'] ?? null);
        $this->assertFalse($hasil[1]['berhasil'] ?? true);

        $this->assertRecorded();
    }

    public function test_simulate_kurir_nonaktif_dan_tanpa_origin(): void
    {
        $this->actingAsRole('admin');

        // Nonaktifkan pos lewat endpoint admin.
        $this->hit('PUT', '/api/admin/shipping/couriers/pos', [
            'endpoint' => 'PUT /api/admin/shipping/couriers/{code}',
            'label' => 'Nonaktifkan kurir pos sebelum simulasi',
            'json' => ['aktif' => false],
            'expected' => 200,
        ]);

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi meminta kurir yang nonaktif saja (422, aturan bisnis)',
            'json' => [
                'destination' => 61622,
                'weight' => 1000,
                'couriers' => ['pos'],
            ],
            'expected' => 422,
        ]);
        $this->assertSame(
            'Tidak ada kurir aktif yang diminta.',
            $this->recorded[1]['response']['message'] ?? null
        );

        // Tanpa origin pengiriman -> menolak sebelum memanggil RajaOngkir.
        $this->clearShippingSettings();
        Http::fake();

        $this->hit('POST', '/api/admin/shipping/simulate', [
            'endpoint' => 'POST /api/admin/shipping/simulate',
            'label' => 'Simulasi saat RAJAONGKIR_ORIGIN_ID kosong (502, tanpa panggilan API)',
            'json' => ['destination' => 61622, 'weight' => 1000, 'couriers' => ['jne']],
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_NOT_CONFIGURED', $this->recorded[2]['response']['message'] ?? null);
        Http::assertNothingSent();

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------

    private function clearShippingSettings(): void
    {
        Setting::query()->delete();
        Cache::flush();
        config([
            'services.rajaongkir.origin_id' => '',
            'services.rajaongkir.api_key' => '',
        ]);
    }
}
