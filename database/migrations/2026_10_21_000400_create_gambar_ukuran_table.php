<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel gambar panduan ukuran (size guide).
     *
     * Hanya menampung path gambar (relatif terhadap disk "public"), bukan
     * binary gambar. PK berupa BIGINT UNSIGNED auto increment. Tabel dibuat
     * hanya bila belum ada agar tidak mengganggu/menghapus data yang sudah
     * ada pada lingkungan yang sudah berjalan.
     */
    public function up(): void
    {
        if (Schema::hasTable('gambar_ukuran')) {
            return;
        }

        Schema::create('gambar_ukuran', function (Blueprint $table) {
            $table->id('id_gambar_ukuran');
            $table->string('gambar', 255);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gambar_ukuran');
    }
};
