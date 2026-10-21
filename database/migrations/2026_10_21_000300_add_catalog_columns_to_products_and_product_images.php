<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom katalog pada tabel yang sudah ada (TIDAK membuat
     * tabel baru). Tabel domain (products, product_variants, product_images)
     * dibuat langsung di MySQL produksi sehingga migration ini memakai
     * Schema::hasTable() sebagai penjaga: bila tabelnya belum ada, migration
     * dilewati tanpa error.
     *
     * products:
     * - sku         : SKU tingkat produk (induk). NULLABLE + UNIQUE sehingga
     *                  produk lama yang belum punya SKU tetap valid dan banyak
     *                  produk boleh ber-SKU NULL (MySQL mengizinkan banyak NULL
     *                  pada unique index).
     * - berat_produk: berat produk dalam gram. NULL berarti belum diisi;
     *                  berat per varian tetap memakai product_variants.berat_gram.
     * - bahan_kain  : material kain, mis. "Katun", "Sutra".
     *
     * product_images:
     * - type        : jenis gambar. 'produk' = foto produk (default),
     *                  'size_chart' = tabel ukuran. Size chart disimpan di
     *                  tabel yang sama dengan id_variant NULL.
     * - id_variant  : gambar yang menempel pada varian tertentu. NULL untuk
     *                  foto produk umum maupun size chart.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sku')) {
                $table->string('sku', 100)->nullable()->after('nama_produk');
            }

            if (! Schema::hasColumn('products', 'berat_produk')) {
                $table->unsignedInteger('berat_produk')->nullable()->after('deskripsi');
            }

            if (! Schema::hasColumn('products', 'bahan_kain')) {
                $table->string('bahan_kain', 100)->nullable()->after('berat_produk');
            }
        });

        $this->syncSkuUniqueIndex();

        if (! Schema::hasTable('product_images')) {
            return;
        }

        Schema::table('product_images', function (Blueprint $table) {
            if (! Schema::hasColumn('product_images', 'type')) {
                $table->string('type', 30)->default('produk')->after('alt_text');
            }

            if (! Schema::hasColumn('product_images', 'id_variant')) {
                $table->string('id_variant', 36)->nullable()->after('id_product');
            }
        });

        $this->syncImageIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_images')) {
            Schema::table('product_images', function (Blueprint $table) {
                if (Schema::hasColumn('product_images', 'id_variant')) {
                    $table->dropColumn('id_variant');
                }

                if (Schema::hasColumn('product_images', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'bahan_kain')) {
                    $table->dropColumn('bahan_kain');
                }

                if (Schema::hasColumn('products', 'berat_produk')) {
                    $table->dropColumn('berat_produk');
                }

                if (Schema::hasColumn('products', 'sku')) {
                    $table->dropColumn('sku');
                }
            });
        }
    }

    /**
     * Unique index untuk products.sku dibuat terpisah karena harus diabaikan
     * bila sudah ada (produk lama bisa saja sudah punya index manual).
     */
    private function syncSkuUniqueIndex(): void
    {
        if ($this->hasIndex('products', 'uq_products_sku')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unique('sku', 'uq_products_sku');
        });
    }

    /**
     * Index penyaring gambar (id_product + type) dan foreign key gambar
     * varian. Idempoten: dilewati bila sudah terpasang.
     */
    private function syncImageIndexes(): void
    {
        if (! $this->hasIndex('product_images', 'idx_images_product_type')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->index(['id_product', 'type'], 'idx_images_product_type');
            });
        }

        if (! Schema::hasTable('product_variants')) {
            return;
        }

        if ($this->hasIndex('product_images', 'fk_images_variant')) {
            return;
        }

        Schema::table('product_images', function (Blueprint $table) {
            $table->foreign('id_variant', 'fk_images_variant')
                ->references('id_variant')
                ->on('product_variants')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Mengecek keberadaan index berdasarkan nama (bukan kolom) supaya aman
     * dipakai lintas database.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
