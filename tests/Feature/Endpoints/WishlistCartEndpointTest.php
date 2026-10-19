<?php

namespace Tests\Feature\Endpoints;

/**
 * Endpoint wishlist & keranjang: /api/wishlist, /api/cart
 */
class WishlistCartEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Wishlist & Keranjang';

    // ------------------------------------------------------------------
    // Wishlist
    // ------------------------------------------------------------------

    public function test_wishlist_akses_tanpa_token(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/wishlist', [
            'endpoint' => 'GET /api/wishlist',
            'label' => 'Wishlist tanpa token',
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/wishlist', [
            'endpoint' => 'POST /api/wishlist',
            'label' => 'Tambah wishlist tanpa token',
            'json' => ['id_product' => 'x'],
            'expected' => 401,
        ]);

        $this->hit('DELETE', '/api/wishlist/'.(string) \Illuminate\Support\Str::uuid(), [
            'endpoint' => 'DELETE /api/wishlist/{id}',
            'label' => 'Hapus wishlist tanpa token',
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_wishlist_index_hanya_milik_sendiri(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $other = $this->makeUser('pelanggan');
        $product = $this->seedCatalog()['product'];

        $this->seedWishlist($user, $product);
        $this->seedWishlist($other, $product);

        $this->hit('GET', '/api/wishlist', [
            'endpoint' => 'GET /api/wishlist',
            'label' => 'Daftar wishlist (hanya milik sendiri)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data, 'Wishlist pengguna lain ikut tampil.');
        $this->assertSame($product->id_product, $data[0]['product']['id_product'] ?? null);
        $this->assertNotEmpty($data[0]['product']['category'] ?? null, 'Relasi kategori tidak ikut dimuat.');

        $this->assertRecorded();
    }

    public function test_wishlist_store(): void
    {
        $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $inactive = $this->makeProduct('Produk Nonaktif', 'nonaktif');

        $this->hit('POST', '/api/wishlist', [
            'endpoint' => 'POST /api/wishlist',
            'label' => 'Tambah produk ke wishlist',
            'json' => ['id_product' => $catalog['product']->id_product],
            'expected' => 201,
        ]);

        $this->assertSame(['wishlist_items' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);

        $this->hit('POST', '/api/wishlist', [
            'endpoint' => 'POST /api/wishlist',
            'label' => 'Tambah produk yang sudah ada di wishlist',
            'json' => ['id_product' => $catalog['product']->id_product],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/wishlist', [
            'endpoint' => 'POST /api/wishlist',
            'label' => 'Tambah produk tidak ditemukan (ditolak validasi exists)',
            'json' => ['id_product' => 'produk-tidak-ada'],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/wishlist', [
            'endpoint' => 'POST /api/wishlist',
            'label' => 'Tambah produk nonaktif',
            'json' => ['id_product' => $inactive->id_product],
            'expected' => 404,
        ]);

        $this->hit('POST', '/api/wishlist', [
            'endpoint' => 'POST /api/wishlist',
            'label' => 'Tambah wishlist tanpa id_product',
            'json' => [],
            'expected' => 422,
        ]);

        $this->assertSame([], $this->recorded[4]['db_effect'], 'Baris tidak boleh bertambah saat validasi gagal.');

        $this->assertRecorded();
    }

    public function test_wishlist_destroy(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $product = $this->seedCatalog()['product'];
        $item = $this->seedWishlist($user, $product);

        $this->hit('DELETE', '/api/wishlist/'.$item->id_wishlist_item, [
            'endpoint' => 'DELETE /api/wishlist/{id}',
            'label' => 'Hapus item wishlist milik sendiri',
            'expected' => 200,
        ]);

        $this->assertSame(['wishlist_items' => '1 -> 0 (-1)'], $this->recorded[0]['db_effect']);

        $this->hit('DELETE', '/api/wishlist/'.$item->id_wishlist_item, [
            'endpoint' => 'DELETE /api/wishlist/{id}',
            'label' => 'Hapus item wishlist yang sudah dihapus (404)',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_wishlist_destroy_milik_pengguna_lain(): void
    {
        $me = $this->actingAsRole('pelanggan');
        $other = $this->makeUser('pelanggan');
        $product = $this->seedCatalog()['product'];
        $theirItem = $this->seedWishlist($other, $product);
        $myItem = $this->seedWishlist($me, $product);

        $this->hit('DELETE', '/api/wishlist/'.$theirItem->id_wishlist_item, [
            'endpoint' => 'DELETE /api/wishlist/{id}',
            'label' => 'Hapus wishlist pengguna lain (404)',
            'expected' => 404,
        ]);

        $this->assertNotNull($theirItem->fresh(), 'Wishlist pengguna lain ikut terhapus.');

        $this->hit('DELETE', '/api/wishlist/tidak-ada', [
            'endpoint' => 'DELETE /api/wishlist/{id}',
            'label' => 'Hapus wishlist tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertNotNull($myItem->fresh());
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Keranjang
    // ------------------------------------------------------------------

    public function test_keranjang_akses_tanpa_token(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/cart', [
            'endpoint' => 'GET /api/cart',
            'label' => 'Lihat keranjang tanpa token',
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah item tanpa token',
            'json' => ['id_variant' => 'x', 'jumlah' => 1],
            'expected' => 401,
        ]);

        $this->hit('DELETE', '/api/cart', [
            'endpoint' => 'DELETE /api/cart',
            'label' => 'Kosongkan keranjang tanpa token',
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_keranjang_show(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $this->addToCart($user, $catalog['variant'], 2);

        $this->hit('GET', '/api/cart', [
            'endpoint' => 'GET /api/cart',
            'label' => 'Lihat keranjang berisi item',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data['items'] ?? []);
        $this->assertSame(2, (int) ($data['items'][0]['jumlah'] ?? 0));

        $this->hit('GET', '/api/cart', [
            'endpoint' => 'GET /api/cart',
            'label' => 'Lihat keranjang lagi (tidak duplikat)',
            'expected' => 200,
        ]);

        $this->assertSame([], $this->recorded[1]['db_effect'], 'Keranjang dibuat dua kali pada satu akun.');

        $this->assertRecorded();
    }

    public function test_keranjang_store_baru_dan_gabung(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah item pertama ke keranjang (201, keranjang dibuat otomatis)',
            'json' => ['id_variant' => $catalog['variant']->id_variant, 'jumlah' => 2],
            'expected' => 201,
        ]);

        $this->assertSame('2', (string) ($this->recorded[0]['response']['data']['jumlah'] ?? ''));

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah item sama lagi (200, jumlah digabung)',
            'json' => ['id_variant' => $catalog['variant']->id_variant, 'jumlah' => 3],
            'expected' => 200,
        ]);

        $this->assertSame('5', (string) ($this->recorded[1]['response']['data']['jumlah'] ?? ''));
        $this->assertSame([], $this->recorded[1]['db_effect'], 'Baris cart_items bertambah walau item sudah ada.');

        $this->assertRecorded();
    }

    public function test_keranjang_store_validasi(): void
    {
        $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $nonaktif = $this->makeProduct('Produk Keranjang Nonaktif', 'nonaktif');
        $variantNonaktif = $this->makeVariant($nonaktif, 5, 'nonaktif');

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah item tanpa data apa pun',
            'json' => [],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah item dengan jumlah < 1',
            'json' => ['id_variant' => $catalog['variant']->id_variant, 'jumlah' => 0],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah varian tidak ditemukan',
            'json' => ['id_variant' => 'varian-tidak-ada', 'jumlah' => 1],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah varian produk nonaktif',
            'json' => ['id_variant' => $variantNonaktif->id_variant, 'jumlah' => 1],
            'expected' => 422,
        ]);

        $this->note(
            'Request yang ditolak (melebihi stok) tetap membuat baris carts kosong karena keranjang dibuat '
            .'otomatis sebelum pemeriksaan stok; efek samping minor karena GET /api/cart juga selalu membuatnya.'
        );

        $this->hit('POST', '/api/cart/items', [
            'endpoint' => 'POST /api/cart/items',
            'label' => 'Tambah item melebihi stok',
            'json' => ['id_variant' => $catalog['variant']->id_variant, 'jumlah' => 999],
            'expected' => 422,
        ]);

        foreach ($this->recorded as $entry) {
            $this->assertArrayNotHasKey(
                'cart_items',
                $entry['db_effect'],
                'Baris cart_items tidak boleh bertambah: '.$entry['label']
            );
            $this->assertArrayNotHasKey(
                'product_variants',
                $entry['db_effect'],
                'Stok varian tidak boleh berubah: '.$entry['label']
            );
        }

        $this->assertRecorded();
    }

    public function test_keranjang_update(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $item = $this->addToCart($user, $catalog['variant'], 2);

        $this->hit('PUT', '/api/cart/items/'.$item->id_cart_item, [
            'endpoint' => 'PUT /api/cart/items/{id}',
            'label' => 'Perbarui jumlah item (valid)',
            'json' => ['jumlah' => 4],
            'expected' => 200,
        ]);

        $this->assertSame('4', (string) ($this->recorded[0]['response']['data']['jumlah'] ?? ''));
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Perbaruhan jumlah tidak mengubah jumlah baris.');

        $this->hit('PUT', '/api/cart/items/'.$item->id_cart_item, [
            'endpoint' => 'PUT /api/cart/items/{id}',
            'label' => 'Perbarui jumlah melebihi stok',
            'json' => ['jumlah' => 999],
            'expected' => 422,
        ]);

        $this->assertSame(4, (int) $item->fresh()->jumlah, 'Gagal stok tidak boleh mengubah jumlah.');

        $this->hit('PUT', '/api/cart/items/'.$item->id_cart_item, [
            'endpoint' => 'PUT /api/cart/items/{id}',
            'label' => 'Perbarui jumlah tanpa field',
            'json' => [],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/cart/items/tidak-ada', [
            'endpoint' => 'PUT /api/cart/items/{id}',
            'label' => 'Perbarui item tidak ditemukan',
            'json' => ['jumlah' => 1],
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_keranjang_update_varian_nonaktif(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $product = $this->makeProduct('Produk Nonaktif Update', 'nonaktif');
        $variant = $this->makeVariant($product, 5, 'aktif');
        $item = $this->addToCart($user, $variant, 1);

        $this->hit('PUT', '/api/cart/items/'.$item->id_cart_item, [
            'endpoint' => 'PUT /api/cart/items/{id}',
            'label' => 'Perbarui jumlah saat produk nonaktif (422)',
            'json' => ['jumlah' => 2],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_keranjang_destroy(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $item = $this->addToCart($user, $catalog['variant'], 1);

        $this->hit('DELETE', '/api/cart/items/'.$item->id_cart_item, [
            'endpoint' => 'DELETE /api/cart/items/{id}',
            'label' => 'Hapus item keranjang milik sendiri',
            'expected' => 200,
        ]);

        $this->assertSame(['cart_items' => '1 -> 0 (-1)'], $this->recorded[0]['db_effect']);

        $this->hit('DELETE', '/api/cart/items/'.$item->id_cart_item, [
            'endpoint' => 'DELETE /api/cart/items/{id}',
            'label' => 'Hapus item yang sudah dihapus (404)',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_keranjang_destroy_milik_pengguna_lain(): void
    {
        $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $other = $this->makeUser('pelanggan');
        $theirItem = $this->addToCart($other, $catalog['variant'], 1);

        $this->hit('DELETE', '/api/cart/items/'.$theirItem->id_cart_item, [
            'endpoint' => 'DELETE /api/cart/items/{id}',
            'label' => 'Hapus item keranjang pengguna lain (404)',
            'expected' => 404,
        ]);

        $this->assertNotNull($theirItem->fresh(), 'Item keranjang pengguna lain ikut terhapus.');
        $this->assertRecorded();
    }

    public function test_keranjang_clear(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $catalog = $this->seedCatalog();
        $this->addToCart($user, $catalog['variant'], 1);
        $this->addToCart($user, $catalog['variant'], 1);

        $this->hit('DELETE', '/api/cart', [
            'endpoint' => 'DELETE /api/cart',
            'label' => 'Kosongkan keranjang (semua item ikut terhapus)',
            'expected' => 200,
        ]);

        $this->assertSame(['cart_items' => '2 -> 0 (-2)'], $this->recorded[0]['db_effect']);

        $this->hit('DELETE', '/api/cart', [
            'endpoint' => 'DELETE /api/cart',
            'label' => 'Kosongkan keranjang yang sudah kosong (idempoten)',
            'expected' => 200,
        ]);

        $this->assertSame([], $this->recorded[1]['db_effect']);

        $this->assertRecorded();
    }
}
