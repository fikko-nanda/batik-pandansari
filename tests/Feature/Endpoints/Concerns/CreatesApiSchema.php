<?php

namespace Tests\Feature\Endpoints\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membuat seluruh skema database domain (SQLite in-memory) yang dipakai API.
 *
 * Catatan: migrasi resmi proyek hanya berisi tabel users/cache/jobs/webhook_logs,
 * order_fulfillments dan settings, sedangkan tabel domain (products, orders,
 * shipments, dst.) hanya ada di database MySQL produksi sehingga skema dibuat
 * ulang di sini mengikuti struktur MySQL asli.
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
        'product_categories',
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
        'order_fulfillments',
        'payments',
        'inventory_movements',
        'stock_opnames',
        'stock_opname_items',
        'shipments',
        'shipment_histories',
        'settings',
        'cms_slide_hero',
        'cms_galeri',
        'cms_gambar_galeri',
        'cms_kategori',
        'profil_usaha',
        'fasilitas_usaha',
        'gambar_ukuran',
        'webhook_logs',
        'personal_access_tokens',
        'registration_otps',
        'password_reset_otps',
        'password_reset_tokens_custom',
    ];

    protected function createApiSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id_user')->primary();
            $table->string('nama_pengguna', 100);
            $table->string('email', 150)->unique();
            $table->string('nomor_telepon', 20)->nullable();
            $table->string('kata_sandi', 255)->nullable();
            $table->string('peran', 20)->default('pelanggan');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('google_id', 64)->nullable()->unique();
            $table->string('avatar', 500)->nullable();
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
            $table->string('sku', 100)->nullable()->unique();
            $table->string('slug', 180)->unique();
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('berat_produk')->nullable();
            $table->string('bahan_kain', 100)->nullable();
            $table->string('status_produk', 20)->default('draft');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->string('id_product', 36);
            $table->string('id_category', 36);
            $table->primary(['id_product', 'id_category']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->string('id_image')->primary();
            $table->string('id_product', 36);
            $table->string('id_variant', 36)->nullable();
            $table->string('path_gambar', 255);
            $table->string('alt_text', 150)->nullable();
            $table->string('type', 30)->default('produk');
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
            $table->decimal('harga_modal', 12, 2)->nullable();
            $table->unsignedInteger('stok')->default(0);
            $table->unsignedInteger('stok_dipesan')->default(0);
            $table->unsignedInteger('stok_minimum')->default(0);
            $table->unsignedInteger('berat_gram')->default(500);
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
            $table->string('gambar', 500)->nullable();
            $table->string('tipe_diskon', 20);
            $table->decimal('nilai_diskon', 12, 2);
            $table->decimal('minimal_pembelian', 12, 2)->default(0);
            $table->decimal('maksimal_diskon', 12, 2)->nullable();
            $table->unsignedInteger('kuota_total')->nullable();
            $table->unsignedInteger('kuota_per_pengguna')->default(1);
            $table->string('id_product_peruntukan', 36)->nullable();
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
            $table->timestamp('kedaluwarsa_pada')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('biaya_pengiriman', 12, 2)->default(0);
            $table->decimal('diskon', 12, 2)->default(0);
            $table->decimal('pph', 12, 2)->default(0);
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

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->string('id_stock_opname')->primary();
            $table->string('kode_opname', 50)->unique();
            $table->string('status_opname', 20)->default('draf');
            $table->string('id_user', 36);
            $table->text('catatan')->nullable();
            $table->unsignedInteger('total_item')->default(0);
            $table->integer('total_selisih')->default(0);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
            $table->index('status_opname', 'idx_stock_opnames_status');
            $table->index('id_user', 'idx_stock_opnames_user');
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->string('id_stock_opname_item')->primary();
            $table->string('id_stock_opname', 36);
            $table->string('id_variant', 36);
            $table->unsignedInteger('stok_sistem');
            $table->unsignedInteger('stok_fisik');
            $table->integer('selisih');
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
            $table->unique(
                ['id_stock_opname', 'id_variant'],
                'idx_stock_opname_items_opname_variant'
            );
            $table->index('id_stock_opname', 'idx_stock_opname_items_opname');
            $table->index('id_variant', 'idx_stock_opname_items_variant');
        });

        Schema::create('order_fulfillments', function (Blueprint $table) {
            $table->string('id_fulfillment')->primary();
            $table->string('id_order', 36);
            $table->string('status_fulfillment', 30)->default('persiapan');
            $table->text('catatan')->nullable();
            $table->string('diubah_oleh', 36)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
            $table->unsignedBigInteger('urutan')->default(0);
            $table->unique(['id_order', 'urutan'], 'idx_order_fulfillments_order_urutan');
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->string('id_shipment')->primary();
            $table->string('id_order', 36);
            $table->string('provider', 50)->default('manual');
            $table->string('kurir', 100);
            $table->string('layanan_pengiriman', 100)->nullable();
            $table->string('kode_layanan', 100)->nullable();
            $table->unsignedInteger('berat_gram')->default(0);
            $table->decimal('biaya_pengiriman', 12, 2)->default(0);
            $table->string('nomor_resi', 100)->nullable();
            $table->string('status_pengiriman', 30)->default('menunggu');
            $table->timestamp('tanggal_dikirim')->nullable();
            $table->timestamp('tanggal_diterima')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('shipment_histories', function (Blueprint $table) {
            $table->string('id_shipment_history')->primary();
            $table->string('id_shipment', 36);
            $table->string('status_sebelumnya', 30)->nullable();
            $table->string('status_baru', 30);
            $table->string('lokasi', 255)->nullable();
            $table->text('keterangan')->nullable();
            $table->dateTime('waktu_perubahan');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('id_setting')->primary();
            $table->string('kunci', 100)->unique();
            $table->text('nilai')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('cms_slide_hero', function (Blueprint $table) {
            $table->string('id_slide_hero')->primary();
            $table->string('judul', 150);
            $table->string('subjudul', 255)->nullable();
            $table->string('label_badge', 100)->nullable();
            $table->string('teks_tombol', 80);
            $table->string('tautan_tombol', 500);
            $table->string('gambar', 500);
            $table->unsignedInteger('urutan_tampilan')->default(1);
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('cms_kategori', function (Blueprint $table) {
            $table->string('id_kategori')->primary();
            $table->string('nama_kategori', 100)->unique();
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->string('gambar', 500);
            $table->string('tautan_tujuan', 500);
            $table->unsignedInteger('urutan_tampilan')->default(1);
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('cms_galeri', function (Blueprint $table) {
            $table->unsignedTinyInteger('id_galeri')->primary();
            $table->string('judul', 150);
            $table->string('subjudul', 255)->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('label_lokasi', 100)->nullable();
            $table->string('judul_lokasi', 150)->nullable();
            $table->text('deskripsi_lokasi')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('cms_gambar_galeri', function (Blueprint $table) {
            $table->string('id_gambar_galeri')->primary();
            $table->unsignedTinyInteger('id_galeri')->default(1);
            $table->string('gambar', 500);
            $table->string('teks_alternatif', 255)->nullable();
            $table->unsignedInteger('urutan_tampilan')->default(1);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('profil_usaha', function (Blueprint $table) {
            $table->unsignedTinyInteger('id_profil_usaha')->primary();
            $table->string('nama_usaha', 150);
            $table->string('deskripsi_singkat', 255)->nullable();
            $table->text('deskripsi_lengkap')->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('email_resmi', 255)->nullable();
            $table->string('nomor_whatsapp', 30)->nullable();
            $table->string('nomor_telepon', 30)->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('tautan_google_maps', 500)->nullable();
            $table->longText('jam_operasional')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('fasilitas_usaha', function (Blueprint $table) {
            $table->string('id_fasilitas')->primary();
            $table->string('nama_fasilitas', 100);
            $table->string('deskripsi', 255)->nullable();
            $table->unsignedInteger('urutan_tampilan')->default(1);
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('gambar_ukuran', function (Blueprint $table) {
            $table->id('id_gambar_ukuran');
            $table->string('gambar', 255);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('cms_promo', function (Blueprint $table) {
            $table->id('id_cms_promo');
            $table->string('id_voucher', 36)->nullable();
            $table->string('badge', 40)->nullable();
            $table->string('tautan_tujuan', 500)->nullable();
            $table->string('gambar', 500)->nullable();
            $table->unsignedInteger('urutan_tampilan')->default(0);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->string('id_webhook_log')->primary();
            $table->string('provider', 50);
            $table->string('event_id', 100)->nullable();
            $table->string('event_type', 100)->nullable();
            $table->string('provider_transaction_id', 150)->nullable();
            $table->string('idempotency_key', 150)->nullable();
            $table->string('id_order', 36)->nullable();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->longText('payload');
            $table->string('signature', 255)->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->string('status', 30)->default('diterima');
            $table->string('processing_status', 30)->default('received');
            $table->json('payload_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(1);
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('alamat_ip', 45)->nullable();
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

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('connection');
            $table->string('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
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

        foreach (['registration_otps', 'password_reset_otps'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('otp_hash');
                $table->timestamp('expires_at');
                $table->timestamp('sent_at')->nullable();
                $table->integer('attempts')->default(0);
                $table->integer('max_attempts')->default(5);
                $table->boolean('used')->default(false);
                $table->timestamps();
            });
        }

        Schema::create('password_reset_tokens_custom', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token_hash');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });
    }
}
