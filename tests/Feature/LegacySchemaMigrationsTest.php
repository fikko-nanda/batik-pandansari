<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LegacySchemaMigrationsTest extends TestCase
{
    public function test_create_users_migration_preserves_existing_uuid_users_and_creates_missing_tables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->char('id_user', 36)->primary();
            $table->string('nama_pengguna');
        });

        DB::table('users')->insert([
            'id_user' => 'user-uuid',
            'nama_pengguna' => 'Pengguna Lama',
        ]);

        $migration = require database_path('migrations/0001_01_01_000000_create_users_table.php');
        $migration->up();

        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame('Pengguna Lama', DB::table('users')->value('nama_pengguna'));
        $this->assertTrue(Schema::hasTable('password_reset_tokens'));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertContains('user_id', Schema::getColumnListing('sessions'));
    }

    public function test_create_cache_migration_preserves_existing_cache_and_creates_missing_locks(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration');
        });

        DB::table('cache')->insert([
            'key' => 'keep-me',
            'value' => 'cached-data',
            'expiration' => 9999999999,
        ]);

        $migration = require database_path('migrations/0001_01_01_000001_create_cache_table.php');
        $migration->up();

        $this->assertSame('cached-data', DB::table('cache')->where('key', 'keep-me')->value('value'));
        $this->assertTrue(Schema::hasTable('cache_locks'));
    }

    public function test_product_categories_migration_creates_constraints_and_backfills_without_duplicates(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->char('id_product', 36)->primary();
            $table->char('id_category', 36);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->char('id_category', 36)->primary();
        });

        DB::table('categories')->insert(['id_category' => 'category-uuid']);
        DB::table('products')->insert([
            'id_product' => 'product-uuid',
            'id_category' => 'category-uuid',
        ]);

        $migration = require database_path('migrations/2026_10_27_062216_create_product_categories_table.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseHas('product_categories', [
            'id_product' => 'product-uuid',
            'id_category' => 'category-uuid',
        ]);
        $this->assertSame(1, DB::table('product_categories')->count());
        $this->assertSame(
            Schema::getColumnType('products', 'id_product'),
            Schema::getColumnType('product_categories', 'id_product')
        );
        $this->assertSame(
            Schema::getColumnType('categories', 'id_category'),
            Schema::getColumnType('product_categories', 'id_category')
        );

        $foreignKeys = Schema::getForeignKeys('product_categories');
        $this->assertCount(2, $foreignKeys);
        $this->assertSame(
            ['categories', 'products'],
            collect($foreignKeys)->pluck('foreign_table')->sort()->values()->all()
        );
    }

    public function test_product_categories_migration_fails_if_parent_tables_are_missing(): void
    {
        $migration = require database_path('migrations/2026_10_27_062216_create_product_categories_table.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('both products and categories tables must exist first');

        $migration->up();
    }
}
