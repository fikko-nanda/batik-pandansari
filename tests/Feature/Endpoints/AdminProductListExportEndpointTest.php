<?php

namespace Tests\Feature\Endpoints;

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/**
 * Daftar & ekspor produk admin:
 * GET /api/admin/products (filter status/sort/order + varian),
 * GET /api/admin/products/export (CSV).
 */
class AdminProductListExportEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Katalog Admin (Daftar & Ekspor)';

    public function test_filter_status_pencarian_dan_kategori(): void
    {
        $this->seedCategory();
        $aktif = $this->makeProduct('Batik Parang', 'aktif');
        $draft = $this->makeProduct('Batik Mega', 'draft');
        $this->makeProduct('Kain Lurik', 'nonaktif');

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/products?status=draft', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Filter produk berstatus draft',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame($draft->id_product, $data[0]['id_product'] ?? null);
        $this->assertSame(1, $this->recorded[0]['response']['meta']['total'] ?? null);

        $this->hit('GET', '/api/admin/products?status=aktif', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Filter produk berstatus aktif',
            'expected' => 200,
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame($aktif->id_product, $data[0]['id_product'] ?? null);

        $this->hit('GET', '/api/admin/products?search=Lurik', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Cari produk berdasarkan nama',
            'expected' => 200,
        ]);

        $this->assertSame('Kain Lurik', $this->recorded[2]['response']['data'][0]['nama_produk'] ?? null);

        $this->hit('GET', '/api/admin/products?category=cat-1&status=nonaktif', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Filter kategori + status sekaligus',
            'expected' => 200,
        ]);

        $this->assertSame('Kain Lurik', $this->recorded[3]['response']['data'][0]['nama_produk'] ?? null);

        $this->hit('GET', '/api/admin/products?status=ngawur', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Filter status di luar enum (validasi admin)',
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('status', $this->recorded[4]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    public function test_sorting_produk(): void
    {
        $this->seedCategory();
        $this->makeProduct('Cempaka', 'aktif');
        $this->makeProduct('Anggrek', 'aktif');
        $this->makeProduct('Bunga', 'aktif');

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/products?sort=nama_produk&order=asc', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Urutkan nama produk A-Z',
            'expected' => 200,
        ]);

        $this->assertSame(
            ['Anggrek', 'Bunga', 'Cempaka'],
            array_column($this->recorded[0]['response']['data'] ?? [], 'nama_produk')
        );

        $this->hit('GET', '/api/admin/products?sort=nama_produk&order=desc', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Urutkan nama produk Z-A',
            'expected' => 200,
        ]);

        $this->assertSame(
            ['Cempaka', 'Bunga', 'Anggrek'],
            array_column($this->recorded[1]['response']['data'] ?? [], 'nama_produk')
        );

        $this->hit('GET', '/api/admin/products?sort=harga', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Kolom sort tidak diizinkan',
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('sort', $this->recorded[2]['response']['errors'] ?? []);

        $this->hit('GET', '/api/admin/products?sort=nama_produk&order=naik', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Arah urutan tidak valid',
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('order', $this->recorded[3]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    public function test_varian_hanya_ikut_di_jalur_admin(): void
    {
        $this->seedCatalog();
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/products', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Daftar produk admin menyertakan varian',
            'expected' => 200,
        ]);

        $adminItem = $this->recorded[0]['response']['data'][0] ?? [];
        $this->assertArrayHasKey('variants', $adminItem);
        $this->assertCount(1, $adminItem['variants']);

        $this->asGuest();
        $this->hit('GET', '/api/products', [
            'endpoint' => 'GET /api/products',
            'label' => 'Daftar produk publik tanpa relasi varian',
            'expected' => 200,
        ]);

        $publicItem = $this->recorded[1]['response']['data'][0] ?? [];
        $this->assertArrayNotHasKey('variants', $publicItem, 'Payload publik tidak boleh berubah.');

        $this->hit('GET', '/api/products?status=draft&sort=harga&order=naik', [
            'endpoint' => 'GET /api/products',
            'label' => 'Parameter khusus admin diabaikan di jalur publik',
            'expected' => 200,
        ]);

        $this->assertCount(1, $this->recorded[2]['response']['data'] ?? [], 'Publik tetap hanya menampilkan produk aktif.');
        $this->note('Filter status/sort/order hanya divalidasi & diterapkan pada jalur admin; GET /api/products publik tidak berubah.');

        $this->assertRecorded();
    }

    public function test_ekspor_produk_csv(): void
    {
        $this->seedCategory();

        $parang = $this->makeProduct('Batik Parang', 'aktif');
        $this->makeVariant($parang, 10, 'aktif', 150000);
        $this->makeVariant($parang, 4, 'aktif', 175000);

        $mega = $this->makeProduct('Batik Mega', 'draft');
        $this->makeVariant($mega, 5, 'aktif', 200000);

        $this->actingAsRole('admin');

        $response = $this->hit('GET', '/api/admin/products/export', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Ekspor seluruh produk sebagai CSV',
            'expected' => 200,
        ]);

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertMatchesRegularExpression(
            '/produk-\d{4}-\d{2}-\d{2}\.csv/',
            (string) $response->headers->get('content-disposition')
        );

        $rows = $this->csvRows($response);
        $this->assertSame(
            [
                'id_product',
                'nama_produk',
                'slug',
                'kategori',
                'status_produk',
                'harga_min',
                'harga_maks',
                'stok_total',
                'jumlah_varian',
                'dibuat_pada',
            ],
            $rows[0]
        );
        $this->assertCount(3, $rows, 'Header + 2 produk (tanpa paginasi).');

        $byName = [];
        foreach (array_slice($rows, 1) as $row) {
            $byName[$row[1]] = $row;
        }

        $this->assertArrayHasKey('Batik Parang', $byName);
        $this->assertArrayHasKey('Batik Mega', $byName);

        $this->assertSame('aktif', $byName['Batik Parang'][4]);
        $this->assertSame('Batik Tulis', $byName['Batik Parang'][3]);
        $this->assertSame(150000.0, (float) $byName['Batik Parang'][5]);
        $this->assertSame(175000.0, (float) $byName['Batik Parang'][6]);
        $this->assertSame(14, (int) $byName['Batik Parang'][7]);
        $this->assertSame(2, (int) $byName['Batik Parang'][8]);

        $this->assertSame('draft', $byName['Batik Mega'][4]);
        $this->assertSame(1, (int) $byName['Batik Mega'][8]);

        $all = $this->hit('GET', '/api/admin/products/export?per_page=1', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Parameter paginasi diabaikan saat ekspor',
            'expected' => 200,
        ]);

        $this->assertCount(3, $this->csvRows($all));
        $this->note('Ekspor memakai chunk(200) tanpa paginasi; parameter page/per_page tidak memengaruhi isi CSV.');

        $draftOnly = $this->hit('GET', '/api/admin/products/export?status=draft', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Ekspor produk berstatus draft saja',
            'expected' => 200,
        ]);

        $draftRows = $this->csvRows($draftOnly);
        $this->assertCount(2, $draftRows);
        $this->assertSame('Batik Mega', $draftRows[1][1]);

        $search = $this->hit('GET', '/api/admin/products/export?search=Parang', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Ekspor hasil pencarian nama produk',
            'expected' => 200,
        ]);

        $searchRows = $this->csvRows($search);
        $this->assertCount(2, $searchRows);
        $this->assertSame('Batik Parang', $searchRows[1][1]);

        DB::table('products')->where('id_product', $parang->id_product)
            ->update(['dibuat_pada' => '2026-01-02 23:59:59']);
        DB::table('products')->where('id_product', $mega->id_product)
            ->update(['dibuat_pada' => '2026-01-03 00:00:00']);

        $dateRange = $this->hit('GET', '/api/admin/products/export?start_date=2026-01-02&end_date=2026-01-02', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Filter tanggal produk dengan batas akhir inklusif',
            'expected' => 200,
        ]);
        $dateRows = $this->csvRows($dateRange);
        $this->assertCount(2, $dateRows);
        $this->assertSame('Batik Parang', $dateRows[1][1]);

        $invalidDates = $this->hit('GET', '/api/admin/products/export?start_date=2026-01-04&end_date=2026-01-03', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Rentang tanggal produk yang terbalik ditolak',
            'expected' => 422,
        ]);
        $this->assertSame(422, $invalidDates->status());

        $this->assertRecorded();
    }

    public function test_ekspor_produk_akses_non_admin(): void
    {
        $this->seedCatalog();

        $this->asGuest();
        $this->hit('GET', '/api/admin/products/export', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Ekspor produk tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/products/export', [
            'endpoint' => 'GET /api/admin/products/export',
            'label' => 'Ekspor produk oleh pelanggan',
            'expected' => 403,
        ]);

        $this->hit('GET', '/api/admin/products', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Daftar produk admin oleh pelanggan',
            'expected' => 403,
        ]);

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
