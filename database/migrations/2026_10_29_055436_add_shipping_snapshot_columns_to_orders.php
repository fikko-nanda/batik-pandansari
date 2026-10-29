<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            throw new RuntimeException(
                'The orders table must exist before adding shipping snapshot columns.'
            );
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'jarak_km')) {
                $table->decimal('jarak_km', 8, 3)->nullable()->after('biaya_pengiriman');
            }

            if (! Schema::hasColumn('orders', 'zona_pengiriman')) {
                $table->string('zona_pengiriman', 100)->nullable()->after('jarak_km');
            }

            if (! Schema::hasColumn('orders', 'berat_pengiriman_gram')) {
                $table->unsignedInteger('berat_pengiriman_gram')->nullable()->after('zona_pengiriman');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'berat_pengiriman_gram')) {
                $table->dropColumn('berat_pengiriman_gram');
            }

            if (Schema::hasColumn('orders', 'zona_pengiriman')) {
                $table->dropColumn('zona_pengiriman');
            }

            if (Schema::hasColumn('orders', 'jarak_km')) {
                $table->dropColumn('jarak_km');
            }
        });
    }
};
