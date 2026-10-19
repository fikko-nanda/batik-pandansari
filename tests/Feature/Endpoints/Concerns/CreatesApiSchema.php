<?php

namespace Tests\Feature\Endpoints\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat seluruh skema database domain (SQLite in-memory) yang dipakai API.
 *
 * Catatan: migrasi resmi proyek hanya berisi tabel users/cache/jobs/webhook_logs,
 * sedangkan tabel domain (products, orders, dst.) hanya ada di database MySQL
 * produksi sehingga skema dibuat ulang di sini mengikuti struktur MySQL asli.
 */
trait CreatesApiSchema
{
    /**
     * Tabel yang jumlah barisnya dipantau untuk mendeteksi efek samping request.
     */
    public const TRACKED_TABLES = [
        'users',
        'categories',
        'products',
        'product_images',
        'product_variants',
        'carts',
        'cart_items',
        'vouchers',
        'voucher_usages',
        'user_addresses',
        'wishlist_items',
        'orders',
        'order_items',
        'order_status_histories',
        'payments',
        'inventory_movements',
        'webhook_logs',
        'personal_access_tokens',
    ];

    protected function createApiSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id_user')->primary();
            $table->string('nama_pengguna', 100);
            $table->string('email', 150)->unique();
            $table->string('nomor_telepon', 20)->nullable();
            $table->string('kata_sandi', 255);
            $table->string('peran', 20)->default('pelanggan');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->string('id_category')->primary();
            $table->string('nama_kategori', 80);
            $table->string('slug', 100)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('lokasi_gambar', 200)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->string('id_product')->primary();
            $table->string('id_category', 36);
            $table->string('nama_produk', 150);
            $table->string('slug', 180)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('status_produk', 20)->default('draft');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->string('id_image')->primary();
            $table->string('id_product', 36);
            $table->string('path_gambar', 255);
            $table->string('alt_text', 150)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamp('dibuat_pada')->nullable();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->string('id_variant')->primary();
            $table->string('id_product', 36);
            $table->string('sku', 100)->unique();
            $table->string('nama_varian', 100)->nullable();
            $table->string('warna', 50)->nullable();
            $table->string('ukuran', 30)->nullable();
            $table->decimal('harga', 12, 2);
            $table->unsignedInteger('stok')->default(0);
            $table->string('status_varian', 20)->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->string('id_cart')->primary();
            $table->string('id_user', 36);
            $table->string('status_cart', 20)->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->string('id_cart_item')->primary();
            $table->string('id_cart', 36);
            $table->string('id_variant', 36);
            $table->unsignedInteger('jumlah')->default(1);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->string('id_voucher')->primary();
            $table->string('kode_voucher', 50)->unique();
            $table->string('nama_voucher', 100);
            $table->text('deskripsi')->nullable();
            $table->string('tipe_diskon', 20);
            $table->decimal('nilai_diskon', 12, 2);
            $table->decimal('minimal_pembelian', 12, 2)->default(0);
            $table->decimal('maksimal_diskon', 12, 2)->nullable();
            $table->unsignedInteger('kuota_total')->nullable();
            $table->unsignedInteger('kuota_per_pengguna')->default(1);
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai');
            $table->string('status_voucher', 20)->default('aktif');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->string('id_voucher_usage')->primary();
            $table->string('id_voucher', 36);
            $table->string('id_user', 36);
            $table->string('id_order', 36)->unique();
            $table->decimal('jumlah_diskon', 12, 2)->default(0);
            $table->string('status_penggunaan', 20)->default('menunggu');
            $table->dateTime('digunakan_pada')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('user_addresses', function (Blueprint $table) {
            $table->string('id_user_address')->primary();
            $table->string('id_user', 36);
            $table->string('label_alamat', 30);
            $table->string('nama_penerima', 100);
            $table->string('nomor_telepon', 20);
            $table->string('nama_provinsi', 60);
            $table->string('nama_kota', 60);
            $table->string('nama_kecamatan', 60);
            $table->string('kode_pos', 10);
            $table->text('alamat_lengkap');
            $table->boolean('alamat_utama')->default(false);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->string('id_wishlist_item')->primary();
            $table->string('id_user', 36);
            $table->string('id_product', 36);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->string('id_order')->primary();
            $table->string('id_user', 36);
            $table->string('nomor_pesanan', 50)->unique();
            $table->string('status_pesanan', 30)->default('menunggu_pembayaran');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('biaya_pengiriman', 12, 2)->default(0);
            $table->decimal('diskon', 12, 2)->default(0);
            $table->decimal('total_pembayaran', 12, 2);
            $table->string('nama_penerima', 150);
            $table->string('nomor_telepon_penerima', 25);
            $table->text('alamat_pengiriman');
            $table->string('kode_tujuan_pengiriman', 100)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->string('id_order_item')->primary();
            $table->string('id_order', 36);
            $table->string('id_variant', 36);
            $table->string('nama_produk', 150);
            $table->string('sku', 100);
            $table->string('warna', 50)->nullable();
            $table->string('ukuran', 30)->nullable();
            $table->decimal('harga_satuan', 12, 2);
            $table->unsignedInteger('jumlah');
            $table->decimal('subtotal', 12, 2);
            $table->timestamp('dibuat_pada')->nullable();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->string('id_order_status_history')->primary();
            $table->string('id_order', 36);
            $table->string('status_sebelumnya', 30)->nullable();
            $table->string('status_baru', 30);
            $table->text('catatan')->nullable();
            $table->string('diubah_oleh', 36)->nullable();
            $table->dateTime('waktu_perubahan');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('id_payment')->primary();
            $table->string('id_order', 36);
            $table->string('provider', 50);
            $table->string('provider_transaction_id', 150)->nullable();
            $table->string('metode_pembayaran', 50)->nullable();
            $table->decimal('jumlah_pembayaran', 12, 2);
            $table->string('status_pembayaran', 30)->default('menunggu');
            $table->string('payment_url', 500)->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->string('id_inventory_movement')->primary();
            $table->string('id_variant', 36);
            $table->string('id_user', 36)->nullable();
            $table->string('jenis_pergerakan', 20);
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('stok_sebelum');
            $table->unsignedInteger('stok_sesudah');
            $table->string('referensi', 100)->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('waktu_pergerakan');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->string('id_webhook_log')->primary();
            $table->string('provider', 50);
            $table->string('provider_transaction_id', 150)->nullable();
            $table->string('id_order', 36)->nullable();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->longText('payload');
            $table->string('signature', 255)->nullable();
            $table->string('status', 30)->default('diterima');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->string('tokenable_id', 36);
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
