<?php

namespace Tests\Feature\Endpoints;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint gambar admin: /api/admin/products/{id}/images (unggah & daftar),
 * /api/admin/products/{id}/images/{imageId} (hapus), size chart, dan batas
 * jumlah gambar per produk.
 */
class AdminProductImageEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Katalog Admin (Gambar)';

    public function test_maksimal_lima_gambar_per_produk(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Lima Gambar', 'aktif');
        $this->actingAsRole('admin');

        for ($i = 1; $i <= 5; $i++) {
            $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
                'endpoint' => 'POST /api/admin/products/{id}/images',
                'label' => 'Unggah gambar ke-'.$i,
                'json' => ['alt_text' => 'Gambar '.$i],
                'files' => ['gambar' => UploadedFile::fake()->image('gambar'.$i.'.png', 60, 60)],
                'expected' => 201,
            ]);
        }

        $this->assertSame(['product_images' => '4 -> 5 (+1)'], $this->recorded[4]['db_effect']);

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Unggah gambar ke-6 (ditolak karena maksimal 5)',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('gambar6.png', 60, 60)],
            'expected' => 422,
        ]);

        $this->assertStringContainsString('5 gambar', $this->recorded[5]['response']['message'] ?? '');
        $this->assertSame([], $this->recorded[5]['db_effect'], 'Gambar ke-6 tidak tersimpan.');

        $this->assertRecorded();
    }

    public function test_size_chart_tidak_boleh_tertempel_varian(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Size Chart Varian', 'aktif');
        $varian = $this->makeVariant($product);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Size chart dengan id_variant (ditolak)',
            'json' => ['type' => 'size_chart', 'id_variant' => $varian->id_variant],
            'files' => ['gambar' => UploadedFile::fake()->image('sizechart.png', 60, 60)],
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['id_variant'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_size_chart_disimpan_tanpa_varian(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Size Chart', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Unggah size chart',
            'json' => ['type' => 'size_chart', 'alt_text' => 'Tabel ukuran'],
            'files' => ['gambar' => UploadedFile::fake()->image('sizechart.png', 60, 60)],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('size_chart', $data['type'] ?? null);
        $this->assertArrayHasKey('id_variant', $data, 'Field id_variant tetap ada di response.');
        $this->assertNull($data['id_variant'], 'Size chart tidak menempel pada varian.');
        $this->assertFalse((bool) ($data['is_primary'] ?? true), 'Size chart tidak pernah menjadi gambar utama.');
        $this->assertNotEmpty($data['url'] ?? null);

        $this->assertRecorded();
    }

    public function test_size_chart_kedua_ditolak(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Size Chart Ganda', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah size chart pertama',
            'json' => ['type' => 'size_chart'],
            'files' => ['gambar' => UploadedFile::fake()->image('sizechart1.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Unggah size chart kedua (ditolak)',
            'json' => ['type' => 'size_chart'],
            'files' => ['gambar' => UploadedFile::fake()->image('sizechart2.png', 60, 60)],
            'expected' => 422,
        ]);

        $this->assertSame([], $this->recorded[1]['db_effect']);

        $this->assertRecorded();
    }

    public function test_gambar_dengan_varian_milik_produk_lain_ditolak(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Gambar Varian', 'aktif');
        $lain = $this->makeProduct('Produk Varian Asing', 'aktif');
        $varianLain = $this->makeVariant($lain);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Gambar memakai varian produk lain (ditolak)',
            'json' => ['id_variant' => $varianLain->id_variant],
            'files' => ['gambar' => UploadedFile::fake()->image('gambar.png', 60, 60)],
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['id_variant'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_gambar_dengan_varian_milik_produk_sama_berhasil(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Gambar Varian Sendiri', 'aktif');
        $varian = $this->makeVariant($product);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Gambar menempel pada varian produk yang sama',
            'json' => ['id_variant' => $varian->id_variant],
            'files' => ['gambar' => UploadedFile::fake()->image('gambar.png', 60, 60)],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($varian->id_variant, $data['id_variant'] ?? null);
        $this->assertSame('produk', $data['type'] ?? null);

        $this->assertRecorded();
    }

    public function test_daftar_gambar_dapat_difilter_lewat_type(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Filter Gambar', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah foto produk',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('foto.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah size chart',
            'json' => ['type' => 'size_chart'],
            'files' => ['gambar' => UploadedFile::fake()->image('sizechart.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->hit('GET', '/api/products/'.$product->id_product.'/images?type=size_chart', [
            'endpoint' => 'GET /api/products/{id}/images',
            'label' => 'Daftar gambar difilter size chart',
            'expected' => 200,
        ]);

        $data = $this->recorded[2]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame('size_chart', $data[0]['type'] ?? null);

        $this->hit('GET', '/api/products/'.$product->id_product.'/images?type=produk', [
            'endpoint' => 'GET /api/products/{id}/images',
            'label' => 'Daftar gambar difilter foto produk',
            'expected' => 200,
        ]);

        $data = $this->recorded[3]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame('produk', $data[0]['type'] ?? null);

        $this->assertRecorded();
    }

    public function test_size_chart_tidak_muncul_di_detail_produk_publik(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Detail Size Chart', 'aktif');
        $this->makeVariant($product, 5);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah foto produk',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('foto.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah size chart',
            'json' => ['type' => 'size_chart'],
            'files' => ['gambar' => UploadedFile::fake()->image('sizechart.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->hit('GET', '/api/products/'.$product->id_product, [
            'endpoint' => 'GET /api/products/{id}',
            'label' => 'Detail produk publik',
            'expected' => 200,
        ]);

        $data = $this->recorded[2]['response']['data'] ?? [];
        $this->assertCount(1, $data['images'] ?? [], 'Size chart tidak ikut pada galeri produk publik.');
        $this->assertSame('produk', $data['images'][0]['type'] ?? null);
        $this->assertSame('size_chart', $data['size_chart']['type'] ?? null, 'Size chart tersedia pada field tersendiri.');
        $this->assertNotEmpty($data['images'][0]['url'] ?? null, 'Gambar menyertakan url publik.');

        $this->assertRecorded();
    }

    public function test_hapus_gambar_scoped_ke_produk(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Hapus Scoped', 'aktif');
        $produkLain = $this->makeProduct('Produk Gambar Asing', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah gambar utama produk',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('utama.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah gambar kedua',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('kedua.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->hit('POST', '/api/admin/products/'.$produkLain->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Setup: unggah gambar produk lain',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('asing.png', 60, 60)],
            'expected' => 201,
        ]);

        $imageUtama = $this->recorded[0]['response']['data'] ?? [];
        $imageKedua = $this->recorded[1]['response']['data'] ?? [];
        $imageAsing = $this->recorded[2]['response']['data'] ?? [];

        $this->assertTrue((bool) ($imageUtama['is_primary'] ?? false));

        // Gambar milik produk lain tidak boleh dihapus lewat path produk ini.
        $this->hit('DELETE', '/api/admin/products/'.$product->id_product.'/images/'.$imageAsing['id_image'], [
            'endpoint' => 'DELETE /api/admin/products/{id}/images/{imageId}',
            'label' => 'Hapus gambar milik produk lain (ditolak)',
            'expected' => 404,
        ]);

        $this->assertSame([], $this->recorded[3]['db_effect'], 'Gambar produk lain tidak ikut terhapus.');
        Storage::disk('public')->assertExists($imageAsing['path_gambar']);

        // Hapus gambar utama produk ini: gambar kedua otomatis jadi utama.
        $this->hit('DELETE', '/api/admin/products/'.$product->id_product.'/images/'.$imageUtama['id_image'], [
            'endpoint' => 'DELETE /api/admin/products/{id}/images/{imageId}',
            'label' => 'Hapus gambar utama (scoped ke produk)',
            'expected' => 200,
        ]);

        Storage::disk('public')->assertMissing($imageUtama['path_gambar']);
        $this->assertSame(['product_images' => '3 -> 2 (-1)'], $this->recorded[4]['db_effect']);

        $this->hit('GET', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'GET /api/admin/products/{id}/images',
            'label' => 'Verifikasi gambar utama baru',
            'expected' => 200,
        ]);

        $data = $this->recorded[5]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame($imageKedua['id_image'], $data[0]['id_image'] ?? null);
        $this->assertTrue((bool) ($data[0]['is_primary'] ?? false), 'Gambar utama berikutnya dipromosikan.');

        $this->assertRecorded();
    }

    public function test_hapus_gambar_produk_tidak_ada(): void
    {
        $this->actingAsRole('admin');

        $this->hit('DELETE', '/api/admin/products/tidak-ada/images/apa-saja', [
            'endpoint' => 'DELETE /api/admin/products/{id}/images/{imageId}',
            'label' => 'Hapus gambar pada produk tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_akses_gambar_admin(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct('Produk Akses Gambar', 'aktif');
        $this->asGuest();

        $this->hit('GET', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'GET /api/admin/products/{id}/images',
            'label' => 'Daftar gambar admin tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'GET /api/admin/products/{id}/images',
            'label' => 'Daftar gambar admin oleh pelanggan',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/images', [
            'endpoint' => 'POST /api/admin/products/{id}/images',
            'label' => 'Unggah gambar admin',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('admin.png', 60, 60)],
            'expected' => 201,
        ]);

        $this->assertRecorded();
    }
}
