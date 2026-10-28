<?php

namespace Tests\Feature\Endpoints;

use App\Models\Shipment;
use App\Models\ShipmentHistory;
use Illuminate\Support\Str;

/**
 * Pengiriman (admin):
 * GET /api/admin/shipments                 ?status=&kurir=&search=&dari=&sampai=
 * PUT /api/admin/shipments/{id}/status     {status_pengiriman, nomor_resi?, lokasi?, keterangan?}
 */
class AdminShipmentEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Pengiriman Admin';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_shipments_akses(): void
    {
        $order = $this->makeOrder($this->makeUser(), ['nomor_pesanan' => 'ORD-SHIP-A']);
        $shipment = $this->seedShipment($order->id_order);

        $this->asGuest();
        $this->hit('GET', '/api/admin/shipments', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status pengiriman tanpa token (401)',
            'json' => ['status_pengiriman' => 'diproses'],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/shipments', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman sebagai pelanggan (403)',
            'expected' => 403,
        ]);
        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status pengiriman sebagai pelanggan (403)',
            'json' => ['status_pengiriman' => 'diproses'],
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/admin/shipments', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman sebagai admin (200)',
            'expected' => 200,
        ]);

        $this->assertSame('menunggu', Shipment::whereKey($shipment->id_shipment)->value('status_pengiriman'), 'Akses ditolak tidak boleh mengubah data.');
        $this->assertSame(0, ShipmentHistory::count());

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/shipments
    // ------------------------------------------------------------------

    public function test_shipments_index_dan_filter(): void
    {
        $this->actingAsRole('admin');

        $orderA = $this->makeOrder($this->makeUser(), ['nomor_pesanan' => 'ORD-SHIP-B']);
        $orderB = $this->makeOrder($this->makeUser(), ['nomor_pesanan' => 'ORD-SHIP-C']);

        $this->seedShipment($orderA->id_order, [
            'kurir' => 'JNE',
            'status_pengiriman' => 'menunggu',
            'nomor_resi' => null,
        ]);
        $this->seedShipment($orderB->id_order, [
            'kurir' => 'SiCepat',
            'status_pengiriman' => 'dikirim',
            'nomor_resi' => 'RESI-777',
            'tanggal_dikirim' => now(),
        ]);

        $this->hit('GET', '/api/admin/shipments', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman (2 data, menyertakan relasi pesanan)',
            'expected' => 200,
        ]);
        $response = $this->recorded[0]['response'] ?? [];
        $this->assertSame(2, $response['meta']['total'] ?? null);
        $this->assertCount(2, $response['data'] ?? []);
        $this->assertNotEmpty($response['data'][0]['order'] ?? null);

        $this->hit('GET', '/api/admin/shipments?status=dikirim', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman difilter status dikirim',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[1]['response']['meta']['total'] ?? null);
        $this->assertSame('RESI-777', $this->recorded[1]['response']['data'][0]['nomor_resi'] ?? null);

        $this->hit('GET', '/api/admin/shipments?kurir=JNE', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman difilter kurir JNE',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[2]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/shipments?search=RESI-777', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman dicari lewat nomor resi',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[3]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/shipments?search=ORD-SHIP-B', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman dicari lewat nomor pesanan',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[4]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/shipments?status=bukan-status', [
            'endpoint' => 'GET /api/admin/shipments',
            'label' => 'Daftar pengiriman dengan status tidak dikenal (422)',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // PUT /api/admin/shipments/{id}/status
    // ------------------------------------------------------------------

    public function test_shipments_update_status_sukses(): void
    {
        $this->actingAsRole('admin');
        $order = $this->makeOrder($this->makeUser(), ['nomor_pesanan' => 'ORD-SHIP-D']);
        $shipment = $this->seedShipment($order->id_order);

        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status menunggu -> dikirim (wajib nomor resi)',
            'json' => [
                'status_pengiriman' => 'dikirim',
                'nomor_resi' => 'RESI-2026-001',
                'lokasi' => 'Pekalongan',
                'keterangan' => 'Paket diserahkan ke kurir',
            ],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('dikirim', $data['status_pengiriman'] ?? null);
        $this->assertSame('RESI-2026-001', $data['nomor_resi'] ?? null);

        $fresh = Shipment::whereKey($shipment->id_shipment)->first();
        $this->assertSame('dikirim', $fresh->status_pengiriman, 'Status harus tersimpan.');
        $this->assertNotNull($fresh->tanggal_dikirim, 'tanggal_dikirim harus terisi saat status dikirim.');
        $this->assertNull($fresh->tanggal_diterima);

        $history = ShipmentHistory::where('id_shipment', $shipment->id_shipment)->first();
        $this->assertNotNull($history, 'Perubahan status harus mencatat shipment_histories.');
        $this->assertSame('menunggu', $history->status_sebelumnya);
        $this->assertSame('dikirim', $history->status_baru);
        $this->assertSame('Pekalongan', $history->lokasi);
        $this->assertNotNull($history->waktu_perubahan);
        $this->assertArrayHasKey('shipment_histories', $this->recorded[0]['db_effect'] ?? []);

        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status dikirim -> diterima (tanggal_diterima terisi)',
            'json' => [
                'status_pengiriman' => 'diterima',
                'lokasi' => 'Surakarta',
                'keterangan' => 'Diterima penerima',
            ],
            'expected' => 200,
        ]);

        $fresh = Shipment::whereKey($shipment->id_shipment)->first();
        $this->assertSame('diterima', $fresh->status_pengiriman);
        $this->assertNotNull($fresh->tanggal_diterima);
        $this->assertSame('RESI-2026-001', $fresh->nomor_resi, 'Nomor resi tetap dipertahankan bila tidak dikirim ulang.');
        $this->assertSame(2, ShipmentHistory::where('id_shipment', $shipment->id_shipment)->count());

        $histories = ShipmentHistory::where('id_shipment', $shipment->id_shipment)->get()->all();
        $this->assertEqualsCanonicalizing(
            [['menunggu', 'dikirim'], ['dikirim', 'diterima']],
            array_map(fn ($h) => [$h->status_sebelumnya, $h->status_baru], $histories),
            'Dua transisi status harus tercatat: menunggu->dikirim dan dikirim->diterima.'
        );

        $diterima = array_values(array_filter($histories, fn ($h) => $h->status_baru === 'diterima'))[0] ?? null;
        $this->assertSame('Surakarta', $diterima?->lokasi);
        $this->assertNotNull($diterima?->waktu_perubahan);

        $this->assertRecorded();
    }

    public function test_shipments_update_status_ditolak(): void
    {
        $this->actingAsRole('admin');
        $order = $this->makeOrder($this->makeUser(), ['nomor_pesanan' => 'ORD-SHIP-E']);
        $shipment = $this->seedShipment($order->id_order);

        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status ke status yang sama (422)',
            'json' => ['status_pengiriman' => 'menunggu'],
            'expected' => 422,
        ]);

        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status ke dikirim tanpa nomor resi (422, aturan bisnis)',
            'json' => ['status_pengiriman' => 'dikirim'],
            'expected' => 422,
        ]);

        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status dengan nilai di luar daftar (422)',
            'json' => ['status_pengiriman' => 'hilang-gaib'],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/admin/shipments/00000000-0000-4000-8000-000000000000/status', [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status pengiriman yang tidak ada (404)',
            'json' => ['status_pengiriman' => 'diproses'],
            'expected' => 404,
        ]);

        $this->hit('PUT', "/api/admin/shipments/{$shipment->id_shipment}/status", [
            'endpoint' => 'PUT /api/admin/shipments/{id}/status',
            'label' => 'Ubah status tanpa body (422 validasi)',
            'json' => [],
            'expected' => 422,
        ]);

        $this->assertSame('menunggu', Shipment::whereKey($shipment->id_shipment)->value('status_pengiriman'), 'Semua penolakan tidak boleh mengubah data.');
        $this->assertSame(0, ShipmentHistory::count(), 'Penolakan tidak boleh membuat riwayat.');

        $this->assertRecorded();
    }

    private function seedShipment(string $idOrder, array $overrides = []): Shipment
    {
        return Shipment::create(array_merge([
            'id_shipment' => (string) Str::uuid(),
            'id_order' => $idOrder,
            'provider' => 'manual',
            'kurir' => 'JNE',
            'layanan_pengiriman' => 'Reguler',
            'kode_layanan' => 'REG',
            'berat_gram' => 500,
            'biaya_pengiriman' => 9000,
            'nomor_resi' => null,
            'status_pengiriman' => 'menunggu',
            'tanggal_dikirim' => null,
            'tanggal_diterima' => null,
        ], $overrides));
    }
}
