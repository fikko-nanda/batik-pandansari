<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan peran "teknisi" ke enum users.peran tanpa mengubah data lama.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE `users` MODIFY `peran` ENUM('pelanggan','admin','teknisi') "
                ."NOT NULL DEFAULT 'pelanggan'"
            );
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::table('users')->where('peran', 'teknisi')->update(['peran' => 'pelanggan']);

            DB::statement(
                "ALTER TABLE `users` MODIFY `peran` ENUM('pelanggan','admin') "
                ."NOT NULL DEFAULT 'pelanggan'"
            );
        }
    }
};
