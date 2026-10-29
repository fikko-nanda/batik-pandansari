<?php

namespace Tests\Feature\Endpoints;

use App\Models\Cart;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Models\VoucherUsage;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Midtrans\Config;

/**
 * Endpoint checkout, pesanan (pelanggan), pembayaran (Midtrans),
 * pesanan/pembayaran admin, dan webhook notifikasi Midtrans.
 */
class CheckoutOrderPaymentEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Checkout & Pesanan';

    protected function setUp(): void
    {
        parent::setUp();

        // Panggilan SDK Midtrans ke sandbox dibatasi waktunya agar pengujian
        // tidak menunggu lama bila jaringan tersambung. CURLOPT_HTTPHEADER
        // ikut diisi karena SDK mengakses kunci ini tanpa isset() (PHP 8 warning).
        Config::$curlOptions[CURLOPT_CONNECTTIMEOUT] = 5;
        Config::$curlOptions[CURLOPT_TIMEOUT] = 20;
        Config::$curlOptions[CURLOPT_HTTPHEADER] = [];
    }

    // ------------------------------------------------------------------
    // Checkout
    // ------------------------------------------------------------------

    public function test_checkout_akses_tanpa_token(): void
    {
        $this->asGuest();
        $uuid = (string) Str::uuid();

        $cases = [
            ['POST', '/api/checkout', ['id_user_address' => 'x']],
            ['GET', '/api/orders', []],
            ['GET', '/api/orders/'.$uuid, []],
            ['POST', '/api/orders/'.$uuid.'/payment', []],
            ['GET', '/api/orders/'.$uuid.'/payment', []],
        ];

        foreach ($cases as [$method, $url, $json]) {
            $this->hit($method, $url, [
                'endpoint' => $this->normalizeEndpoint($method, $url),
                'label' => strtoupper($method).' '.parse_url($url, PHP_URL_PATH).' tanpa token',
                'json' => $json,
                'expected' => 401,
            ]);
        }

        $this->assertRecorded();
    }

    public function test_checkout_sukses(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 2);
        $this->seedShippingBaseline(5.0);

        $this->note('Checkout dengan ongkir: 5 km, 1 kg, zona uji Rp5.000 + Rp2.000/kg.');

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout sukses (dengan ongkir)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'catatan' => 'Tolong dikemas aman.',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $order = Order::findOrFail($data['id_order']);
        $this->assertSame('menunggu_pembayaran', $data['status_pesanan'] ?? null);
        $this->assertArrayNotHasKey('stok_direservasi', $data);
        $this->assertSame(300000.0, (float) ($data['subtotal'] ?? 0), 'Subtotal 2 x 150.000.');
        $this->assertSame(7000.0, (float) ($data['biaya_pengiriman'] ?? 0), 'Ongkir zona uji 1 kg.');
        $this->assertSame(5.0, (float) ($data['jarak_km'] ?? 0));
        $this->assertSame('Zona Uji', $data['zona_pengiriman'] ?? null);
        $this->assertSame(1000, (int) ($data['berat_pengiriman_gram'] ?? 0));
        $this->assertSame(0.0, (float) ($data['diskon'] ?? 0));
        $this->assertSame(33000.0, (float) ($data['pph'] ?? 0), 'PPh 11% x 300.000.');
        $this->assertSame(340000.0, (float) ($data['total_pembayaran'] ?? 0));
        $this->assertSame('Tolong dikemas aman.', $data['catatan'] ?? null);
        $this->assertCount(1, $data['items'] ?? []);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{4}$/', (string) ($data['nomor_pesanan'] ?? ''));

        $this->assertSame(
            [
                'orders' => '0 -> 1 (+1)',
                'order_items' => '0 -> 1 (+1)',
                'order_status_histories' => '0 -> 1 (+1)',
            ],
            $this->recorded[0]['db_effect']
        );

        $variant = $catalog['variant']->fresh();
        $this->assertSame(10, (int) $variant->stok, 'Stok fisik tidak berkurang sebelum pembayaran.');
        $this->assertSame(2, (int) $variant->stok_dipesan, 'Checkout mereservasi jumlah yang dipesan.');
        $this->assertTrue((bool) $order->stok_direservasi);
        $this->assertSame('checkout', $this->currentCartStatus($user));

        $this->note('Checkout tidak mengurangi atau mereservasi stok; stok fisik dikurangi setelah pembayaran.');

        $this->assertRecorded();
    }

    public function test_checkout_dengan_voucher(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 3);
        $this->seedShippingBaseline(5.0);
        $voucher = $this->makeVoucher([
            'kode_voucher' => 'COCHK10',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'minimal_pembelian' => 0,
            'kuota_per_pengguna' => 5,
        ]);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan voucher persen 10% (dengan ongkir)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kode_voucher' => 'COCHK10',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(450000.0, (float) ($data['subtotal'] ?? 0), 'Subtotal 3 x 150.000.');
        $this->assertSame(45000.0, (float) ($data['diskon'] ?? 0), 'Diskon 10% x 450.000.');
        $this->assertSame(9000.0, (float) ($data['biaya_pengiriman'] ?? 0), 'Ongkir zona uji 2 kg.');
        $this->assertSame(44550.0, (float) ($data['pph'] ?? 0), 'PPh 11% x 405.000.');
        $this->assertSame(458550.0, (float) ($data['total_pembayaran'] ?? 0), '405.000 + 44.550 + 9.000.');

        $this->assertSame(
            [
                'voucher_usages' => '0 -> 1 (+1)',
                'orders' => '0 -> 1 (+1)',
                'order_items' => '0 -> 1 (+1)',
                'order_status_histories' => '0 -> 1 (+1)',
            ],
            $this->recorded[0]['db_effect']
        );

        $usage = VoucherUsage::where('id_voucher', $voucher->id_voucher)->first();
        $this->assertSame('menunggu', $usage->status_penggunaan ?? null);
        $this->assertNull($usage->digunakan_pada ?? null, 'Voucher ditandai terpakai sebelum pesanan dibayar.');

        $this->assertRecorded();
    }

    public function test_checkout_dengan_voucher_per_produk(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $this->seedCategory();
        $productA = $this->makeProduct('Batik Peruntukan A');
        $variantA = $this->makeVariant($productA, 10, 'aktif', 150000);
        $productB = $this->makeProduct('Batik Lain B');
        $variantB = $this->makeVariant($productB, 10, 'aktif', 200000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $variantA, 2);
        $this->addToCart($user, $variantB, 1);
        $this->seedShippingBaseline(5.0);
        $voucher = $this->makeVoucher([
            'kode_voucher' => 'COPRODUK10',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'minimal_pembelian' => 0,
            'kuota_per_pengguna' => 5,
            'id_product_peruntukan' => $productA->id_product,
        ]);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout voucher per produk: diskon hanya dari subtotal produk peruntukan',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kode_voucher' => 'COPRODUK10',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(500000.0, (float) ($data['subtotal'] ?? 0), 'Subtotal 2 x 150.000 + 1 x 200.000.');
        $this->assertSame(30000.0, (float) ($data['diskon'] ?? 0), 'Diskon 10% x 300.000 (produk A saja).');
        $this->assertSame(9000.0, (float) ($data['biaya_pengiriman'] ?? 0), 'Ongkir zona uji 2 kg.');
        $this->assertSame(51700.0, (float) ($data['pph'] ?? 0), 'PPh 11% x 470.000.');
        $this->assertSame(530700.0, (float) ($data['total_pembayaran'] ?? 0), '470.000 + 51.700 + 9.000.');

        $usage = VoucherUsage::where('id_voucher', $voucher->id_voucher)->first();
        $this->assertSame(30000.0, (float) ($usage->jumlah_diskon ?? 0));

        $this->assertRecorded();
    }

    public function test_checkout_gagal(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $lain = $this->makeUser('pelanggan');
        $alamatLain = $this->makeAddress($lain);
        $this->seedShippingBaseline(5.0);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout tanpa id_user_address',
            'json' => [],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('id_user_address', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan alamat milik pengguna lain',
            'json' => [
                'id_user_address' => $alamatLain->id_user_address,
            ],
            'expected' => 404,
        ]);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan keranjang kosong',
            'json' => [
                'id_user_address' => $address->id_user_address,
            ],
            'expected' => 422,
        ]);
        $this->assertStringContainsString('Keranjang Anda kosong.', (string) ($this->recorded[2]['response']['message'] ?? ''));

        $cartItem = $this->addToCart($user, $catalog['variant'], 50);
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout saat stok tidak mencukupi (50 dari stok 10)',
            'json' => [
                'id_user_address' => $address->id_user_address,
            ],
            'expected' => 422,
        ]);
        $this->assertStringContainsString('Stok tidak mencukupi', implode(' ', (array) ($this->recorded[3]['response']['errors']['cart'] ?? [])));

        $cartItem->update(['jumlah' => 1]);
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan kode voucher tidak dikenal',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kode_voucher' => 'TIDAKADA',
            ],
            'expected' => 422,
        ]);
        $this->assertStringContainsString('Kode voucher tidak ditemukan.', implode(' ', (array) ($this->recorded[4]['response']['errors']['kode_voucher'] ?? [])));

        foreach ($this->recorded as $entry) {
            $this->assertArrayNotHasKey('orders', $entry['db_effect'], 'Pesanan tidak boleh terbentuk: '.$entry['label']);
            $this->assertArrayNotHasKey('order_items', $entry['db_effect'], 'Order item tidak boleh terbentuk: '.$entry['label']);
        }

        $this->assertSame(10, (int) $catalog['variant']->fresh()->stok, 'Stok berubah walau checkout gagal.');

        $this->assertRecorded();
    }

    public function test_checkout_varian_nonaktif(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $product = $this->makeProduct('Produk Nonaktif Checkout', 'nonaktif');
        $variant = $this->makeVariant($product, 10, 'aktif');
        $address = $this->makeAddress($user);
        $this->addToCart($user, $variant, 1);
        $this->seedShippingBaseline(5.0);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout berisi varian produk nonaktif (ditolak, transaksi dibatalkan)',
            'json' => [
                'id_user_address' => $address->id_user_address,
            ],
            'expected' => 422,
        ]);

        $this->assertStringContainsString(
            'Varian produk tidak tersedia untuk dibeli.',
            implode(' ', (array) ($this->recorded[0]['response']['errors']['cart'] ?? []))
        );
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Tidak boleh ada baris pesanan sama sekali.');
        $this->assertSame(10, (int) $variant->fresh()->stok);
        $this->assertSame('aktif', $this->currentCartStatus($user), 'Keranjang tidak boleh ditutup saat checkout gagal.');

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Pesanan pelanggan
    // ------------------------------------------------------------------

    public function test_orders_pelanggan_index(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $lain = $this->makeUser('pelanggan');
        $orderDenganPembayaran = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-1']);
        $this->makePayment($orderDenganPembayaran);
        $orderTanpaPembayaran = $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-2',
            'status_pesanan' => 'dibatalkan',
        ]);
        $this->makeOrder($lain, ['nomor_pesanan' => 'ORD-3']);

        $this->hit('GET', '/api/orders', [
            'endpoint' => 'GET /api/orders',
            'label' => 'Daftar pesanan milik sendiri (default per_page 10)',
            'expected' => 200,
        ]);

        $meta = $this->recorded[0]['response']['meta'] ?? [];
        $this->assertCount(2, $this->recorded[0]['response']['data'] ?? []);
        $this->assertSame(10, (int) ($meta['per_page'] ?? 0));
        $this->assertSame(2, (int) ($meta['total'] ?? 0));
        $pesanan = collect($this->recorded[0]['response']['data'] ?? [])->keyBy('id_order');
        $this->assertSame(true, $pesanan[$orderDenganPembayaran->id_order]['payments_exists'] ?? null);
        $this->assertSame(false, $pesanan[$orderTanpaPembayaran->id_order]['payments_exists'] ?? null);

        $this->hit('GET', '/api/orders?status=dibatalkan', [
            'endpoint' => 'GET /api/orders',
            'label' => 'Filter pesanan status dibatalkan',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[1]['response']['data'] ?? []);

        $this->hit('GET', '/api/orders?status=dikirim', [
            'endpoint' => 'GET /api/orders',
            'label' => 'Filter status tanpa data (200, kosong)',
            'expected' => 200,
        ]);
        $this->assertCount(0, $this->recorded[2]['response']['data'] ?? []);

        $this->hit('GET', '/api/orders?status=ngawur', [
            'endpoint' => 'GET /api/orders',
            'label' => 'Filter status tidak dikenal',
            'expected' => 422,
        ]);

        $this->hit('GET', '/api/orders?per_page=101', [
            'endpoint' => 'GET /api/orders',
            'label' => 'per_page melebihi batas 100',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_orders_pelanggan_show(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $lain = $this->makeUser('pelanggan');
        $catalog = $this->seedCatalog();
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-SHOW-1'], [$catalog['variant']]);
        $this->makePayment($order);
        $orderLain = $this->makeOrder($lain, ['nomor_pesanan' => 'ORD-SHOW-2']);

        $this->hit('GET', '/api/orders/'.$order->id_order, [
            'endpoint' => 'GET /api/orders/{id}',
            'label' => 'Detail pesanan milik sendiri (items & payments ikut termuat)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data['items'] ?? []);
        $this->assertCount(1, $data['payments'] ?? []);
        $this->assertSame($order->id_order, $data['id_order'] ?? null);

        $this->hit('GET', '/api/orders/'.$orderLain->id_order, [
            'endpoint' => 'GET /api/orders/{id}',
            'label' => 'Detail pesanan milik pengguna lain',
            'expected' => 404,
        ]);

        $this->hit('GET', '/api/orders/tidak-ada', [
            'endpoint' => 'GET /api/orders/{id}',
            'label' => 'Detail pesanan tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_order_payment_show(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $lain = $this->makeUser('pelanggan');
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-PAY-1']);
        $payment = $this->makePayment($order, ['payment_reference' => 'SB-Midtrans-Ref']);
        $orderLain = $this->makeOrder($lain, ['nomor_pesanan' => 'ORD-PAY-2']);
        $this->makePayment($orderLain);
        $orderKosong = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-PAY-3']);

        $this->hit('GET', '/api/orders/'.$order->id_order.'/payment', [
            'endpoint' => 'GET /api/orders/{id}/payment',
            'label' => 'Detail pembayaran pesanan milik sendiri',
            'expected' => 200,
        ]);
        $this->assertSame($payment->id_payment, $this->recorded[0]['response']['data']['id_payment'] ?? null);

        $this->hit('GET', '/api/orders/'.$orderKosong->id_order.'/payment', [
            'endpoint' => 'GET /api/orders/{id}/payment',
            'label' => 'Detail pembayaran pesanan yang belum punya pembayaran',
            'expected' => 404,
        ]);

        $this->hit('GET', '/api/orders/'.$orderLain->id_order.'/payment', [
            'endpoint' => 'GET /api/orders/{id}/payment',
            'label' => 'Detail pembayaran pesanan milik pengguna lain',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Pembayaran (Midtrans)
    // ------------------------------------------------------------------

    public function test_payment_store_gagal_tanpa_kredensial_midtrans(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-SNAP-1']);
        $orderDibayar = $this->makeOrder($user, [
            'nomor_pesanan' => 'ORD-SNAP-2',
            'status_pesanan' => 'dibayar',
        ]);
        $lain = $this->makeUser('pelanggan');
        $orderLain = $this->makeOrder($lain, ['nomor_pesanan' => 'ORD-SNAP-3']);

        config(['services.midtrans.server_key' => '']);

        $this->note(
            'Skenario kegagalan: MIDTRANS_SERVER_KEY dikosongkan lewat config sehingga pembuatan '
            .'transaksi Snap gagal (502) dan baris payment dibatalkan kembali — memastikan alur '
            .'error tetap tertangani meski kredensial terpasang.'
        );

        $this->hit('POST', '/api/orders/'.$order->id_order.'/payment', [
            'endpoint' => 'POST /api/orders/{id}/payment',
            'label' => 'Buat transaksi pembayaran (Snap) saat kredensial dikosongkan (502)',
            'expected' => 502,
        ]);

        $this->assertStringContainsString('Gagal membuat transaksi pembayaran.', (string) ($this->recorded[0]['response']['message'] ?? ''));
        $this->assertArrayNotHasKey('payments', $this->recorded[0]['db_effect'], 'Baris payment tidak boleh tersisa setelah gagal.');

        $this->hit('POST', '/api/orders/'.$orderDibayar->id_order.'/payment', [
            'endpoint' => 'POST /api/orders/{id}/payment',
            'label' => 'Buat pembayaran untuk pesanan berstatus dibayar',
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/orders/'.$orderLain->id_order.'/payment', [
            'endpoint' => 'POST /api/orders/{id}/payment',
            'label' => 'Buat pembayaran untuk pesanan milik pengguna lain',
            'expected' => 404,
        ]);

        $this->assertSame(
            0,
            Payment::count(),
            'Baris payment tidak boleh tersisa setelah kegagalan Snap (dibuat lalu dihapus kembali).'
        );

        $this->assertRecorded();
    }

    public function test_payment_store_sukses_snap_sandbox(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 2);
        $this->seedShippingBaseline(5.0);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout sebagai persiapan pembuatan Snap',
            'json' => [
                'id_user_address' => $address->id_user_address,
            ],
            'expected' => 201,
        ]);

        $idOrder = $this->recorded[0]['response']['data']['id_order'] ?? null;
        $this->assertNotEmpty($idOrder, 'Checkout tidak menghasilkan id_order.');

        // Sandbox Midtrans menolak order_id yang sudah pernah dipakai. Nomor
        // pesanan bawaan bersifat deterministik per hari (ORD-YYYYMMDD-####),
        // sehingga pada database uji in-memory yang baru tiap run tetap bentrok
        // dengan transaksi sandbox lama. Beri sufiks acak agar unik.
        $order = Order::where('id_order', $idOrder)->firstOrFail();
        $order->update(['nomor_pesanan' => $order->nomor_pesanan.'-'.strtoupper(Str::random(6))]);

        $this->hit('POST', '/api/orders/'.$idOrder.'/payment', [
            'endpoint' => 'POST /api/orders/{id}/payment',
            'label' => 'Buat transaksi pembayaran (Snap) — sandbox Midtrans (201 + snap_token)',
            'expected' => 201,
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame('Transaksi pembayaran berhasil dibuat.', $this->recorded[1]['response']['message'] ?? null);
        $this->assertNotEmpty($data['snap_token'] ?? null, 'snap_token tidak boleh kosong.');
        $this->assertNotEmpty($data['redirect_url'] ?? null, 'redirect_url tidak boleh kosong.');
        $this->assertStringContainsString('sandbox', (string) ($data['redirect_url'] ?? ''), 'redirect_url menuju sandbox Midtrans.');
        $this->assertSame('menunggu', $data['status_pembayaran'] ?? null);
        $this->assertSame((string) config('services.midtrans.client_key'), (string) ($data['client_key'] ?? ''));
        $this->assertSame(340000.0, (float) ($data['jumlah_pembayaran'] ?? 0));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9._-]+$/', (string) ($data['snap_token'] ?? ''));

        $this->note(
            'Pengujian memakai kredensial sandbox Midtrans dari .env; snap_token & redirect_url '
            .'asli diperoleh dari api.sandbox.midtrans.com (transaksi tidak dibayar, hanya dibuat).'
        );

        $this->hit('POST', '/api/orders/'.$idOrder.'/payment', [
            'endpoint' => 'POST /api/orders/{id}/payment',
            'label' => 'Buat pembayaran ulang: token lama dipakai kembali (200, tidak dobel)',
            'expected' => 200,
        ]);
        $this->assertSame(
            $data['snap_token'] ?? null,
            $this->recorded[2]['response']['data']['snap_token'] ?? null,
            'snap_token diharapkan sama saat dipakai kembali.'
        );

        $this->hit('GET', '/api/orders/'.$idOrder.'/payment', [
            'endpoint' => 'GET /api/orders/{id}/payment',
            'label' => 'Detail pembayaran pesanan yang baru dibuat',
            'expected' => 200,
        ]);
        $this->assertSame($data['id_payment'] ?? null, $this->recorded[3]['response']['data']['id_payment'] ?? null);

        $this->assertSame(1, Payment::count(), 'Tidak boleh ada baris payment ganda setelah reuse.');

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Admin: pesanan & pembayaran
    // ------------------------------------------------------------------

    public function test_admin_orders_akses(): void
    {
        $this->asGuest();
        $this->hit('GET', '/api/admin/orders', [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Daftar pesanan (admin) tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $uuid = (string) Str::uuid();

        $this->hit('GET', '/api/admin/orders', [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Daftar pesanan (admin) oleh pelanggan',
            'expected' => 403,
        ]);

        $this->hit('GET', '/api/admin/orders/'.$uuid, [
            'endpoint' => 'GET /api/admin/orders/{id}',
            'label' => 'Detail pesanan (admin) oleh pelanggan',
            'expected' => 403,
        ]);

        $this->hit('PUT', '/api/admin/orders/'.$uuid.'/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Ubah status pesanan oleh pelanggan',
            'json' => ['status_pesanan' => 'dibatalkan'],
            'expected' => 403,
        ]);

        $this->hit('GET', '/api/admin/payments', [
            'endpoint' => 'GET /api/admin/payments',
            'label' => 'Daftar pembayaran (admin) oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_orders_index(): void
    {
        $this->actingAsRole('admin');
        $this->makeOrder($this->makeUser('pelanggan'), [
            'nomor_pesanan' => 'ORD-A1',
            'nama_penerima' => 'Andi Wijaya',
            'status_pesanan' => 'diproses',
        ]);
        $this->makeOrder($this->makeUser('pelanggan'), [
            'nomor_pesanan' => 'ORD-A2',
            'nama_penerima' => 'Budi Santoso',
            'status_pesanan' => 'menunggu_pembayaran',
        ]);

        $this->hit('GET', '/api/admin/orders', [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Daftar semua pesanan (admin, default per_page 15)',
            'expected' => 200,
        ]);

        $meta = $this->recorded[0]['response']['meta'] ?? [];
        $this->assertSame(2, (int) ($meta['total'] ?? 0));
        $this->assertSame(15, (int) ($meta['per_page'] ?? 0));
        $this->assertNotEmpty($this->recorded[0]['response']['data'][0]['user'] ?? null, 'Relasi user tidak ikut dimuat.');

        $this->hit('GET', '/api/admin/orders?status=diproses', [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Filter pesanan (admin) status diproses',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[1]['response']['data'] ?? []);
        $this->assertSame('ORD-A1', $this->recorded[1]['response']['data'][0]['nomor_pesanan'] ?? null);

        $this->hit('GET', '/api/admin/orders?search=Santoso', [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Pencarian pesanan (admin) per nama penerima',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[2]['response']['data'] ?? []);
        $this->assertSame('ORD-A2', $this->recorded[2]['response']['data'][0]['nomor_pesanan'] ?? null);

        $this->hit('GET', '/api/admin/orders?dari='.now()->subDay()->toDateString().'&sampai='.now()->addDay()->toDateString(), [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Filter rentang tanggal pembuatan (admin)',
            'expected' => 200,
        ]);
        $this->assertCount(2, $this->recorded[3]['response']['data'] ?? []);

        $this->hit('GET', '/api/admin/orders?status=ngawur', [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Filter status tidak dikenal (admin)',
            'expected' => 422,
        ]);

        $this->hit('GET', '/api/admin/orders?search=', [
            'endpoint' => 'GET /api/admin/orders',
            'label' => 'Pencarian kosong (diperlakukan sebagai tanpa filter)',
            'expected' => 200,
        ]);
        $this->assertCount(2, $this->recorded[5]['response']['data'] ?? []);

        $this->assertRecorded();
    }

    public function test_admin_orders_show(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser('pelanggan');
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-ADM-1']);
        $this->makePayment($order);
        OrderStatusHistory::create([
            'id_order_status_history' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'status_sebelumnya' => null,
            'status_baru' => 'menunggu_pembayaran',
            'catatan' => 'Pesanan dibuat.',
            'diubah_oleh' => $user->id_user,
            'waktu_perubahan' => now(),
        ]);

        $this->hit('GET', '/api/admin/orders/'.$order->id_order, [
            'endpoint' => 'GET /api/admin/orders/{id}',
            'label' => 'Detail pesanan (admin) dengan relasi lengkap',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($user->email, $data['user']['email'] ?? null);
        $this->assertCount(1, $data['payments'] ?? []);
        $this->assertNotEmpty($data['status_histories'] ?? [], 'Kunci relasi memakai snake_case: status_histories.');
        $this->assertSame(
            $user->email,
            $data['status_histories'][0]['changed_by']['email'] ?? null,
            'Relasi changed_by pada riwayat status tidak ikut dimuat.'
        );

        $this->hit('GET', '/api/admin/orders/tidak-ada', [
            'endpoint' => 'GET /api/admin/orders/{id}',
            'label' => 'Detail pesanan (admin) tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_update_status(): void
    {
        $admin = $this->actingAsRole('admin');
        $user = $this->makeUser('pelanggan');
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-STS-1']);
        $usage = $this->makeUsage($this->makeVoucher(), $user, 'menunggu', $order);

        $this->hit('PUT', '/api/admin/orders/'.$order->id_order.'/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Ubah status: menunggu_pembayaran -> dibayar',
            'json' => ['status_pesanan' => 'dibayar', 'catatan' => 'Transfer diterima.'],
            'expected' => 200,
        ]);

        $this->assertSame('dibayar', $order->fresh()->status_pesanan);
        $this->assertSame('digunakan', $usage->fresh()->status_penggunaan, 'Voucher tidak ditandai terpakai saat pesanan dibayar.');
        $this->assertNotNull($usage->fresh()->digunakan_pada);
        $this->assertSame(
            ['order_status_histories' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect'],
            'Hanya riwayat status yang bertambah.'
        );
        $this->assertSame($admin->id_user, $order->fresh()->statusHistories()->first()->diubah_oleh);

        $this->hit('PUT', '/api/admin/orders/'.$order->id_order.'/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Ubah status ke status yang sama (tidak membuat riwayat ganda)',
            'json' => ['status_pesanan' => 'dibayar'],
            'expected' => 200,
        ]);
        $this->assertSame([], $this->recorded[1]['db_effect']);

        $this->hit('PUT', '/api/admin/orders/'.$order->id_order.'/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Ubah status ke nilai di luar daftar status',
            'json' => ['status_pesanan' => 'rahasia'],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/admin/orders/'.$order->id_order.'/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Ubah status tanpa field status_pesanan',
            'json' => [],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/admin/orders/tidak-ada/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Ubah status pesanan tidak ditemukan',
            'json' => ['status_pesanan' => 'diproses'],
            'expected' => 404,
        ]);

        $this->assertSame('dibayar', $order->fresh()->status_pesanan, 'Permintaan gagal tidak boleh mengubah status.');
        $this->assertRecorded();
    }

    public function test_admin_update_status_batal_mengembalikan_stok(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-BATAL-1'], [$catalog['variant']]);
        $usage = $this->makeUsage($this->makeVoucher(), $user, 'menunggu', $order);

        // Simulasikan reservasi stok yang masih aktif (pesanan menunggu pembayaran).
        $catalog['variant']->update(['stok_dipesan' => 1]);
        $order->update(['stok_direservasi' => true]);

        $this->hit('PUT', '/api/admin/orders/'.$order->id_order.'/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Batalkan pesanan: reservasi dipertahankan sementara dan voucher dibatalkan',
            'json' => ['status_pesanan' => 'dibatalkan', 'catatan' => 'Dibatalkan admin.'],
            'expected' => 200,
        ]);

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);

        $variant = $catalog['variant']->fresh();
        $this->assertSame(10, (int) $variant->stok, 'Stok fisik tidak berubah saat pembatalan pesanan belum dibayar.');
        $this->assertSame(1, (int) $variant->stok_dipesan, 'Reservasi dipertahankan selama satu menit setelah pembatalan.');
        $this->assertSame('dibatalkan', $usage->fresh()->status_penggunaan, 'VoucherUsage tidak dibatalkan.');
        $this->assertSame(
            [
                'order_status_histories' => '0 -> 1 (+1)',
            ],
            $this->recorded[0]['db_effect']
        );

        $this->assertSame(0, InventoryMovement::count(), 'Tidak ada pergerakan stok untuk pesanan yang belum dibayar.');

        $this->note('Pembatalan mempertahankan reservasi selama satu menit; stok fisik tetap.');

        $this->travel(61)->seconds();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame(0, (int) $variant->fresh()->stok_dipesan);

        $this->assertRecorded();
    }

    public function test_admin_payments_index(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser('pelanggan');
        $orderA = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-BAYAR-1']);
        $this->makePayment($orderA, ['status_pembayaran' => 'menunggu']);
        $orderB = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-BAYAR-2']);
        $this->makePayment($orderB, [
            'status_pembayaran' => 'dibayar',
            'provider' => 'midtrans',
            'paid_at' => now(),
        ]);

        $this->hit('GET', '/api/admin/payments', [
            'endpoint' => 'GET /api/admin/payments',
            'label' => 'Daftar pembayaran (admin, default per_page 15)',
            'expected' => 200,
        ]);

        $meta = $this->recorded[0]['response']['meta'] ?? [];
        $this->assertSame(2, (int) ($meta['total'] ?? 0));
        $this->assertSame(15, (int) ($meta['per_page'] ?? 0));
        $this->assertNotEmpty($this->recorded[0]['response']['data'][0]['order']['user'] ?? null);

        $this->hit('GET', '/api/admin/payments?status=dibayar', [
            'endpoint' => 'GET /api/admin/payments',
            'label' => 'Filter pembayaran status dibayar',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[1]['response']['data'] ?? []);

        $this->hit('GET', '/api/admin/payments?provider=gopay', [
            'endpoint' => 'GET /api/admin/payments',
            'label' => 'Filter pembayaran provider gopay (tidak ada data)',
            'expected' => 200,
        ]);
        $this->assertCount(0, $this->recorded[2]['response']['data'] ?? []);

        $this->hit('GET', '/api/admin/payments?per_page=101', [
            'endpoint' => 'GET /api/admin/payments',
            'label' => 'per_page melebihi batas 100',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_order_export_filters_inclusive_date_range_and_rejects_invalid_range(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser('pelanggan');
        $firstOrder = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-EXPORT-1']);
        $secondOrder = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-EXPORT-2']);

        DB::table('orders')->where('id_order', $firstOrder->id_order)
            ->update(['dibuat_pada' => '2026-04-10 23:59:59']);
        DB::table('orders')->where('id_order', $secondOrder->id_order)
            ->update(['dibuat_pada' => '2026-04-11 00:00:00']);

        $allOrders = $this->hit('GET', '/api/admin/orders/export', [
            'endpoint' => 'GET /api/admin/orders/export',
            'label' => 'Ekspor semua pesanan tanpa filter tanggal',
            'expected' => 200,
        ]);
        $this->assertCount(3, $this->csvRows($allOrders));

        $dateRange = $this->hit('GET', '/api/admin/orders/export?start_date=2026-04-10&end_date=2026-04-10', [
            'endpoint' => 'GET /api/admin/orders/export',
            'label' => 'Filter tanggal pesanan dengan batas akhir inklusif',
            'expected' => 200,
        ]);
        $dateRows = $this->csvRows($dateRange);
        $this->assertCount(2, $dateRows);
        $this->assertSame('ORD-EXPORT-1', $dateRows[1][0]);

        $invalidRange = $this->hit('GET', '/api/admin/orders/export?start_date=2026-04-12&end_date=2026-04-10', [
            'endpoint' => 'GET /api/admin/orders/export',
            'label' => 'Rentang tanggal pesanan yang terbalik ditolak',
            'expected' => 422,
        ]);
        $this->assertSame(422, $invalidRange->status());

        $this->assertRecorded();
    }

    public function test_admin_payment_exports_filter_dates_for_payments_and_cashbook(): void
    {
        $this->actingAsRole('admin');
        $user = $this->makeUser('pelanggan');
        $firstOrder = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-PAY-EXPORT-1']);
        $firstPayment = $this->makePayment($firstOrder);
        $secondOrder = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-PAY-EXPORT-2']);
        $secondPayment = $this->makePayment($secondOrder);

        DB::table('payments')->where('id_payment', $firstPayment->id_payment)
            ->update(['dibuat_pada' => '2026-05-01 23:59:59']);
        DB::table('payments')->where('id_payment', $secondPayment->id_payment)
            ->update(['dibuat_pada' => '2026-05-02 00:00:00']);

        $allPayments = $this->hit('GET', '/api/admin/payments/export', [
            'endpoint' => 'GET /api/admin/payments/export',
            'label' => 'Ekspor semua pembayaran tanpa filter tanggal',
            'expected' => 200,
        ]);
        $this->assertCount(3, $this->csvRows($allPayments));

        $paymentsByDate = $this->hit('GET', '/api/admin/payments/export?start_date=2026-05-01&end_date=2026-05-01', [
            'endpoint' => 'GET /api/admin/payments/export',
            'label' => 'Filter tanggal pembayaran dengan batas akhir inklusif',
            'expected' => 200,
        ]);
        $paymentRows = $this->csvRows($paymentsByDate);
        $this->assertCount(2, $paymentRows);
        $this->assertSame('ORD-PAY-EXPORT-1', $paymentRows[1][0]);

        $cashbookByDate = $this->hit('GET', '/api/admin/payments/export?format=buku-kas&start_date=2026-05-01&end_date=2026-05-01', [
            'endpoint' => 'GET /api/admin/payments/export?format=buku-kas',
            'label' => 'Filter tanggal buku kas dengan batas akhir inklusif',
            'expected' => 200,
        ]);
        $this->assertCount(2, $this->csvRows($cashbookByDate));

        $invalidRange = $this->hit('GET', '/api/admin/payments/export?start_date=2026-05-02&end_date=2026-05-01', [
            'endpoint' => 'GET /api/admin/payments/export',
            'label' => 'Rentang tanggal pembayaran yang terbalik ditolak',
            'expected' => 422,
        ]);
        $this->assertSame(422, $invalidRange->status());

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Webhook Midtrans
    // ------------------------------------------------------------------

    public function test_webhook_tanpa_signature(): void
    {
        $payload = json_encode([
            'transaction_id' => 'TRX-NO-SIG',
            'order_id' => 'ORD-WH-1',
            'transaction_status' => 'settlement',
            'gross_amount' => '150000.00',
        ]);

        $this->hit('POST', '/api/payments/midtrans/notification', [
            'endpoint' => 'POST /api/payments/midtrans/notification',
            'label' => 'Webhook tanpa signature_key (403, dicatat sebagai rejected)',
            'group' => 'Webhook Midtrans',
            'raw' => $payload,
            'expected' => 403,
        ]);

        $this->assertSame(
            ['webhook_logs' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect'],
            'Percobaan signature invalid dicatat untuk audit.'
        );
        $this->assertSame(1, WebhookLog::count());

        $log = WebhookLog::first();
        $this->assertFalse($log->signature_valid);
        $this->assertSame('rejected', $log->processing_status);
        $this->assertSame('ditolak', $log->status);
        $this->assertSame(403, (int) $log->http_status);

        $this->assertRecorded();
    }

    public function test_webhook_signature_salah(): void
    {
        config(['services.midtrans.server_key' => 'RAHASIA-UJI']);

        $payload = json_encode([
            'transaction_id' => 'TRX-BAD-SIG',
            'order_id' => 'ORD-WH-2',
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'transaction_status' => 'settlement',
            'signature_key' => str_repeat('0', 128),
        ]);

        $this->hit('POST', '/api/payments/midtrans/notification', [
            'endpoint' => 'POST /api/payments/midtrans/notification',
            'label' => 'Webhook dengan signature_key salah (403, dicatat sebagai rejected)',
            'group' => 'Webhook Midtrans',
            'raw' => $payload,
            'expected' => 403,
        ]);

        $this->assertSame(
            ['webhook_logs' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect'],
            'Percobaan signature invalid dicatat untuk audit.'
        );
        $this->assertSame(1, WebhookLog::count());

        $log = WebhookLog::first();
        $this->assertFalse($log->signature_valid);
        $this->assertSame('rejected', $log->processing_status);
        $this->assertSame('ditolak', $log->status);
        $this->assertSame(403, (int) $log->http_status);
        $this->assertStringNotContainsString('signature_key', (string) $log->catatan);

        $this->assertRecorded();
    }

    public function test_webhook_dibatasi_throttle(): void
    {
        config(['services.midtrans.webhook_throttle' => 1]);

        $payload = json_encode([
            'transaction_id' => 'TRX-THROTTLE',
            'order_id' => 'ORD-WH-THROTTLE',
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid',
        ]);

        $this->hit('POST', '/api/payments/midtrans/notification', [
            'endpoint' => 'POST /api/payments/midtrans/notification',
            'label' => 'Webhook percobaan ke-1 (403, lalu kena batas per IP)',
            'group' => 'Webhook Midtrans',
            'raw' => $payload,
            'expected' => 403,
        ]);

        $this->hit('POST', '/api/payments/midtrans/notification', [
            'endpoint' => 'POST /api/payments/midtrans/notification',
            'label' => 'Webhook percobaan ke-2 melebihi batas (429)',
            'group' => 'Webhook Midtrans',
            'raw' => $payload,
            'expected' => 429,
        ]);

        $this->assertRecorded();
    }

    public function test_webhook_signature_valid_tapi_verifikasi_status_gagal(): void
    {
        config(['services.midtrans.server_key' => 'RAHASIA-UJI']);
        $user = $this->makeUser('pelanggan');
        $order = $this->makeOrder($user, ['nomor_pesanan' => 'ORD-WH-3']);

        $orderId = 'ORD-WH-3';
        $statusCode = '200';
        $grossAmount = (string) (float) $order->total_pembayaran;
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.'RAHASIA-UJI');

        $payload = json_encode([
            'transaction_id' => 'TRX-VALID-SIG',
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'signature_key' => $signature,
        ]);

        $this->hit('POST', '/api/payments/midtrans/notification', [
            'endpoint' => 'POST /api/payments/midtrans/notification',
            'label' => 'Webhook signature valid, tetapi verifikasi status ke Midtrans gagal (500)',
            'group' => 'Webhook Midtrans',
            'raw' => $payload,
            'expected' => 500,
        ]);

        $log = WebhookLog::orderByDesc('dibuat_pada')->first();
        $this->assertSame('gagal', $log->status);
        $this->assertSame(500, (int) $log->http_status);
        $this->assertSame('Gagal memverifikasi status transaksi ke Midtrans.', $log->catatan);

        $this->assertSame('menunggu_pembayaran', $order->fresh()->status_pesanan, 'Status pesanan tidak boleh berubah.');
        $this->assertSame(
            ['webhook_logs' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect'],
            'Hanya webhook_logs yang terisi; tidak ada perubahan status.'
        );
        $this->note(
            'SDK Midtrans selalu memanggil Transaction::status() ke server Midtrans pada notifikasi; '
            .'tanpa kredensial/jaringan valid, alur berhenti di sini (gagal aman, tidak ada perubahan data).'
        );

        $this->assertRecorded();
    }

    public function test_webhook_sukses_diblokir(): void
    {
        $this->blocked(
            'POST /api/payments/midtrans/notification (jalur sukses: pending/cancel/settlement)',
            'SDK Midtrans membaca payload dari php://input; pada konteks PHPUnit (CLI) stream tersebut '
            .'kosong sehingga Transaction::status() selalu gagal (500) apa pun kredensialnya. '
            .'Jalur sukses webhook (pending, cancel + notifikasi ganda, settlement) diverifikasi '
            .'pada sesi verifikasi HTTP live (php artisan serve) — tercatat di JSONL grup '
            .'"Verifikasi Live HTTP". Settlement penuh tetap memerlukan pembayaran sungguhan di halaman Snap.'
        );
    }

    private function currentCartStatus(User $user): ?string
    {
        return Cart::where('id_user', $user->id_user)->value('status_cart');
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function csvRows(TestResponse $response): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\n|\r/', trim($response->streamedContent())) as $line) {
            if ($line !== '') {
                $rows[] = str_getcsv($line, ',', '"', '');
            }
        }

        return $rows;
    }
}
