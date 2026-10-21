<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pemenuhan (fulfillment) pesanan oleh admin: persiapan barang,
     * pengemasan, hingga siap kirim. Berbeda dari shipments (perjalanan
     * paket lewat kurir) dan order_status_histories (status pesanan).
     */
    public function up(): void
    {
        if (Schema::hasTable('order_fulfillments')) {
            return;
        }

        Schema::create('order_fulfillments', function (Blueprint $table) {
            $table->char('id_fulfillment', 36)->primary();
            $table->char('id_order', 36);
            $table->string('status_fulfillment', 30)->default('persiapan');
            $table->text('catatan')->nullable();
            $table->char('diubah_oleh', 36)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
            $table->unsignedBigInteger('urutan')->default(0);

            $table->index('id_order', 'idx_order_fulfillments_order');
            $table->index('status_fulfillment', 'idx_order_fulfillments_status');
            $table->unique(['id_order', 'urutan'], 'idx_order_fulfillments_order_urutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_fulfillments');
    }
};
