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
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Midtrans\Config;
use Tests\Feature\Endpoints\Concerns\FakesRajaOngkir;

/**
 * Endpoint checkout, pesanan (pelanggan), pembayaran (Midtrans),
 * pesanan/pembayaran admin, dan webhook notifikasi Midtrans.
 */
class CheckoutOrderPaymentEndpointTest extends EndpointTestCase
{
    use FakesRajaOngkir;

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
        $this->fakeRajaOngkirCost(20000);

        $this->note(
            'Ongkir dihitung backend lewat RajaOngkir (fake 20.000, berat 2 x 500 gram = 1.000 gram); '
            .'biaya_pengiriman 11.111 yang dikirim klien sengaja dipalsukan dan harus diabaikan.'
        );

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout sukses (ongkir dihitung backend, biaya_pengiriman klien diabaikan)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
                'biaya_pengiriman' => 11111,
                'catatan' => 'Tolong dikemas aman.',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('menunggu_pembayaran', $data['status_pesanan'] ?? null);
        $this->assertSame(300000.0, (float) ($data['subtotal'] ?? 0), 'Subtotal 2 x 150.000.');
        $this->assertSame(20000.0, (float) ($data['biaya_pengiriman'] ?? 0), 'Ongkir dari RajaOngkir, bukan nilai klien 11.111.');
        $this->assertSame(0.0, (float) ($data['diskon'] ?? 0));
        $this->assertSame(320000.0, (float) ($data['total_pembayaran'] ?? 0));
        $this->assertCount(1, $data['items'] ?? []);
        $this->assertMatchesRegularExpression('/^ORD-\d{8}-\d{4}$/', (string) ($data['nomor_pesanan'] ?? ''));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/calculate/domestic-cost')
                && $request['origin'] == '82935'
                && $request['destination'] == '61622'
                && $request['weight'] == 1000
                && $request['courier'] === 'jne';
        });

        $this->assertSame(
            [
                'orders' => '0 -> 1 (+1)',
                'order_items' => '0 -> 1 (+1)',
                'order_status_histories' => '0 -> 1 (+1)',
            ],
            $this->recorded[0]['db_effect']
        );

        $variant = $catalog['variant']->fresh();
        $this->assertSame(10, (int) $variant->stok, 'Stok fisik tidak berkurang saat checkout (reservasi).');
        $this->assertSame(2, (int) $variant->stok_dipesan, 'Stok direservasi sebesar jumlah pesanan.');
        $this->assertSame('checkout', $this->currentCartStatus($user));

        $this->note('Stok fisik tetap 10; 2 unit masuk stok_dipesan (tersedia = stok - stok_dipesan).');

        $this->assertRecorded();
    }

    public function test_checkout_dengan_tarif_manual(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 2);

        // Tidak ada fake RajaOngkir: pembuktian bahwa API tidak dipanggil.
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan tarif manual (RajaOngkir dilewati)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'is_manual_shipping' => true,
                'shipping_cost' => 22000,
                'shipping_courier' => 'JNE Reguler (Manual)',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('menunggu_pembayaran', $data['status_pesanan'] ?? null);
        $this->assertSame(300000.0, (float) ($data['subtotal'] ?? 0), 'Subtotal 2 x 150.000.');
        $this->assertSame(22000.0, (float) ($data['biaya_pengiriman'] ?? 0), 'Ongkir manual 22.000 dipakai apa adanya.');
        $this->assertSame(322000.0, (float) ($data['total_pembayaran'] ?? 0), '300.000 + 22.000.');
        $this->assertStringContainsString('JNE Reguler (Manual)', (string) ($data['catatan'] ?? ''), 'Label kurir manual terekam.');

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'rajaongkir'));

        $this->assertSame(
            [
                'orders' => '0 -> 1 (+1)',
                'order_items' => '0 -> 1 (+1)',
                'order_status_histories' => '0 -> 1 (+1)',
            ],
            $this->recorded[0]['db_effect']
        );

        $this->assertSame(2, (int) $catalog['variant']->fresh()->stok_dipesan, 'Stok tetap direservasi.');

        $this->assertRecorded();
    }

    public function test_checkout_tarif_manual_tanpa_destination_rajaongkir(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        // Alamat tanpa tujuan RajaOngkir: mode otomatis akan gagal, mode manual lolos.
        $address->update(['id_tujuan_rajaongkir' => null]);
        $this->addToCart($user, $catalog['variant'], 1);
        $this->fakeRajaOngkirCost(20000);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout tarif manual tanpa id_tujuan_rajaongkir (tetap sukses)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'is_manual_shipping' => true,
                'shipping_cost' => 18000,
                'shipping_courier' => 'J&T Manual',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(18000.0, (float) ($data['biaya_pengiriman'] ?? 0));

        $this->assertRecorded();
    }

    public function test_checkout_tarif_manual_wajib_shipping_cost(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 1);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout tarif manual tanpa shipping_cost (422)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'is_manual_shipping' => true,
            ],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('shipping_cost', $this->recorded[0]['response']['errors'] ?? []);

        $this->assertSame(0, (int) $catalog['variant']->fresh()->stok_dipesan);

        $this->assertRecorded();
    }

    public function test_checkout_fallback_manual_saat_kuota_rajaongkir_habis(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 2);

        // RajaOngkir membalas kuota habis (429)...
        $this->fakeRajaOngkirError(429, 429);

        // ...tetapi klien mengirim tarif manual sebagai fallback.
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout otomatis saat kuota habis -> fallback tarif manual (tetap sukses)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
                'shipping_cost' => 25000,
                'shipping_courier' => 'JNE Reguler (Fallback Manual)',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(25000.0, (float) ($data['biaya_pengiriman'] ?? 0), 'Fallback memakai ongkir manual klien.');
        $this->assertSame(325000.0, (float) ($data['total_pembayaran'] ?? 0), '300.000 + 25.000.');

        $this->assertRecorded();
    }

    public function test_checkout_kuota_habis_tanpa_fallback_tetap_429(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 2);

        $this->fakeRajaOngkirError(429, 429);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout otomatis saat kuota habis tanpa tarif manual (429)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
            ],
            'expected' => 429,
        ]);

        $this->assertSame('RAJAONGKIR_QUOTA_EXCEEDED', $this->recorded[0]['response']['message'] ?? null);
        $this->assertSame(0, (int) $catalog['variant']->fresh()->stok_dipesan, 'Tidak ada reservasi saat gagal.');

        $this->assertRecorded();
    }

    public function test_checkout_dengan_voucher_dan_ongkir(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 3);
        $voucher = $this->makeVoucher([
            'kode_voucher' => 'COCHK10',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'minimal_pembelian' => 0,
            'kuota_per_pengguna' => 5,
        ]);
        $this->fakeRajaOngkirCost(25000);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan voucher persen 10% + ongkir backend 25.000',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
                'kode_voucher' => 'COCHK10',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(450000.0, (float) ($data['subtotal'] ?? 0), 'Subtotal 3 x 150.000.');
        $this->assertSame(45000.0, (float) ($data['diskon'] ?? 0), 'Diskon 10% x 450.000.');
        $this->assertSame(430000.0, (float) ($data['total_pembayaran'] ?? 0), '450.000 + 25.000 - 45.000.');

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

    public function test_checkout_gagal(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $lain = $this->makeUser('pelanggan');
        $alamatLain = $this->makeAddress($lain);
        $this->fakeRajaOngkirCost(20000);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout tanpa id_user_address',
            'json' => [],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('id_user_address', $this->recorded[0]['response']['errors'] ?? []);
        $this->assertArrayHasKey('kurir', $this->recorded[0]['response']['errors'] ?? [], 'kurir kini wajib.');
        $this->assertArrayHasKey('layanan', $this->recorded[0]['response']['errors'] ?? [], 'layanan kini wajib.');

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan alamat milik pengguna lain',
            'json' => [
                'id_user_address' => $alamatLain->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
            ],
            'expected' => 404,
        ]);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan keranjang kosong',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
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
                'kurir' => 'jne',
                'layanan' => 'CTC',
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
                'kurir' => 'jne',
                'layanan' => 'CTC',
                'kode_voucher' => 'TIDAKADA',
            ],
            'expected' => 422,
        ]);
        $this->assertStringContainsString('Kode voucher tidak ditemukan.', implode(' ', (array) ($this->recorded[4]['response']['errors']['kode_voucher'] ?? [])));

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan biaya pengiriman negatif',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
                'biaya_pengiriman' => -1,
            ],
            'expected' => 422,
        ]);

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
        $this->fakeRajaOngkirCost(20000);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout berisi varian produk nonaktif (ditolak, transaksi dibatalkan)',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
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

    public function test_checkout_gagal_ongkir(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog(10, 150000);
        $address = $this->makeAddress($user);
        $this->addToCart($user, $catalog['variant'], 1);

        $payload = [
            'id_user_address' => $address->id_user_address,
            'kurir' => 'jne',
            'layanan' => 'CTC',
        ];

        $this->fakeRajaOngkirError(500, 500);
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout saat RajaOngkir upstream error (502, order tidak dibuat)',
            'json' => $payload,
            'expected' => 502,
        ]);
        $this->assertSame('RAJAONGKIR_UPSTREAM_ERROR', $this->recorded[0]['response']['message'] ?? null);

        $this->fakeRajaOngkirConnectionError();
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout saat koneksi RajaOngkir gagal (503, order tidak dibuat)',
            'json' => $payload,
            'expected' => 503,
        ]);
        $this->assertSame('RAJAONGKIR_UNAVAILABLE', $this->recorded[1]['response']['message'] ?? null);

        $this->fakeRajaOngkirCost(20000, 'CTC');
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan layanan yang tidak tersedia pada kurir (422)',
            'json' => ['id_user_address' => $address->id_user_address, 'kurir' => 'jne', 'layanan' => 'KILAT-SPESIAL'],
            'expected' => 422,
        ]);
        $this->assertSame('LAYANAN_TIDAK_TERSEDIA', $this->recorded[2]['response']['message'] ?? null);

        $address->update(['id_tujuan_rajaongkir' => null]);
        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout dengan alamat tanpa tujuan RajaOngkir (422)',
            'json' => $payload,
            'expected' => 422,
        ]);
        $this->assertSame('ALAMAT_TANPA_TUJUAN_RAJAONGKIR', $this->recorded[3]['response']['message'] ?? null);

        foreach ($this->recorded as $entry) {
            $this->assertArrayNotHasKey('orders', $entry['db_effect'], 'Pesanan tidak boleh terbentuk: '.$entry['label']);
            $this->assertArrayNotHasKey('order_items', $entry['db_effect'], 'Order item tidak boleh terbentuk: '.$entry['label']);
        }

        $this->assertSame(10, (int) $catalog['variant']->fresh()->stok, 'Stok berubah walau checkout ongkir gagal.');
        $this->assertSame('aktif', $this->currentCartStatus($user), 'Keranjang tidak boleh ditutup saat ongkir gagal.');

        $this->note(
            'Semua kegagalan ongkir (502 upstream, 503 koneksi, 422 layanan/tujuan) terjadi '
            .'sebelum transaksi database sehingga order, item, stok, dan keranjang tidak berubah.'
        );

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Pesanan pelanggan
    // ------------------------------------------------------------------

    public function test_orders_pelanggan_index(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $lain = $this->makeUser('pelanggan');
        $this->makeOrder($user, ['nomor_pesanan' => 'ORD-1']);
        $this->makeOrder($user, ['nomor_pesanan' => 'ORD-2', 'status_pesanan' => 'dibatalkan']);
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
        $this->fakeRajaOngkirCost(20000);

        $this->hit('POST', '/api/checkout', [
            'endpoint' => 'POST /api/checkout',
            'label' => 'Checkout sebagai persiapan pembuatan Snap',
            'json' => [
                'id_user_address' => $address->id_user_address,
                'kurir' => 'jne',
                'layanan' => 'CTC',
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
        $this->assertSame(320000.0, (float) ($data['jumlah_pembayaran'] ?? 0));
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

        $this->hit('PUT', '/api/admin/orders/'.$order->id_order.'/status', [
            'endpoint' => 'PUT /api/admin/orders/{id}/status',
            'label' => 'Batalkan pesanan: reservasi stok dilepas + voucher dibatalkan',
            'json' => ['status_pesanan' => 'dibatalkan', 'catatan' => 'Dibatalkan admin.'],
            'expected' => 200,
        ]);

        $this->assertSame('dibatalkan', $order->fresh()->status_pesanan);

        $variant = $catalog['variant']->fresh();
        $this->assertSame(10, (int) $variant->stok, 'Stok fisik tidak berubah saat pembatalan pesanan belum dibayar.');
        $this->assertSame(0, (int) $variant->stok_dipesan, 'Reservasi stok dilepas saat pembatalan.');
        $this->assertSame('dibatalkan', $usage->fresh()->status_penggunaan, 'VoucherUsage tidak dibatalkan.');
        $this->assertSame(
            [
                'order_status_histories' => '0 -> 1 (+1)',
            ],
            $this->recorded[0]['db_effect']
        );

        $this->assertSame(0, InventoryMovement::count(), 'Tidak ada pergerakan stok untuk pesanan yang belum dibayar.');

        $this->note('Pembatalan pesanan menunggu_pembayaran hanya melepas reservasi (stok_dipesan -> 0), stok fisik tetap.');

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
            'label' => 'Webhook tanpa signature_key (ditolak 403, tidak menyentuh database)',
            'group' => 'Webhook Midtrans',
            'raw' => $payload,
            'expected' => 403,
        ]);

        $this->assertSame([], $this->recorded[0]['db_effect'], 'Signature invalid tidak boleh menulis webhook_logs.');
        $this->assertSame(0, WebhookLog::count());

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
            'label' => 'Webhook dengan signature_key salah (403, tidak menyentuh database)',
            'group' => 'Webhook Midtrans',
            'raw' => $payload,
            'expected' => 403,
        ]);

        $this->assertSame([], $this->recorded[0]['db_effect'], 'Signature invalid tidak boleh menulis webhook_logs.');
        $this->assertSame(0, WebhookLog::count());

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
}
