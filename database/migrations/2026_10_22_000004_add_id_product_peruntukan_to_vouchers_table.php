<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')
            || Schema::hasColumn('vouchers', 'id_product_peruntukan')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->char('id_product_peruntukan', 36)
                ->nullable()
                ->after('kuota_per_pengguna');

            $table->foreign('id_product_peruntukan', 'fk_voucher_peruntukan')
                ->references('id_product')
                ->on('products');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers')
            || ! Schema::hasColumn('vouchers', 'id_product_peruntukan')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign('fk_voucher_peruntukan');
            $table->dropColumn('id_product_peruntukan');
        });
    }
};
