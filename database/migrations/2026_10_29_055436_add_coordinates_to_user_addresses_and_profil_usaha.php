<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_addresses') || ! Schema::hasTable('profil_usaha')) {
            throw new RuntimeException(
                'The user_addresses and profil_usaha tables must exist before adding shipping coordinates.'
            );
        }

        if (Schema::hasTable('user_addresses')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                if (! Schema::hasColumn('user_addresses', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('alamat_lengkap');
                }

                if (! Schema::hasColumn('user_addresses', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }

        if (Schema::hasTable('profil_usaha')) {
            Schema::table('profil_usaha', function (Blueprint $table) {
                if (! Schema::hasColumn('profil_usaha', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('alamat_lengkap');
                }

                if (! Schema::hasColumn('profil_usaha', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_addresses')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                if (Schema::hasColumn('user_addresses', 'longitude')) {
                    $table->dropColumn('longitude');
                }

                if (Schema::hasColumn('user_addresses', 'latitude')) {
                    $table->dropColumn('latitude');
                }
            });
        }

        if (Schema::hasTable('profil_usaha')) {
            Schema::table('profil_usaha', function (Blueprint $table) {
                if (Schema::hasColumn('profil_usaha', 'longitude')) {
                    $table->dropColumn('longitude');
                }

                if (Schema::hasColumn('profil_usaha', 'latitude')) {
                    $table->dropColumn('latitude');
                }
            });
        }
    }
};
