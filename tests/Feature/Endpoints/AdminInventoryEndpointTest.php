<?php

namespace Tests\Feature\Endpoints;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Testing\TestResponse;

/**
 * Inventaris admin (8 endpoint):
 * GET    /api/admin/inventory
 * GET    /api/admin/inventory/movements
 * POST   /api/admin/inventory/adjustments
 * GET    /api/admin/inventory/low-stock
 * GET    /api/admin/inventory/summary
 * GET    /api/admin/inventory/valuation
 * GET    /api/admin/inventory/export
 * GET    /api/admin/inventory/{variant_id}
 */
class AdminInventoryEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Inventaris Admin';

    // ------------------------------------------------------------------
    // Akses
    // ------------------------------------------------------------------

    public function test_akses_seluruh_endpoint_inventaris(): void
    {
        $this->seedCatalog();

        $endpoints = [
            'GET /api/admin/inventory',
            'GET /api/admin/inventory/movements',
            'GET /api/admin/inventory/low-stock',
            'GET /api/admin/inventory/summary',
            'GET /api/admin/inventory/valuation',
            'GET /api/admin/inventory/export',
            'GET /api/admin/inventory/00000000-0000-0000-0000-000000000000',
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

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian stok tanpa token (401)',
            'expected' => 401,
            'json' => [],
        ]);

        $this->actingAsRole('pelanggan');

        foreach ($endpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint, 2);
            $this->hit($method, $url, [
                'endpoint' => $endpoint,
                'label' => $endpoint.' sebagai pelanggan (403)',
                'expected' => 403,
            ]);
        }

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian stok sebagai pelanggan (403)',
            'expected' => 403,
            'json' => [],
        ]);

        $this->actingAsRole('admin');

        foreach ($endpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint, 2);
            $this->hit($method, $url, [
                'endpoint' => $endpoint,
                'label' => $endpoint.' sebagai admin (200/404)',
                'expected' => $url === '/api/admin/inventory/00000000-0000-0000-0000-000000000000' ? 404 : 200,
            ]);
        }

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/inventory — filter, sorting, paginasi
    // ------------------------------------------------------------------

    public function test_daftar_inventaris_filter_sorting_paginasi(): void
    {
        $this->seedCategory('cat-1', 'Batik Tulis');
        $this->seedCategory('cat-2', 'Batik Cap');

        $parang = $this->makeProduct('Batik Parang', 'aktif', 'cat-1');
        $parang->categories()->sync(['cat-1', 'cat-2']);
        $vAman = $this->makeVariant($parang, 20, 'aktif', 150000);
        $vMenipis = $this->makeVariant($parang, 3, 'aktif', 175000);
        $vKritis = $this->makeVariant($parang, 0, 'nonaktif', 200000);

        ProductVariant::whereKey($vMenipis->id_variant)->update(['stok_minimum' => 5]);
        ProductVariant::whereKey($vKritis->id_variant)->update(['stok_minimum' => 5]);

        $other = $this->makeProduct('Kain Lurik', 'aktif', 'cat-1');
        $this->makeVariant($other, 50, 'aktif', 90000);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/inventory', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Daftar seluruh inventaris',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(4, $data);
        $this->assertSame(4, $this->recorded[0]['response']['meta']['total'] ?? null);

        $statusById = [];
        foreach ($data as $row) {
            $statusById[$row['id_variant']] = $row['status_stok'];
        }

        $this->assertSame('aman', $statusById[$vAman->id_variant]);
        $this->assertSame('menipis', $statusById[$vMenipis->id_variant]);
        $this->assertSame('kritis', $statusById[$vKritis->id_variant]);

        $this->hit('GET', '/api/admin/inventory?status_stok=kritis', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Filter status stok kritis',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[1]['response']['meta']['total'] ?? null);
        $this->assertSame($vKritis->id_variant, $this->recorded[1]['response']['data'][0]['id_variant'] ?? null);

        $this->hit('GET', '/api/admin/inventory?status_stok=menipis', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Filter status stok menipis',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[2]['response']['meta']['total'] ?? null);
        $this->assertSame($vMenipis->id_variant, $this->recorded[2]['response']['data'][0]['id_variant'] ?? null);

        $this->hit('GET', '/api/admin/inventory?status_stok=aman', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Filter status stok aman',
            'expected' => 200,
        ]);
        $this->assertSame(2, $this->recorded[3]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory?search=Parang', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Cari inventaris berdasarkan nama produk',
            'expected' => 200,
        ]);
        $this->assertSame(3, $this->recorded[4]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory?category=cat-2&sort=stok&order=asc', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Filter kategori + urutkan stok menaik',
            'expected' => 200,
        ]);
        $stok = array_column($this->recorded[5]['response']['data'] ?? [], 'stok');
        $sorted = $stok;
        sort($sorted);
        $this->assertSame($sorted, $stok);

        $this->hit('GET', '/api/admin/inventory?per_page=2&page=2', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Paginasi halaman kedua',
            'expected' => 200,
        ]);
        $this->assertCount(2, $this->recorded[6]['response']['data'] ?? []);
        $this->assertSame(2, $this->recorded[6]['response']['meta']['current_page'] ?? null);
        $this->assertSame(4, $this->recorded[6]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory?sort=stok&order=desc&status_stok=kritis', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Kombinasi filter + sorting',
            'expected' => 200,
        ]);
        $this->assertSame($vKritis->id_variant, $this->recorded[7]['response']['data'][0]['id_variant'] ?? null);

        $this->hit('GET', '/api/admin/inventory?sort=kolom_ngawur', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Kolom sort tidak diizinkan (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('sort', $this->recorded[8]['response']['errors'] ?? []);

        $this->hit('GET', '/api/admin/inventory?status_stok=ngawur', [
            'endpoint' => 'GET /api/admin/inventory',
            'label' => 'Filter status stok tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('status_stok', $this->recorded[9]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/inventory/{variant_id} — tidak bentrok route statis
    // ------------------------------------------------------------------

    public function test_detail_inventaris_dan_riwayat_pergerakan(): void
    {
        $catalog = $this->seedCatalog(10);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/inventory/'.$variant->id_variant, [
            'endpoint' => 'GET /api/admin/inventory/{id}',
            'label' => 'Detail inventaris varian',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($variant->id_variant, $data['id_variant'] ?? null);
        $this->assertSame(10, $data['stok'] ?? null);
        $this->assertSame('aman', $data['status_stok'] ?? null);
        $this->assertArrayHasKey('inventory_movements', $data);

        $this->hit('GET', '/api/admin/inventory/00000000-0000-0000-0000-000000000000', [
            'endpoint' => 'GET /api/admin/inventory/{id}',
            'label' => 'Detail inventaris varian tidak ditemukan (404)',
            'expected' => 404,
        ]);

        // Route statis tidak boleh tertangkap wildcard {variant_id}.
        $this->hit('GET', '/api/admin/inventory/movements', [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Route /movements tidak tertangkap {variant_id}',
            'expected' => 200,
        ]);
        $this->assertArrayHasKey('meta', $this->recorded[2]['response'] ?? []);

        $this->hit('GET', '/api/admin/inventory/summary', [
            'endpoint' => 'GET /api/admin/inventory/summary',
            'label' => 'Route /summary tidak tertangkap {variant_id}',
            'expected' => 200,
        ]);
        $this->assertArrayHasKey('total_varian', $this->recorded[3]['response']['data'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // POST /api/admin/inventory/adjustments
    // ------------------------------------------------------------------

    public function test_penyesuaian_stok_masuk_dan_keluar_konsisten(): void
    {
        $catalog = $this->seedCatalog(10);
        $variant = $catalog['variant'];

        $admin = $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian stok masuk 5 unit',
            'expected' => 200,
            'json' => [
                'id_variant' => $variant->id_variant,
                'jenis_penyesuaian' => 'masuk',
                'jumlah' => 5,
                'alasan' => 'Koreksi stok fisik gudang',
            ],
        ]);

        $body = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(15, $body['variant']['stok'] ?? null);
        $this->assertSame('penyesuaian_masuk', $body['movement']['jenis_pergerakan'] ?? null);
        $this->assertSame(10, (int) ($body['movement']['stok_sebelum'] ?? 0));
        $this->assertSame(15, (int) ($body['movement']['stok_sesudah'] ?? 0));
        $this->assertSame($admin->id_user, $body['movement']['id_user'] ?? null);

        $this->assertSame(15, (int) ProductVariant::find($variant->id_variant)->stok);
        $this->assertSame(1, InventoryMovement::count());

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian stok keluar 3 unit',
            'expected' => 200,
            'json' => [
                'id_variant' => $variant->id_variant,
                'jenis_penyesuaian' => 'keluar',
                'jumlah' => 3,
                'alasan' => 'Barang rusak',
            ],
        ]);

        $body = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame(12, $body['variant']['stok'] ?? null);
        $this->assertSame('penyesuaian_keluar', $body['movement']['jenis_pergerakan'] ?? null);
        $this->assertSame(15, (int) ($body['movement']['stok_sebelum'] ?? 0));
        $this->assertSame(12, (int) ($body['movement']['stok_sesudah'] ?? 0));

        // Riwayat berurutan: stok_sebelum request kedua = stok_sesudah request pertama.
        $all = InventoryMovement::orderBy('dibuat_pada')->get();
        $this->assertCount(2, $all);
        $this->assertSame($all[0]->stok_sesudah, $all[1]->stok_sebelum);
        $this->assertSame(12, (int) ProductVariant::find($variant->id_variant)->stok);

        $this->assertRecorded();
    }

    public function test_penyesuaian_stok_keluar_melebihi_stok_ditolak_tanpa_perubahan(): void
    {
        $catalog = $this->seedCatalog(4);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian keluar melebihi stok (422)',
            'expected' => 422,
            'json' => [
                'id_variant' => $variant->id_variant,
                'jenis_penyesuaian' => 'keluar',
                'jumlah' => 5,
                'alasan' => 'Melebihi stok tersedia',
            ],
        ]);

        $this->assertArrayHasKey('jumlah', $this->recorded[0]['response']['errors'] ?? []);

        // Stok tidak berubah dan tidak ada pergerakan setengah jadi.
        $this->assertSame(4, (int) ProductVariant::find($variant->id_variant)->stok);
        $this->assertSame(0, InventoryMovement::count());

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian stok masuk tepat habis',
            'expected' => 200,
            'json' => [
                'id_variant' => $variant->id_variant,
                'jenis_penyesuaian' => 'masuk',
                'jumlah' => 0 + 1,
                'alasan' => 'Uji operasi gagal sebelumnya tidak menyisakan perubahan',
            ],
        ]);
        $this->assertSame(5, (int) ProductVariant::find($variant->id_variant)->stok);
        $this->assertSame(1, InventoryMovement::count());

        $this->assertRecorded();
    }

    public function test_penyesuaian_keluar_tidak_boleh_mengurangi_stok_yang_direservasi(): void
    {
        $catalog = $this->seedCatalog(5);
        $variant = $catalog['variant'];
        $variant->update(['stok_dipesan' => 3]);

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian keluar melebihi stok tersedia (5 - 3 = 2)',
            'expected' => 422,
            'json' => [
                'id_variant' => $variant->id_variant,
                'jenis_penyesuaian' => 'keluar',
                'jumlah' => 3,
                'alasan' => 'Mengurangi stok yang sudah direservasi',
            ],
        ]);

        $this->assertStringContainsString(
            'Stok tersedia: 2.',
            implode(' ', (array) ($this->recorded[0]['response']['errors']['jumlah'] ?? []))
        );
        $this->assertSame(5, (int) $variant->fresh()->stok);
        $this->assertSame(3, (int) $variant->fresh()->stok_dipesan);
        $this->assertSame(0, InventoryMovement::count());

        $this->assertRecorded();
    }

    public function test_penyesuaian_stok_validasi(): void
    {
        $catalog = $this->seedCatalog(10);

        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian tanpa alasan (422)',
            'expected' => 422,
            'json' => [
                'id_variant' => $catalog['variant']->id_variant,
                'jenis_penyesuaian' => 'masuk',
                'jumlah' => 5,
            ],
        ]);
        $this->assertArrayHasKey('alasan', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian jenis tidak dikenal (422)',
            'expected' => 422,
            'json' => [
                'id_variant' => $catalog['variant']->id_variant,
                'jenis_penyesuaian' => 'ngawur',
                'jumlah' => 5,
                'alasan' => 'Uji',
            ],
        ]);
        $this->assertArrayHasKey('jenis_penyesuaian', $this->recorded[1]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian jumlah nol (422)',
            'expected' => 422,
            'json' => [
                'id_variant' => $catalog['variant']->id_variant,
                'jenis_penyesuaian' => 'masuk',
                'jumlah' => 0,
                'alasan' => 'Uji',
            ],
        ]);
        $this->assertArrayHasKey('jumlah', $this->recorded[2]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/inventory/adjustments', [
            'endpoint' => 'POST /api/admin/inventory/adjustments',
            'label' => 'Penyesuaian varian tidak ada (422)',
            'expected' => 422,
            'json' => [
                'id_variant' => '00000000-0000-0000-0000-000000000000',
                'jenis_penyesuaian' => 'masuk',
                'jumlah' => 1,
                'alasan' => 'Uji',
            ],
        ]);
        $this->assertArrayHasKey('id_variant', $this->recorded[3]['response']['errors'] ?? []);

        $this->assertSame(0, InventoryMovement::count());
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/inventory/movements
    // ------------------------------------------------------------------

    public function test_riwayat_pergerakan_stok_filter_dan_paginasi(): void
    {
        $catalog = $this->seedCatalog(10);
        $variant = $catalog['variant'];

        $this->actingAsRole('admin');

        // Buat 3 pergerakan: masuk, keluar, masuk.
        foreach ([['masuk', 5], ['keluar', 2], ['masuk', 3]] as $i => [$jenis, $jumlah]) {
            $this->hit('POST', '/api/admin/inventory/adjustments', [
                'endpoint' => 'POST /api/admin/inventory/adjustments',
                'label' => 'Siapkan pergerakan ke-'.($i + 1).' ('.$jenis.')',
                'expected' => 200,
                'json' => [
                    'id_variant' => $variant->id_variant,
                    'jenis_penyesuaian' => $jenis,
                    'jumlah' => $jumlah,
                    'alasan' => 'Persiapan data pergerakan',
                    'referensi' => 'REF-'.($i + 1),
                ],
            ]);
        }

        $this->hit('GET', '/api/admin/inventory/movements', [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Seluruh riwayat pergerakan',
            'expected' => 200,
        ]);
        $this->assertSame(3, $this->recorded[3]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory/movements?jenis_pergerakan=penyesuaian_keluar', [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Filter jenis pergerakan keluar',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[4]['response']['meta']['total'] ?? null);
        $this->assertSame(
            'penyesuaian_keluar',
            $this->recorded[4]['response']['data'][0]['jenis_pergerakan'] ?? null
        );

        $this->hit('GET', '/api/admin/inventory/movements?id_variant='.$variant->id_variant, [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Filter berdasarkan varian',
            'expected' => 200,
        ]);
        $this->assertSame(3, $this->recorded[5]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory/movements?search=REF-2', [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Cari berdasarkan referensi',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[6]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory/movements?per_page=2&page=1', [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Paginasi pergerakan',
            'expected' => 200,
        ]);
        $this->assertCount(2, $this->recorded[7]['response']['data'] ?? []);
        $this->assertSame(3, $this->recorded[7]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory/movements?dari='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Rentang tanggal tanpa data',
            'expected' => 200,
        ]);
        $this->assertSame(0, $this->recorded[8]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/inventory/movements?jenis_pergerakan=ngawur', [
            'endpoint' => 'GET /api/admin/inventory/movements',
            'label' => 'Jenis pergerakan tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('jenis_pergerakan', $this->recorded[9]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/inventory/low-stock
    // ------------------------------------------------------------------

    public function test_daftar_stok_menipis(): void
    {
        $this->seedCategory();

        $product = $this->makeProduct('Batik Parang');
        $kritis = $this->makeVariant($product, 0);
        $menipis = $this->makeVariant($product, 3);
        $aman = $this->makeVariant($product, 30);

        ProductVariant::whereKey($menipis->id_variant)->update(['stok_minimum' => 5]);
        // Varian dengan stok_minimum 0 tidak boleh ditandai menipis meski stok kecil.
        ProductVariant::whereKey($aman->id_variant)->update(['stok_minimum' => 0]);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/inventory/low-stock', [
            'endpoint' => 'GET /api/admin/inventory/low-stock',
            'label' => 'Daftar stok menipis & kritis',
            'expected' => 200,
        ]);
        $this->assertSame(2, $this->recorded[0]['response']['meta']['total'] ?? null);

        $byId = [];
        foreach ($this->recorded[0]['response']['data'] as $row) {
            $byId[$row['id_variant']] = $row['level'];
        }
        $this->assertSame('kritis', $byId[$kritis->id_variant]);
        $this->assertSame('menipis', $byId[$menipis->id_variant]);
        $this->assertArrayNotHasKey($aman->id_variant, $byId);

        $this->hit('GET', '/api/admin/inventory/low-stock?level=kritis', [
            'endpoint' => 'GET /api/admin/inventory/low-stock',
            'label' => 'Hanya kategori kritis',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[1]['response']['meta']['total'] ?? null);
        $this->assertSame($kritis->id_variant, $this->recorded[1]['response']['data'][0]['id_variant'] ?? null);

        $this->hit('GET', '/api/admin/inventory/low-stock?level=menipis', [
            'endpoint' => 'GET /api/admin/inventory/low-stock',
            'label' => 'Hanya kategori menipis',
            'expected' => 200,
        ]);
        $this->assertSame(1, $this->recorded[2]['response']['meta']['total'] ?? null);
        $this->assertSame($menipis->id_variant, $this->recorded[2]['response']['data'][0]['id_variant'] ?? null);

        $this->hit('GET', '/api/admin/inventory/low-stock?level=ngawur', [
            'endpoint' => 'GET /api/admin/inventory/low-stock',
            'label' => 'Level tidak dikenal (422)',
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('level', $this->recorded[3]['response']['errors'] ?? []);

        $this->hit('GET', '/api/admin/inventory/low-stock?per_page=1&page=2', [
            'endpoint' => 'GET /api/admin/inventory/low-stock',
            'label' => 'Paginasi stok menipis',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[4]['response']['data'] ?? []);
        $this->assertSame(2, $this->recorded[4]['response']['meta']['current_page'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/inventory/summary
    // ------------------------------------------------------------------

    public function test_ringkasan_inventaris_akurat(): void
    {
        $this->seedCategory();

        $productA = $this->makeProduct('Batik A');
        $this->makeVariant($productA, 10);
        $vMenipis = $this->makeVariant($productA, 2);
        ProductVariant::whereKey($vMenipis->id_variant)->update(['stok_minimum' => 5]);

        $productB = $this->makeProduct('Batik B');
        $this->makeVariant($productB, 0);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/inventory/summary', [
            'endpoint' => 'GET /api/admin/inventory/summary',
            'label' => 'Ringkasan inventaris',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        $this->assertSame(3, $data['total_varian'] ?? null);
        $this->assertSame(3, $data['total_sku'] ?? null);
        $this->assertSame(2, $data['total_produk'] ?? null);
        $this->assertSame(12, $data['total_unit_stok'] ?? null);
        $this->assertSame(1, $data['varian_kritis'] ?? null);
        $this->assertSame(1, $data['varian_menipis'] ?? null);
        $this->assertSame(1, $data['varian_aman'] ?? null);
        $this->assertTrue($data['jumlah_produk_dihitung_terpisah'] ?? false);

        $this->assertRecorded();
    }

    public function test_ringkasan_inventaris_kosong(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/inventory/summary', [
            'endpoint' => 'GET /api/admin/inventory/summary',
            'label' => 'Ringkasan inventaris tanpa data',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(0, $data['total_varian'] ?? null);
        $this->assertSame(0, $data['total_produk'] ?? null);
        $this->assertSame(0, $data['total_unit_stok'] ?? null);
        $this->assertSame(0, $data['varian_kritis'] ?? null);
        $this->assertSame(0, $data['varian_menipis'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/inventory/valuation
    // ------------------------------------------------------------------

    public function test_valuasi_inventaris_hanya_hitung_harga_modal(): void
    {
        $this->seedCategory();
        $this->seedCategory('cat-2', 'Batik Cap');

        $product = $this->makeProduct('Batik Parang');
        $product->categories()->sync(['cat-1', 'cat-2']);
        $punyaModal = $this->makeVariant($product, 10, 'aktif', 150000);
        $tanpaModal = $this->makeVariant($product, 4, 'aktif', 175000);

        ProductVariant::whereKey($punyaModal->id_variant)->update(['harga_modal' => 100000]);
        // tanpaModal dibiarkan harga_modal NULL.

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/inventory/valuation', [
            'endpoint' => 'GET /api/admin/inventory/valuation',
            'label' => 'Valuasi persediaan',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];

        // 10 x 100.000 = 1.000.000; varian tanpa modal tidak dihitung.
        $this->assertEquals(1000000, $data['total_nilai_persediaan'] ?? null);
        $this->assertSame(1, $data['varian_dihitung'] ?? null);
        $this->assertSame(1, $data['varian_belum_dihitung'] ?? null);
        $this->assertSame(4, $data['unit_belum_dihitung'] ?? null);
        $this->assertFalse($data['lengkap'] ?? true);

        $this->assertCount(1, $data['per_produk'] ?? []);
        $this->assertEquals(1000000, $data['per_produk'][0]['total_nilai'] ?? null);
        $this->assertSame(1, $data['per_produk'][0]['varian_belum_dihitung'] ?? null);

        // Harga jual (175.000) tidak boleh dipakai sebagai harga modal.
        $this->assertStringContainsString('harga_modal', $data['keterangan_nilai'] ?? '');

        $this->hit('GET', '/api/admin/inventory/valuation?category=cat-2', [
            'endpoint' => 'GET /api/admin/inventory/valuation',
            'label' => 'Valuasi difilter kategori',
            'expected' => 200,
        ]);
        $this->assertEquals(1000000, $this->recorded[1]['response']['data']['total_nilai_persediaan'] ?? null);

        $this->assertRecorded();
    }

    public function test_valuasi_inventaris_tanpa_harga_modal_sama_sekali(): void
    {
        $catalog = $this->seedCatalog(10);

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/inventory/valuation', [
            'endpoint' => 'GET /api/admin/inventory/valuation',
            'label' => 'Valuasi saat tidak ada harga modal',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        // NULL tidak diperlakukan sebagai 0 sehingga total = 0 namun lengkap = false.
        $this->assertEquals(0, $data['total_nilai_persediaan'] ?? null);
        $this->assertSame(0, $data['varian_dihitung'] ?? null);
        $this->assertSame(1, $data['varian_belum_dihitung'] ?? null);
        $this->assertFalse($data['lengkap'] ?? true);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/inventory/export (CSV)
    // ------------------------------------------------------------------

    public function test_ekspor_inventaris_csv_dan_formula_injection(): void
    {
        $this->seedCategory();

        $product = $this->makeProduct('Batik Parang');
        $normal = $this->makeVariant($product, 10);
        ProductVariant::whereKey($normal->id_variant)->update(['harga_modal' => 100000]);

        // SKU yang berbahaya bagi spreadsheet: diawali "=".
        $berbahaya = $this->makeVariant($product, 4);
        ProductVariant::whereKey($berbahaya->id_variant)->update([
            'sku' => '=1+1',
            'stok_minimum' => 5,
        ]);

        $this->actingAsRole('admin');

        $response = $this->hit('GET', '/api/admin/inventory/export', [
            'endpoint' => 'GET /api/admin/inventory/export',
            'label' => 'Ekspor seluruh inventaris',
            'expected' => 200,
        ]);

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertMatchesRegularExpression(
            '/inventaris-\d{4}-\d{2}-\d{2}\.csv/',
            (string) $response->headers->get('content-disposition')
        );

        $rows = $this->csvRows($response);
        $this->assertSame([
            'id_variant',
            'sku',
            'nama_produk',
            'kategori',
            'nama_varian',
            'warna',
            'ukuran',
            'stok',
            'stok_minimum',
            'status_stok',
            'harga',
            'harga_modal',
            'nilai_persediaan',
            'status_varian',
            'dibuat_pada',
        ], $rows[0]);
        $this->assertCount(3, $rows);

        $bySku = [];
        foreach (array_slice($rows, 1) as $row) {
            $bySku[$row[1]] = $row;
        }

        // Nilai berbahaya harus dinetralkan dengan apostrof di depan.
        $this->assertArrayHasKey("'=1+1", $bySku, 'Sel SKU harus dinetralkan dari CSV formula injection.');
        $this->assertSame('menipis', $bySku["'=1+1"][9]);

        $normalRow = $bySku[$normal->sku];
        $this->assertSame('aman', $normalRow[9]);
        $this->assertEquals(1000000, (float) $normalRow[12]);
        $this->assertEquals(100000, (float) $normalRow[11]);

        // Filter yang sama dengan daftar; paginasi diabaikan.
        $filtered = $this->hit('GET', '/api/admin/inventory/export?status_stok=kritis&per_page=1', [
            'endpoint' => 'GET /api/admin/inventory/export',
            'label' => 'Ekspor difilter status kritis tanpa paginasi',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->csvRows($filtered), 'Hanya header karena tidak ada varian kritis.');

        $search = $this->hit('GET', '/api/admin/inventory/export?search=Parang', [
            'endpoint' => 'GET /api/admin/inventory/export',
            'label' => 'Ekspor hasil pencarian',
            'expected' => 200,
        ]);
        $this->assertCount(3, $this->csvRows($search));

        $this->assertRecorded();
    }

    public function test_ekspor_inventaris_csv_kosong(): void
    {
        $this->actingAsRole('admin');

        $response = $this->hit('GET', '/api/admin/inventory/export', [
            'endpoint' => 'GET /api/admin/inventory/export',
            'label' => 'Ekspor saat tidak ada data',
            'expected' => 200,
        ]);

        $rows = $this->csvRows($response);
        $this->assertCount(1, $rows, 'Hanya baris header.');
        $this->assertSame('id_variant', $rows[0][0]);

        $this->assertRecorded();
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function csvRows(TestResponse $response): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\n|\r/', trim($response->streamedContent())) as $line) {
            if ($line === '') {
                continue;
            }

            $rows[] = str_getcsv($line, ',', '"', '');
        }

        return $rows;
    }
}
