<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShoppingApiTest extends TestCase
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

        Schema::create('product_categories', function (Blueprint $table) {
            $table->string('id_product');
            $table->string('id_category');
            $table->primary(['id_product', 'id_category']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->string('id_variant')->primary();
            $table->string('id_product');
            $table->string('sku');
            $table->string('nama_varian')->nullable();
            $table->string('warna')->nullable();
            $table->string('ukuran')->nullable();
            $table->decimal('harga', 12, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->unsignedInteger('berat_gram')->default(500);
            $table->string('status_varian')->default('aktif');
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

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->string('id_wishlist_item')->primary();
            $table->string('id_user');
            $table->string('id_product');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->string('id_cart')->primary();
            $table->string('id_user');
            $table->string('status_cart')->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->string('id_cart_item')->primary();
            $table->string('id_cart');
            $table->string('id_variant');
            $table->unsignedInteger('jumlah')->default(1);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->string('id_voucher')->primary();
            $table->string('kode_voucher')->unique();
            $table->string('nama_voucher');
            $table->text('deskripsi')->nullable();
            $table->string('gambar', 500)->nullable();
            $table->string('tipe_diskon');
            $table->decimal('nilai_diskon', 12, 2);
            $table->decimal('minimal_pembelian', 12, 2)->default(0);
            $table->decimal('maksimal_diskon', 12, 2)->nullable();
            $table->unsignedInteger('kuota_total')->nullable();
            $table->unsignedInteger('kuota_per_pengguna')->default(1);
            $table->string('id_product_peruntukan', 36)->nullable();
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai');
            $table->string('status_voucher')->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->string('id_voucher_usage')->primary();
            $table->string('id_voucher');
            $table->string('id_user');
            $table->string('id_order');
            $table->decimal('jumlah_diskon', 12, 2)->default(0);
            $table->string('status_penggunaan')->default('menunggu');
            $table->dateTime('digunakan_pada')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });
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

    private function seedCategory(): void
    {
        Category::create([
            'id_category' => 'cat-1',
            'nama_kategori' => 'Batik',
            'slug' => 'batik',
            'status_aktif' => true,
        ]);
    }

    private function makeProduct(string $nama, string $status = 'aktif'): Product
    {
        $product = Product::create([
            'id_product' => (string) Str::uuid(),
            'id_category' => 'cat-1',
            'nama_produk' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => "Deskripsi {$nama}",
            'status_produk' => $status,
        ]);

        $product->categories()->sync(['cat-1']);

        return $product;
    }

    private function makeVariant(Product $product, int $stok = 10, string $status = 'aktif'): ProductVariant
    {
        return ProductVariant::create([
            'id_variant' => (string) Str::uuid(),
            'id_product' => $product->id_product,
            'sku' => 'SKU-'.Str::random(6),
            'nama_varian' => 'Varian',
            'harga' => 100000,
            'stok' => $stok,
            'status_varian' => $status,
        ]);
    }

    private function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'id_voucher' => (string) Str::uuid(),
            'kode_voucher' => 'KODE'.Str::upper(Str::random(6)),
            'nama_voucher' => 'Voucher Test',
            'deskripsi' => 'Voucher pengujian',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'minimal_pembelian' => 0,
            'maksimal_diskon' => null,
            'kuota_total' => null,
            'kuota_per_pengguna' => 1,
            'tanggal_mulai' => Carbon::now()->subDay(),
            'tanggal_selesai' => Carbon::now()->addDay(),
            'status_voucher' => 'aktif',
        ], $overrides));
    }

    private function makeUsage(Voucher $voucher, User $user, string $status = 'digunakan'): VoucherUsage
    {
        return VoucherUsage::create([
            'id_voucher_usage' => (string) Str::uuid(),
            'id_voucher' => $voucher->id_voucher,
            'id_user' => $user->id_user,
            'id_order' => (string) Str::uuid(),
            'jumlah_diskon' => 10000,
            'status_penggunaan' => $status,
        ]);
    }

    // ---------------- Wishlist ----------------

    public function test_wishlist_requires_authentication(): void
    {
        $this->getJson('/api/wishlist')->assertStatus(401);
    }

    public function test_customer_can_add_view_and_delete_wishlist(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Parang');
        $user = $this->makeUser('pelanggan');
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/wishlist', ['id_product' => $product->id_product]);
        $create->assertStatus(201)->assertJsonPath('data.id_product', $product->id_product);

        $this->getJson('/api/wishlist')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->deleteJson('/api/wishlist/'.$create->json('data.id_wishlist_item'))
            ->assertStatus(200);

        $this->getJson('/api/wishlist')->assertJsonCount(0, 'data');
    }

    public function test_wishlist_duplicate_rejected(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Kawung');
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/wishlist', ['id_product' => $product->id_product])->assertStatus(201);
        $this->postJson('/api/wishlist', ['id_product' => $product->id_product])->assertStatus(422);
    }

    public function test_wishlist_rejects_inactive_product(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Draft', 'draft');
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/wishlist', ['id_product' => $product->id_product])->assertStatus(404);
    }

    public function test_wishlist_delete_only_owner(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Truntum');
        $owner = $this->makeUser('pelanggan');

        Sanctum::actingAs($owner);
        $item = $this->postJson('/api/wishlist', ['id_product' => $product->id_product]);
        $id = $item->json('data.id_wishlist_item');

        Sanctum::actingAs($this->makeUser('pelanggan'));
        $this->deleteJson('/api/wishlist/'.$id)->assertStatus(404);

        Sanctum::actingAs($owner);
        $this->deleteJson('/api/wishlist/'.$id)->assertStatus(200);
    }

    public function test_wishlist_only_returns_own_items(): void
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Sogan');
        $owner = $this->makeUser('pelanggan');

        Sanctum::actingAs($owner);
        $this->postJson('/api/wishlist', ['id_product' => $product->id_product])->assertStatus(201);

        Sanctum::actingAs($this->makeUser('pelanggan'));
        $this->getJson('/api/wishlist')->assertJsonCount(0, 'data');
    }

    // ---------------- Cart ----------------

    public function test_cart_requires_authentication(): void
    {
        $this->getJson('/api/cart')->assertStatus(401);
    }

    public function test_get_cart_creates_active_cart(): void
    {
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->getJson('/api/cart')
            ->assertStatus(200)
            ->assertJsonPath('data.status_cart', 'aktif')
            ->assertJsonCount(0, 'data.items');
    }

    public function test_add_variant_to_cart(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Bali'), 5);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 2])
            ->assertStatus(201)
            ->assertJsonPath('data.jumlah', 2);
    }

    public function test_add_same_variant_merges_quantity(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Lasem'), 5);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 1])->assertStatus(201);
        $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 2])
            ->assertStatus(200)
            ->assertJsonPath('data.jumlah', 3);
    }

    public function test_add_exceeding_stock_rejected(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Madura'), 3);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 5])
            ->assertStatus(422);
    }

    public function test_add_inactive_variant_rejected(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Aceh'), 5, 'nonaktif');
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 1])
            ->assertStatus(422);
    }

    public function test_update_cart_item_quantity(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Jambi'), 10);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $add = $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 1]);
        $id = $add->json('data.id_cart_item');

        $this->putJson('/api/cart/items/'.$id, ['jumlah' => 4])
            ->assertStatus(200)
            ->assertJsonPath('data.jumlah', 4);
    }

    public function test_update_cart_item_exceeding_stock_rejected(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Riau'), 2);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $add = $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 1]);
        $id = $add->json('data.id_cart_item');

        $this->putJson('/api/cart/items/'.$id, ['jumlah' => 9])->assertStatus(422);
    }

    public function test_cart_item_update_only_owner(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Papua'), 5);
        $owner = $this->makeUser('pelanggan');

        Sanctum::actingAs($owner);
        $add = $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 1]);
        $id = $add->json('data.id_cart_item');

        Sanctum::actingAs($this->makeUser('pelanggan'));
        $this->putJson('/api/cart/items/'.$id, ['jumlah' => 2])->assertStatus(404);
        $this->deleteJson('/api/cart/items/'.$id)->assertStatus(404);
    }

    public function test_delete_cart_item(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Solo'), 5);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $add = $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 2]);
        $id = $add->json('data.id_cart_item');

        $this->deleteJson('/api/cart/items/'.$id)->assertStatus(200);
        $this->getJson('/api/cart')->assertJsonCount(0, 'data.items');
    }

    public function test_clear_cart(): void
    {
        $this->seedCategory();
        $variant = $this->makeVariant($this->makeProduct('Batik Pekalongan'), 5);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/cart/items', ['id_variant' => $variant->id_variant, 'jumlah' => 2])->assertStatus(201);

        $this->deleteJson('/api/cart')->assertStatus(200);
        $this->getJson('/api/cart')->assertJsonCount(0, 'data.items');
    }

    // ---------------- Voucher ----------------

    public function test_voucher_index_access_control(): void
    {
        $this->getJson('/api/vouchers')->assertStatus(401);

        Sanctum::actingAs($this->makeUser('pelanggan'));
        $this->getJson('/api/vouchers')->assertStatus(403);

        Sanctum::actingAs($this->makeUser('admin'));
        $this->getJson('/api/vouchers')->assertStatus(200);
    }

    public function test_admin_can_create_voucher(): void
    {
        Sanctum::actingAs($this->makeUser('admin'));

        $this->postJson('/api/vouchers', [
            'kode_voucher' => 'DISKONBARU',
            'nama_voucher' => 'Diskon Baru',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 15,
            'minimal_pembelian' => 100000,
            'maksimal_diskon' => 30000,
            'kuota_total' => 20,
            'kuota_per_pengguna' => 1,
            'tanggal_mulai' => Carbon::now()->toDateTimeString(),
            'tanggal_selesai' => Carbon::now()->addMonth()->toDateTimeString(),
        ])->assertStatus(201)->assertJsonPath('data.kode_voucher', 'DISKONBARU');
    }

    public function test_voucher_create_validates_tipe_diskon(): void
    {
        Sanctum::actingAs($this->makeUser('admin'));

        $this->postJson('/api/vouchers', [
            'kode_voucher' => 'SALAH',
            'nama_voucher' => 'Salah',
            'tipe_diskon' => 'gratis',
            'nilai_diskon' => 10,
            'tanggal_mulai' => Carbon::now()->toDateTimeString(),
            'tanggal_selesai' => Carbon::now()->addDay()->toDateTimeString(),
        ])->assertStatus(422);
    }

    public function test_available_returns_only_active_and_valid_vouchers(): void
    {
        $this->makeVoucher(['kode_voucher' => 'AKTIF1']);
        $this->makeVoucher(['kode_voucher' => 'NONAKTIF1', 'status_voucher' => 'nonaktif']);
        $this->makeVoucher([
            'kode_voucher' => 'EXPIRED1',
            'tanggal_selesai' => Carbon::now()->subDay(),
        ]);

        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->getJson('/api/vouchers/available')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_voucher', 'AKTIF1');
    }

    public function test_validate_persentase_discount(): void
    {
        $this->makeVoucher([
            'kode_voucher' => 'PERSEN10',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'maksimal_diskon' => 50000,
        ]);

        Sanctum::actingAs($this->makeUser('pelanggan'));

        $response = $this->postJson('/api/vouchers/validate', [
            'kode_voucher' => 'PERSEN10',
            'subtotal' => 200000,
        ])->assertStatus(200);

        $this->assertEqualsWithDelta(20000, $response->json('data.jumlah_diskon'), 0.01);
    }

    public function test_validate_persentase_capped_by_maksimal_diskon(): void
    {
        $this->makeVoucher([
            'kode_voucher' => 'PERSENCAP',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'maksimal_diskon' => 50000,
        ]);

        Sanctum::actingAs($this->makeUser('pelanggan'));

        $response = $this->postJson('/api/vouchers/validate', [
            'kode_voucher' => 'PERSENCAP',
            'subtotal' => 1000000,
        ])->assertStatus(200);

        $this->assertEqualsWithDelta(50000, $response->json('data.jumlah_diskon'), 0.01);
    }

    public function test_validate_nominal_discount(): void
    {
        $this->makeVoucher([
            'kode_voucher' => 'NOMINAL25',
            'tipe_diskon' => 'nominal',
            'nilai_diskon' => 25000,
            'minimal_pembelian' => 100000,
        ]);

        Sanctum::actingAs($this->makeUser('pelanggan'));

        $response = $this->postJson('/api/vouchers/validate', [
            'kode_voucher' => 'NOMINAL25',
            'subtotal' => 150000,
        ])->assertStatus(200);

        $this->assertEqualsWithDelta(25000, $response->json('data.jumlah_diskon'), 0.01);
    }

    public function test_validate_below_minimal_rejected(): void
    {
        $this->makeVoucher(['kode_voucher' => 'MINIMAL', 'minimal_pembelian' => 200000]);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/vouchers/validate', [
            'kode_voucher' => 'MINIMAL',
            'subtotal' => 100000,
        ])->assertStatus(422);
    }

    public function test_validate_inactive_voucher_rejected(): void
    {
        $this->makeVoucher(['kode_voucher' => 'OFF', 'status_voucher' => 'nonaktif']);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/vouchers/validate', [
            'kode_voucher' => 'OFF',
            'subtotal' => 500000,
        ])->assertStatus(422);
    }

    public function test_validate_expired_voucher_rejected(): void
    {
        $this->makeVoucher([
            'kode_voucher' => 'OLD',
            'tanggal_mulai' => Carbon::now()->subMonth(),
            'tanggal_selesai' => Carbon::now()->subDay(),
        ]);
        Sanctum::actingAs($this->makeUser('pelanggan'));

        $this->postJson('/api/vouchers/validate', [
            'kode_voucher' => 'OLD',
            'subtotal' => 500000,
        ])->assertStatus(422);
    }

    public function test_validate_per_user_quota_rejected(): void
    {
        $voucher = $this->makeVoucher(['kode_voucher' => 'SEKALI', 'kuota_per_pengguna' => 1]);
        $user = $this->makeUser('pelanggan');
        $this->makeUsage($voucher, $user);

        Sanctum::actingAs($user);

        $this->postJson('/api/vouchers/validate', [
            'kode_voucher' => 'SEKALI',
            'subtotal' => 500000,
        ])->assertStatus(422);
    }

    public function test_admin_can_update_and_soft_delete_voucher(): void
    {
        $voucher = $this->makeVoucher(['kode_voucher' => 'EDITABLE']);
        Sanctum::actingAs($this->makeUser('admin'));

        $this->putJson('/api/vouchers/'.$voucher->id_voucher, ['nilai_diskon' => 30])
            ->assertStatus(200)
            ->assertJsonPath('data.kode_voucher', 'EDITABLE');

        $this->deleteJson('/api/vouchers/'.$voucher->id_voucher)
            ->assertStatus(200)
            ->assertJsonPath('data.status_voucher', 'nonaktif');
    }

    public function test_voucher_show_not_found(): void
    {
        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson('/api/vouchers/tidak-ada')->assertStatus(404);
    }

    // ---------------- Voucher usages ----------------

    public function test_voucher_usages_index_admin_only(): void
    {
        Sanctum::actingAs($this->makeUser('pelanggan'));
        $this->getJson('/api/voucher-usages')->assertStatus(403);

        Sanctum::actingAs($this->makeUser('admin'));
        $this->getJson('/api/voucher-usages')->assertStatus(200);
    }

    public function test_voucher_usages_me_only_own(): void
    {
        $voucher = $this->makeVoucher(['kode_voucher' => 'RIWAYAT']);
        $userA = $this->makeUser('pelanggan');
        $userB = $this->makeUser('pelanggan');
        $this->makeUsage($voucher, $userA);
        $this->makeUsage($voucher, $userB);

        Sanctum::actingAs($userA);

        $this->getJson('/api/voucher-usages/me')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_user', $userA->id_user);
    }
}
