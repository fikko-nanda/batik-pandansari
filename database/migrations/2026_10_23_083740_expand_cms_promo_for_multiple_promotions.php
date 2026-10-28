<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_promo')) {
            return;
        }

        if (! Schema::hasColumn('cms_promo', 'urutan_tampilan')) {
            Schema::table('cms_promo', function (Blueprint $table): void {
                $table->unsignedInteger('urutan_tampilan')->default(0);
            });
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE cms_promo MODIFY id_cms_promo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cms_promo')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            if (DB::table('cms_promo')->where('id_cms_promo', '>', 255)->exists()) {
                throw new RuntimeException('Cannot roll back cms_promo while IDs exceed the unsigned tiny integer range.');
            }

            DB::statement('ALTER TABLE cms_promo MODIFY id_cms_promo TINYINT UNSIGNED NOT NULL DEFAULT 1');
        }

        if (Schema::hasColumn('cms_promo', 'urutan_tampilan')) {
            Schema::table('cms_promo', function (Blueprint $table): void {
                $table->dropColumn('urutan_tampilan');
            });
        }
    }
};
