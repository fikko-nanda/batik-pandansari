<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('google_id', 64)->nullable()->unique()->after('email_verified_at');
                $table->string('avatar', 500)->nullable()->after('google_id');
            });
        }

        if ($this->kataSandiSudahNullable()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('kata_sandi', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['google_id']);
                $table->dropColumn(['google_id', 'avatar']);
            });
        }

        if ($this->kataSandiSudahNullable()) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('kata_sandi', 255)->nullable(false)->change();
            });
        }
    }

    /**
     * Kolom kata_sandi wajib nullable agar pengguna khusus Google bisa
     * dibuat tanpa sandi. Dicek dulu karena ->change() pada MySQL butuh
     * informasi kolom yang akurat dan aman dijalankan berulang.
     */
    private function kataSandiSudahNullable(): bool
    {
        foreach (Schema::getColumns('users') as $column) {
            if (($column['name'] ?? null) === 'kata_sandi') {
                return (bool) ($column['nullable'] ?? false);
            }
        }

        return false;
    }
};
