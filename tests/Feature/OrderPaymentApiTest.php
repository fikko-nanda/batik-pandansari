<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProfilUsaha;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Voucher;
use App\Models\WebhookLog;
use App\Models\ZonaPengiriman;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Midtrans\Notification;
use Mockery;
use Tests\TestCase;

class OrderPaymentApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();

        ProfilUsaha::create([
            'id_profil_usaha' => 1,
            'nama_usaha' => 'Batik Pandansari',
            'alamat_lengkap' => 'Wonokerto, Jembangan, Plupuh, Sragen',
            'latitude' => -7.445,
            'longitude' => 110.89,
        ]);

        ZonaPengiriman::create([
            'id_zona' => (string) Str::uuid(),
            'nama_zona' => 'Zona Uji',
            'jarak_min_km' => 0,
            'jarak_maks_km' => 100,
            'biaya_dasar' => 5000,
            'biaya_per_kg' => 2000,
            'status_aktif' => true,
        ]);

        Http::fake([
            rtrim((string) config('services.osm.osrm_url'), '/').'/*' => Http::response([
                'code' => 'Ok',
                'routes' => [
                    [
                        'distance' => 5000,
                        'duration' => 900,
                    ],
                ],
            ]),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id_user')->primary();
            $table->string('nama_pengguna');
            $table->string('email')->unique();
            $table->string('nomor_telepon')->nullable();
            $table->string('nomor_telepon_normalized')->nullable()->unique();
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
            $table->string('slug');
            $table->text('deskripsi')->nullable();
            $table->string('status_produk')->default('draft');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
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
            $table->unsignedInteger('stok_dipesan')->default(0);
            $table->unsignedInteger('berat_gram')->default(500);
            $table->string('status_varian')->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->string('id_user_address')->primary();
            $table->string('id_user');
            $table->string('label_alamat');
            $table->string('nama_penerima');
            $table->string('nomor_telepon');
            $table->string('nama_provinsi');
            $table->string('nama_kota');
            $table->string('nama_kecamatan');
            $table->string('kode_pos');
            $table->text('alamat_lengkap');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('alamat_utama')->default(false);
            $table->string('id_tujuan_rajaongkir', 100)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('profil_usaha', function (Blueprint $table) {
            $table->unsignedTinyInteger('id_profil_usaha')->primary();
            $table->string('nama_usaha');
            $table->text('alamat_lengkap')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('zona_pengiriman', function (Blueprint $table) {
            $table->string('id_zona', 36)->primary();
            $table->string('nama_zona', 100);
            $table->decimal('jarak_min_km', 8, 2);
            $table->decimal('jarak_maks_km', 8, 2);
            $table->decimal('biaya_dasar', 12, 2)->default(0);
            $table->decimal('biaya_per_kg', 12, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
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
            $table->unique(['id_cart', 'id_variant']);
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

        Schema::create('orders', function (Blueprint $table) {
            $table->string('id_order')->primary();
            $table->string('id_user');
            $table->string('nomor_pesanan')->unique();
            $table->string('status_pesanan')->default('menunggu_pembayaran');
            $table->boolean('stok_direservasi')->default(false);
            $table->timestamp('kedaluwarsa_pada')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('biaya_pengiriman', 12, 2)->default(0);
            $table->decimal('jarak_km', 8, 2)->nullable();
            $table->string('zona_pengiriman', 100)->nullable();
            $table->unsignedInteger('berat_pengiriman_gram')->nullable();
            $table->decimal('diskon', 12, 2)->default(0);
            $table->decimal('pph', 12, 2)->default(0);
            $table->decimal('total_pembayaran', 12, 2)->default(0);
            $table->string('nama_penerima');
            $table->string('nomor_telepon_penerima');
            $table->text('alamat_pengiriman');
            $table->string('kode_tujuan_pengiriman')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->string('id_order_item')->primary();
            $table->string('id_order');
            $table->string('id_variant');
            $table->string('nama_produk');
            $table->string('sku');
            $table->string('warna')->nullable();
            $table->string('ukuran')->nullable();
            $table->decimal('harga_satuan', 12, 2);
            $table->unsignedInteger('jumlah');
            $table->decimal('subtotal', 12, 2);
            $table->timestamp('dibuat_pada')->nullable();
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
            $table->unique('id_order');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->string('id_order_status_history')->primary();
            $table->string('id_order');
            $table->string('status_sebelumnya')->nullable();
            $table->string('status_baru');
            $table->text('catatan')->nullable();
            $table->string('diubah_oleh')->nullable();
            $table->dateTime('waktu_perubahan');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->string('id_inventory_movement')->primary();
            $table->string('id_variant');
            $table->string('id_user')->nullable();
            $table->string('jenis_pergerakan');
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('stok_sebelum');
            $table->unsignedInteger('stok_sesudah');
            $table->string('referensi')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('waktu_pergerakan');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('id_payment')->primary();
            $table->string('id_order');
            $table->string('provider');
            $table->string('provider_transaction_id')->nullable();
            $table->string('metode_pembayaran')->nullable();
            $table->decimal('jumlah_pembayaran', 12, 2);
            $table->string('status_pembayaran')->default('menunggu');
            $table->string('payment_url')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
            $table->unique(['provider', 'provider_transaction_id']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->string('id_webhook_log')->primary();
            $table->string('provider');
            $table->string('event_id', 100)->nullable();
            $table->string('event_type', 100)->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->string('id_order')->nullable();
            $table->string('endpoint');
            $table->string('method');
            $table->longText('payload');
            $table->string('signature')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->string('status')->default('diterima');
            $table->string('processing_status', 30)->default('received');
            $table->json('payload_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(1);
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('api_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id_audit_log');
            $table->string('correlation_id', 100)->nullable();
            $table->char('id_pengguna', 36)->nullable();
            $table->string('service_name', 50);
            $table->string('http_method', 10);
            $table->string('endpoint', 500);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->bigIncrements('id_notification_log');
            $table->char('id_pengguna', 36)->nullable();
            $table->string('channel', 20);
            $table->string('provider', 50);
            $table->string('notification_type', 100);
            $table->string('recipient_masked', 255)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->string('provider_message_id', 150)->nullable();
            $table->string('delivery_status', 30)->default('queued');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    private function makeUser(string $peran = 'pelanggan'): User
    {
        return User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => ucfirst($peran).' Test',
            'email' => $peran.'_'.Str::random(8).'@example.com',
            'kata_sandi' => 'password123',
            'peran' => $peran,
        ]);
    }

    private function makeCategoryProductVariant(int $stok = 10, float $harga = 100000): ProductVariant
    {
        Category::create([
            'id_category' => 'cat-1',
            'nama_kategori' => 'Batik',
            'slug' => 'batik',
            'status_aktif' => true,
        ]);

        $product = Product::create([
            'id_product' => 'prod-1',
            'id_category' => 'cat-1',
            'nama_produk' => 'Batik Parang',
            'slug' => 'batik-parang',
            'status_produk' => 'aktif',
        ]);

        return ProductVariant::create([
            'id_variant' => 'var-1',
            'id_product' => $product->id_product,
            'sku' => 'PARANG-M',
            'nama_varian' => 'Parang M',
            'warna' => 'Cokelat',
            'ukuran' => 'M',
            'harga' => $harga,
            'stok' => $stok,
            'status_varian' => 'aktif',
        ]);
    }

    private function makeAddress(User $user): UserAddress
    {
        return UserAddress::create([
            'id_user_address' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'label_alamat' => 'Rumah',
            'nama_penerima' => 'Budi',
            'nomor_telepon' => '08123456789',
            'nama_provinsi' => 'Jawa Tengah',
            'nama_kota' => 'Surakarta',
            'nama_kecamatan' => 'Banjarsari',
            'kode_pos' => '57134',
            'alamat_lengkap' => 'Jl. Contoh No. 10',
            'latitude' => -7.5,
            'longitude' => 110.8,
            'alamat_utama' => true,
        ]);
    }

    private function makeCart(User $user, ProductVariant $variant, int $jumlah): Cart
    {
        $cart = Cart::create([
            'id_cart' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'status_cart' => 'aktif',
        ]);

        CartItem::create([
            'id_cart_item' => (string) Str::uuid(),
            'id_cart' => $cart->id_cart,
            'id_variant' => $variant->id_variant,
            'jumlah' => $jumlah,
        ]);

        return $cart;
    }

    private function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'id_voucher' => (string) Str::uuid(),
            'kode_voucher' => 'BATIKHEMAT',
            'nama_voucher' => 'Diskon Batik',
            'deskripsi' => 'Diskon',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'minimal_pembelian' => 0,
            'maksimal_diskon' => 50000,
            'kuota_total' => null,
            'kuota_per_pengguna' => 1,
            'tanggal_mulai' => Carbon::now()->subDay(),
            'tanggal_selesai' => Carbon::now()->addDay(),
            'status_voucher' => 'aktif',
        ], $overrides));
    }

    private function makeOrder(User $user, ProductVariant $variant, int $jumlah = 1, float $total = 100000, string $status = 'menunggu_pembayaran'): Order
    {
        $order = Order::create([
            'id_order' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'nomor_pesanan' => 'ORD-'.Str::upper(Str::random(10)),
            'status_pesanan' => $status,
            'subtotal' => $total,
            'biaya_pengiriman' => 0,
            'diskon' => 0,
            'total_pembayaran' => $total,
            'nama_penerima' => 'Budi',
            'nomor_telepon_penerima' => '08123456789',
            'alamat_pengiriman' => 'Jl. Contoh No. 10',
            'kode_tujuan_pengiriman' => 'Surakarta',
            'catatan' => null,
        ]);

        OrderItem::create([
            'id_order_item' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'id_variant' => $variant->id_variant,
            'nama_produk' => 'Batik Parang',
            'sku' => $variant->sku,
            'warna' => $variant->warna,
            'ukuran' => $variant->ukuran,
            'harga_satuan' => $variant->harga,
            'jumlah' => $jumlah,
            'subtotal' => (float) $variant->harga * $jumlah,
        ]);

        return $order;
    }

    private function mockMidtrans(): void
    {
        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('createSnapTransaction')->andReturn([
                'token' => 'snap-token-test',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/test',
                'raw' => null,
            ]);
            $mock->shouldReceive('clientKey')->andReturn('SB-Mid-client-test');
            $mock->shouldReceive('snapScriptUrl')->andReturn('https://app.sandbox.midtrans.com/snap/snap.js');
        });
    }

    private function mockNotification(array $payload, bool $signatureValid = true): void
    {
        $reflection = new \ReflectionClass(Notification::class);
        $notification = $reflection->newInstanceWithoutConstructor();
        $response = $reflection->getProperty('response');
        $response->setAccessible(true);
        $response->setValue($notification, (object) $payload);

        $this->mock(MidtransService::class, function ($mock) use ($notification, $signatureValid) {
            $mock->shouldReceive('verifySignature')->andReturn($signatureValid);
            $mock->shouldReceive('notification')->andReturn($notification);
        });
    }

    private function mockTransactionStatus(array $status): void
    {
        $this->mock(MidtransService::class, function ($mock) use ($status) {
            $mock->shouldReceive('transactionStatus')->andReturn($status);
        });
    }

    // ---------------- Checkout ----------------

    public function test_checkout_requires_authentication(): void
    {
        $this->postJson('/api/checkout', [])->assertStatus(401);
    }

    public function test_checkout_creates_order_without_changing_stock_until_payment(): void
    {
        $this->freezeTime();
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(10, 100000);
        $address = $this->makeAddress($user);
        $this->makeCart($user, $variant, 2);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/checkout', [
            'id_user_address' => $address->id_user_address,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_pesanan', 'menunggu_pembayaran');

        $order = Order::where('id_user', $user->id_user)->firstOrFail();
        $this->assertTrue(
            $order->kedaluwarsa_pada->between(now()->addMinutes(14)->addSeconds(59), now()->addMinutes(15)->addSecond()),
            'Batas pembayaran harus 15 menit sejak checkout.'
        );

        $fresh = $variant->fresh();
        $this->assertSame(10, (int) $fresh->stok, 'Stok fisik tidak berkurang saat checkout.');
        $this->assertSame(0, (int) $fresh->stok_dipesan, 'Checkout tidak mereservasi stok.');
        $this->assertFalse((bool) $order->fresh()->stok_direservasi);

        $this->assertDatabaseHas('orders', [
            'id_user' => $user->id_user,
            'subtotal' => 200000,
            'biaya_pengiriman' => 7000,
            'jarak_km' => 5,
            'zona_pengiriman' => 'Zona Uji',
            'berat_pengiriman_gram' => 1000,
            'pph' => 22000,
            'total_pembayaran' => 229000,
        ]);
        $this->assertSame('checkout', Cart::where('id_user', $user->id_user)->value('status_cart'));
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_checkout_rejects_address_of_other_user(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $address = $this->makeAddress($other);
        $this->makeCart($user, $variant, 1);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'id_user_address' => $address->id_user_address,
        ])->assertStatus(404);
    }

    public function test_checkout_rejects_empty_cart(): void
    {
        $user = $this->makeUser();
        $address = $this->makeAddress($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'id_user_address' => $address->id_user_address,
        ])->assertStatus(422);
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(1);
        $address = $this->makeAddress($user);
        $this->makeCart($user, $variant, 5);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'id_user_address' => $address->id_user_address,
        ])->assertStatus(422);
    }

    public function test_checkout_applies_voucher(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(10, 100000);
        $address = $this->makeAddress($user);
        $this->makeCart($user, $variant, 2);
        $this->makeVoucher(['tipe_diskon' => 'persentase', 'nilai_diskon' => 10, 'maksimal_diskon' => 50000]);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'id_user_address' => $address->id_user_address,
            'kode_voucher' => 'BATIKHEMAT',
        ])->assertStatus(201)
            ->assertJsonPath('data.diskon', '20000.00')
            ->assertJsonPath('data.pph', '19800.00')
            ->assertJsonPath('data.biaya_pengiriman', '7000.00')
            ->assertJsonPath('data.total_pembayaran', '206800.00');

        $this->assertDatabaseHas('voucher_usages', [
            'id_user' => $user->id_user,
            'status_penggunaan' => 'menunggu',
        ]);
    }

    public function test_checkout_cannot_reuse_consumed_cart(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(10);
        $address = $this->makeAddress($user);
        $this->makeCart($user, $variant, 1);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'id_user_address' => $address->id_user_address,
        ])->assertStatus(201);
        $this->postJson('/api/checkout', [
            'id_user_address' => $address->id_user_address,
        ])->assertStatus(422);
        $this->assertDatabaseCount('orders', 1);
    }

    // ---------------- Orders ----------------

    public function test_customer_only_sees_own_orders(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $this->makeOrder($user, $variant);
        $this->makeOrder($other, $variant);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_customer_cannot_view_other_order(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($other, $variant);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id_order)->assertStatus(404);
    }

    public function test_admin_can_list_and_update_order_status(): void
    {
        $customer = $this->makeUser();
        $admin = $this->makeUser('admin');
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($customer, $variant, 2, 200000);

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/orders')->assertStatus(200)->assertJsonCount(1, 'data');

        $this->putJson('/api/admin/orders/'.$order->id_order.'/status', [
            'status_pesanan' => 'diproses',
        ])->assertStatus(200)->assertJsonPath('data.status_pesanan', 'diproses');

        $this->assertDatabaseHas('order_status_histories', [
            'id_order' => $order->id_order,
            'status_baru' => 'diproses',
        ]);
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        Sanctum::actingAs($this->makeUser());
        $this->getJson('/api/admin/orders')->assertStatus(403);
    }

    // ---------------- Payment ----------------

    public function test_payment_requires_ownership(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($other, $variant);
        $this->mockMidtrans();

        Sanctum::actingAs($user);
        $this->postJson('/api/orders/'.$order->id_order.'/payment')->assertStatus(404);
    }

    public function test_payment_creates_snap_token_and_reuses_pending(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        $this->mockMidtrans();

        Sanctum::actingAs($user);

        $first = $this->postJson('/api/orders/'.$order->id_order.'/payment')
            ->assertStatus(201)
            ->assertJsonPath('data.snap_token', 'snap-token-test')
            ->assertJsonPath('data.client_key', 'SB-Mid-client-test')
            ->assertJsonPath('data.snap_script_url', 'https://app.sandbox.midtrans.com/snap/snap.js');

        $second = $this->postJson('/api/orders/'.$order->id_order.'/payment')
            ->assertStatus(200)
            ->assertJsonPath('data.snap_token', 'snap-token-test');

        $this->assertSame(
            $first->json('data.id_payment'),
            $second->json('data.id_payment')
        );
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_rejected_when_order_not_payable(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000, 'dibatalkan');
        $this->mockMidtrans();

        Sanctum::actingAs($user);
        $this->postJson('/api/orders/'.$order->id_order.'/payment')->assertStatus(422);
    }

    public function test_customer_can_view_own_payment(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id_order.'/payment')
            ->assertStatus(200)
            ->assertJsonPath('data.status_pembayaran', 'menunggu');
    }

    public function test_payment_lookup_returns_404_without_calling_midtrans_when_payment_is_missing(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        $midtrans = Mockery::mock(MidtransService::class);
        $midtrans->shouldNotReceive('transactionStatus');
        $this->app->instance(MidtransService::class, $midtrans);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id_order.'/payment')
            ->assertNotFound()
            ->assertJsonPath('message', 'Pembayaran untuk pesanan ini belum dibuat.');
    }

    public function test_snap_script_url_matches_midtrans_environment(): void
    {
        config(['services.midtrans.is_production' => false]);
        $this->assertSame(
            'https://app.sandbox.midtrans.com/snap/snap.js',
            (new MidtransService)->snapScriptUrl()
        );

        config(['services.midtrans.is_production' => true]);
        $this->assertSame(
            'https://app.midtrans.com/snap/snap.js',
            (new MidtransService)->snapScriptUrl()
        );
    }

    // ---------------- Webhook ----------------

    public function test_webhook_settlement_marks_order_paid(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-123',
            'gross_amount' => '100000.00',
            'status_code' => '200',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'signature_key' => 'whatever',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('payments', [
            'id_order' => $order->id_order,
            'status_pembayaran' => 'dibayar',
            'provider_transaction_id' => 'trx-123',
            'metode_pembayaran' => 'qris',
        ]);
        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('webhook_logs', ['status' => 'diproses']);
    }

    public function test_webhook_is_idempotent(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $payload = [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-123',
            'gross_amount' => '100000.00',
            'status_code' => '200',
        ];

        $this->mockNotification($payload);
        $this->postJson('/api/payments/midtrans/notification', $payload)->assertStatus(200);
        $this->postJson('/api/payments/midtrans/notification', $payload)->assertStatus(200);

        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('payments', ['status_pembayaran' => 'dibayar']);
        $this->assertSame(1, Payment::where('id_order', $order->id_order)->count());
    }

    public function test_webhook_expire_cancels_order_and_releases_reservation(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($user, $variant, 2, 200000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'menunggu',
        ]);

        // Simulasikan reservasi stok aktif untuk pesanan menunggu pembayaran.
        $variant->update(['stok_dipesan' => 2]);
        $order->update(['stok_direservasi' => true]);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'expire',
            'fraud_status' => '',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-expire',
            'gross_amount' => '200000.00',
            'status_code' => '202',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'expire',
            'gross_amount' => '200000.00',
            'status_code' => '202',
            'signature_key' => 'x',
        ])->assertStatus(200);

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);

        $fresh = $variant->fresh();
        $this->assertSame(3, (int) $fresh->stok, 'Stok fisik tidak berubah untuk pesanan yang belum dibayar.');
        $this->assertSame(0, (int) $fresh->stok_dipesan, 'Reservasi stok dilepas saat kedaluwarsa.');
        $this->assertDatabaseHas('payments', ['id_order' => $order->id_order, 'status_pembayaran' => 'kedaluwarsa']);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->mockNotification([
            'order_id' => 'ORD-ANY',
            'transaction_status' => 'settlement',
        ], false);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => 'ORD-ANY',
            'transaction_status' => 'settlement',
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'signature_key' => 'invalid',
        ])->assertStatus(403);

        // Percobaan signature tidak valid tetap dicatat untuk audit, tetapi
        // signature asli tidak boleh disimpan.
        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'midtrans',
            'signature_valid' => false,
            'status' => 'ditolak',
            'processing_status' => 'rejected',
            'http_status' => 403,
        ]);

        $log = WebhookLog::latest('dibuat_pada')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->signature);
        $this->assertSame('[REDACTED]', $log->payload_summary['signature_key'] ?? null);
        $this->assertStringNotContainsString('"invalid"', (string) $log->payload);
    }

    public function test_webhook_ignores_amount_mismatch(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '50000.00',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '50000.00',
            'status_code' => '200',
            'signature_key' => 'x',
        ])->assertStatus(200)->assertJsonPath('success', false);

        $this->assertDatabaseHas('payments', ['id_order' => $order->id_order, 'status_pembayaran' => 'menunggu']);
    }

    public function test_admin_can_list_payments(): void
    {
        $customer = $this->makeUser();
        $admin = $this->makeUser('admin');
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($customer, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'dibayar',
        ]);

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/payments')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_webhook_alias_route_midtrans_notification(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-alias',
            'gross_amount' => '100000.00',
            'status_code' => '200',
        ]);

        $this->postJson('/api/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'signature_key' => 'whatever',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('payments', ['id_order' => $order->id_order, 'status_pembayaran' => 'dibayar']);
    }

    public function test_order_show_syncs_status_from_midtrans_when_pending(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->mockTransactionStatus([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-sync',
            'gross_amount' => 100000.0,
            'status_code' => '200',
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id_order)
            ->assertStatus(200)
            ->assertJsonPath('data.status_pesanan', 'dibayar');

        $this->assertDatabaseHas('payments', ['id_order' => $order->id_order, 'status_pembayaran' => 'dibayar']);
        $this->assertDatabaseCount('webhook_logs', 0);
    }

    public function test_order_payment_show_syncs_status_from_midtrans_when_pending(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->mockTransactionStatus([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'trx-sync-2',
            'gross_amount' => 100000.0,
            'status_code' => '200',
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id_order.'/payment')
            ->assertStatus(200)
            ->assertJsonPath('data.status_pembayaran', 'dibayar');

        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
    }

    public function test_order_show_sync_is_idempotent_on_repeat(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->mockTransactionStatus([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-sync-3',
            'gross_amount' => 100000.0,
            'status_code' => '200',
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id_order.'/payment')->assertStatus(200);
        $this->getJson('/api/orders/'.$order->id_order.'/payment')->assertStatus(200);

        $this->assertDatabaseCount('order_status_histories', 1);
    }

    public function test_order_show_sync_ignores_pending_midtrans_status(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant();
        $order = $this->makeOrder($user, $variant, 1, 100000);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 100000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->mockTransactionStatus([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'pending',
            'fraud_status' => '',
            'payment_type' => null,
            'transaction_id' => null,
            'gross_amount' => 100000.0,
            'status_code' => '201',
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/orders/'.$order->id_order)
            ->assertStatus(200)
            ->assertJsonPath('data.status_pesanan', 'menunggu_pembayaran');
    }

    public function test_webhook_settles_order_after_expiry_and_commits_released_stock(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(5);
        $order = $this->makeOrder($user, $variant, 2, 200000, 'dibatalkan');
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'kedaluwarsa',
        ]);

        $payload = [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-late-settlement',
            'gross_amount' => '200000.00',
            'status_code' => '200',
        ];

        $this->mockNotification($payload);
        $this->postJson('/api/payments/midtrans/notification', $payload)->assertStatus(200);

        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
        $this->assertSame(3, (int) $variant->fresh()->stok, 'Stok fisik dikurangi setelah settlement terlambat.');
        $this->assertDatabaseHas('payments', [
            'id_order' => $order->id_order,
            'status_pembayaran' => 'dibayar',
        ]);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('order_status_histories', 1);

        $this->postJson('/api/payments/midtrans/notification', $payload)->assertStatus(200);

        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
        $this->assertSame(3, (int) $variant->fresh()->stok, 'Notifikasi duplikat tidak mengurangi stok lagi.');
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    public function test_expire_command_cancels_unpaid_order_and_marks_payment(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($user, $variant, 2, 200000);
        $order->update(['kedaluwarsa_pada' => now()->subMinute()]);
        $variant->update(['stok_dipesan' => 2]);
        $order->update(['stok_direservasi' => true]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('payments', ['id_order' => $order->id_order, 'status_pembayaran' => 'kedaluwarsa']);
        $this->assertSame(0, (int) $variant->fresh()->stok_dipesan, 'Reservasi stok dilepas.');
    }

    public function test_expire_command_does_not_cancel_paid_order(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($user, $variant, 2, 200000, 'dibayar');
        // Kedaluwarsa lewat, tetapi pesanan sudah dibayar: tidak boleh dibatalkan.
        $order->update(['kedaluwarsa_pada' => now()->subMinute()]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'dibayar',
        ]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('dibayar', $order->fresh()->status_pesanan, 'Pesanan yang sudah dibayar tidak boleh di-expire.');
        $this->assertDatabaseHas('payments', ['id_order' => $order->id_order, 'status_pembayaran' => 'dibayar']);
    }

    public function test_expire_does_not_touch_present_order(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($user, $variant, 2, 200000);
        $order->update(['kedaluwarsa_pada' => now()->addMinute()]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('menunggu_pembayaran', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('payments', ['id_order' => $order->id_order, 'status_pembayaran' => 'menunggu']);
    }

    public function test_order_detail_expires_unpaid_order_immediately_after_deadline(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($user, $variant, 2, 200000);
        $order->update(['kedaluwarsa_pada' => now()->subSecond()]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'menunggu',
        ]);
        $this->mockTransactionStatus([
            'transaction_status' => 'pending',
            'fraud_status' => '',
            'payment_type' => null,
            'transaction_id' => null,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/orders/'.$order->id_order)
            ->assertOk()
            ->assertJsonPath('data.status_pesanan', 'dibatalkan')
            ->assertJsonPath('data.payments.0.status_pembayaran', 'kedaluwarsa');

        $this->assertSame(3, (int) $variant->fresh()->stok, 'Stok fisik pesanan belum dibayar tidak boleh berubah.');
        $this->assertSame(0, (int) $variant->fresh()->stok_dipesan, 'Tidak ada reservasi stok yang tertinggal.');
    }

    public function test_order_index_expires_stale_order_before_applying_status_filter(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($user, $variant, 2, 200000);
        $order->update(['kedaluwarsa_pada' => now()->subSecond()]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'menunggu',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/orders?status=menunggu_pembayaran')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('payments', [
            'id_order' => $order->id_order,
            'status_pembayaran' => 'kedaluwarsa',
        ]);
        $this->assertSame(3, (int) $variant->fresh()->stok, 'Stok fisik pesanan belum dibayar tidak boleh berubah.');
    }

    public function test_payment_detail_expires_unpaid_payment_after_deadline(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeCategoryProductVariant(3);
        $order = $this->makeOrder($user, $variant, 2, 200000);
        $order->update(['kedaluwarsa_pada' => now()->subSecond()]);
        Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => 200000,
            'status_pembayaran' => 'menunggu',
        ]);
        $this->mockTransactionStatus([
            'transaction_status' => 'pending',
            'fraud_status' => '',
            'payment_type' => null,
            'transaction_id' => null,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/orders/'.$order->id_order.'/payment')
            ->assertOk()
            ->assertJsonPath('data.status_pembayaran', 'kedaluwarsa');

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);
        $this->assertSame(3, (int) $variant->fresh()->stok, 'Stok fisik pesanan belum dibayar tidak boleh berubah.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
