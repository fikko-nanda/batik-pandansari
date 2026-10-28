<?php

namespace Tests\Feature\Endpoints;

use App\Models\ProductVariant;

/**
 * Endpoint bulk varian admin: POST /api/admin/products/{id}/variants/bulk
 * serta aturan ukuran minimal satu varian dan keunikan SKU.
 */
class AdminProductVariantBulkEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Katalog Admin (Varian Bulk)';

    public function test_bulk_membuat_varian(): void
    {
        $product = $this->makeProduct('Produk Bulk', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Simpan tiga varian sekaligus',
            'json' => [
                'items' => [
                    ['sku' => 'BULK-M-COK', 'ukuran' => 'M', 'warna' => 'Cokelat', 'harga' => 175000, 'stok' => 10],
                    ['sku' => 'BULK-L-BIRU', 'ukuran' => 'L', 'warna' => 'Biru', 'harga' => 185000, 'stok' => 5],
                    ['sku' => 'BULK-XL-MER', 'ukuran' => 'XL', 'warna' => 'Merah', 'harga' => 195000, 'stok' => 3],
                ],
            ],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(3, $data['dibuat'] ?? null);
        $this->assertSame(0, $data['diperbarui'] ?? null);
        $this->assertCount(3, $data['items'] ?? []);
        $this->assertSame(['product_variants' => '0 -> 3 (+3)'], $this->recorded[0]['db_effect']);

        $this->assertSame(3, ProductVariant::where('id_product', $product->id_product)->count());

        $this->assertRecorded();
    }

    public function test_bulk_sku_duplikat_dalam_payload_ditolak(): void
    {
        $product = $this->makeProduct('Produk SKU Sama', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Dua item memakai SKU yang sama',
            'json' => [
                'items' => [
                    ['sku' => 'SKU-KEMBAR', 'ukuran' => 'M', 'harga' => 1000],
                    ['sku' => 'SKU-KEMBAR', 'ukuran' => 'L', 'harga' => 2000],
                ],
            ],
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['items.1.sku'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Tidak ada varian yang tersimpan.');

        $this->assertRecorded();
    }

    public function test_bulk_sku_bentrok_varian_lain_ditolak(): void
    {
        $product = $this->makeProduct('Produk Bentrok', 'aktif');
        $lain = $this->makeProduct('Produk Lain', 'aktif');
        $varianLain = $this->makeVariant($lain);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'SKU sudah dipakai varian produk lain',
            'json' => [
                'items' => [
                    ['sku' => 'SKU-BARU-1', 'ukuran' => 'M', 'harga' => 1000],
                    ['sku' => $varianLain->sku, 'ukuran' => 'L', 'harga' => 2000],
                ],
            ],
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['items.1.sku'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Tidak ada varian yang tersimpan.');

        $this->assertRecorded();
    }

    public function test_bulk_memperbarui_varian_dengan_sku_sendiri(): void
    {
        $product = $this->makeProduct('Produk Update Bulk', 'aktif');
        $varian = $this->makeVariant($product, stok: 10, harga: 150000);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Perbarui varian memakai SKU miliknya sendiri',
            'json' => [
                'items' => [
                    [
                        'id_variant' => $varian->id_variant,
                        'sku' => $varian->sku,
                        'ukuran' => 'M',
                        'harga' => 165000,
                        'stok' => 25,
                    ],
                ],
            ],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(0, $data['dibuat'] ?? null);
        $this->assertSame(1, $data['diperbarui'] ?? null);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Perbarui varian tidak menambah/menghapus baris.');

        $varian->refresh();
        $this->assertSame(165000.0, (float) $varian->harga);
        $this->assertSame(25, (int) $varian->stok);

        $this->assertRecorded();
    }

    public function test_bulk_id_variant_milik_produk_lain_ditolak(): void
    {
        $product = $this->makeProduct('Produk Asing', 'aktif');
        $lain = $this->makeProduct('Produk Asal', 'aktif');
        $varianLain = $this->makeVariant($lain);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'id_variant milik produk lain',
            'json' => [
                'items' => [
                    ['id_variant' => $varianLain->id_variant, 'sku' => 'SKU-ASING', 'ukuran' => 'M', 'harga' => 1000],
                ],
            ],
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['items.0.id_variant'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_bulk_hapus_yang_tidak_disediakan(): void
    {
        $product = $this->makeProduct('Produk Sinkron', 'aktif');
        $varianA = $this->makeVariant($product);
        $varianB = $this->makeVariant($product);
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Sinkron varian: varian yang tidak dikirim dinonaktifkan',
            'json' => [
                'items' => [
                    ['id_variant' => $varianA->id_variant, 'sku' => $varianA->sku, 'ukuran' => 'M', 'harga' => 150000],
                ],
                'hapus_yang_tidak_disediakan' => true,
            ],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(1, $data['dinonaktifkan'] ?? null);
        $this->assertSame('aktif', $varianA->fresh()->status_varian);
        $this->assertSame('nonaktif', $varianB->fresh()->status_varian, 'Varian yang tidak dikirim dinonaktifkan (soft delete).');

        $this->assertRecorded();
    }

    public function test_bulk_tanpa_ukuran_ditolak(): void
    {
        $product = $this->makeProduct('Produk Tanpa Ukuran', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Varian pertama tanpa ukuran (ditolak)',
            'json' => [
                'items' => [
                    ['sku' => 'TANPA-UKURAN', 'warna' => 'Merah', 'harga' => 1000],
                ],
            ],
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['ukuran'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Transaksi dibatalkan sehingga tidak ada varian tersimpan.');

        $this->assertRecorded();
    }

    public function test_bulk_items_kosong_ditolak(): void
    {
        $product = $this->makeProduct('Produk Bulk Kosong', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Payload items kosong',
            'json' => ['items' => []],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_bulk_akses_admin_dan_produk_tidak_ada(): void
    {
        $product = $this->makeProduct('Produk Akses', 'aktif');
        $this->asGuest();

        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Bulk varian tanpa token',
            'json' => ['items' => [['sku' => 'X-1', 'ukuran' => 'M', 'harga' => 1000]]],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('POST', '/api/admin/products/'.$product->id_product.'/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Bulk varian oleh pelanggan',
            'json' => ['items' => [['sku' => 'X-2', 'ukuran' => 'M', 'harga' => 1000]]],
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('POST', '/api/admin/products/tidak-ada/variants/bulk', [
            'endpoint' => 'POST /api/admin/products/{id}/variants/bulk',
            'label' => 'Bulk varian pada produk tidak ditemukan',
            'json' => ['items' => [['sku' => 'X-3', 'ukuran' => 'M', 'harga' => 1000]]],
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_bulk_varian_tanpa_ukuran_ditolak_saat_tambah_varian(): void
    {
        $product = $this->makeProduct('Produk Varian Tunggal', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products/'.$product->id_product.'/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Varian tanpa ukuran pada produk tanpa varian lain (ditolak)',
            'json' => ['sku' => 'TANPA-UKURAN-1', 'warna' => 'Merah', 'harga' => 1000],
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['ukuran'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_varian_dengan_ukuran_berhasil_dibuat(): void
    {
        $product = $this->makeProduct('Produk Bersize', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/products/'.$product->id_product.'/variants', [
            'endpoint' => 'POST /api/products/{id}/variants',
            'label' => 'Varian dengan ukuran valid',
            'json' => ['sku' => 'BERSIZE-1', 'ukuran' => 'All Size', 'harga' => 1000],
            'expected' => 201,
        ]);

        $this->assertSame(['product_variants' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_menonaktifkan_varian_berukuran_satu_satunya_ditolak(): void
    {
        $product = $this->makeProduct('Produk Varian Kombinasi', 'aktif');
        $varianBerukuran = $this->makeVariant($product);
        $varianTanpaUkuran = $this->makeVariant($product);
        $varianTanpaUkuran->update(['ukuran' => null, 'sku' => 'TANPA-UKURAN-2']);
        $this->actingAsRole('admin');

        $this->hit('DELETE', '/api/variants/'.$varianBerukuran->id_variant, [
            'endpoint' => 'DELETE /api/variants/{id}',
            'label' => 'Nonaktifkan satu-satunya varian berukuran (ditolak)',
            'expected' => 422,
        ]);

        $this->assertNotEmpty($this->recorded[0]['response']['errors']['ukuran'] ?? []);
        $this->assertSame('aktif', $varianBerukuran->fresh()->status_varian, 'Varian tetap aktif karena penolakan aturan ukuran.');

        $this->assertRecorded();
    }

    public function test_varian_tanpa_varian_lain_tetap_bisa_dinonaktifkan(): void
    {
        $product = $this->makeProduct('Produk Varian Tunggal Murni', 'aktif');
        $varian = $this->makeVariant($product);
        $this->actingAsRole('admin');

        $this->hit('DELETE', '/api/variants/'.$varian->id_variant, [
            'endpoint' => 'DELETE /api/variants/{id}',
            'label' => 'Nonaktifkan varian tunggal tanpa sisa varian lain',
            'expected' => 200,
        ]);

        $this->assertSame('nonaktif', $varian->fresh()->status_varian);

        $this->assertRecorded();
    }

    public function test_varian_partial_update_membedakan_null_dan_tidak_dikirim(): void
    {
        $product = $this->makeProduct('Produk Partial Varian', 'aktif');
        $varian = $this->makeVariant($product);
        $varian->update(['warna' => 'Sogan', 'ukuran' => 'M', 'nama_varian' => 'Varian Utama']);
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/variants/'.$varian->id_variant, [
            'endpoint' => 'PUT /api/variants/{id}',
            'label' => 'Partial update varian: hanya harga yang dikirim',
            'json' => ['harga' => 175000],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(175000.0, (float) ($data['harga'] ?? 0));
        $this->assertSame('Sogan', $data['warna'] ?? null, 'Field yang tidak dikirim tetap sama.');
        $this->assertSame('M', $data['ukuran'] ?? null, 'Field yang tidak dikirim tetap sama.');

        $this->hit('PUT', '/api/variants/'.$varian->id_variant, [
            'endpoint' => 'PUT /api/variants/{id}',
            'label' => 'Partial update varian: warna dikirim null',
            'json' => ['warna' => null],
            'expected' => 200,
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertArrayHasKey('warna', $data, 'Field yang dikirim null tetap ada di response.');
        $this->assertNull($data['warna'], 'Field yang dikirim null menjadi null.');
        $this->assertSame('M', $data['ukuran'] ?? null, 'Field lain tetap sama.');

        $this->assertRecorded();
    }

    public function test_produk_tanpa_varian_tetap_boleh(): void
    {
        $product = $this->makeProduct('Produk Tanpa Varian', 'aktif');
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/products/'.$product->id_product, [
            'endpoint' => 'PUT /api/products/{id}',
            'label' => 'Produk tanpa varian tetap dapat diperbarui',
            'json' => ['nama_produk' => 'Produk Tanpa Varian Diperbarui'],
            'expected' => 200,
        ]);

        $this->assertSame(0, ProductVariant::where('id_product', $product->id_product)->count());

        $this->assertRecorded();
    }
}
