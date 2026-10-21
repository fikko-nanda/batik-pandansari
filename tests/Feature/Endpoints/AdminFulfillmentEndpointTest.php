<?php

namespace Tests\Feature\Endpoints;

use App\Models\OrderFulfillment;
use Illuminate\Support\Str;

/**
 * Pemenuhan pesanan (fulfillment) admin:
 * GET  /api/admin/order-fulfillments        ?status=&id_order=&page=&per_page=
 * POST /api/admin/orders/{id}/fulfillments  {status_fulfillment, catatan?}
 */
class AdminFulfillmentEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Pemenuhan Pesanan';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_fulfillment_akses(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-FUL-A',
            'status_pesanan' => 'dibayar',
        ]);

        $this->asGuest();
        $this->hit('GET', '/api/admin/order-fulfillments', [
            'endpoint' => 'GET /api/admin/order-fulfillments',
            'label' => 'Daftar fulfillment tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment tanpa token (401)',
            'json' => ['status_fulfillment' => 'persiapan'],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/order-fulfillments', [
            'endpoint' => 'GET /api/admin/order-fulfillments',
            'label' => 'Daftar fulfillment sebagai pelanggan (403)',
            'expected' => 403,
        ]);
        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment sebagai pelanggan (403)',
            'json' => ['status_fulfillment' => 'persiapan'],
            'expected' => 403,
        ]);

        $admin = $this->actingAsRole('admin');
        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment sebagai admin (201)',
            'json' => ['status_fulfillment' => 'persiapan'],
            'expected' => 201,
        ]);

        $this->assertSame(
            1,
            OrderFulfillment::count(),
            'Hanya permintaan admin yang boleh membuat data; permintaan 401/403 tidak boleh.'
        );
        $this->assertSame($admin->id_user, OrderFulfillment::first()->diubah_oleh, 'Pencatat harus admin yang menjalankan request.');
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // POST /api/admin/orders/{id}/fulfillments
    // ------------------------------------------------------------------

    public function test_fulfillment_catat_sukses(): void
    {
        $admin = $this->actingAsRole('admin');
        $user = $this->makeUser();
        $order = $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-FUL-B',
            'status_pesanan' => 'dibayar',
        ]);

        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment persiapan (201, tersimpan ke database)',
            'json' => [
                'status_fulfillment' => 'persiapan',
                'catatan' => 'Barang disiapkan dari gudang',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('persiapan', $data['status_fulfillment'] ?? null);
        $this->assertSame($order->id_order, $data['id_order'] ?? null);
        $this->assertSame($order->id_order, $data['order']['id_order'] ?? null);
        $this->assertArrayHasKey('order_fulfillments', $this->recorded[0]['db_effect'] ?? []);

        $stored = OrderFulfillment::where('id_order', $order->id_order)->first();
        $this->assertNotNull($stored, 'Fulfillment harus benar-benar tersimpan.');
        $this->assertSame('Barang disiapkan dari gudang', $stored->catatan);
        $this->assertSame($admin->id_user, $stored->diubah_oleh, 'Pencatat harus admin yang menjalankan request.');
        $this->assertSame(1, (int) $stored->urutan, 'Catatan pertama memakai urutan 1.');

        // Status bergerak maju: persiapan -> dikemas.
        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment berikutnya (dikemas, 201)',
            'json' => ['status_fulfillment' => 'dikemas'],
            'expected' => 201,
        ]);

        // Catatan terakhir berstatus sama -> ditolak.
        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment berstatus sama dengan terakhir (422, aturan bisnis)',
            'json' => ['status_fulfillment' => 'dikemas'],
            'expected' => 422,
        ]);

        $this->assertSame(2, OrderFulfillment::where('id_order', $order->id_order)->count());
        $this->assertSame(2, (int) OrderFulfillment::where('id_order', $order->id_order)
            ->orderByDesc('urutan')
            ->value('urutan'), 'Catatan kedua memakai urutan 2 sehingga urutan terakhir dapat ditentukan.');
        $this->assertRecorded();
    }

    public function test_fulfillment_status_pesanan_diblokir(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();

        $cases = [
            ['nomor_pesanan' => 'ORD-FUL-C1', 'status_pesanan' => 'menunggu_pembayaran'],
            ['nomor_pesanan' => 'ORD-FUL-C2', 'status_pesanan' => 'dibatalkan'],
            ['nomor_pesanan' => 'ORD-FUL-C3', 'status_pesanan' => 'selesai'],
        ];

        foreach ($cases as $index => $case) {
            $order = $this->makeOrder($user, $case);

            $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
                'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
                'label' => "Fulfillment pada pesanan berstatus {$case['status_pesanan']} (422, aturan bisnis)",
                'json' => ['status_fulfillment' => 'persiapan'],
                'expected' => 422,
            ]);

            $this->assertNotEmpty($this->recorded[$index]['response']['message'] ?? null);
        }

        $this->hit('POST', '/api/admin/orders/00000000-0000-4000-8000-000000000000/fulfillments', [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Fulfillment pada pesanan yang tidak ada (404)',
            'json' => ['status_fulfillment' => 'persiapan'],
            'expected' => 404,
        ]);

        $orderValid = $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-FUL-C4',
            'status_pesanan' => 'dibayar',
        ]);

        $this->hit('POST', "/api/admin/orders/{$orderValid->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Fulfillment dengan status di luar daftar (422 validasi)',
            'json' => ['status_fulfillment' => 'rahasia'],
            'expected' => 422,
        ]);

        $this->assertSame(0, OrderFulfillment::count(), 'Semua penolakan tidak boleh membuat data.');
        $this->assertRecorded();
    }

    public function test_fulfillment_validasi_input(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();
        $order = $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-FUL-D',
            'status_pesanan' => 'diproses',
        ]);

        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment tanpa body (422)',
            'json' => [],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status_fulfillment', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('POST', "/api/admin/orders/{$order->id_order}/fulfillments", [
            'endpoint' => 'POST /api/admin/orders/{id}/fulfillments',
            'label' => 'Catat fulfillment dengan status di luar daftar (422)',
            'json' => ['status_fulfillment' => 'langsung-selesai'],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status_fulfillment', $this->recorded[1]['response']['errors'] ?? []);

        $this->assertSame(0, OrderFulfillment::count());
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/order-fulfillments
    // ------------------------------------------------------------------

    public function test_fulfillment_index_dan_filter(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser();
        $orderA = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-FUL-E1', 'status_pesanan' => 'dibayar']);
        $orderB = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-FUL-E2', 'status_pesanan' => 'diproses']);

        $this->seedFulfillment($orderA->id_order, 'persiapan');
        $this->seedFulfillment($orderA->id_order, 'dikemas');
        $this->seedFulfillment($orderB->id_order, 'persiapan');

        $this->hit('GET', '/api/admin/order-fulfillments', [
            'endpoint' => 'GET /api/admin/order-fulfillments',
            'label' => 'Daftar fulfillment (3 data, relasi pesanan & admin terisi)',
            'expected' => 200,
        ]);
        $response = $this->recorded[0]['response'] ?? [];
        $this->assertSame(3, $response['meta']['total'] ?? null);
        $this->assertCount(3, $response['data'] ?? []);
        $this->assertNotEmpty($response['data'][0]['order'] ?? null);

        $this->hit('GET', '/api/admin/order-fulfillments?status=dikemas', [
            'endpoint' => 'GET /api/admin/order-fulfillments',
            'label' => 'Daftar fulfillment difilter status dikemas',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[1]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/order-fulfillments?id_order='.$orderB->id_order, [
            'endpoint' => 'GET /api/admin/order-fulfillments',
            'label' => 'Daftar fulfillment difilter per pesanan',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[2]['response']['meta']['total'] ?? null);
        $this->assertSame($orderB->id_order, $this->recorded[2]['response']['data'][0]['id_order'] ?? null);

        $this->hit('GET', '/api/admin/order-fulfillments?status=tidak-ada', [
            'endpoint' => 'GET /api/admin/order-fulfillments',
            'label' => 'Daftar fulfillment dengan status tidak dikenal (422)',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    private function seedFulfillment(string $idOrder, string $status): void
    {
        OrderFulfillment::create([
            'id_fulfillment' => (string) Str::uuid(),
            'id_order' => $idOrder,
            'status_fulfillment' => $status,
            'catatan' => null,
            'diubah_oleh' => null,
            'urutan' => ((int) OrderFulfillment::where('id_order', $idOrder)->max('urutan')) + 1,
        ]);
    }
}
