<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id_user')->primary();
            $table->string('nama_pengguna');
            $table->string('email')->unique();
            $table->string('nomor_telepon')->nullable();
            $table->string('kata_sandi');
            $table->string('peran')->default('pelanggan');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->string('id_category')->primary();
            $table->string('nama_kategori');
            $table->string('slug');
            $table->text('deskripsi')->nullable();
            $table->string('lokasi_gambar')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->string('id_product')->primary();
            $table->string('id_category');
            $table->string('nama_produk');
            $table->string('sku')->nullable()->unique();
            $table->string('slug');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('berat_produk')->nullable();
            $table->string('bahan_kain')->nullable();
            $table->string('status_produk')->default('draft');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->string('id_image')->primary();
            $table->string('id_product');
            $table->string('id_variant')->nullable();
            $table->string('path_gambar');
            $table->string('alt_text')->nullable();
            $table->string('type')->default('produk');
            $table->boolean('is_primary')->default(false);
            $table->integer('urutan')->default(0);
            $table->timestamp('dibuat_pada')->nullable();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->string('id_variant')->primary();
            $table->string('id_product');
            $table->string('sku');
            $table->string('nama_varian');
            $table->string('warna')->nullable();
            $table->string('ukuran')->nullable();
            $table->decimal('harga', 12, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->unsignedInteger('berat_gram')->default(500);
            $table->string('status_varian')->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    private function seedCategory(): void
    {
        Category::create([
            'id_category' => 'cat-1',
            'nama_kategori' => 'Batik',
            'slug' => 'batik',
            'status_aktif' => true,
        ]);
    }

    private function makeUser(string $peran): User
    {
        return User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => ucfirst($peran).' Test',
            'email' => $peran.'_'.Str::random(8).'@example.com',
            'kata_sandi' => 'password123',
            'peran' => $peran,
        ]);
    }

    private function makeProduct(string $nama, string $status): Product
    {
        return Product::create([
            'id_product' => (string) Str::uuid(),
            'id_category' => 'cat-1',
            'nama_produk' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => "Deskripsi {$nama}",
            'status_produk' => $status,
        ]);
    }

    public function test_public_index_only_returns_active_products(): void
    {
        $this->seedCategory();
        $this->makeProduct('Produk Aktif', 'aktif');
        $this->makeProduct('Produk Draft', 'draft');
        $this->makeProduct('Produk Nonaktif', 'nonaktif');

        $this->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status_produk', 'aktif')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_public_index_supports_search_and_pagination(): void
    {
        $this->seedCategory();
        for ($i = 1; $i <= 15; $i++) {
            $this->makeProduct("Batik Aktif {$i}", 'aktif');
        }
        $this->makeProduct('Kain Lain', 'aktif');

        $this->getJson('/api/products?search=Batik&per_page=5&page=1')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_public_show_returns_active_product(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Produk Aktif', 'aktif');

        $this->getJson('/api/products/'.$product->id_product)
            ->assertStatus(200)
            ->assertJsonPath('data.id_product', $product->id_product);
    }

    public function test_public_show_hides_non_active_product(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Produk Draft', 'draft');

        $this->getJson('/api/products/'.$product->id_product)
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_admin_index_requires_authentication(): void
    {
        $this->getJson('/api/admin/products')->assertStatus(401);
    }

    public function test_admin_index_forbidden_for_non_admin(): void
    {
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->getJson('/api/admin/products')->assertStatus(403);
    }

    public function test_admin_index_returns_all_statuses(): void
    {
        $this->seedCategory();
        $this->makeProduct('Produk Aktif', 'aktif');
        $this->makeProduct('Produk Draft', 'draft');
        $this->makeProduct('Produk Nonaktif', 'nonaktif');

        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson('/api/admin/products')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_show_returns_non_active_product(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Produk Draft', 'draft');

        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson('/api/admin/products/'.$product->id_product)
            ->assertStatus(200)
            ->assertJsonPath('data.id_product', $product->id_product)
            ->assertJsonPath('data.status_produk', 'draft');
    }

    public function test_admin_show_forbidden_for_non_admin(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Produk Draft', 'draft');

        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->getJson('/api/admin/products/'.$product->id_product)
            ->assertStatus(403);
    }

    public function test_admin_show_returns_404_for_missing_product(): void
    {
        $this->seedCategory();

        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson('/api/admin/products/tidak-ada')->assertStatus(404);
    }
}
