<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyimpanan konfigurasi aplikasi yang boleh diubah lewat API admin
     * (mis. RAJAONGKIR_ORIGIN_ID dan daftar kurir aktif). Nilai awal tetap
     * berasal dari .env/config; tabel ini hanya menampung perubahan admin.
     */
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->char('id_setting', 36)->primary();
            $table->string('kunci', 100)->unique();
            $table->text('nilai')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
