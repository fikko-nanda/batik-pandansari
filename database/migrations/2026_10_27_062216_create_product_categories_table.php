<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            ! DB::connection()->pretending()
            && (! Schema::hasTable('products') || ! Schema::hasTable('categories'))
        ) {
            throw new RuntimeException(
                'Cannot create product_categories: both products and categories tables must exist first.'
            );
        }

        if (! Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
                $table->char('id_product', 36);
                $table->char('id_category', 36);
                $table->primary(['id_product', 'id_category'], 'pk_product_categories');
                $table->index('id_category', 'idx_product_categories_category');
                $table->foreign('id_product', 'fk_product_categories_product')
                    ->references('id_product')
                    ->on('products')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreign('id_category', 'fk_product_categories_category')
                    ->references('id_category')
                    ->on('categories')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        DB::table('product_categories')->insertUsing(
            ['id_product', 'id_category'],
            DB::table('products')
                ->whereNotNull('id_category')
                ->whereNotExists(fn ($query) => $query
                    ->selectRaw('1')
                    ->from('product_categories')
                    ->whereColumn('product_categories.id_product', 'products.id_product')
                    ->whereColumn('product_categories.id_category', 'products.id_category')
                )
                ->select('products.id_product', 'products.id_category')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'The product category migration is forward-only because multiple category links cannot be restored losslessly.'
        );
    }
};
