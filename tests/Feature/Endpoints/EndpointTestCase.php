<?php

namespace Tests\Feature\Endpoints;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Models\WishlistItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Feature\Endpoints\Concerns\CreatesApiSchema;
use Tests\Feature\Endpoints\Concerns\ResultLogger;
use Tests\TestCase;
use Throwable;

/**
 * Basis seluruh pengujian endpoint API.
 *
 * Setiap permintaan HTTP nyata dikirim lewat kernel Laravel, respons dicatat
 * apa adanya (status code + body JSON) ke berkas JSONL, termasuk efek perubahan
 * jumlah baris pada tabel database terkait.
 */
abstract class EndpointTestCase extends TestCase
{
    use CreatesApiSchema;

    protected static string $group = 'Umum';

    protected ?string $token = null;

    protected string $role = 'publik';

    protected array $recorded = [];

    protected array $pendingNotes = [];

    protected static bool $loggerReady = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! self::$loggerReady) {
            ResultLogger::reset();
            self::$loggerReady = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createApiSchema();

        $this->token = null;
        $this->role = 'publik';
        $this->recorded = [];
        $this->pendingNotes = [];
    }

    // ------------------------------------------------------------------
    // Utilitas data
    // ------------------------------------------------------------------

    protected function makeUser(string $peran = 'pelanggan', string $plain = 'password123'): User
    {
        return User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => ucfirst($peran).' '.Str::upper(Str::random(4)),
            'email' => $peran.'_'.Str::lower(Str::random(10)).'@example.com',
            'nomor_telepon' => '0812'.random_int(1000000, 9999999),
            'kata_sandi' => $plain,
            'peran' => $peran,
        ]);
    }

    /**
     * Masuk lewat endpoint login sungguhan sehingga token Sanctum asli didapat.
     */
    protected function loginAs(User $user, string $plain = 'password123', string $role = 'pelanggan'): string
    {
        $response = $this->json('POST', '/api/login', [
            'email' => $user->email,
            'kata_sandi' => $plain,
        ]);

        $token = $response->json('data.token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Gagal mendapatkan token login untuk '.$user->email.' ('.$response->status().')');
        }

        $this->token = $token;
        $this->role = $role;

        // Guard auth Sanctum di-cache pada kontainer test; dibersihkan agar
        // request berikutnya benar-benar membaca token terbaru (seperti produksi).
        $this->app->make('auth')->forgetGuards();

        return $token;
    }

    protected function actingAsRole(string $peran, string $plain = 'password123'): User
    {
        $user = $this->makeUser($peran, $plain);
        $this->loginAs($user, $plain, $peran);

        return $user;
    }

    protected function asGuest(): void
    {
        $this->token = null;
        $this->role = 'publik';
    }

    protected function seedCategory(string $id = 'cat-1', string $nama = 'Batik Tulis'): Category
    {
        return Category::create([
            'id_category' => $id,
            'nama_kategori' => $nama,
            'slug' => Str::slug($nama),
            'deskripsi' => 'Kategori '.$nama,
            'status_aktif' => true,
        ]);
    }

    protected function makeProduct(string $nama, string $status = 'aktif', string $idCategory = 'cat-1'): Product
    {
        $product = Product::create([
            'id_product' => (string) Str::uuid(),
            'id_category' => $idCategory,
            'nama_produk' => $nama,
            'slug' => Str::slug($nama).'-'.Str::lower(Str::random(5)),
            'deskripsi' => 'Deskripsi '.$nama,
            'status_produk' => $status,
        ]);

        $product->categories()->sync([$idCategory]);

        return $product;
    }

    protected function makeVariant(Product $product, int $stok = 10, string $status = 'aktif', float $harga = 150000): ProductVariant
    {
        return ProductVariant::create([
            'id_variant' => (string) Str::uuid(),
            'id_product' => $product->id_product,
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'nama_varian' => 'Varian Utama',
            'warna' => 'Sogan',
            'ukuran' => 'All Size',
            'harga' => $harga,
            'stok' => $stok,
            'status_varian' => $status,
        ]);
    }

    protected function makeImage(Product $product, bool $primary = true): ProductImage
    {
        return ProductImage::create([
            'id_image' => (string) Str::uuid(),
            'id_product' => $product->id_product,
            'path_gambar' => 'products/sample.jpg',
            'alt_text' => 'Gambar '.$product->nama_produk,
            'is_primary' => $primary,
            'urutan' => 0,
        ]);
    }

    protected function makeAddress(User $user, bool $utama = true): UserAddress
    {
        return UserAddress::create([
            'id_user_address' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'label_alamat' => 'Rumah',
            'nama_penerima' => $user->nama_pengguna,
            'nomor_telepon' => '081234567890',
            'nama_provinsi' => 'Jawa Tengah',
            'nama_kota' => 'Pekalongan',
            'nama_kecamatan' => 'Pekalongan Barat',
            'kode_pos' => '51133',
            'alamat_lengkap' => 'Jl. Batik No. 12',
            'alamat_utama' => $utama,
        ]);
    }

    protected function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'id_voucher' => (string) Str::uuid(),
            'kode_voucher' => 'VCH'.Str::upper(Str::random(6)),
            'nama_voucher' => 'Voucher Uji',
            'deskripsi' => 'Voucher untuk pengujian',
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

    protected function makeUsage(Voucher $voucher, User $user, string $status = 'menunggu', ?Order $order = null): VoucherUsage
    {
        return VoucherUsage::create([
            'id_voucher_usage' => (string) Str::uuid(),
            'id_voucher' => $voucher->id_voucher,
            'id_user' => $user->id_user,
            'id_order' => $order?->id_order ?? (string) Str::uuid(),
            'jumlah_diskon' => 10000,
            'status_penggunaan' => $status,
            'digunakan_pada' => $status === 'digunakan' ? now() : null,
        ]);
    }

    protected function addToCart(User $user, ProductVariant $variant, int $jumlah = 1): CartItem
    {
        $cart = Cart::firstOrCreate(
            ['id_user' => $user->id_user, 'status_cart' => 'aktif'],
            ['id_cart' => (string) Str::uuid()]
        );

        return CartItem::create([
            'id_cart_item' => (string) Str::uuid(),
            'id_cart' => $cart->id_cart,
            'id_variant' => $variant->id_variant,
            'jumlah' => $jumlah,
        ]);
    }

    protected function makeOrder(
        User $user,
        array $overrides = [],
        array $itemVariants = []
    ): Order {
        $order = Order::create(array_merge([
            'id_order' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'nomor_pesanan' => 'ORD-'.now()->format('Ymd').'-'.random_int(1000, 9999),
            'status_pesanan' => 'menunggu_pembayaran',
            'subtotal' => 150000,
            'biaya_pengiriman' => 0,
            'diskon' => 0,
            'total_pembayaran' => 150000,
            'nama_penerima' => $user->nama_pengguna,
            'nomor_telepon_penerima' => '081234567890',
            'alamat_pengiriman' => 'Jl. Batik No. 12, Pekalongan',
            'kode_tujuan_pengiriman' => 'Pekalongan',
            'catatan' => null,
        ], $overrides));

        foreach ($itemVariants as $variant) {
            OrderItem::create([
                'id_order_item' => (string) Str::uuid(),
                'id_order' => $order->id_order,
                'id_variant' => $variant->id_variant,
                'nama_produk' => $variant->product->nama_produk,
                'sku' => $variant->sku,
                'warna' => $variant->warna,
                'ukuran' => $variant->ukuran,
                'harga_satuan' => $variant->harga,
                'jumlah' => 1,
                'subtotal' => $variant->harga,
            ]);
        }

        return $order;
    }

    protected function makePayment(Order $order, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'id_payment' => (string) Str::uuid(),
            'id_order' => $order->id_order,
            'provider' => 'midtrans',
            'provider_transaction_id' => null,
            'metode_pembayaran' => null,
            'jumlah_pembayaran' => $order->total_pembayaran,
            'status_pembayaran' => 'menunggu',
            'payment_url' => null,
            'payment_reference' => null,
            'expires_at' => null,
            'paid_at' => null,
        ], $overrides));
    }

    protected function seedWishlist(User $user, Product $product): WishlistItem
    {
        return WishlistItem::create([
            'id_wishlist_item' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'id_product' => $product->id_product,
        ]);
    }

    /**
     * Siapkan katalog lengkap: 1 kategori, 1 produk aktif, 1 varian, 1 gambar.
     *
     * @return array{product: Product, variant: ProductVariant}
     */
    protected function seedCatalog(int $stok = 10, float $harga = 150000): array
    {
        $this->seedCategory();
        $product = $this->makeProduct('Batik Parang Seling');
        $variant = $this->makeVariant($product, $stok, 'aktif', $harga);
        $this->makeImage($product);

        return ['product' => $product, 'variant' => $variant];
    }

    // ------------------------------------------------------------------
    // Perekam request
    // ------------------------------------------------------------------

    protected function note(string $text): void
    {
        $entry = [
            'type' => 'note',
            'group' => static::$group,
            'test' => static::class.'::'.$this->name(),
            'text' => $text,
        ];

        ResultLogger::write($entry);
        $this->pendingNotes[] = $text;
    }

    protected function blocked(string $endpoint, string $reason): void
    {
        ResultLogger::write([
            'type' => 'blocked',
            'group' => static::$group,
            'test' => static::class.'::'.$this->name(),
            'endpoint' => $endpoint,
            'reason' => $reason,
        ]);

        $this->markTestSkipped($reason);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function hit(string $method, string $url, array $options = []): ?TestResponse
    {
        $headers = $options['headers'] ?? [];

        if ($this->token !== null && ! isset($headers['Authorization'])) {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }

        $body = $options['json'] ?? ($options['body'] ?? []);
        $expected = $options['expected'] ?? null;
        $label = $options['label'] ?? (strtoupper($method).' '.$url);
        $endpoint = $options['endpoint'] ?? $this->normalizeEndpoint($method, $url);

        $requestRecord = array_key_exists('raw', $options)
            ? ['_raw' => $options['raw']]
            : (array_key_exists('files', $options)
                ? ['_multipart' => array_keys($options['files']), 'fields' => (object) $body]
                : $body);

        $before = $this->tableCounts();
        $exception = null;
        $response = null;

        // Tiap request diperlakukan seperti proses PHP baru (produksi):
        // guard auth yang di-cache kontainer dibersihkan lebih dulu.
        $this->app->make('auth')->forgetGuards();

        try {
            if (array_key_exists('raw', $options)) {
                $server = $this->transformHeadersToServerVars($headers);
                $server['CONTENT_TYPE'] = 'application/json';
                $server['HTTP_ACCEPT'] = 'application/json';

                $response = $this->call($method, $url, [], [], [], $server, $options['raw']);
            } elseif (array_key_exists('files', $options)) {
                // Berkas ikut di dalam body; kernel Laravel mengekstraknya
                // menjadi berkas unggahan sungguhan.
                $response = $this->call(
                    $method,
                    $url,
                    array_merge($body, $options['files']),
                    [],
                    [],
                    $this->transformHeadersToServerVars($headers)
                );
            } else {
                $response = $this->json($method, $url, $body, $headers);
            }
        } catch (Throwable $e) {
            $exception = $e::class.': '.$e->getMessage();
        }

        $status = $response ? $response->getStatusCode() : 0;
        $payload = $this->responsePayload($response);

        $ok = $expected === null
            ? true
            : in_array($status, (array) $expected, true);

        $after = $this->tableCounts();

        $entry = [
            'type' => 'request',
            'group' => $options['group'] ?? static::$group,
            'test' => static::class.'::'.$this->name(),
            'label' => $label,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'url' => $url,
            'auth' => $this->authLabel(),
            'request' => $requestRecord,
            'expected_status' => is_array($expected) ? $expected : $expected,
            'actual_status' => $status,
            'ok' => $ok,
            'response' => $payload,
            'exception' => $exception,
            'db_effect' => $this->diffCounts($before, $after),
            'notes' => $this->pendingNotes,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->pendingNotes = [];

        ResultLogger::write($entry);
        $this->recorded[] = $entry;

        return $response;
    }

    /**
     * Menggagalkan test bila ada request yang tidak sesuai harapan.
     */
    protected function assertRecorded(): void
    {
        $messages = [];

        foreach ($this->recorded as $entry) {
            if ($entry['ok'] === true) {
                continue;
            }

            $messages[] = sprintf(
                '%s | %s | diharapkan status %s, aktual %s%s',
                $entry['label'],
                $entry['endpoint'],
                json_encode($entry['expected_status']),
                $entry['actual_status'],
                $entry['exception'] !== null ? ' | exception: '.$entry['exception'] : ''
            );
        }

        $this->assertSame(
            [],
            $messages,
            'Permintaan tidak sesuai harapan ('.count($messages)." kasus):\n".implode("\n", $messages)
        );

        $this->assertNotEmpty($this->recorded, 'Tidak ada request yang direkam pada test ini.');
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    protected function authLabel(): string
    {
        return $this->token === null
            ? 'Tanpa autentikasi (publik)'
            : 'Bearer token ('.$this->role.')';
    }

    protected function normalizeEndpoint(string $method, string $url): string
    {
        $path = preg_replace(
            '/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/',
            '{id}',
            $url
        );

        return strtoupper($method).' '.$path;
    }

    protected function responsePayload(?TestResponse $response): array
    {
        if ($response === null) {
            return ['_error' => 'Request tidak menghasilkan respons (exception pada kernel).'];
        }

        try {
            $json = $response->json();
        } catch (Throwable) {
            $json = null;
        }

        if (is_array($json)) {
            return $json;
        }

        return ['_body' => $response->getContent()];
    }

    protected function tableCounts(): array
    {
        $counts = [];

        foreach (self::TRACKED_TABLES as $table) {
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (Throwable) {
                $counts[$table] = null;
            }
        }

        return $counts;
    }

    protected function diffCounts(array $before, array $after): array
    {
        $diff = [];

        foreach ($after as $table => $count) {
            $previous = $before[$table] ?? null;

            if ($previous === null || $count === null || $previous === $count) {
                continue;
            }

            $delta = $count - $previous;
            $diff[$table] = sprintf('%d -> %d (%s%d)', $previous, $count, $delta > 0 ? '+' : '', $delta);
        }

        return $diff;
    }
}
