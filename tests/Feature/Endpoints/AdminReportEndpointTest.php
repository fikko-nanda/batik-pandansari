<?php

namespace Tests\Feature\Endpoints;

/**
 * Laporan keuangan admin (4 endpoint):
 * GET /api/admin/reports/summary
 * GET /api/admin/reports/transactions
 * GET /api/admin/reports/transactions/{id}
 * GET /api/admin/reports/export/pdf
 */
class AdminReportEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Laporan Admin';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_akses_laporan(): void
    {
        $catalog = $this->seedCatalog(10);
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-AKSES-01']);
        $payment = $this->makePayment($order, ['status_pembayaran' => 'dibayar', 'paid_at' => now()]);

        $endpoints = [
            'GET /api/admin/reports/summary',
            'GET /api/admin/reports/transactions',
            'GET /api/admin/reports/export/pdf',
            'GET /api/admin/reports/transactions/'.$payment->id_payment,
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
    // GET /api/admin/reports/summary
    // ------------------------------------------------------------------

    public function test_ringkasan_laporan_akurat(): void
    {
        $user = $this->makeUser();

        $orderA = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-REP-A', 'total_pembayaran' => 200000]);
        $orderB = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-REP-B', 'total_pembayaran' => 100000]);
        $orderC = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-REP-C', 'total_pembayaran' => 50000]);

        $this->makePayment($orderA, ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 200000.0, 'paid_at' => now()->subDay()]);
        $this->makePayment($orderB, ['status_pembayaran' => 'menunggu', 'jumlah_pembayaran' => 100000.0]);
        $this->makePayment($orderC, ['status_pembayaran' => 'ditolak', 'jumlah_pembayaran' => 50000.0]);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/reports/summary', [
            'endpoint' => 'GET /api/admin/reports/summary',
            'label' => 'Ringkasan laporan dari data nyata',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertEquals(200000, $data['pendapatan']['total_pendapatan'] ?? null, 'Hanya pembayaran dibayar');
        $this->assertSame(1, $data['pendapatan']['jumlah_transaksi_bayar'] ?? null);
        $this->assertSame(3, $data['pembayaran']['total_transaksi'] ?? null);
        $this->assertSame(1, $data['pembayaran']['per_status']['dibayar'] ?? null);
        $this->assertSame(1, $data['pembayaran']['per_status']['menunggu'] ?? null);
        $this->assertSame(1, $data['pembayaran']['per_status']['ditolak'] ?? null);
        $this->assertSame(0, $data['pembayaran']['per_status']['kedaluwarsa'] ?? null);
        $this->assertEquals(200000, $data['pembayaran']['nilai_per_status']['dibayar'] ?? null);
        $this->assertSame(3, $data['pesanan']['jumlah_pesanan'] ?? null);
        $this->assertEquals(350000, $data['pesanan']['total_nilai_pesanan'] ?? null, '200k + 100k + 50k');
        $this->assertStringContainsString('bukan laba bersih', $data['keterangan'] ?? '');

        // Filter periode yang mengecualikan pembayaran kemarin.
        $this->hit('GET', '/api/admin/reports/summary?dari='.now()->toDateString(), [
            'endpoint' => 'GET /api/admin/reports/summary',
            'label' => 'Ringkasan laporan hanya hari ini',
            'expected' => 200,
        ]);
        $this->assertEquals(
            0,
            $this->recorded[1]['response']['data']['pendapatan']['total_pendapatan'] ?? null,
            'Pembayaran dibayar kemarin di luar rentang'
        );

        $this->hit('GET', '/api/admin/reports/summary?dari=2026-10-20&sampai=2026-10-01', [
            'endpoint' => 'GET /api/admin/reports/summary',
            'label' => 'Rentang terbalik (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('sampai', $this->recorded[2]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/reports/transactions
    // ------------------------------------------------------------------

    public function test_buku_kas_transaksi_filter_sort_paginasi(): void
    {
        $user = $this->makeUser();

        $orders = [];
        for ($i = 1; $i <= 4; $i++) {
            $orders[$i] = $this->makeOrder($user, [
                'nomor_pesanan' => 'ORD-TRX-000'.$i,
                'total_pembayaran' => 100000 * $i,
            ]);
        }

        $this->makePayment($orders[1], ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 100000.0, 'paid_at' => now()->subDays(3), 'provider' => 'midtrans']);
        $this->makePayment($orders[2], ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 200000.0, 'paid_at' => now()->subDays(2), 'provider' => 'midtrans']);
        $this->makePayment($orders[3], ['status_pembayaran' => 'menunggu', 'jumlah_pembayaran' => 300000.0, 'provider' => 'midtrans']);
        $this->makePayment($orders[4], ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 400000.0, 'paid_at' => now()->subDay(), 'provider' => 'manual']);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/reports/transactions', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Buku kas seluruh transaksi',
            'expected' => 200,
        ]);
        $this->assertSame(4, $this->recorded[0]['response']['meta']['total'] ?? null);
        $this->assertArrayHasKey('nomor_pesanan', $this->recorded[0]['response']['data'][0] ?? []);
        $this->assertSame('pembayaran', $this->recorded[0]['response']['data'][0]['jenis_transaksi'] ?? null);

        $this->hit('GET', '/api/admin/reports/transactions?status=dibayar', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Filter status dibayar',
            'expected' => 200,
        ]);
        $this->assertSame(3, $this->recorded[1]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/reports/transactions?provider=manual', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Filter provider manual',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[2]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/reports/transactions?search=ORD-TRX-0002', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Cari lewat nomor pesanan',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[3]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/reports/transactions?sort=jumlah_pembayaran&order=asc', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Sortir jumlah pembayaran menaik',
            'expected' => 200,
        ]);
        $jumlah = array_column($this->recorded[4]['response']['data'], 'jumlah_pembayaran');
        $this->assertSame([100000.0, 200000.0, 300000.0, 400000.0], array_map('floatval', $jumlah));

        $this->hit('GET', '/api/admin/reports/transactions?dari='.now()->toDateString(), [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Filter rentang hanya hari ini',
            'expected' => 200,
        ]);
        // Waktu transaksi = COALESCE(paid_at, dibuat_pada). Hanya pembayaran
        // tanpa paid_at yang dibuat hari ini yang termasuk rentang ini.
        $this->assertSame(1, $this->recorded[5]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/reports/transactions?per_page=2&page=2', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Paginasi halaman kedua',
            'expected' => 200,
        ]);
        $this->assertCount(2, $this->recorded[6]['response']['data'] ?? []);
        $this->assertSame(2, $this->recorded[6]['response']['meta']['current_page'] ?? null);

        $this->hit('GET', '/api/admin/reports/transactions?status=ngawur', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Status tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status', $this->recorded[7]['response']['errors'] ?? []);

        $this->hit('GET', '/api/admin/reports/transactions?sort=kolom_ngawur', [
            'endpoint' => 'GET /api/admin/reports/transactions',
            'label' => 'Kolom sortir tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('sort', $this->recorded[8]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/reports/transactions/{id}
    // ------------------------------------------------------------------

    public function test_detail_transaksi_dan_404(): void
    {
        $catalog = $this->seedCatalog(10);
        $variant = $catalog['variant'];
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-DETAIL-01'], [$variant]);

        $payment = $this->makePayment($order, [
            'status_pembayaran' => 'dibayar',
            'jumlah_pembayaran' => 150000.0,
            'paid_at' => now(),
            'payment_url' => 'https://app.sandbox.midtrans.com/snap/v2/rahasia',
        ]);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/reports/transactions/'.$payment->id_payment, [
            'endpoint' => 'GET /api/admin/reports/transactions/{id}',
            'label' => 'Detail transaksi berhasil',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($payment->id_payment, $data['id_payment'] ?? null);
        $this->assertSame('ORD-DETAIL-01', $data['nomor_pesanan'] ?? null);
        $this->assertSame('pembayaran', $data['jenis_transaksi'] ?? null);
        $this->assertSame('dibayar', $data['status_pembayaran'] ?? null);
        $this->assertArrayNotHasKey('payment_url', $data, 'payment_url tidak boleh diekspos');
        $this->assertSame('ORD-DETAIL-01', $data['order']['nomor_pesanan'] ?? null);
        $this->assertCount(1, $data['order']['items'] ?? []);

        $this->hit('GET', '/api/admin/reports/transactions/00000000-0000-0000-0000-000000000000', [
            'endpoint' => 'GET /api/admin/reports/transactions/{id}',
            'label' => 'Detail transaksi tidak ditemukan (404)',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/reports/export/pdf
    // ------------------------------------------------------------------

    public function test_ekspor_pdf_menghasilkan_berkas_pdf(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-PDF-01', 'total_pembayaran' => 250000]);
        $this->makePayment($order, ['status_pembayaran' => 'dibayar', 'jumlah_pembayaran' => 250000.0, 'paid_at' => now()]);

        $this->actingAsRole('admin');

        $response = $this->hit('GET', '/api/admin/reports/export/pdf', [
            'endpoint' => 'GET /api/admin/reports/export/pdf',
            'label' => 'Ekspor PDF seluruh periode',
            'expected' => 200,
        ]);

        $this->assertNotNull($response);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('laporan-keuangan-', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());

        // Ekspor dengan filter tidak menghasilkan error.
        $response = $this->hit('GET', '/api/admin/reports/export/pdf?status=dibayar&provider=midtrans', [
            'endpoint' => 'GET /api/admin/reports/export/pdf',
            'label' => 'Ekspor PDF dengan filter pembayaran sukses',
            'expected' => 200,
        ]);
        $this->assertStringStartsWith('%PDF', (string) $response?->getContent());

        // Rentang tanpa data tetap menghasilkan PDF (dengan ringkasan nol).
        $response = $this->hit('GET', '/api/admin/reports/export/pdf?dari='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/reports/export/pdf',
            'label' => 'Ekspor PDF rentang kosong',
            'expected' => 200,
        ]);
        $this->assertStringStartsWith('%PDF', (string) $response?->getContent());

        $this->hit('GET', '/api/admin/reports/export/pdf?status=ngawur', [
            'endpoint' => 'GET /api/admin/reports/export/pdf',
            'label' => 'Ekspor PDF status tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status', $this->recorded[3]['response']['errors'] ?? []);

        $this->assertRecorded();
    }
}
