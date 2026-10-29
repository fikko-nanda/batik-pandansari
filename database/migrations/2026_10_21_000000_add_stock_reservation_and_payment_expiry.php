<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom pendukung reservasi stok & batas waktu pembayaran:
     * - product_variants.stok_dipesan : jumlah unit yang sedang direservasi
     *   oleh pesanan yang menunggu pembayaran. Stok tersedia = stok - stok_dipesan.
     * - orders.kedaluwarsa_pada : batas waktu pembayaran (15 menit sejak pesanan
     *   dibuat). Setelah lewat, pesanan menunggu pembayaran di-expire dan
     *   reservasi stok dilepas.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'stok_dipesan')) {
                $table->unsignedInteger('stok_dipesan')->default(0)->after('stok');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'kedaluwarsa_pada')) {
                $table->timestamp('kedaluwarsa_pada')->nullable()->after('status_pesanan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'stok_dipesan')) {
                $table->dropColumn('stok_dipesan');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'kedaluwarsa_pada')) {
                $table->dropColumn('kedaluwarsa_pada');
            }
        });
    }
};
