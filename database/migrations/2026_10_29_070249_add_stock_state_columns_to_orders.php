<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('product_variants')) {
            throw new RuntimeException(
                'Migrasi status stok order membutuhkan tabel orders dan product_variants.'
            );
        }

        if (! Schema::hasColumn('product_variants', 'stok_dipesan')) {
            throw new RuntimeException(
                'Migrasi status stok order membutuhkan kolom product_variants.stok_dipesan.'
            );
        }

        if (! Schema::hasColumn('orders', 'stok_direservasi')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('stok_direservasi')->default(false)->after('status_pesanan');
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migrasi stok order tidak dapat di-rollback aman karena status reservasi order telah dipakai.'
        );
    }
};
