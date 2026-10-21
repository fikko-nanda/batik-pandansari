<?php

namespace Tests\Feature\Endpoints;

use Illuminate\Cache\ArrayStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Endpoints\Concerns\FakesRajaOngkir;

/**
 * Endpoint cek ongkir serentak:
 * POST /api/rajaongkir/check-all {destination, weight}
 *
 * Memeriksa JNE/J&T/POS dalam satu panggilan dengan cache 7 hari per kurir
 * agar hemat kuota RajaOngkir. Seluruh panggilan upstream memakai Http::fake
 * (tanpa jaringan/kuota asli) dan CACHE_STORE=array pada phpunit.
 */
class RajaOngkirCheckAllEndpointTest extends EndpointTestCase
{
    use FakesRajaOngkir;

    protected static string $group = 'Cek Ongkir Serentak';

    /**
     * Data 3 kurir (jne/jnt/pos) dengan kode layanan berbeda per kurir
     * sehingga bisa dibedakan asalnya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function threeCourierCosts(): array
    {
        return [
            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 20000, 'etd' => '2-3'],
            ['name' => 'J&T', 'code' => 'jnt', 'service' => 'EZ', 'description' => 'Ekonomis', 'cost' => 18000, 'etd' => '3-4'],
            ['name' => 'POS', 'code' => 'pos', 'service' => 'Pos Reguler', 'description' => 'Reguler', 'cost' => 17000, 'etd' => '3-5'],
        ];
    }

    private function fakeCostBody(array $data): void
    {
        $this->fakeRajaOngkir([
            'status' => 200,
            'body' => [
                'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                'data' => $data,
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // Akses & validasi
    // ------------------------------------------------------------------

    public function test_check_all_akses_tanpa_token(): void
    {
        $this->asGuest();
        $this->fakeRajaOngkirCost();

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cek ongkir serentak tanpa token',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 401,
        ]);

        Http::assertNothingSent();

        $this->assertRecorded();
    }

    public function test_check_all_validasi_input(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeRajaOngkirCost();

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cek ongkir serentak tanpa field destination & weight',
            'json' => [],
            'expected' => 422,
        ]);
        $errors = $this->recorded[0]['response']['errors'] ?? [];
        $this->assertArrayHasKey('destination', $errors);
        $this->assertArrayHasKey('weight', $errors);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cek ongkir serentak dengan berat 0 gram',
            'json' => ['destination' => 61622, 'weight' => 0],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cek ongkir serentak dengan berat melebihi 30.000 gram',
            'json' => ['destination' => 61622, 'weight' => 30001],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cek ongkir serentak dengan destination 0 (tidak valid)',
            'json' => ['destination' => 0, 'weight' => 1000],
            'expected' => 422,
        ]);

        Http::assertNothingSent();

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Sukses & penyusunan hasil
    // ------------------------------------------------------------------

    public function test_check_all_sukses_satu_request_untuk_tiga_kurir(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeCostBody($this->threeCourierCosts());

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cek JNE/J&T/POS serentak (satu request, tarif diteruskan apa adanya)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $this->assertTrue($response['success'] ?? false);
        $this->assertCount(3, $response['data'] ?? []);
        $this->assertSame(['jne', 'jnt', 'pos'], $response['meta']['kurir_dicek'] ?? null);
        $this->assertSame(7, $response['meta']['cache_ttl_hari'] ?? null);
        $this->assertSame([], $response['meta']['dari_cache'] ?? null);
        $this->assertSame(['jne', 'jnt', 'pos'], $response['meta']['diambil_dari_api'] ?? null);
        $this->assertSame([], $response['meta']['kurir_tanpa_tarif'] ?? null);

        // data_per_kurir wajib selalu memuat ketiga kurir sesuai urutan, semuanya tersedia.
        $perKurir = $response['data_per_kurir'] ?? [];
        $this->assertCount(3, $perKurir);
        $this->assertSame(['jne', 'jnt', 'pos'], array_column($perKurir, 'courier'));
        $this->assertSame([true, true, true], array_column($perKurir, 'tersedia'));
        $this->assertCount(1, $perKurir[0]['layanan'] ?? []);

        // Tiga kurir harus dicek dalam SATU request upstream.
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/calculate/domestic-cost')
                && $request->header('key') !== ''
                && ($request['origin'] ?? null) == (string) config('services.rajaongkir.origin_id')
                && ($request['destination'] ?? null) == '61622'
                && (int) ($request['weight'] ?? 0) === 1000
                && ($request['courier'] ?? null) === 'jne:jnt:pos';
        });

        $this->assertRecorded();
    }

    public function test_check_all_hasil_diurutkan_termurah(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeCostBody([
            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 24000, 'etd' => '2-3'],
            ['name' => 'POS', 'code' => 'pos', 'service' => 'Reguler', 'description' => 'Reguler', 'cost' => 17000, 'etd' => '3-5'],
            ['name' => 'J&T', 'code' => 'jnt', 'service' => 'EZ', 'description' => 'Ekonomis', 'cost' => 19000, 'etd' => '3-4'],
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Tarif hasil digabung & diurutkan dari termurah',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $costs = array_map(fn ($s) => $s['cost'], $this->recorded[0]['response']['data'] ?? []);
        $this->assertSame([17000, 19000, 24000], $costs);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Cache
    // ------------------------------------------------------------------

    public function test_check_all_cache_hit_tidak_memanggil_api_lagi(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeCostBody($this->threeCourierCosts());

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Panggilan pertama (cache miss, ambil dari API)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);
        $this->assertSame(['jne', 'jnt', 'pos'], $this->recorded[0]['response']['meta']['diambil_dari_api'] ?? null);
        Http::assertSentCount(1);

        // Panggilan kedua dengan parameter sama: seluruhnya dari cache,
        // tidak ada request upstream tambahan (hemat kuota).
        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Panggilan kedua (cache hit, tanpa request API)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);
        $second = $this->recorded[1]['response'] ?? [];
        $this->assertSame([], $second['meta']['diambil_dari_api'] ?? null);
        $this->assertSame(['jne', 'jnt', 'pos'], $second['meta']['dari_cache'] ?? null);
        $this->assertSame([17000, 18000, 20000], array_map(fn ($s) => $s['cost'], $second['data'] ?? []));
        Http::assertSentCount(1);

        $this->assertRecorded();
    }

    public function test_check_all_cache_bergeser_saat_tujuan_atau_berat_berbeda(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeCostBody($this->threeCourierCosts());

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cache key dasar (tujuan & berat tertentu)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Tujuan berbeda wajib memanggil API lagi (bukan cache)',
            'json' => ['destination' => 61623, 'weight' => 1000],
            'expected' => 200,
        ]);
        $this->assertSame(['jne', 'jnt', 'pos'], $this->recorded[1]['response']['meta']['diambil_dari_api'] ?? null);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Berat berbeda wajib memanggil API lagi (bukan cache)',
            'json' => ['destination' => 61622, 'weight' => 2000],
            'expected' => 200,
        ]);
        $this->assertSame(['jne', 'jnt', 'pos'], $this->recorded[2]['response']['meta']['diambil_dari_api'] ?? null);

        // Tiga request berbeda = tiga panggilan upstream.
        Http::assertSentCount(3);

        $this->assertRecorded();
    }

    public function test_check_all_cache_disimpan_tujuh_hari(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeCostBody($this->threeCourierCosts());

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Panggilan pertama mengisi cache',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        // Cari entry cache rajaongkir:cost:* dan pastikan TTL-nya ~7 hari.
        $store = Cache::getStore();
        $this->assertInstanceOf(ArrayStore::class, $store);

        $reflection = new \ReflectionProperty($store, 'storage');
        $reflection->setAccessible(true);
        $entries = $reflection->getValue($store);

        $found = null;
        foreach ($entries as $key => $value) {
            if (str_contains($key, 'rajaongkir:cost:')) {
                $found = $value;
                break;
            }
        }

        $this->assertNotNull($found, 'Entry cache tarif tidak ditemukan setelah panggilan pertama.');
        $ttl = $found['expiresAt'] - now()->getTimestamp();
        $this->assertGreaterThanOrEqual(6 * 86400, $ttl);
        $this->assertLessThanOrEqual(7 * 86400 + 5, $ttl);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Sebagian kurir gagal (partial failure)
    // ------------------------------------------------------------------

    public function test_check_all_sebagian_kurir_tanpa_tarif_tetap_200(): void
    {
        $this->actingAsRole('pelanggan');
        // Hanya jne & pos yang tersedia; jnt tidak melayani rute ini.
        $this->fakeCostBody([
            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 20000, 'etd' => '2-3'],
            ['name' => 'POS', 'code' => 'pos', 'service' => 'Pos Reguler', 'description' => 'Reguler', 'cost' => 17000, 'etd' => '3-5'],
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Sebagian kurir tanpa tarif -> 200 dengan kurir_tanpa_tarif',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $this->assertTrue($response['success'] ?? false);
        $this->assertCount(2, $response['data'] ?? []);
        $this->assertSame('jnt', $response['meta']['kurir_tanpa_tarif'][0]['courier'] ?? null);

        // Kurir tanpa tarif tetap tampil di data_per_kurir sebagai tersedia: false.
        $perKurir = $response['data_per_kurir'] ?? [];
        $this->assertCount(3, $perKurir);
        $this->assertSame(['jne', 'jnt', 'pos'], array_column($perKurir, 'courier'));
        $this->assertSame([true, false, true], array_column($perKurir, 'tersedia'));
        $this->assertSame([], $perKurir[1]['layanan'] ?? null);

        $this->assertRecorded();
    }

    public function test_check_all_data_per_kurir_selalu_tiga_entri(): void
    {
        $this->actingAsRole('pelanggan');

        // Kasus 1: hanya jne yang tersedia (mirip rute asli) -> tetap 3 entri.
        $this->fakeCostBody([
            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 9000, 'etd' => '1 day'],
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Hanya jne tersedia -> data_per_kurir tetap 3 entri',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $perKurir = $this->recorded[0]['response']['data_per_kurir'] ?? [];
        $this->assertSame(['jne', 'jnt', 'pos'], array_column($perKurir, 'courier'));
        $this->assertSame([true, false, false], array_column($perKurir, 'tersedia'));
        $this->assertCount(1, $perKurir[0]['layanan'] ?? []);
        $this->assertSame([], $perKurir[1]['layanan'] ?? null);
        $this->assertSame([], $perKurir[2]['layanan'] ?? null);
        Http::assertSentCount(1);

        $this->assertRecorded();
    }

    public function test_check_all_semua_kurir_kosong_data_per_kurir_tetap_tiga(): void
    {
        $this->actingAsRole('pelanggan');

        // API membalas 404 (tidak ada tarif sama sekali untuk rute ini).
        $this->fakeRajaOngkir([
            'status' => 200,
            'body' => [
                'meta' => ['message' => 'Not Found', 'code' => 404, 'status' => 'error'],
                'data' => null,
            ],
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Semua kurir kosong -> data_per_kurir tetap 3 entri tersedia:false',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame([], $response['data'] ?? null);

        $perKurir = $response['data_per_kurir'] ?? [];
        $this->assertCount(3, $perKurir);
        $this->assertSame(['jne', 'jnt', 'pos'], array_column($perKurir, 'courier'));
        $this->assertSame([false, false, false], array_column($perKurir, 'tersedia'));
        Http::assertSentCount(1);

        $this->assertRecorded();
    }

    public function test_check_all_upstream_gagal_total_mengembalikan_error(): void
    {
        $this->actingAsRole('pelanggan');
        $payload = ['destination' => 61622, 'weight' => 1000];

        $this->fakeRajaOngkirError(500, 500);
        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Upstream error (502 RAJAONGKIR_UPSTREAM_ERROR)',
            'json' => $payload,
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_UPSTREAM_ERROR', $this->recorded[0]['response']['message'] ?? null);

        $this->fakeRajaOngkirError(429, 429, null);
        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Kuota harian habis (429 RAJAONGKIR_QUOTA_EXCEEDED)',
            'json' => $payload,
            'expected' => 429,
        ]);
        $this->assertSame('RAJAONGKIR_QUOTA_EXCEEDED', $this->recorded[1]['response']['message'] ?? null);
        $this->assertNotEmpty($this->recorded[1]['response']['pesan'] ?? null);

        $this->fakeRajaOngkirError(401, 401);
        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'API key ditolak (502 RAJAONGKIR_UNAUTHORIZED)',
            'json' => $payload,
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_UNAUTHORIZED', $this->recorded[2]['response']['message'] ?? null);

        $this->fakeRajaOngkirConnectionError();
        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Koneksi gagal (503 RAJAONGKIR_UNAVAILABLE)',
            'json' => $payload,
            'expected' => 503,
        ]);
        $this->assertSame('RAJAONGKIR_UNAVAILABLE', $this->recorded[3]['response']['message'] ?? null);

        $this->assertRecorded();
    }

    public function test_check_all_konfigurasi_kosong(): void
    {
        $this->actingAsRole('pelanggan');
        config(['services.rajaongkir.api_key' => '']);
        $this->fakeCostBody($this->threeCourierCosts());

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'API key kosong (502 RAJAONGKIR_NOT_CONFIGURED)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_NOT_CONFIGURED', $this->recorded[0]['response']['message'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Pembatas laju & efek samping database
    // ------------------------------------------------------------------

    public function test_check_all_dibatasi_laju_per_pengguna(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $this->fakeCostBody($this->threeCourierCosts());

        // Batas per pengguna = 20/menit. 20 request pertama harus sukses.
        for ($i = 0; $i < 20; $i++) {
            $this->hit('POST', '/api/rajaongkir/check-all', [
                'endpoint' => 'POST /api/rajaongkir/check-all',
                'label' => 'Panggilan ke-'.($i + 1).' (dalam batas 20/menit)',
                'json' => ['destination' => 61622, 'weight' => 1000 + $i],
                'expected' => 200,
            ]);
        }

        // Panggilan ke-21 melebihi batas -> 429 dari throttle.
        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Panggilan ke-21 melebihi 20/menit -> 429 Too Many Requests',
            'json' => ['destination' => 61622, 'weight' => 9999],
            'expected' => 429,
        ]);

        $this->assertSame(429, $this->recorded[20]['actual_status'] ?? null);

        $this->assertRecorded();
        $this->assertNotNull($user->id_user);
    }

    public function test_check_all_tidak_membuat_baris_shipment(): void
    {
        $this->actingAsRole('pelanggan');
        $this->fakeCostBody($this->threeCourierCosts());

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Cek ongkir serentak tidak menyentuh tabel shipments (murni baca)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $this->assertSame([], $this->recorded[0]['db_effect'] ?? null);

        $this->assertRecorded();
    }

    public function test_check_all_tidak_membocorkan_api_key(): void
    {
        $this->actingAsRole('pelanggan');
        config(['services.rajaongkir.api_key' => 'SUPER-SECRET-RAJAONGKIR-KEY']);
        $this->fakeCostBody($this->threeCourierCosts());

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Respons tidak pernah memuat RAJAONGKIR_API_KEY',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $encoded = json_encode($this->recorded[0]['response'] ?? []);
        $this->assertStringNotContainsString('SUPER-SECRET-RAJAONGKIR-KEY', (string) $encoded);

        // Header key tetap dikirim ke upstream, tapi tidak pernah ke klien.
        Http::assertSent(fn (Request $request) => $request->header('key')[0] === 'SUPER-SECRET-RAJAONGKIR-KEY');

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Ketahanan (retry) tetap berlaku untuk endpoint baru
    // ------------------------------------------------------------------

    public function test_check_all_retry_koneksi_sesaat_lalu_sukses(): void
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
                    'data' => $this->threeCourierCosts(),
                ], 200);
            },
        ]);

        $this->hit('POST', '/api/rajaongkir/check-all', [
            'endpoint' => 'POST /api/rajaongkir/check-all',
            'label' => 'Gangguan koneksi sesaat diulang otomatis lalu sukses (bukan 503)',
            'json' => ['destination' => 61622, 'weight' => 1000],
            'expected' => 200,
        ]);

        $this->assertSame(2, $attempts);
        $this->assertTrue($this->recorded[0]['response']['success'] ?? false);
        $this->assertCount(3, $this->recorded[0]['response']['data'] ?? []);

        $this->assertRecorded();
    }
}
