<?php

namespace Tests\Feature\Endpoints;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint tulis katalog (admin): /api/admin/products, /api/products (POST/PUT/DELETE),
 * /api/products/{id}/variants, /api/variants/{id}, /api/products/{id}/images, /api/images/{id}
 */
class AdminProductEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Katalog Admin (Tulis)';

    // ------------------------------------------------------------------
    // Admin index & show
    // ------------------------------------------------------------------

    public function test_admin_produk_index_akses(): void
    {
        $this->seedCategory();
        $this->makeProduct('Produk Aktif', 'aktif');
        $this->makeProduct('Produk Draft', 'draft');

        $this->asGuest();
        $this->hit('GET', '/api/admin/products', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Daftar produk admin tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/products', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Daftar produk admin dengan peran pelanggan',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/admin/products?per_page=5', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Daftar produk admin (semua status)',
            'expected' => 200,
        ]);

        $response = $this->recorded[2]['response'];
        $this->assertCount(2, $response['data'] ?? []);
        $this->assertSame(5, $response['meta']['per_page'] ?? null);

        $this->assertRecorded();
    }

    public function test_admin_produk_index_validasi(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/products?per_page=101', [
            'endpoint' => 'GET /api/admin/products',
            'label' => 'Daftar produk admin per_page=101 (melebihi batas)',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_produk_show(): void
    {
        $draft = $this->makeProduct('Produk Draft', 'draft');

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/products/'.$draft->id_product, [
            'endpoint' => 'GET /api/admin/products/{id}',
            'label' => 'Detail produk draft oleh pelanggan',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/admin/products/'.$draft->id_product, [
            'endpoint' => 'GET /api/admin/products/{id}',
            'label' => 'Detail produk draft oleh admin',
            'expected' => 200,
        ]);

        $this->hit('GET', '/api/admin/products/tidak-ada', [
            'endpoint' => 'GET /api/admin/products/{id}',
            'label' => 'Detail produk tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Produk CRUD
    // ------------------------------------------------------------------

    public function test_admin_membuat_produk(): void
    {
        $this->seedCategory();
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products', [
            'endpoint' => 'POST /api/products',
            'label' => 'Buat produk baru (slug dibuat otomatis)',
            'json' => [
                'id_category' => 'cat-1',
                'nama_produk' => 'Batik Sogan Pekalongan',
                'deskripsi' => 'Batik sogan khas Pekalongan',
                'status_produk' => 'aktif',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('batik-sogan-pekalongan', $data['slug'] ?? null);
        $this->assertSame('aktif', $data['status_produk'] ?? null);
        $this->assertNotEmpty($data['category'] ?? null);
        $this->assertSame(['products' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_admin_membuat_produk_status_default_draft(): void
    {
        $this->seedCategory();
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products', [
            'endpoint' => 'POST /api/products',
            'label' => 'Buat produk tanpa status (default draft)',
            'json' => [
                'id_category' => 'cat-1',
                'nama_produk' => 'Batik Mangrove',
            ],
            'expected' => 201,
        ]);

        $this->assertSame('draft', $this->recorded[0]['response']['data']['status_produk'] ?? null);

        $this->assertRecorded();
    }

    public function test_admin_membuat_produk_validasi(): void
    {
        $this->seedCategory();
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products', [
            'endpoint' => 'POST /api/products',
            'label' => 'Buat produk tanpa nama & kategori',
            'json' => [],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products', [
            'endpoint' => 'POST /api/products',
            'label' => 'Buat produk dengan kategori tidak ada',
            'json' => ['id_category' => 'cat-tidak-ada', 'nama_produk' => 'Produk Ajaib'],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products', [
            'endpoint' => 'POST /api/products',
            'label' => 'Buat produk dengan status tidak valid',
            'json' => [
                'id_category' => 'cat-1',
                'nama_produk' => 'Produk Aneh',
                'status_produk' => 'laris',
            ],
            'expected' => 422,
        ]);

        $this->assertSame([], $this->recorded[2]['db_effect'], 'Produk tidak boleh tersimpan saat validasi gagal.');

        $this->assertRecorded();
    }

    public function test_admin_membuat_produk_slug_duplikat(): void
    {
        $this->seedCategory();
        $existing = $this->makeProduct('Batik Sogan', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products', [
            'endpoint' => 'POST /api/products',
            'label' => 'Buat produk dengan slug milik produk lain',
            'json' => [
                'id_category' => 'cat-1',
                'nama_produk' => 'Produk Baru',
                'slug' => $existing->slug,
            ],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('slug', $this->recorded[0]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    public function test_produk_tulis_akses_non_admin(): void
    {
        $product = $this->makeProduct('Produk Target', 'aktif');

        $this->asGuest();
        $this->hit('POST', '/api/products', [
            'endpoint' => 'POST /api/products',
            'label' => 'Buat produk tanpa token',
            'json' => ['id_category' => 'cat-1', 'nama_produk' => 'X'],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('PUT', '/api/products/'.$product->id_product, [
            'endpoint' => 'PUT /api/products/{id}',
            'label' => 'Perbarui produk oleh pelanggan',
            'json' => ['nama_produk' => 'Diubah Penyusup'],
            'expected' => 403,
        ]);

        $this->hit('DELETE', '/api/products/'.$product->id_product, [
            'endpoint' => 'DELETE /api/products/{id}',
            'label' => 'Hapus produk oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertSame('Produk Target', $product->fresh()->nama_produk);

        $this->assertRecorded();
    }

    public function test_admin_memperbarui_produk(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Produk Lama', 'draft');
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/products/'.$product->id_product, [
            'endpoint' => 'PUT /api/products/{id}',
            'label' => 'Perbarui nama, status & slug produk',
            'json' => [
                'nama_produk' => 'Batik Jlamprang Baru',
                'slug' => 'BATIK Jlamprang Baru!!',
                'status_produk' => 'aktif',
            ],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('Batik Jlamprang Baru', $data['nama_produk'] ?? null);
        $this->assertSame('batik-jlamprang-baru', $data['slug'] ?? null, 'Slug dinormalisasi dengan Str::slug.');
        $this->assertSame('aktif', $data['status_produk'] ?? null);

        $this->assertRecorded();
    }

    public function test_admin_memperbarui_produk_validasi_dan_404(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Produk Pertama', 'aktif');
        $other = $this->makeProduct('Produk Kedua', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/products/tidak-ada', [
            'endpoint' => 'PUT /api/products/{id}',
            'label' => 'Perbarui produk tidak ditemukan',
            'json' => ['nama_produk' => 'Baru'],
            'expected' => 404,
        ]);

        $this->hit('PUT', '/api/products/'.$product->id_product, [
            'endpoint' => 'PUT /api/products/{id}',
            'label' => 'Perbarui dengan status tidak valid',
            'json' => ['status_produk' => 'gratis'],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/products/'.$product->id_product, [
            'endpoint' => 'PUT /api/products/{id}',
            'label' => 'Perbarui slug menjadi milik produk lain',
            'json' => ['slug' => $other->slug],
            'expected' => 422,
        ]);

        $this->assertSame('Produk Pertama', $product->fresh()->nama_produk);

        $this->assertRecorded();
    }

    public function test_admin_menonaktifkan_produk(): void
    {
        $product = $this->makeProduct('Produk Dihapus', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('DELETE', '/api/products/'.$product->id_product, [
            'endpoint' => 'DELETE /api/products/{id}',
            'label' => 'Nonaktifkan produk (soft delete status)',
            'expected' => 200,
        ]);

        $this->assertSame('nonaktif', $product->fresh()->status_produk);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Delete tidak menghapus baris; hanya menonaktifkan.');
        $this->note('Produk tetap tersimpan dengan status_produk=nonaktif (bukan penghapusan fisik).');

        $this->hit('DELETE', '/api/products/tidak-ada', [
            'endpoint' => 'DELETE /api/products/{id}',
            'label' => 'Nonaktifkan produk tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Varian
    // ------------------------------------------------------------------

    public function test_admin_membuat_varian(): void
    {
        $product = $this->makeProduct('Produk Dengan Varian', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products/'.$product->id_product.'/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Buat varian produk',
            'json' => [
                'sku' => 'SKU-BARU-001',
                'nama_varian' => 'Sogan XL',
                'warna' => 'Sogan',
                'ukuran' => 'XL',
                'harga' => 250000,
                'stok' => 7,
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('SKU-BARU-001', $data['sku'] ?? null);
        $this->assertSame(7, $data['stok'] ?? null);
        $this->assertSame('aktif', $data['status_varian'] ?? null);
        $this->assertSame(['product_variants' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_admin_membuat_varian_validasi(): void
    {
        $product = $this->makeProduct('Produk Validasi Varian', 'aktif');
        $duplicateSku = $this->makeVariant($this->makeProduct('Produk Lain', 'aktif'))->sku;
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products/'.$product->id_product.'/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Buat varian tanpa SKU & harga',
            'json' => ['nama_varian' => 'Tanpa Data'],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products/'.$product->id_product.'/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Buat varian dengan harga negatif',
            'json' => ['sku' => 'SKU-NEGATIF', 'harga' => -1000],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products/'.$product->id_product.'/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Buat varian dengan SKU duplikat',
            'json' => ['sku' => $duplicateSku, 'harga' => 100000],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products/tidak-ada/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Buat varian pada produk tidak ditemukan',
            'json' => ['sku' => 'SKU-404', 'harga' => 100000],
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_memperbarui_varian(): void
    {
        $variant = $this->makeVariant($this->makeProduct('Produk Update Varian', 'aktif'));
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/variants/'.$variant->id_variant, [
            'endpoint' => 'PUT /api/variants/{id}',
            'label' => 'Perbarui harga, stok & status varian',
            'json' => ['harga' => 175000, 'stok' => 20, 'status_varian' => 'nonaktif'],
            'expected' => 200,
        ]);

        $fresh = $variant->fresh();
        $this->assertSame(175000.0, (float) $fresh->harga);
        $this->assertSame(20, $fresh->stok);
        $this->assertSame('nonaktif', $fresh->status_varian);

        $this->hit('PUT', '/api/variants/tidak-ada', [
            'endpoint' => 'PUT /api/variants/{id}',
            'label' => 'Perbarui varian tidak ditemukan',
            'json' => ['harga' => 1000],
            'expected' => 404,
        ]);

        $this->hit('PUT', '/api/variants/'.$variant->id_variant, [
            'endpoint' => 'PUT /api/variants/{id}',
            'label' => 'Perbarui varian dengan status tidak valid',
            'json' => ['status_varian' => 'habis'],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_menonaktifkan_varian(): void
    {
        $variant = $this->makeVariant($this->makeProduct('Produk Hapus Varian', 'aktif'));
        $this->actingAsRole('admin');

        $this->hit('DELETE', '/api/variants/'.$variant->id_variant, [
            'endpoint' => 'DELETE /api/variants/{id}',
            'label' => 'Nonaktifkan varian',
            'expected' => 200,
        ]);

        $this->assertSame('nonaktif', $variant->fresh()->status_varian);
        $this->note('Delete varian bersifat nonaktif, baris tetap ada.');

        $this->hit('DELETE', '/api/variants/tidak-ada', [
            'endpoint' => 'DELETE /api/variants/{id}',
            'label' => 'Nonaktifkan varian tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_varian_akses_non_admin(): void
    {
        $product = $this->makeProduct('Produk Proteksi Varian', 'aktif');
        $variant = $this->makeVariant($product);

        $this->asGuest();
        $this->hit('POST', '/api/products/'.$product->id_product.'/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Buat varian tanpa token',
            'json' => ['sku' => 'SKU-TANPA-LOGIN', 'harga' => 1000],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('PUT', '/api/variants/'.$variant->id_variant, [
            'endpoint' => 'PUT /api/variants/{id}',
            'label' => 'Perbarui varian oleh pelanggan',
            'json' => ['harga' => 1],
            'expected' => 403,
        ]);

        $this->hit('DELETE', '/api/variants/'.$variant->id_variant, [
            'endpoint' => 'DELETE /api/variants/{id}',
            'label' => 'Nonaktifkan varian oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Gambar
    // ------------------------------------------------------------------

    public function test_admin_mengunggah_gambar(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Dengan Gambar', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/products/{id}/images',
            'label' => 'Unggah gambar produk pertama (jadi utama otomatis)',
            'json' => [
                'alt_text' => 'Gambar produk',
            ],
            'files' => ['gambar' => UploadedFile::fake()->image('produk.png', 120, 120)],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertTrue((bool) ($data['is_primary'] ?? false), 'Gambar pertama seharusnya menjadi utama.');
        $this->assertNotEmpty($data['url'] ?? null);
        $this->assertSame(['product_images' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);
        Storage::disk('public')->assertExists($data['path_gambar'] ?? '-');

        $this->assertRecorded();
    }

    public function test_admin_mengunggah_gambar_validasi(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Validasi Gambar', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/products/{id}/images',
            'label' => 'Unggah tanpa berkas gambar',
            'json' => ['alt_text' => 'Tanpa berkas'],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/products/{id}/images',
            'label' => 'Unggah berkas berekstensi .txt',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->create('catatan.txt', 10, 'text/plain')],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/products/{id}/images',
            'label' => 'Unggah berkas terlalu besar (> 2 MB)',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->create('besar.png', 3000, 'image/png')],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/products/tidak-ada/images', [
            'endpoint' => 'POST /api/products/{id}/images',
            'label' => 'Unggah gambar pada produk tidak ditemukan',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('x.png', 10, 10)],
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_menghapus_gambar(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Hapus Gambar', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/products/{id}/images',
            'label' => 'Setup: unggah gambar yang akan dihapus',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('hapus.png', 50, 50)],
            'expected' => 201,
        ]);

        $imageId = $this->recorded[0]['response']['data']['id_image'] ?? null;
        $path = $this->recorded[0]['response']['data']['path_gambar'] ?? null;
        $this->assertNotEmpty($imageId);
        Storage::disk('public')->assertExists($path);

        $this->hit('DELETE', '/api/images/'.$imageId, [
            'endpoint' => 'DELETE /api/images/{id}',
            'label' => 'Hapus gambar produk (baris & berkas ikut terhapus)',
            'expected' => 200,
        ]);

        $this->assertSame(['product_images' => '1 -> 0 (-1)'], $this->recorded[1]['db_effect']);
        Storage::disk('public')->assertMissing($path);

        $this->hit('DELETE', '/api/images/tidak-ada', [
            'endpoint' => 'DELETE /api/images/{id}',
            'label' => 'Hapus gambar tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_gambar_akses_non_admin(): void
    {
        $product = $this->makeProduct('Produk Proteksi Gambar', 'aktif');

        $this->asGuest();
        $this->hit('POST', '/api/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/products/{id}/images',
            'label' => 'Unggah gambar tanpa token',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('x.png', 10, 10)],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('DELETE', '/api/images/'.(string) \Illuminate\Support\Str::uuid(), [
            'endpoint' => 'DELETE /api/images/{id}',
            'label' => 'Hapus gambar oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }
}
