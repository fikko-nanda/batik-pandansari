<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rincian stock opname: satu baris per varian yang diperiksa.
     *
     * stok_sistem dibaca saat opname dibuat (dengan row lock), stok_fisik
     * adalah hasil hitung fisik yang dikirim admin, dan selisih =
     * stok_fisik - stok_sistem (negatif = stok fisik kurang).
     *
     * Pembuatan baris ini TIDAK mengubah product_variants.stok; penerapan
     * selisih dilakukan lewat POST /api/admin/inventory/adjustments.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
