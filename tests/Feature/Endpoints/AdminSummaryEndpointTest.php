<?php

namespace Tests\Feature\Endpoints;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Ringkasan admin:
 * GET /api/admin/orders/summary    ?status=&dari=&sampai=
 * GET /api/admin/payments/summary  ?status=&provider=&dari=&sampai=
 */
class AdminSummaryEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Ringkasan Admin';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_ringkasan_akses(): void
    {
        $this->asGuest();
        $this->hit('GET', '/api/admin/orders/summary', [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/admin/payments/summary', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran tanpa token (401)',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/orders/summary', [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan sebagai pelanggan (403)',
            'expected' => 403,
        ]);
        $this->hit('GET', '/api/admin/payments/summary', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran sebagai pelanggan (403)',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/admin/orders/summary', [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan sebagai admin (200)',
            'expected' => 200,
        ]);
        $this->hit('GET', '/api/admin/payments/summary', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran sebagai admin (200)',
            'expected' => 200,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/orders/summary
    // ------------------------------------------------------------------

    public function test_ringkasan_orders_akurasi(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();

        $this->seedOrders($user);

        $this->hit('GET', '/api/admin/orders/summary', [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan (dihitung dari 3 pesanan nyata)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame(3, $data['total_pesanan'] ?? null);
        $this->assertEquals(400000, $data['total_nilai'] ?? null);
        $this->assertEquals(133333.33, $data['rata_rata_nilai'] ?? null);
        $this->assertEquals([
            'menunggu_pembayaran' => 1,
            'dibayar' => 1,
            'diproses' => 0,
            'dikirim' => 0,
            'selesai' => 0,
            'dibatalkan' => 1,
        ], $data['per_status'] ?? null);

        $this->assertRecorded();
    }

    public function test_ringkasan_orders_filter(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();
        $this->seedOrders($user);

        $this->hit('GET', '/api/admin/orders/summary?status=dibayar', [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan difilter status dibayar',
            'expected' => 200,
        ]);
        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(1, $data['total_pesanan'] ?? null);
        $this->assertEquals(250000, $data['total_nilai'] ?? null);
        $this->assertSame(1, $data['per_status']['dibayar'] ?? null);
        $this->assertSame(0, $data['per_status']['menunggu_pembayaran'] ?? null);

        $this->hit('GET', '/api/admin/orders/summary?dari='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan pada rentang tanggal tanpa data',
            'expected' => 200,
        ]);
        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame(0, $data['total_pesanan'] ?? null);
        $this->assertEquals(0, $data['total_nilai'] ?? null);
        $this->assertEquals(0, $data['rata_rata_nilai'] ?? null);

        $this->hit('GET', '/api/admin/orders/summary?dari='.now()->subDay()->toDateString().'&sampai='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan pada rentang tanggal yang berisi data',
            'expected' => 200,
        ]);
        $this->assertSame(3, $this->recorded[2]['response']['data']['total_pesanan'] ?? null);

        $this->assertRecorded();
    }

    public function test_ringkasan_orders_validasi(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/orders/summary?status=bukan-status', [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan dengan status tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('GET', '/api/admin/orders/summary?dari=bukan-tanggal', [
            'endpoint' => 'GET /api/admin/orders/summary',
            'label' => 'Ringkasan pesanan dengan tanggal tidak valid (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('dari', $this->recorded[1]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/payments/summary
    // ------------------------------------------------------------------

    public function test_ringkasan_payments_akurasi(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-SUM-0001']);

        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'metode_pembayaran' => null,
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
            'payment_url' => null,
            'payment_reference' => null,
            'expires_at' => null,
            'paid_at' => null,
        ]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'metode_pembayaran' => 'qris',
            'jumlah_pembayaran' => 250000,
            'status_pembayaran' => 'dibayar',
            'payment_url' => null,
            'payment_reference' => 'TRX-1',
            'expires_at' => null,
            'paid_at' => now(),
        ]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'metode_pembayaran' => null,
            'jumlah_pembayaran' => 50000,
            'status_pembayaran' => 'dibatalkan',
            'payment_url' => null,
            'payment_reference' => null,
            'expires_at' => null,
            'paid_at' => null,
        ]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'manual',
            'metode_pembayaran' => 'transfer',
            'jumlah_pembayaran' => 75000,
            'status_pembayaran' => 'menunggu',
            'payment_url' => null,
            'payment_reference' => null,
            'expires_at' => null,
            'paid_at' => null,
        ]);

        $this->hit('GET', '/api/admin/payments/summary', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran (dihitung dari 4 pembayaran nyata)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame(4, $data['total_pembayaran'] ?? null);
        $this->assertEquals(475000, $data['total_nilai'] ?? null);
        $this->assertEquals(250000, $data['nilai_dibayar'] ?? null);
        $this->assertEquals([
            'menunggu' => 2,
            'dibayar' => 1,
            'ditolak' => 0,
            'kedaluwarsa' => 0,
            'dibatalkan' => 1,
        ], $data['per_status'] ?? null);
        $this->assertEquals(['midtrans' => 3, 'manual' => 1], $data['per_provider'] ?? null);

        $this->assertRecorded();
    }

    public function test_ringkasan_payments_filter_dan_validasi(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-SUM-0002']);
        $this->makePayment($order, ['status_pembayaran' => 'dibayar']);
        $this->makePayment($order, ['status_pembayaran' => 'menunggu']);

        $this->hit('GET', '/api/admin/payments/summary?status=dibayar', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran difilter status dibayar',
            'expected' => 200,
        ]);
        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(1, $data['total_pembayaran'] ?? null);
        $this->assertSame(1, $data['per_status']['dibayar'] ?? null);
        $this->assertSame(0, $data['per_status']['menunggu'] ?? null);

        $this->hit('GET', '/api/admin/payments/summary?provider=midtrans', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran difilter provider',
            'expected' => 200,
        ]);
        $this->assertSame(2, $this->recorded[1]['response']['data']['total_pembayaran'] ?? null);

        $this->hit('GET', '/api/admin/payments/summary?provider=tidak-ada', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran dengan provider yang tidak ada (0 data)',
            'expected' => 200,
        ]);
        $this->assertSame(0, $this->recorded[2]['response']['data']['total_pembayaran'] ?? null);

        $this->hit('GET', '/api/admin/payments/summary?status=bukan-status', [
            'endpoint' => 'GET /api/admin/payments/summary',
            'label' => 'Ringkasan pembayaran dengan status tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status', $this->recorded[3]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    /**
     * 3 pesanan: menunggu_pembayaran 100.000, dibayar 250.000, dibatalkan 50.000.
     */
    private function seedOrders(User $user): void
    {
        $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-SUM-A',
            'status_pesanan' => 'menunggu_pembayaran',
            'subtotal' => 100000,
            'total_pembayaran' => 100000,
        ]);
        $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-SUM-B',
            'status_pesanan' => 'dibayar',
            'subtotal' => 250000,
            'total_pembayaran' => 250000,
        ]);
        $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-SUM-C',
            'status_pesanan' => 'dibatalkan',
            'subtotal' => 50000,
            'total_pembayaran' => 50000,
        ]);
    }
}
