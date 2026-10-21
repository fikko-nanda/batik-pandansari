<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id_product';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_product',
        'id_category',
        'nama_produk',
        'sku',
        'slug',
        'deskripsi',
        'berat_produk',
        'bahan_kain',
        'status_produk',
    ];

    protected function casts(): array
    {
        return [
            'berat_produk' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(
            Category::class,
            'id_category',
            'id_category'
        );
    }

    public function variants()
    {
        return $this->hasMany(
            ProductVariant::class,
            'id_product',
            'id_product'
        );
    }

    public function images()
    {
        return $this->hasMany(
            ProductImage::class,
            'id_product',
            'id_product'
        );
    }

    /**
     * Size chart (tabel ukuran) produk. Disimpan pada tabel product_images
     * dengan type = 'size_chart' dan id_variant NULL.
     */
    public function sizeChart()
    {
        return $this->hasOne(
            ProductImage::class,
            'id_product',
            'id_product'
        )->where('type', ProductImage::TYPE_SIZE_CHART);
    }
}
