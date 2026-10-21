<?php

namespace Tests\Feature\Endpoints;

use App\Models\Order;
use App\Models\ProductVariant;

/**
 * Dashboard admin (4 endpoint):
 * GET /api/admin/dashboard/summary
 * GET /api/admin/dashboard/revenue
 * GET /api/admin/dashboard/orders
 * GET /api/admin/dashboard/aov
 */
class AdminDashboardEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Dashboard Admin';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_akses_dashboard(): void
    {
        $endpoints = [
            'GET /api/admin/dashboard/summary',
            'GET /api/admin/dashboard/revenue',
            'GET /api/admin/dashboard/orders',
            'GET /api/admin/dashboard/aov',
        ];

        $this->asGuest();
        foreach ($endpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint, 2);
            $this->hit($method, $url, [
                'endpoint' => $endpoint,
                'label' => $endpoint.' tanpa token (401)',
                'expected' => 401,
            ]);
        }

        $this->actingAsRole('pelanggan');
        foreach ($endpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint, 2);
            $this->hit($method, $url, [
                'endpoint' => $endpoint,
                'label' => $endpoint.' sebagai pelanggan (403)',
                'expected' => 403,
            ]);
        }

        $this->actingAsRole('admin');
        foreach ($endpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint, 2);
            $this->hit($method, $url, [
                'endpoint' => $endpoint,
                'label' => $endpoint.' sebagai admin (200)',
                'expected' => 200,
            ]);
        }

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/dashboard/summary
    // ------------------------------------------------------------------

    public function test_ringkasan_dashboard_akurat(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Parang');
        $this->makeVariant($product, 10);
        $vKritis = $this->makeVariant($product, 0);
        $vMenipis = $this->makeVariant($product, 2);
        ProductVariant::whereKey($vMenipis->id_variant)->update(['stok_minimum' => 5]);

        // 3 pengguna tambahan di luar admin -> total 4.
        $user = $this->makeUser();
        $this->makeUser();
        $this->makeUser();

        $orderDibayar = $this->makeOrder($user, ['status_pesanan' => 'dibayar', 'total_pembayaran' => 100000]);
        $orderDiproses = $this->makeOrder($user, ['status_pesanan' => 'diproses', 'total_pembayaran' => 200000]);
        $this->makeOrder($user, ['status_pesanan' => 'menunggu_pembayaran', 'total_pembayaran' => 50000]);
        $this->makeOrder($user, ['status_pesanan' => 'dibatalkan', 'total_pembayaran' => 40000]);

        // Hanya pembayaran berstatus dibayar yang dihitung sebagai pendapatan.
        $this->makePayment($orderDibayar, ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 100000.0, 'paid_at' => now()]);
        $this->makePayment($orderDiproses, ['status_pembayaran' => 'menunggu', 'jumlah_pembayaran' => 200000.0]);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/dashboard/summary', [
            'endpoint' => 'GET /api/admin/dashboard/summary',
            'label' => 'Ringkasan dashboard dari data nyata',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame(1, $data['total_produk'] ?? null);
        $this->assertSame(3, $data['total_varian'] ?? null);
        $this->assertSame(4, $data['total_pengguna'] ?? null, 'admin + 3 pelanggan');
        $this->assertSame(4, $data['total_pesanan'] ?? null);
        $this->assertSame(2, $data['pesanan_perlu_diproses'] ?? null, 'dibayar + diproses');
        $this->assertSame(1, $data['varian_kritis'] ?? null);
        $this->assertSame(1, $data['varian_menipis'] ?? null);
        $this->assertSame(2, $data['total_pembayaran'] ?? null);
        $this->assertEquals(100000, $data['total_pendapatan'] ?? null, 'Hanya pembayaran dibayar');

        $this->assertRecorded();
    }

    public function test_ringkasan_dashboard_tanpa_data(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/dashboard/summary', [
            'endpoint' => 'GET /api/admin/dashboard/summary',
            'label' => 'Ringkasan dashboard kosong',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(0, $data['total_produk'] ?? null);
        $this->assertSame(0, $data['total_varian'] ?? null);
        $this->assertSame(0, $data['total_pesanan'] ?? null);
        $this->assertSame(0, $data['total_pendapatan'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/dashboard/revenue
    // ------------------------------------------------------------------

    public function test_statistik_pendapatan_hanya_transaksi_sukses(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-REV-0001']);

        $this->makePayment($order, [
            'status_pembayaran' => 'dibayar',
            'jumlah_pembayaran' => 150000.0,
            'paid_at' => now()->subDays(2),
        ]);
        $this->makePayment($order, [
            'status_pembayaran' => 'menunggu',
            'jumlah_pembayaran' => 999999.0,
            'paid_at' => null,
        ]);
        $this->makePayment($order, [
            'status_pembayaran' => 'ditolak',
            'jumlah_pembayaran' => 888888.0,
            'paid_at' => null,
        ]);
        $this->makePayment($order, [
            'status_pembayaran' => 'kedaluwarsa',
            'jumlah_pembayaran' => 777777.0,
            'paid_at' => null,
        ]);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/dashboard/revenue', [
            'endpoint' => 'GET /api/admin/dashboard/revenue',
            'label' => 'Pendapatan hanya dari pembayaran dibayar',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertEquals(150000, $data['total_pendapatan'] ?? null);
        $this->assertSame(1, $data['jumlah_transaksi_bayar'] ?? null);
        $this->assertSame('hari', $data['periode']['group'] ?? null);
        $this->assertCount(1, $data['seri'] ?? []);
        $this->assertEquals(150000, $data['seri'][0]['pendapatan'] ?? null);

        // Pengelompokan bulanan.
        $this->hit('GET', '/api/admin/dashboard/revenue?group=bulan', [
            'endpoint' => 'GET /api/admin/dashboard/revenue',
            'label' => 'Pendapatan dikelompokkan per bulan',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[1]['response']['data']['seri'] ?? []);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}$/',
            (string) ($this->recorded[1]['response']['data']['seri'][0]['periode'] ?? '')
        );

        // Pengelompokan mingguan.
        $this->hit('GET', '/api/admin/dashboard/revenue?group=minggu', [
            'endpoint' => 'GET /api/admin/dashboard/revenue',
            'label' => 'Pendapatan dikelompokkan per minggu',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[2]['response']['data']['seri'] ?? []);

        // Rentang tanpa data.
        $this->hit('GET', '/api/admin/dashboard/revenue?dari='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/dashboard/revenue',
            'label' => 'Pendapatan pada rentang tanpa data',
            'expected' => 200,
        ]);
        $data = $this->recorded[3]['response']['data'] ?? [];
        $this->assertEquals(0, $data['total_pendapatan'] ?? null);
        $this->assertSame([], $data['seri'] ?? null);

        // Filter yang memuat data.
        $this->hit('GET', '/api/admin/dashboard/revenue?dari='.now()->subDays(5)->toDateString().'&sampai='.now()->toDateString(), [
            'endpoint' => 'GET /api/admin/dashboard/revenue',
            'label' => 'Pendapatan pada rentang yang memuat data',
            'expected' => 200,
        ]);
        $this->assertEquals(150000, $this->recorded[4]['response']['data']['total_pendapatan'] ?? null);

        $this->hit('GET', '/api/admin/dashboard/revenue?group=ngawur', [
            'endpoint' => 'GET /api/admin/dashboard/revenue',
            'label' => 'Group tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('group', $this->recorded[5]['response']['errors'] ?? []);

        $this->hit('GET', '/api/admin/dashboard/revenue?dari=2026-10-20&sampai=2026-10-01', [
            'endpoint' => 'GET /api/admin/dashboard/revenue',
            'label' => 'Rentang terbalik (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('sampai', $this->recorded[6]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/dashboard/orders
    // ------------------------------------------------------------------

    public function test_statistik_pesanan_semua_status_dan_tren(): void
    {
        $user = $this->makeUser();

        // Satu pesanan per status kecuali 'dikirim' (nol) untuk membuktikan
        // status dengan jumlah nol tetap tampil.
        foreach (['menunggu_pembayaran', 'dibayar', 'diproses', 'selesai', 'dibatalkan'] as $status) {
            $this->makeOrder($user, ['status_pesanan' => $status, 'nomor_pesanan' => 'ORD-'.strtoupper($status)]);
        }

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/dashboard/orders', [
            'endpoint' => 'GET /api/admin/dashboard/orders',
            'label' => 'Statistik pesanan per status',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame([
            'menunggu_pembayaran' => 1,
            'dibayar' => 1,
            'diproses' => 1,
            'dikirim' => 0,
            'selesai' => 1,
            'dibatalkan' => 1,
        ], $data['per_status'] ?? null);
        $this->assertSame(5, $data['total_pesanan'] ?? null);
        $this->assertNotEmpty($data['tren'] ?? []);
        $this->assertSame(5, array_sum(array_column($data['tren'], 'jumlah')));

        // Filter status pada rentang tanggal.
        $this->hit('GET', '/api/admin/dashboard/orders?dari='.now()->subDay()->toDateString().'&sampai='.now()->toDateString(), [
            'endpoint' => 'GET /api/admin/dashboard/orders',
            'label' => 'Statistik pesanan pada rentang berisi data',
            'expected' => 200,
        ]);
        $this->assertSame(5, $this->recorded[1]['response']['data']['total_pesanan'] ?? null);

        $this->hit('GET', '/api/admin/dashboard/orders?dari='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/dashboard/orders',
            'label' => 'Statistik pesanan pada rentang kosong',
            'expected' => 200,
        ]);
        $data = $this->recorded[2]['response']['data'] ?? [];
        $this->assertSame(0, $data['total_pesanan'] ?? null);
        $this->assertSame(0, $data['per_status']['dibayar'] ?? null);
        $this->assertSame([], $data['tren'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/dashboard/aov
    // ------------------------------------------------------------------

    public function test_aov_sesuai_kriteria_pendapatan(): void
    {
        $user = $this->makeUser();

        // Dua pesanan punya pembayaran dibayar (250k & 150k), satu pesanan
        // hanya menunggu dan tidak boleh dihitung.
        $ordA = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-AOV-A', 'total_pembayaran' => 250000]);
        $ordB = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-AOV-B', 'total_pembayaran' => 150000]);
        $ordC = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-AOV-C', 'total_pembayaran' => 900000]);

        // Pesanan A punya dua pembayaran dibayar -> tetap dihitung sekali.
        $this->makePayment($ordA, ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 250000.0, 'paid_at' => now()]);
        $this->makePayment($ordA, ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 250000.0, 'paid_at' => now()]);
        $this->makePayment($ordB, ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 150000.0, 'paid_at' => now()]);
        $this->makePayment($ordC, ['status_pembayaran' => 'menunggu', 'jumlah_pembayaran' => 900000.0, 'paid_at' => null]);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/dashboard/aov', [
            'endpoint' => 'GET /api/admin/dashboard/aov',
            'label' => 'AOV dari pesanan dengan pembayaran sukses',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame(2, $data['jumlah_pesanan'] ?? null, 'Pesanan unik, tidak dobel walau dua pembayaran');
        $this->assertEquals(400000, $data['total_nilai_pesanan'] ?? null, '250k + 150k');
        $this->assertEquals(200000, $data['aov'] ?? null, '400000 / 2');
        $this->assertStringContainsString('dibayar', $data['kriteria'] ?? '');

        $this->assertRecorded();
    }

    public function test_aov_tanpa_pesanan_tidak_error(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/dashboard/aov', [
            'endpoint' => 'GET /api/admin/dashboard/aov',
            'label' => 'AOV tanpa pesanan -> 0 tanpa error',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(0, $data['jumlah_pesanan'] ?? null);
        $this->assertEquals(0, $data['total_nilai_pesanan'] ?? null);
        $this->assertEquals(0, $data['aov'] ?? null);

        $this->assertRecorded();
    }

    public function test_aov_filter_periode(): void
    {
        $user = $this->makeUser();

        $lama = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-AOV-LAMA', 'total_pembayaran' => 100000]);
        $baru = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-AOV-BARU', 'total_pembayaran' => 300000]);

        $this->makePayment($lama, [
            'status_pembayaran' => 'dibayar',
            'jumlah_pembayaran' => 100000.0,
            'paid_at' => now()->subDays(10),
        ]);
        $this->makePayment($baru, [
            'status_pembayaran' => 'dibayar',
            'jumlah_pembayaran' => 300000.0,
            'paid_at' => now(),
        ]);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/dashboard/aov?dari='.now()->subDays(3)->toDateString(), [
            'endpoint' => 'GET /api/admin/dashboard/aov',
            'label' => 'AOV hanya periode terbaru',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(1, $data['jumlah_pesanan'] ?? null);
        $this->assertEquals(300000, $data['aov'] ?? null);

        $this->hit('GET', '/api/admin/dashboard/aov?dari=bukan-tanggal', [
            'endpoint' => 'GET /api/admin/dashboard/aov',
            'label' => 'Tanggal tidak valid (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('dari', $this->recorded[1]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    /**
     * Membuat satu pembayaran pada sebuah pesanan.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function payment(Order $order, array $overrides = []): void
    {
        $this->makePayment($order, $overrides);
    }
}
