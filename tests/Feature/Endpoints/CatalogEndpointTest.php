<?php

namespace Tests\Feature\Endpoints;

/**
 * Endpoint publik katalog: /api/categories, /api/products,
 * /api/products/{id}/variants, /api/products/{id}/images
 */
class CatalogEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Katalog Publik';

    // ------------------------------------------------------------------
    // Kategori
    // ------------------------------------------------------------------

    public function test_kategori_index(): void
    {
        $this->seedCategory('cat-1', 'Batik Tulis');
        $this->seedCategory('cat-2', 'Batik Cap');

        $this->hit('GET', '/api/categories', [
            'endpoint' => 'GET /api/categories',
            'label' => 'Daftar semua kategori',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(2, $data);
        $this->assertSame('Batik Cap', $data[0]['nama_kategori'] ?? null, 'Kategori tidak terurut berdasarkan nama.');

        $this->assertRecorded();
    }

    public function test_kategori_show(): void
    {
        $this->seedCategory('cat-1', 'Batik Tulis');

        $this->hit('GET', '/api/categories/cat-1', [
            'endpoint' => 'GET /api/categories/{id}',
            'label' => 'Detail kategori yang ada',
            'expected' => 200,
        ]);

        $this->assertSame('Batik Tulis', $this->recorded[0]['response']['data']['nama_kategori'] ?? null);

        $this->hit('GET', '/api/categories/tidak-ada', [
            'endpoint' => 'GET /api/categories/{id}',
            'label' => 'Detail kategori tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertFalse($this->recorded[1]['response']['success'] ?? true);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Produk (publik)
    // ------------------------------------------------------------------

    public function test_produk_index_hanya_menampilkan_stok_aktif(): void
    {
        $this->seedCategory();
        $activeProduct = $this->makeProduct('Batik Aktif A', 'aktif');
        $this->makeVariant($activeProduct, 5);
        $this->makeProduct('Batik Draft B', 'draft');
        $this->makeProduct('Batik Nonaktif C', 'nonaktif');

        $this->hit('GET', '/api/products', [
            'endpoint' => 'GET /api/products',
            'label' => 'Daftar produk publik (hanya status aktif)',
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'];
        $this->assertCount(1, $response['data'] ?? []);
        $this->assertSame('aktif', $response['data'][0]['status_produk'] ?? null);
        $this->assertSame(1, $response['meta']['total'] ?? null);
        $this->assertSame(10, $response['meta']['per_page'] ?? null);
        $this->assertNotEmpty($response['data'][0]['category'] ?? null, 'Relasi kategori tidak ikut dimuat.');

        $this->assertRecorded();
    }

    public function test_produk_index_pencarian_dan_paginasi(): void
    {
        $this->seedCategory();

        for ($i = 1; $i <= 12; $i++) {
            $product = $this->makeProduct("Batik Serimpi {$i}", 'aktif');
            $this->makeVariant($product, 5);
        }
        $this->makeProduct('Kain Tenun Polos', 'aktif');

        $this->hit('GET', '/api/products?search=Serimpi&per_page=5&page=2', [
            'endpoint' => 'GET /api/products',
            'label' => 'Daftar produk dengan pencarian & paginasi',
            'expected' => 200,
        ]);

        $response = $this->recorded[0]['response'];
        $this->assertSame(5, count($response['data'] ?? []));
        $this->assertSame(12, $response['meta']['total'] ?? null);
        $this->assertSame(2, $response['meta']['current_page'] ?? null);
        $this->assertSame(3, $response['meta']['last_page'] ?? null);

        $this->assertRecorded();
    }

    public function test_produk_index_filter_kategori(): void
    {
        $this->seedCategory('cat-1', 'Batik Tulis');
        $this->seedCategory('cat-2', 'Batik Cap');
        $productTulis = $this->makeProduct('Dari Tulis', 'aktif', 'cat-1');
        $this->makeVariant($productTulis, 5);
        $productCap = $this->makeProduct('Dari Cap', 'aktif', 'cat-2');
        $this->makeVariant($productCap, 5);

        $this->hit('GET', '/api/products?category=cat-2', [
            'endpoint' => 'GET /api/products',
            'label' => 'Daftar produk difilter kategori',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame('Dari Cap', $data[0]['nama_produk'] ?? null);

        $this->assertRecorded();
    }

    public function test_produk_index_validasi_query(): void
    {
        $this->seedCategory();

        $this->hit('GET', '/api/products?per_page=0', [
            'endpoint' => 'GET /api/products',
            'label' => 'Daftar produk dengan per_page=0',
            'expected' => 422,
        ]);

        $this->hit('GET', '/api/products?search='.str_repeat('a', 151), [
            'endpoint' => 'GET /api/products',
            'label' => 'Daftar produk dengan search > 150 karakter',
            'expected' => 422,
        ]);

        $this->hit('GET', '/api/products?page=abc', [
            'endpoint' => 'GET /api/products',
            'label' => 'Daftar produk dengan page=abc',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_produk_show(): void
    {
        $catalog = $this->seedCatalog();
        $this->makeVariant($catalog['product'], 5, 'nonaktif');

        $this->hit('GET', '/api/products/'.$catalog['product']->id_product, [
            'endpoint' => 'GET /api/products/{id}',
            'label' => 'Detail produk aktif (varian nonaktif disembunyikan)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('Batik Parang Seling', $data['nama_produk'] ?? null);
        $this->assertCount(1, $data['variants'] ?? [], 'Hanya varian aktif yang boleh tampil.');
        $this->assertCount(1, $data['images'] ?? []);

        $this->hit('GET', '/api/products/'.$this->makeProduct('Produk Draft', 'draft')->id_product, [
            'endpoint' => 'GET /api/products/{id}',
            'label' => 'Detail produk draft (tidak boleh tampil publik)',
            'expected' => 404,
        ]);

        $this->hit('GET', '/api/products/tidak-ada', [
            'endpoint' => 'GET /api/products/{id}',
            'label' => 'Detail produk tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_varian_produk_publik(): void
    {
        $catalog = $this->seedCatalog();
        $this->makeVariant($catalog['product'], 3, 'nonaktif');
        $draft = $this->makeProduct('Produk Draft', 'draft');

        $this->hit('GET', '/api/products/'.$catalog['product']->id_product.'/variants', [
            'endpoint' => 'GET /api/products/{id}/variants',
            'label' => 'Daftar varian produk aktif',
            'expected' => 200,
        ]);

        $this->assertCount(1, $this->recorded[0]['response']['data'] ?? []);

        $this->hit('GET', '/api/products/'.$draft->id_product.'/variants', [
            'endpoint' => 'GET /api/products/{id}/variants',
            'label' => 'Daftar varian produk draft',
            'expected' => 404,
        ]);

        $this->hit('GET', '/api/products/tidak-ada/variants', [
            'endpoint' => 'GET /api/products/{id}/variants',
            'label' => 'Daftar varian produk tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_gambar_produk_publik(): void
    {
        $catalog = $this->seedCatalog();
        $draft = $this->makeProduct('Produk Draft', 'draft');

        $this->hit('GET', '/api/products/'.$catalog['product']->id_product.'/images', [
            'endpoint' => 'GET /api/products/{id}/images',
            'label' => 'Daftar gambar produk aktif',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertNotEmpty($data[0]['url'] ?? null, 'Field url publik tidak dibentuk.');

        $this->hit('GET', '/api/products/'.$draft->id_product.'/images', [
            'endpoint' => 'GET /api/products/{id}/images',
            'label' => 'Daftar gambar produk draft',
            'expected' => 404,
        ]);

        $this->hit('GET', '/api/products/tidak-ada/images', [
            'endpoint' => 'GET /api/products/{id}/images',
            'label' => 'Daftar gambar produk tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }
}
