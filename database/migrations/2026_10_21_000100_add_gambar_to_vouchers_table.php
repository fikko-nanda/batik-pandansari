<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom gambar voucher dipakai sebagai konten Banner Promo di beranda
     * (menampilkan foto/gambar beserta nama voucher).
     */
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'gambar')) {
                $table->string('gambar', 500)->nullable()->after('deskripsi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'gambar')) {
                $table->dropColumn('gambar');
            }
        });
    }
};
