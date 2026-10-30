<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('zona_pengiriman')) {
            return;
        }

        Schema::create('zona_pengiriman', function (Blueprint $table) {
            $table->string('id_zona', 36)->primary();
            $table->string('nama_zona', 100);
            $table->decimal('jarak_min_km', 8, 2);
            $table->decimal('jarak_maks_km', 8, 2);
            $table->decimal('biaya_dasar', 12, 2)->default(0);
            $table->decimal('biaya_per_kg', 12, 2)->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->index(['jarak_min_km', 'jarak_maks_km'], 'idx_zona_jarak');
            $table->index('status_aktif', 'idx_zona_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zona_pengiriman');
    }
};
