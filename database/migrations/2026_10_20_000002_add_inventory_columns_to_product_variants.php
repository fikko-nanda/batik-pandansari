<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom pendukung modul inventaris:
     * - product_variants.stok_minimum : batas minimum stok per varian. Nilai 0
     *   berarti varian tidak pernah ditandai "menipis" sampai admin mengaturnya.
     *   Digunakan GET /api/admin/inventory/low-stock dan field status_stok.
     * - product_variants.harga_modal  : harga beli/modal per unit. NULL berarti
     *   belum diinput sehingga varian TIDAK diikutkan ke perhitungan valuasi
     *   (NULL tidak diperlakukan sebagai 0). Digunakan
     *   GET /api/admin/inventory/valuation.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'stok_minimum')) {
                $table->unsignedInteger('stok_minimum')->default(0)->after('stok');
            }

            if (! Schema::hasColumn('product_variants', 'harga_modal')) {
                $table->decimal('harga_modal', 12, 2)->nullable()->after('harga');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'stok_minimum')) {
                $table->dropColumn('stok_minimum');
            }

            if (Schema::hasColumn('product_variants', 'harga_modal')) {
                $table->dropColumn('harga_modal');
            }
        });
    }
};
