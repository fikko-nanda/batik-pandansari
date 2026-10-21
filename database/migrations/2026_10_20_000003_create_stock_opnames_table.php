<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header catatan stock opname (pemeriksaan stok fisik gudang).
     *
     * Satu baris mewakili satu sesi pemeriksaan yang dapat memuat banyak
     * varian (rinciannya ada di tabel stock_opname_items), mengikuti pola
     * orders / order_items yang dipakai project.
     *
     * status_opname:
     * - 'draf'    : hasil hitung sudah tercatat, stok sistem BELUM disesuaikan.
     * - 'selesai' : selisih sudah diterapkan lewat POST
     *               /api/admin/inventory/adjustments. Saat ini belum ada
     *               endpoint finalisasi otomatis; status tersebut disediakan
     *               untuk alur finalisasi yang akan datang.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
