<?php

namespace Tests\Feature\Endpoints;

use App\Models\Category;
use App\Models\Product;

/**
 * Endpoint kategori untuk admin:
 * POST /api/admin/categories, PUT /api/admin/categories/{id},
 * DELETE /api/admin/categories/{id}
 */
class AdminCategoryEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Kategori Admin';

    public function test_admin_membuat_kategori(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori baru (slug otomatis)',
            'json' => [
                'nama_kategori' => 'Batik Cap',
                'deskripsi' => 'Kategori batik cap Pekalongan',
                'lokasi_gambar' => 'categories/batik-cap.jpg',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('Batik Cap', $data['nama_kategori'] ?? null);
        $this->assertSame('batik-cap', $data['slug'] ?? null);
        $this->assertTrue((bool) ($data['status_aktif'] ?? false), 'Kategori baru harus aktif.');
        $this->assertSame(['categories' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_admin_membuat_kategori_slug_eksplisit(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori dengan slug eksplisit & status nonaktif',
            'json' => [
                'nama_kategori' => 'Kategori Keren',
                'slug' => 'Kategori Keren!!',
                'status_aktif' => false,
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('kategori-keren', $data['slug'] ?? null, 'Slug harus dinormalisasi Str::slug.');
        $this->assertFalse((bool) ($data['status_aktif'] ?? true));

        $this->assertRecorded();
    }

    public function test_admin_membuat_kategori_validasi(): void
    {
        $this->seedCategory('cat-1', 'Batik Tulis');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori tanpa nama',
            'json' => [],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('nama_kategori', $this->recorded[0]['response']['errors'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect']);

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori dengan nama > 80 karakter',
            'json' => ['nama_kategori' => str_repeat('A', 81)],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori dengan slug milik kategori lain',
            'json' => ['nama_kategori' => 'Kategori Lain', 'slug' => 'batik-tulis'],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori dengan lokasi_gambar > 200 karakter',
            'json' => [
                'nama_kategori' => 'Gambar Panjang',
                'lokasi_gambar' => str_repeat('a', 201),
            ],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori dengan status_aktif bukan boolean',
            'json' => ['nama_kategori' => 'Status Salah', 'status_aktif' => 'mungkin'],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_membuat_kategori_slug_otomatis_unik(): void
    {
        $this->seedCategory('cat-1', 'Batik Tulis');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori dengan nama sama (slug otomatis dibuat unik)',
            'json' => ['nama_kategori' => 'Batik Tulis'],
            'expected' => 201,
        ]);

        $this->assertSame('batik-tulis-1', $this->recorded[0]['response']['data']['slug'] ?? null);
        $this->note('Nama kategori tidak unik di skema; yang unik hanya slug, sehingga slug otomatis diberi akhiran.');

        $this->assertRecorded();
    }

    public function test_admin_memperbarui_kategori(): void
    {
        $this->seedCategory('cat-1', 'Batik Tulis');
        $other = $this->seedCategory('cat-2', 'Batik Cap');
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/admin/categories/cat-1', [
            'endpoint' => 'PUT /api/admin/categories/{id}',
            'label' => 'Perbarui nama kategori saja (slug tidak berubah)',
            'json' => ['nama_kategori' => 'Batik Tulis Halus'],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('Batik Tulis Halus', $data['nama_kategori'] ?? null);
        $this->assertSame('batik-tulis', $data['slug'] ?? null, 'Slug lama dipertahankan pada pembaruan parsial.');

        $this->hit('PUT', '/api/admin/categories/cat-1', [
            'endpoint' => 'PUT /api/admin/categories/{id}',
            'label' => 'Nonaktifkan kategori lewat pembaruan parsial',
            'json' => ['status_aktif' => false],
            'expected' => 200,
        ]);

        $this->assertFalse((bool) ($this->recorded[1]['response']['data']['status_aktif'] ?? true));

        $this->hit('PUT', '/api/admin/categories/cat-1', [
            'endpoint' => 'PUT /api/admin/categories/{id}',
            'label' => 'Perbarui slug menjadi milik kategori lain',
            'json' => ['slug' => $other->slug],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/admin/categories/tidak-ada', [
            'endpoint' => 'PUT /api/admin/categories/{id}',
            'label' => 'Perbarui kategori tidak ditemukan',
            'json' => ['nama_kategori' => 'Baru'],
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_kategori_akses_non_admin(): void
    {
        $category = $this->seedCategory();

        $this->asGuest();
        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori tanpa token',
            'json' => ['nama_kategori' => 'Kategori Liar'],
            'expected' => 401,
        ]);
        $this->hit('PUT', '/api/admin/categories/'.$category->id_category, [
            'endpoint' => 'PUT /api/admin/categories/{id}',
            'label' => 'Ubah kategori tanpa token',
            'json' => ['nama_kategori' => 'Diubah'],
            'expected' => 401,
        ]);
        $this->hit('DELETE', '/api/admin/categories/'.$category->id_category, [
            'endpoint' => 'DELETE /api/admin/categories/{id}',
            'label' => 'Nonaktifkan kategori tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('POST', '/api/admin/categories', [
            'endpoint' => 'POST /api/admin/categories',
            'label' => 'Tambah kategori oleh pelanggan',
            'json' => ['nama_kategori' => 'Kategori Liar'],
            'expected' => 403,
        ]);
        $this->hit('PUT', '/api/admin/categories/'.$category->id_category, [
            'endpoint' => 'PUT /api/admin/categories/{id}',
            'label' => 'Ubah kategori oleh pelanggan',
            'json' => ['nama_kategori' => 'Diubah'],
            'expected' => 403,
        ]);
        $this->hit('DELETE', '/api/admin/categories/'.$category->id_category, [
            'endpoint' => 'DELETE /api/admin/categories/{id}',
            'label' => 'Nonaktifkan kategori oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertSame('Batik Tulis', $category->fresh()->nama_kategori);
        $this->assertTrue((bool) $category->fresh()->status_aktif);

        $this->assertRecorded();
    }

    public function test_menonaktifkan_kategori(): void
    {
        $category = $this->seedCategory();
        $this->actingAsRole('admin');

        $this->hit('DELETE', '/api/admin/categories/'.$category->id_category, [
            'endpoint' => 'DELETE /api/admin/categories/{id}',
            'label' => 'Nonaktifkan kategori (baris tetap ada)',
            'expected' => 200,
        ]);

        $this->assertFalse((bool) $category->fresh()->status_aktif);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Delete kategori bersifat nonaktif, bukan hapus fisik.');
        $this->note('DELETE kategori hanya mengubah status_aktif=false; baris tetap tersimpan.');

        $this->hit('DELETE', '/api/admin/categories/'.$category->id_category, [
            'endpoint' => 'DELETE /api/admin/categories/{id}',
            'label' => 'Nonaktifkan kategori yang sudah nonaktif (idempoten)',
            'expected' => 200,
        ]);

        $this->hit('DELETE', '/api/admin/categories/tidak-ada', [
            'endpoint' => 'DELETE /api/admin/categories/{id}',
            'label' => 'Nonaktifkan kategori tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_kategori_dipakai_produk_tetap_aman(): void
    {
        ['product' => $product] = $this->seedCatalog();
        $this->actingAsRole('admin');

        $this->hit('DELETE', '/api/admin/categories/cat-1', [
            'endpoint' => 'DELETE /api/admin/categories/{id}',
            'label' => 'Nonaktifkan kategori yang masih dipakai produk',
            'expected' => 200,
        ]);

        $category = Category::where('id_category', 'cat-1')->first();
        $this->assertNotNull($category, 'Kategori tidak boleh dihapus fisik karena masih dirujuk produk.');
        $this->assertFalse((bool) $category->status_aktif);

        $freshProduct = Product::where('id_product', $product->id_product)->first();
        $this->assertNotNull($freshProduct, 'Produk tidak boleh rusak/hilang.');
        $this->assertSame('cat-1', $freshProduct->id_category, 'Relasi produk ke kategori tetap utuh.');
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Tidak ada baris kategori/produk yang berubah jumlahnya.');

        $this->hit('GET', '/api/products', [
            'endpoint' => 'GET /api/products',
            'label' => 'Produk dari kategori nonaktif tetap dapat dibaca publik',
            'expected' => 200,
        ]);

        $ids = array_column($this->recorded[1]['response']['data'] ?? [], 'id_product');
        $this->assertContains($product->id_product, $ids);

        $this->note('Keputusan desain: DELETE kategori = nonaktifkan (bukan hapus fisik), sehingga relasi ke produk tidak pernah terputus.');

        $this->assertRecorded();
    }
}
