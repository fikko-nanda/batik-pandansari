<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_promo')) {
            return;
        }

        Schema::create('cms_promo', function (Blueprint $table) {
            $table->unsignedTinyInteger('id_cms_promo')->primary()->default(1);
            $table->string('id_voucher', 36)->nullable();
            $table->string('badge', 40)->nullable();
            $table->string('tautan_tujuan', 500)->nullable();
            $table->string('gambar', 500)->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->foreign('id_voucher', 'fk_cms_promo_voucher')
                ->references('id_voucher')
                ->on('vouchers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_promo');
    }
};
