<?php

namespace Tests\Feature\Endpoints;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Support\Str;

/**
 * Stock opname admin (3 endpoint):
 * GET  /api/admin/stock-opnames
 * POST /api/admin/stock-opnames
 * GET  /api/admin/stock-opnames/{id}
 */
class AdminStockOpnameEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Stock Opname Admin';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_akses_stock_opname(): void
    {
        $this->actingAsRole('admin');
        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Siapkan satu opname',
            'expected' => 201,
            'json' => [
                'catatan' => 'Opname persiapan akses',
                'items' => [
                    ['id_variant' => $this->seedCatalog(10)['variant']->id_variant, 'stok_fisik' => 8],
                ],
            ],
        ]);

        $id = $this->recorded[0]['response']['data']['id_stock_opname'] ?? 'x';

        $this->asGuest();
        $this->hit('GET', '/api/admin/stock-opnames', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Daftar opname tanpa token (401)',
            'expected' => 401,
        ]);
        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname tanpa token (401)',
            'expected' => 401,
            'json' => [],
        ]);
        $this->hit('GET', '/api/admin/stock-opnames/'.$id, [
            'endpoint' => 'GET /api/admin/stock-opnames/{id}',
            'label' => 'Detail opname tanpa token (401)',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/stock-opnames', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Daftar opname sebagai pelanggan (403)',
            'expected' => 403,
        ]);
        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname sebagai pelanggan (403)',
            'expected' => 403,
            'json' => [],
        ]);
        $this->hit('GET', '/api/admin/stock-opnames/'.$id, [
            'endpoint' => 'GET /api/admin/stock-opnames/{id}',
            'label' => 'Detail opname sebagai pelanggan (403)',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/admin/stock-opnames', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Daftar opname sebagai admin (200)',
            'expected' => 200,
        ]);
        $this->hit('GET', '/api/admin/stock-opnames/'.$id, [
            'endpoint' => 'GET /api/admin/stock-opnames/{id}',
            'label' => 'Detail opname sebagai admin (200)',
            'expected' => 200,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // POST /api/admin/stock-opnames — perhitungan selisih
    // ------------------------------------------------------------------

    public function test_buat_opname_menghitung_selisih_dengan_benar(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Parang');

        $vKurang = $this->makeVariant($product, 20);
        $vLebih = $this->makeVariant($product, 10);
        $vSama = $this->makeVariant($product, 7);

        $admin = $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname tiga varian (selisih -2, +5, 0)',
            'expected' => 201,
            'json' => [
                'catatan' => 'Opname bulanan',
                'items' => [
                    ['id_variant' => $vKurang->id_variant, 'stok_fisik' => 18, 'catatan' => 'Hilang 2'],
                    ['id_variant' => $vLebih->id_variant, 'stok_fisik' => 15, 'catatan' => 'Temuan 5'],
                    ['id_variant' => $vSama->id_variant, 'stok_fisik' => 7],
                ],
            ],
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame('draf', $data['status_opname'] ?? null);
        $this->assertSame(3, $data['total_item'] ?? null);
        $this->assertSame(3, (int) ($data['total_selisih'] ?? 0), 'total_selisih = -2 + 5 + 0');
        $this->assertSame($admin->id_user, $data['id_user'] ?? null);
        $this->assertMatchesRegularExpression('/^OPN-\d{8}-\d{4}$/', (string) ($data['kode_opname'] ?? ''));

        $byVariant = [];
        foreach ($data['items'] as $item) {
            $byVariant[$item['id_variant']] = $item;
        }

        $this->assertSame(20, (int) $byVariant[$vKurang->id_variant]['stok_sistem']);
        $this->assertSame(18, (int) $byVariant[$vKurang->id_variant]['stok_fisik']);
        $this->assertSame(-2, (int) $byVariant[$vKurang->id_variant]['selisih']);

        $this->assertSame(10, (int) $byVariant[$vLebih->id_variant]['stok_sistem']);
        $this->assertSame(15, (int) $byVariant[$vLebih->id_variant]['stok_fisik']);
        $this->assertSame(5, (int) $byVariant[$vLebih->id_variant]['selisih']);

        $this->assertSame(0, (int) $byVariant[$vSama->id_variant]['selisih']);

        // Stok varian TIDAK boleh berubah hanya karena opname dibuat.
        $this->assertSame(20, (int) ProductVariant::find($vKurang->id_variant)->stok);
        $this->assertSame(10, (int) ProductVariant::find($vLebih->id_variant)->stok);
        $this->assertSame(0, InventoryMovement::count(), 'Opname tidak mencatat pergerakan stok.');

        $this->assertRecorded();
    }

    public function test_buat_opname_validasi(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Parang');
        $v1 = $this->makeVariant($product, 10);
        $v2 = $this->makeVariant($product, 5);

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Item kosong (422)',
            'expected' => 422,
            'json' => ['items' => []],
        ]);
        $this->assertArrayHasKey('items', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Varian ganda dalam satu opname (422)',
            'expected' => 422,
            'json' => [
                'items' => [
                    ['id_variant' => $v1->id_variant, 'stok_fisik' => 5],
                    ['id_variant' => $v1->id_variant, 'stok_fisik' => 6],
                ],
            ],
        ]);
        $this->assertArrayHasKey('items', $this->recorded[1]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Stok fisik negatif (422)',
            'expected' => 422,
            'json' => [
                'items' => [
                    ['id_variant' => $v2->id_variant, 'stok_fisik' => -1],
                ],
            ],
        ]);
        $this->assertArrayHasKey('items.0.stok_fisik', $this->recorded[2]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Varian tidak ditemukan (422)',
            'expected' => 422,
            'json' => [
                'items' => [
                    ['id_variant' => '00000000-0000-0000-0000-000000000000', 'stok_fisik' => 5],
                ],
            ],
        ]);
        $this->assertArrayHasKey('items.0.id_variant', $this->recorded[3]['response']['errors'] ?? []);

        // Tidak ada opname yang benar-benar terbuat dari input tidak valid.
        $this->assertSame(0, StockOpname::count());
        $this->assertSame(0, StockOpnameItem::count());

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/stock-opnames — daftar, filter, paginasi
    // ------------------------------------------------------------------

    public function test_daftar_opname_filter_dan_paginasi(): void
    {
        $catalog = $this->seedCatalog(10);
        $variantId = $catalog['variant']->id_variant;

        $this->actingAsRole('admin');

        for ($i = 1; $i <= 3; $i++) {
            $this->hit('POST', '/api/admin/stock-opnames', [
                'endpoint' => 'POST /api/admin/stock-opnames',
                'label' => 'Buat opname ke-'.$i,
                'expected' => 201,
                'json' => [
                    'catatan' => 'Opname sesi '.$i,
                    'items' => [
                        ['id_variant' => $variantId, 'stok_fisik' => 10 - $i],
                    ],
                ],
            ]);
        }

        $this->hit('GET', '/api/admin/stock-opnames', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Daftar seluruh opname',
            'expected' => 200,
        ]);
        $this->assertSame(3, $this->recorded[3]['response']['meta']['total'] ?? null);
        $this->assertSame(1, $this->recorded[3]['response']['data'][0]['items_count'] ?? null);
        $this->assertArrayHasKey('user', $this->recorded[3]['response']['data'][0] ?? []);

        $this->hit('GET', '/api/admin/stock-opnames?status_opname=draf', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Filter status draf',
            'expected' => 200,
        ]);
        $this->assertSame(3, $this->recorded[4]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/stock-opnames?status_opname=selesai', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Filter status selesai (tidak ada)',
            'expected' => 200,
        ]);
        $this->assertSame(0, $this->recorded[5]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/stock-opnames?search=sesi 2', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Cari berdasarkan catatan',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[6]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/stock-opnames?per_page=2&page=2', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Paginasi halaman kedua',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[7]['response']['data'] ?? []);
        $this->assertSame(2, $this->recorded[7]['response']['meta']['current_page'] ?? null);

        $this->hit('GET', '/api/admin/stock-opnames?dari='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Rentang tanggal tanpa data',
            'expected' => 200,
        ]);
        $this->assertSame(0, $this->recorded[8]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/stock-opnames?status_opname=ngawur', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'Status tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status_opname', $this->recorded[9]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    public function test_paginasi_per_page_batas_atas(): void
    {
        $this->seedCatalog(10);
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/stock-opnames?page=1&per_page=10', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'per_page=10 (nilai umum frontend) diterima',
            'expected' => 200,
        ]);
        $this->assertSame(10, $this->recorded[0]['response']['meta']['per_page'] ?? null);

        $this->hit('GET', '/api/admin/stock-opnames?page=1&per_page=100', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'per_page=100 (batas maksimum) diterima',
            'expected' => 200,
        ]);
        $this->assertSame(100, $this->recorded[1]['response']['meta']['per_page'] ?? null);

        $this->hit('GET', '/api/admin/stock-opnames?page=1&per_page=101', [
            'endpoint' => 'GET /api/admin/stock-opnames',
            'label' => 'per_page=101 (melebihi batas) ditolak 422',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('per_page', $this->recorded[2]['response']['errors'] ?? []);
        $this->assertStringContainsString(
            'greater than 100',
            (string) ($this->recorded[2]['response']['errors']['per_page'][0] ?? '')
        );

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/stock-opnames/{id}
    // ------------------------------------------------------------------

    public function test_detail_opname_dan_404(): void
    {
        $catalog = $this->seedCatalog(20);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname untuk detail',
            'expected' => 201,
            'json' => [
                'catatan' => 'Opname detail',
                'items' => [
                    ['id_variant' => $variant->id_variant, 'stok_fisik' => 17],
                ],
            ],
        ]);

        $id = $this->recorded[0]['response']['data']['id_stock_opname'];

        $this->hit('GET', '/api/admin/stock-opnames/'.$id, [
            'endpoint' => 'GET /api/admin/stock-opnames/{id}',
            'label' => 'Detail opname berhasil',
            'expected' => 200,
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame($id, $data['id_stock_opname'] ?? null);
        $this->assertSame('draf', $data['status_opname'] ?? null);
        $this->assertCount(1, $data['items'] ?? []);
        $this->assertSame(20, (int) $data['items'][0]['stok_sistem']);
        $this->assertSame(17, (int) $data['items'][0]['stok_fisik']);
        $this->assertSame(-3, (int) $data['items'][0]['selisih']);
        $this->assertArrayHasKey('variant', $data['items'][0]);

        $this->hit('GET', '/api/admin/stock-opnames/00000000-0000-0000-0000-000000000000', [
            'endpoint' => 'GET /api/admin/stock-opnames/{id}',
            'label' => 'Detail opname tidak ditemukan (404)',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Alur end-to-end: opname -> penyesuaian -> stok terkoreksi
    // ------------------------------------------------------------------

    public function test_alur_opname_lalu_penyesuaian_stok(): void
    {
        $catalog = $this->seedCatalog(20);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Opname menemukan stok fisik 18 (selisih -2)',
            'expected' => 201,
            'json' => [
                'catatan' => 'Opname gudang',
                'items' => [
                    ['id_variant' => $variant->id_variant, 'stok_fisik' => 18],
                ],
            ],
        ]);

        $this->assertSame(-2, (int) ($this->recorded[0]['response']['data']['total_selisih'] ?? 0));
        // Belum ada perubahan stok.
        $this->assertSame(20, (int) ProductVariant::find($variant->id_variant)->stok);

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Terapkan selisih opname lewat penyesuaian',
            'expected' => 200,
            'json' => [
                'id_variant' => $variant->id_variant,
                'jenis_penyesuaian' => 'keluar',
                'jumlah' => 2,
                'alasan' => 'Selisih hasil stock opname',
            ],
        ]);

        $this->assertSame(18, (int) ProductVariant::find($variant->id_variant)->stok);
        $this->assertSame(1, InventoryMovement::count());
        $this->assertSame(
            'penyesuaian_keluar',
            InventoryMovement::first()->jenis_pergerakan
        );

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // POST /api/admin/stock-opnames/{id}/items — tambah item
    // ------------------------------------------------------------------

    public function test_tambah_item_pada_opname_draf(): void
    {
        $catalog = $this->seedCatalog(20);
        $product = $catalog['product'];
        $vAwal = $catalog['variant'];
        $vTambah = $this->makeVariant($product, 8);

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname awal satu item',
            'expected' => 201,
            'json' => [
                'catatan' => 'Opname bertambah',
                'items' => [
                    ['id_variant' => $vAwal->id_variant, 'stok_fisik' => 18],
                ],
            ],
        ]);

        $id = $this->recorded[0]['response']['data']['id_stock_opname'];

        $this->hit('POST', "/api/admin/stock-opnames/{$id}/items", [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/items',
            'label' => 'Tambah item kedua ke opname draf',
            'expected' => 201,
            'json' => [
                'id_variant' => $vTambah->id_variant,
                'stok_fisik' => 10,
                'catatan' => 'Temuan 2',
            ],
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame(2, $data['total_item'] ?? null);
        // total_selisih = (18-20) + (10-8) = -2 + 2 = 0
        $this->assertSame(0, (int) ($data['total_selisih'] ?? 0));
        $this->assertCount(2, $data['items'] ?? []);

        $byVariant = [];
        foreach ($data['items'] as $item) {
            $byVariant[$item['id_variant']] = $item;
        }
        $this->assertSame(8, (int) $byVariant[$vTambah->id_variant]['stok_sistem']);
        $this->assertSame(10, (int) $byVariant[$vTambah->id_variant]['stok_fisik']);
        $this->assertSame(2, (int) $byVariant[$vTambah->id_variant]['selisih']);

        // Stok tetap tidak berubah sebelum finalisasi.
        $this->assertSame(20, (int) ProductVariant::find($vAwal->id_variant)->stok);
        $this->assertSame(8, (int) ProductVariant::find($vTambah->id_variant)->stok);
        $this->assertSame(0, InventoryMovement::count());

        $this->assertRecorded();
    }

    public function test_tambah_item_validasi_dan_pembatasan(): void
    {
        $catalog = $this->seedCatalog(10);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname untuk uji pembatasan',
            'expected' => 201,
            'json' => [
                'items' => [
                    ['id_variant' => $variant->id_variant, 'stok_fisik' => 10],
                ],
            ],
        ]);

        $id = $this->recorded[0]['response']['data']['id_stock_opname'];

        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/items', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/items',
            'label' => 'Varian sudah ada pada opname (422)',
            'expected' => 422,
            'json' => ['id_variant' => $variant->id_variant, 'stok_fisik' => 5],
        ]);
        $this->assertArrayHasKey('id_variant', $this->recorded[1]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/items', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/items',
            'label' => 'Varian tidak ditemukan (422)',
            'expected' => 422,
            'json' => [
                'id_variant' => '00000000-0000-0000-0000-000000000000',
                'stok_fisik' => 5,
            ],
        ]);
        $this->assertArrayHasKey('id_variant', $this->recorded[2]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/stock-opnames/00000000-0000-0000-0000-000000000000/items', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/items',
            'label' => 'Opname tidak ditemukan (404)',
            'expected' => 404,
            'json' => [
                'id_variant' => $variant->id_variant,
                'stok_fisik' => 5,
            ],
        ]);

        // Finalisasi opname lalu pastikan item tidak bisa ditambah lagi.
        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/complete', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/complete',
            'label' => 'Finalisasi opname (200)',
            'expected' => 200,
        ]);

        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/items', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/items',
            'label' => 'Tambah item ke opname selesai (422)',
            'expected' => 422,
            'json' => ['id_variant' => $variant->id_variant, 'stok_fisik' => 5],
        ]);
        $this->assertArrayHasKey('status_opname', $this->recorded[5]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // POST /api/admin/stock-opnames/{id}/complete — finalisasi
    // ------------------------------------------------------------------

    public function test_finalisasi_menerapkan_selisih_dan_mencatat_pergerakan(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Parang');

        $vKurang = $this->makeVariant($product, 20);
        $vLebih = $this->makeVariant($product, 10);
        $vSama = $this->makeVariant($product, 7);

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname tiga varian (selisih -2, +5, 0)',
            'expected' => 201,
            'json' => [
                'items' => [
                    ['id_variant' => $vKurang->id_variant, 'stok_fisik' => 18],
                    ['id_variant' => $vLebih->id_variant, 'stok_fisik' => 15],
                    ['id_variant' => $vSama->id_variant, 'stok_fisik' => 7],
                ],
            ],
        ]);

        $id = $this->recorded[0]['response']['data']['id_stock_opname'];

        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/complete', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/complete',
            'label' => 'Finalisasi opname',
            'expected' => 200,
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame('selesai', $data['opname']['status_opname'] ?? null);
        $this->assertSame(3, (int) ($data['total_selisih'] ?? 0));
        $this->assertSame(2, (int) ($data['selisih_diterapkan'] ?? 0), 'Hanya varian berselisih yang dicatat.');

        // Stok benar-benar terkoreksi.
        $this->assertSame(18, (int) ProductVariant::find($vKurang->id_variant)->stok);
        $this->assertSame(15, (int) ProductVariant::find($vLebih->id_variant)->stok);
        $this->assertSame(7, (int) ProductVariant::find($vSama->id_variant)->stok);

        // Dua pergerakan tercatat (varian tanpa selisih tidak mencatat).
        $this->assertSame(2, InventoryMovement::count());

        $keluar = InventoryMovement::where('id_variant', $vKurang->id_variant)->first();
        $this->assertSame('penyesuaian_keluar', $keluar->jenis_pergerakan);
        $this->assertSame(2, (int) $keluar->jumlah);
        $this->assertSame(20, (int) $keluar->stok_sebelum);
        $this->assertSame(18, (int) $keluar->stok_sesudah);

        $masuk = InventoryMovement::where('id_variant', $vLebih->id_variant)->first();
        $this->assertSame('penyesuaian_masuk', $masuk->jenis_pergerakan);
        $this->assertSame(5, (int) $masuk->jumlah);
        $this->assertSame(10, (int) $masuk->stok_sebelum);
        $this->assertSame(15, (int) $masuk->stok_sesudah);
        $this->assertSame(
            $this->recorded[0]['response']['data']['kode_opname'],
            $masuk->referensi
        );

        $this->assertRecorded();
    }

    public function test_finalisasi_menolak_opname_selesai_dan_tanpa_item(): void
    {
        $catalog = $this->seedCatalog(10);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname untuk finalisasi ganda',
            'expected' => 201,
            'json' => [
                'items' => [
                    ['id_variant' => $variant->id_variant, 'stok_fisik' => 9],
                ],
            ],
        ]);
        $id = $this->recorded[0]['response']['data']['id_stock_opname'];

        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/complete', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/complete',
            'label' => 'Finalisasi pertama (200)',
            'expected' => 200,
        ]);

        $stokSetelahFinal = (int) ProductVariant::find($variant->id_variant)->stok;
        $pergerakanSetelahFinal = InventoryMovement::count();

        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/complete', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/complete',
            'label' => 'Finalisasi ulang opname selesai (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status_opname', $this->recorded[2]['response']['errors'] ?? []);

        // Tidak ada perubahan stok/pergerakan tambahan.
        $this->assertSame($stokSetelahFinal, (int) ProductVariant::find($variant->id_variant)->stok);
        $this->assertSame($pergerakanSetelahFinal, InventoryMovement::count());

        // Opname tanpa item tidak dapat difinalisasi.
        $opnameKosong = StockOpname::create([
            'id_stock_opname' => (string) Str::uuid(),
            'kode_opname' => 'OPN-'.now()->format('Ymd').'-9999',
            'status_opname' => 'draf',
            'id_user' => $this->actingAsRole('admin')->id_user,
            'total_item' => 0,
            'total_selisih' => 0,
        ]);

        $this->hit('POST', '/api/admin/stock-opnames/'.$opnameKosong->id_stock_opname.'/complete', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/complete',
            'label' => 'Finalisasi opname tanpa item (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('items', $this->recorded[3]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/stock-opnames/00000000-0000-0000-0000-000000000000/complete', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/complete',
            'label' => 'Finalisasi opname tidak ditemukan (404)',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_finalisasi_memeriksa_ulang_stok_terkini(): void
    {
        $catalog = $this->seedCatalog(20);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/stock-opnames', [
            'endpoint' => 'POST /api/admin/stock-opnames',
            'label' => 'Buat opname stok fisik 18',
            'expected' => 201,
            'json' => [
                'items' => [
                    ['id_variant' => $variant->id_variant, 'stok_fisik' => 18],
                ],
            ],
        ]);
        $id = $this->recorded[0]['response']['data']['id_stock_opname'];

        // Stok berubah setelah opname dibuat (mis. penjualan lain).
        ProductVariant::where('id_variant', $variant->id_variant)->update(['stok' => 15]);

        $this->hit('POST', '/api/admin/stock-opnames/'.$id.'/complete', [
            'endpoint' => 'POST /api/admin/stock-opnames/{id}/complete',
            'label' => 'Finalisasi dengan stok terkini 15 (selisih +3)',
            'expected' => 200,
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame(3, (int) ($data['total_selisih'] ?? 0));
        $this->assertSame(18, (int) ProductVariant::find($variant->id_variant)->stok);

        // stok_sistem item disegarkan ke nilai terkini.
        $this->assertSame(15, (int) $data['opname']['items'][0]['stok_sistem']);
        $this->assertSame(3, (int) $data['opname']['items'][0]['selisih']);

        $this->assertRecorded();
    }
}
