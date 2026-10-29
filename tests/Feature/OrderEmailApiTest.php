<?php

namespace Tests\Feature;

use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\InvoiceService;
use App\Services\MidtransService;
use App\Services\OrderMailService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Midtrans\Notification;
use Tests\TestCase;

class OrderEmailApiTest extends TestCase
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
            $table->boolean('alamat_utama')->default(false);
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

        Schema::create('orders', function (Blueprint $table) {
            $table->string('id_order')->primary();
            $table->string('id_user');
            $table->string('nomor_pesanan')->unique();
            $table->string('status_pesanan')->default('menunggu_pembayaran');
            $table->boolean('stok_direservasi')->default(false);
            $table->timestamp('kedaluwarsa_pada')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('biaya_pengiriman', 12, 2)->default(0);
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
            $table->string('provider_transaction_id')->nullable();
            $table->string('id_order')->nullable();
            $table->string('endpoint');
            $table->string('method');
            $table->longText('payload');
            $table->string('signature')->nullable();
            $table->string('status')->default('diterima');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('alamat_ip')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->bigIncrements('id_notification_log');
            $table->string('id_pengguna')->nullable();
            $table->string('channel', 20);
            $table->string('provider', 50);
            $table->string('notification_type', 100);
            $table->string('recipient_masked')->nullable();
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

    private function makeVariant(int $stok = 10, float $harga = 100000): ProductVariant
    {
        $category = Category::create([
            'id_category' => 'cat-'.Str::random(6),
            'nama_kategori' => 'Batik',
            'slug' => 'batik-'.Str::random(6),
            'status_aktif' => true,
        ]);

        $product = Product::create([
            'id_product' => (string) Str::uuid(),
            'id_category' => $category->id_category,
            'nama_produk' => 'Batik Parang',
            'slug' => 'batik-parang-'.Str::random(6),
            'status_produk' => 'aktif',
        ]);

        return ProductVariant::create([
            'id_variant' => (string) Str::uuid(),
            'id_product' => $product->id_product,
            'sku' => 'SKU-'.Str::random(8),
            'warna' => 'Coklat',
            'ukuran' => 'M',
            'harga' => $harga,
            'stok' => $stok,
            'stok_dipesan' => 0,
            'status_varian' => 'aktif',
        ]);
    }

    private function makeOrder(User $user, ProductVariant $variant, int $jumlah = 1, float $total = 100000, string $status = 'menunggu_pembayaran'): Order
    {
        $order = Order::create([
            'id_order' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'nomor_pesanan' => 'ORD-'.Str::upper(Str::random(10)),
            'status_pesanan' => $status,
            'kedaluwarsa_pada' => now()->addMinutes(10),
            'subtotal' => $total,
            'biaya_pengiriman' => 0,
            'diskon' => 0,
            'total_pembayaran' => $total,
            'nama_penerima' => 'Budi',
            'nomor_telepon_penerima' => '08123456789',
            'alamat_pengiriman' => 'Jl. Contoh No. 10, Surakarta',
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
            $mock->shouldReceive('transactionStatus')->andReturn([]);
        });
    }

    private function makePendingPayment(Order $order, float $amount = 100000): Payment
    {
        return Payment::create([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'jumlah_pembayaran' => $amount,
            'status_pembayaran' => 'menunggu',
        ]);
    }

    // 1. Webhook settlement mengirim email konfirmasi ke pelanggan.
    public function test_settlement_queues_paid_email(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);
        $this->makePendingPayment($order);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-1',
            'gross_amount' => '100000.00',
            'status_code' => '200',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'signature_key' => 'whatever',
        ])->assertStatus(200);

        Mail::assertQueued(OrderPaidMail::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
    }

    public function test_payment_window_is_fifteen_minutes(): void
    {
        $base = Carbon::parse('2026-10-23 10:00:00');

        $service = app(OrderService::class);

        $this->assertSame(
            '2026-10-23 10:15:00',
            $service->expiryFrom($base)->format('Y-m-d H:i:s')
        );
        $this->assertSame(33000.0, $service->pphFrom(300000));
        $this->assertSame(11000.0, $service->pphFrom(100001), 'Dibulatkan ke rupiah terdekat.');
    }

    public function test_settlement_sends_paid_order_to_admin_whatsapp(): void
    {
        Mail::fake();
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'id' => 'wa-test-1'], 200),
        ]);
        config([
            'services.fonnte.token' => 'fonnte-test-token',
            'services.fonnte.admin_numbers' => ['081234567890'],
        ]);

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);
        $this->makePendingPayment($order);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-wa',
            'gross_amount' => '100000.00',
            'status_code' => '200',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'signature_key' => 'whatever',
        ])->assertStatus(200);

        Http::assertSent(function ($request) use ($order) {
            $message = (string) $request['message'];

            return $request->url() === 'https://api.fonnte.com/send'
                && (string) $request['target'] === '6281234567890'
                && $request->hasHeader('Authorization', 'fonnte-test-token')
                && str_contains($message, 'Nama Pemesan : Budi')
                && str_contains($message, '*BARANG DIPESAN*')
                && str_contains($message, 'Batik Parang')
                && str_contains($message, 'ID Pesanan : '.$order->nomor_pesanan)
                && str_contains($message, 'Jl. Contoh No. 10, Surakarta')
                && str_contains($message, '*TOTAL PEMBAYARAN*')
                && str_contains($message, 'Rp 100.000,00');
        });
    }

    public function test_whatsapp_failure_does_not_cancel_successful_payment(): void
    {
        Mail::fake();
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'device disconnected'], 500),
        ]);
        config([
            'services.fonnte.token' => 'fonnte-test-token',
            'services.fonnte.admin_numbers' => ['081234567890'],
        ]);

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant, 1, 200000);
        $this->makePendingPayment($order);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-wa-fail',
            'gross_amount' => '200000.00',
            'status_code' => '200',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '200000.00',
            'status_code' => '200',
            'signature_key' => 'whatever',
        ])->assertStatus(200)->assertJsonPath('success', true);

        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
        $this->assertDatabaseHas('notification_logs', [
            'notification_type' => 'order_paid_admin',
            'delivery_status' => 'failed',
        ]);
    }

    // 2. Webhook ganda tidak mengirim email dua kali.
    public function test_duplicate_settlement_sends_email_once(): void
    {
        Mail::fake();
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'id' => 'wa-duplicate-test'], 200),
        ]);
        config([
            'services.fonnte.token' => 'fonnte-test-token',
            'services.fonnte.admin_numbers' => ['081234567890'],
        ]);

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);
        $this->makePendingPayment($order);

        $payload = [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-2',
            'gross_amount' => '100000.00',
            'status_code' => '200',
        ];

        $this->mockNotification($payload);
        $this->postJson('/api/payments/midtrans/notification', $payload)->assertStatus(200);
        $this->postJson('/api/payments/midtrans/notification', $payload)->assertStatus(200);

        Mail::assertQueued(OrderPaidMail::class, 1);
        Http::assertSentCount(1);
    }

    // 3. Email konfirmasi memakai alamat email akun pelanggan.
    public function test_paid_email_recipient_is_customer_email(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);

        app(OrderMailService::class)->sendPaid($order);

        Mail::assertQueued(OrderPaidMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    // 4. Email konfirmasi melampirkan invoice PDF.
    public function test_paid_email_attaches_pdf_invoice(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);
        $this->makePendingPayment($order);

        app(OrderMailService::class)->sendPaid($order);

        Mail::assertQueued(OrderPaidMail::class, function (OrderPaidMail $mail) {
            $mail->build();

            $attachments = (array) $mail->rawAttachments;

            return count($attachments) === 1
                && str_ends_with($attachments[0]['name'], '.pdf');
        });
    }

    // 5. InvoiceService menghasilkan PDF valid dari data pesanan (tanpa midtrans).
    public function test_invoice_service_builds_pdf_from_order_data(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant, 2, 200000);

        $binary = app(InvoiceService::class)->build($order);

        $this->assertStringStartsWith('%PDF-', $binary);
        $this->assertGreaterThan(1000, strlen($binary));
        $this->assertSame('invoice-'.$order->nomor_pesanan.'.pdf', app(InvoiceService::class)->filename($order));
    }

    // 6. Expire command membatalkan pesanan dan mengirim email pembatalan.
    public function test_expire_command_queues_cancellation_email(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $variant = $this->makeVariant(5);
        $variant->update(['stok_dipesan' => 1]);
        $order = $this->makeOrder($user, $variant, 1, 100000);
        $order->update(['stok_direservasi' => true]);
        $order->update(['kedaluwarsa_pada' => now()->subMinute()]);
        $this->makePendingPayment($order);

        $this->artisan('orders:expire')->assertExitCode(0);

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);
        $this->assertSame(1, (int) $variant->fresh()->stok_dipesan);
        Mail::assertQueued(OrderCancelledMail::class, fn ($mail) => $mail->hasTo($user->email));

        $this->travel(61)->seconds();
        $this->artisan('orders:expire')->assertExitCode(0);
        $this->assertSame(0, (int) $variant->fresh()->stok_dipesan);
    }

    // 7. Webhook expire Midtrans juga mengirim email pembatalan.
    public function test_webhook_expire_queues_cancellation_email(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $variant = $this->makeVariant(5);
        $variant->update(['stok_dipesan' => 1]);
        $order = $this->makeOrder($user, $variant, 1, 100000);
        $order->update(['stok_direservasi' => true]);
        $this->makePendingPayment($order);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'expire',
            'fraud_status' => '',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-expire',
            'gross_amount' => '100000.00',
            'status_code' => '202',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'expire',
            'gross_amount' => '100000.00',
            'status_code' => '202',
            'signature_key' => 'whatever',
        ])->assertStatus(200);

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);
        Mail::assertQueued(OrderCancelledMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    // 8. Pengiriman email gagal tidak membatalkan perubahan status pesanan.
    public function test_mail_failure_does_not_break_status_transition(): void
    {
        Mail::shouldReceive('to->queue')->andThrow(new \RuntimeException('smtp down'));

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);
        $this->makePendingPayment($order);

        $this->mockNotification([
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-fail',
            'gross_amount' => '100000.00',
            'status_code' => '200',
        ]);

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => $order->nomor_pesanan,
            'transaction_status' => 'settlement',
            'gross_amount' => '100000.00',
            'status_code' => '200',
            'signature_key' => 'whatever',
        ])->assertStatus(200);

        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
    }

    // 9. Pesanan tanpa email tidak mengirim apa pun dan tidak error.
    public function test_order_without_email_sends_nothing(): void
    {
        Mail::fake();

        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);

        $tanpaEmail = new User(['email' => '']);
        $order->setRelation('user', $tanpaEmail);

        app(OrderMailService::class)->sendPaid($order);

        Mail::assertNothingQueued();
    }

    // 10. Endpoint invoice pelanggan mengembalikan PDF untuk pesanan sendiri.
    public function test_customer_can_download_own_invoice(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);
        Sanctum::actingAs($user);

        $response = $this->get('/api/orders/'.$order->id_order.'/invoice');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    // 11. Pelanggan tidak dapat mengunduh invoice pesanan orang lain.
    public function test_customer_cannot_download_other_invoice(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($owner, $variant);
        Sanctum::actingAs($other);

        $this->getJson('/api/orders/'.$order->id_order.'/invoice')->assertStatus(404);
    }

    // 12. Invoice pelanggan menolak permintaan tanpa autentikasi.
    public function test_invoice_requires_authentication(): void
    {
        $this->getJson('/api/orders/some-id/invoice')->assertStatus(401);
    }

    // 13. View email "Pembayaran Berhasil" harus benar-benar dirender tanpa
    // ParseError. Regresi sebelumnya: directive @if menempel pada teks
    // ("Diskon@if ...") memicu "unexpected endif" sehingga email tidak terkirim.
    public function test_order_paid_view_renders_without_discount(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);

        $html = (new OrderPaidMail($order->id_order))->render();

        $this->assertStringContainsString('Pembayaran Berhasil', $html);
        $this->assertStringContainsString($order->nomor_pesanan, $html);
        $this->assertStringNotContainsString('Diskon', $html);
        $this->assertStringNotContainsString('@endif', $html);
    }

    public function test_order_paid_view_shows_pph_line(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant, 1, 111000);
        $order->update(['subtotal' => 100000, 'pph' => 11000]);

        $html = (new OrderPaidMail($order->id_order))->render();

        $this->assertStringContainsString('PPh 11%', $html);
        $this->assertStringContainsString('111.000,00', $html);
    }

    // 14. View email "Pembayaran Berhasil" harus dirender benar saat ada
    // diskon + voucher (kondisi yang dulu memicu ParseError di baris Diskon).
    public function test_order_paid_view_renders_with_discount_and_voucher(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeVariant();

        $voucher = Voucher::create([
            'id_voucher' => (string) Str::uuid(),
            'kode_voucher' => 'HEMAT10',
            'nama_voucher' => 'Hemat 10 Persen',
            'tipe_diskon' => 'persen',
            'nilai_diskon' => 10,
            'minimal_pembelian' => 0,
            'kuota_per_pengguna' => 1,
            'tanggal_mulai' => Carbon::now()->subDay(),
            'tanggal_selesai' => Carbon::now()->addDay(),
            'status_voucher' => 'aktif',
        ]);

        $order = $this->makeOrder($user, $variant);
        $order->update(['diskon' => 10000, 'total_pembayaran' => 90000]);

        VoucherUsage::create([
            'id_voucher_usage' => (string) Str::uuid(),
            'id_voucher' => $voucher->id_voucher,
            'id_order' => $order->id_order,
            'id_user' => $user->id_user,
            'status_penggunaan' => 'digunakan',
        ]);

        $html = (new OrderPaidMail($order->id_order))->render();

        $this->assertStringContainsString('Pembayaran Berhasil', $html);
        $this->assertStringContainsString('Diskon', $html);
        $this->assertStringContainsString('(HEMAT10)', $html);
        $this->assertStringNotContainsString('@endif', $html);
    }

    // 15. View email pembatalan harus dirender tanpa ParseError.
    public function test_order_cancelled_view_renders(): void
    {
        $user = $this->makeUser();
        $variant = $this->makeVariant();
        $order = $this->makeOrder($user, $variant);

        $html = (new OrderCancelledMail($order->id_order))->render();

        $this->assertStringContainsString($order->nomor_pesanan, $html);
        $this->assertStringNotContainsString('@endif', $html);
    }
}
