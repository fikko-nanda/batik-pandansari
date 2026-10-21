<?php

namespace Tests\Feature;

use App\Exceptions\RajaOngkirException;
use App\Services\RajaOngkirService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Perilaku cache & lock tingkat layanan RajaOngkir (cekOngkir) yang tidak
 * bisa diamati lewat endpoint (mis. jumlah request upstream saat lock
 * diperebutkan, dan kebijakan tidak menyimpan hasil gagal ke cache).
 *
 * Semua panggilan upstream di-fake; CACHE_STORE=array pada phpunit.
 */
class RajaOngkirCachingTest extends TestCase
{
    private function service(): RajaOngkirService
    {
        return $this->app->make(RajaOngkirService::class);
    }

    private function fakeCosts(string $courier, int $cost): void
    {
        Http::fake([
            'rajaongkir.komerce.id/*' => Http::response([
                'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => strtoupper($courier), 'code' => $courier, 'service' => 'REG', 'description' => 'Reguler', 'cost' => $cost, 'etd' => '2-3'],
                ],
            ], 200),
        ]);
    }

    public function test_lock_cached_saat_store_mendukung(): void
    {
        // ArrayStore mendukung lock; pastikan Cache::lock bisa dipakai tanpa
        // melempar, sehingga jalur lock benar-benar dieksekusi.
        $lock = Cache::lock('rajaongkir:uji-lock', 5);
        $this->assertTrue($lock->get());
        $lock->release();
    }

    public function test_cek_ongkir_hanya_satu_request_upstream_untuk_banyak_kurir(): void
    {
        config(['services.rajaongkir.api_key' => 'kunci-uji']);
        Http::fake([
            'rajaongkir.komerce.id/*' => Http::response([
                'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 20000, 'etd' => '2-3'],
                    ['name' => 'J&T', 'code' => 'jnt', 'service' => 'EZ', 'description' => 'Ekonomis', 'cost' => 18000, 'etd' => '3-4'],
                    ['name' => 'POS', 'code' => 'pos', 'service' => 'Pos Reguler', 'description' => 'Reguler', 'cost' => 17000, 'etd' => '3-5'],
                ],
            ], 200),
        ]);

        $result = $this->service()->cekOngkir('61622', 1000, ['jne', 'jnt', 'pos']);

        $this->assertCount(3, $result['services']);
        $this->assertSame(['jne', 'jnt', 'pos'], $result['cache']['couriers_fetched']);
        $this->assertSame([], $result['cache']['couriers_cached']);
        Http::assertSentCount(1);
    }

    public function test_cek_ongkir_menyimpan_hasil_ke_cache_dan_dipakai_ulang(): void
    {
        config(['services.rajaongkir.api_key' => 'kunci-uji']);
        $this->fakeCosts('jne', 21000);

        $first = $this->service()->cekOngkir('61622', 1000, ['jne']);
        $this->assertSame(['jne'], $first['cache']['couriers_fetched']);
        Http::assertSentCount(1);

        // Panggilan kedua: dari cache, tanpa request upstream tambahan.
        $second = $this->service()->cekOngkir('61622', 1000, ['jne']);
        $this->assertSame([], $second['cache']['couriers_fetched']);
        $this->assertSame(['jne'], $second['cache']['couriers_cached']);
        Http::assertSentCount(1);
    }

    public function test_cek_ongkir_tidak_menyimpan_hasil_saat_upstream_gagal(): void
    {
        config(['services.rajaongkir.api_key' => 'kunci-uji']);

        // Fase 1: upstream selalu 500 → gagal menyeluruh, tanpa cache.
        // Fase 2: upstream sehat → panggilan berikutnya harus mencoba lagi.
        $healthy = false;
        $attempts = 0;

        Http::fake([
            'rajaongkir.komerce.id/*' => function () use (&$healthy, &$attempts) {
                $attempts++;

                if (! $healthy) {
                    return Http::response([
                        'meta' => ['message' => 'Gangguan', 'code' => 500, 'status' => 'error'],
                        'data' => null,
                    ], 500);
                }

                return Http::response([
                    'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                    'data' => [
                        ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 21000, 'etd' => '2-3'],
                    ],
                ], 200);
            },
        ]);

        try {
            $this->service()->cekOngkir('61622', 1000, ['jne']);
            $this->fail('Seharusnya melempar RajaOngkirException saat upstream 500.');
        } catch (RajaOngkirException $e) {
            $this->assertSame('RAJAONGKIR_UPSTREAM_ERROR', $e->getMessage());
        }

        // Karena gagal, tidak ada entry cache → panggilan berikutnya harus
        // benar-benar mencoba lagi (bukan menyajikan hasil kosong dari cache).
        $healthy = true;
        $before = $attempts;

        $retry = $this->service()->cekOngkir('61622', 1000, ['jne']);
        $this->assertSame(['jne'], $retry['cache']['couriers_fetched']);
        $this->assertCount(1, $retry['services']);
        $this->assertGreaterThan($before, $attempts);
    }

    public function test_cek_ongkir_kunci_cache_terpisah_per_kurir_dan_berat(): void
    {
        config(['services.rajaongkir.api_key' => 'kunci-uji']);
        $this->fakeCosts('jne', 21000);

        // Isi cache untuk jne @1000g.
        $this->service()->cekOngkir('61622', 1000, ['jne']);
        Http::assertSentCount(1);

        // Kurir berbeda → ambil dari API.
        $this->service()->cekOngkir('61622', 1000, ['jnt']);
        Http::assertSentCount(2);

        // Berat berbeda → ambil dari API.
        $this->service()->cekOngkir('61622', 2000, ['jne']);
        Http::assertSentCount(3);
    }

    public function test_cek_ongkir_menggabungkan_cache_sebagian_dengan_ambil_sisa(): void
    {
        config(['services.rajaongkir.api_key' => 'kunci-uji']);

        // Fake tunggal yang membalas hanya kurir yang diminta (param courier
        // dipisah titik dua) — mencerminkan perilaku API sungguhan.
        $all = [
            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 21000, 'etd' => '2-3'],
            ['name' => 'J&T', 'code' => 'jnt', 'service' => 'EZ', 'description' => 'Ekonomis', 'cost' => 18000, 'etd' => '3-4'],
        ];

        Http::fake([
            'rajaongkir.komerce.id/*' => function ($request) use ($all) {
                $requested = explode(':', (string) ($request['courier'] ?? ''));

                return Http::response([
                    'meta' => ['message' => 'OK', 'code' => 200, 'status' => 'success'],
                    'data' => array_values(array_filter($all, fn ($s) => in_array($s['code'], $requested, true))),
                ], 200);
            },
        ]);

        // Isi cache hanya untuk jne.
        $this->service()->cekOngkir('61622', 1000, ['jne']);
        Http::assertSentCount(1);

        // Minta jne + jnt: jne dari cache, jnt diambil lewat satu request lagi.
        $result = $this->service()->cekOngkir('61622', 1000, ['jne', 'jnt']);

        $this->assertSame(['jne'], $result['cache']['couriers_cached']);
        $this->assertSame(['jnt'], $result['cache']['couriers_fetched']);
        $this->assertCount(2, $result['services']);
        Http::assertSentCount(2);
    }
}
