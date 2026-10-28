<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resolusi bentrok nama tabel stock opname.
     *
     * Basis data `db_pandansari` SUDAH memiliki tabel `stock_opnames` lama
     * berstruktur rata per-varian (kolom: id_variant, stok_sistem, stok_fisik,
     * selisih, tanggal_pemeriksaan) yang tidak pernah tercatat sebagai
     * migration di repositori ini. Sementara implementasi saat ini memakai
     * model desain header + rincian: tabel `stock_opnames` sebagai header sesi
     * (kode_opname, status_opname, total_item, total_selisih) dan tabel
     * `stock_opname_items` untuk rincian per varian.
     *
     * Tanpa penanganan ini, migration
     * `2026_10_20_000003_create_stock_opnames_table` akan gagal
     * ("Base table or view already exists") karena tabelnya sudah ada.
     *
     * Migration ini:
     *  1. Hanya bertindak bila `stock_opnames` ada DAN berstruktur lama
     *     (ditandai: TIDAK punya kolom `kode_opname`).
     *  2. Mengganti nama tabel lama menjadi `stock_opnames_legacy`
     *     (data lama tetap utuh, tidak dihapus) agar tidak terjadi duplikasi.
     *  3. Idempotent: bila sudah berstruktur baru atau sudah pernah di-rename,
     *     tidak melakukan apa-apa.
     */
    public function up(): void
    {
        if (! Schema::hasTable('stock_opnames')) {
            return;
        }

        $strukturLama = ! Schema::hasColumn('stock_opnames', 'kode_opname');

        if (! $strukturLama) {
            return;
        }

        if (Schema::hasTable('stock_opnames_legacy')) {
            // Tujuan rename sudah ada (mis. sisa percobaan sebelumnya). Jangan
            // timpa: cukup biarkan agar migrasi berikutnya bisa membuat tabel
            // header yang baru. Tidak ada data yang dihapus di sini.
            return;
        }

        Schema::rename('stock_opnames', 'stock_opnames_legacy');
    }

    /**
     * Reverse the migrations.
     *
     * Mengembalikan nama tabel lama hanya bila `stock_opnames` baru sudah
     * tidak ada, sehingga tidak menimpa struktur baru.
     */
    public function down(): void
    {
        if (Schema::hasTable('stock_opnames_legacy') && ! Schema::hasTable('stock_opnames')) {
            Schema::rename('stock_opnames_legacy', 'stock_opnames');
        }
    }
};
