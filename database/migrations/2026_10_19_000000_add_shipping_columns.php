<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom pendukung integrasi ongkos kirim RajaOngkir:
     * - product_variants.berat_gram   : berat per unit untuk hitung ongkir di backend.
     * - user_addresses.id_tujuan_rajaongkir : ID tujuan (per kelurahan) hasil
     *   GET /api/shipping/destinations, dipakai POST /api/shipping/cost & checkout.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'berat_gram')) {
                $table->unsignedInteger('berat_gram')->default(500)->after('stok');
            }
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('user_addresses', 'id_tujuan_rajaongkir')) {
                $table->string('id_tujuan_rajaongkir', 20)->nullable()->after('alamat_utama');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'berat_gram')) {
                $table->dropColumn('berat_gram');
            }
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('user_addresses', 'id_tujuan_rajaongkir')) {
                $table->dropColumn('id_tujuan_rajaongkir');
            }
        });
    }
};
