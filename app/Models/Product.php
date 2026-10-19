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
        'slug',
        'deskripsi',
        'status_produk',
    ];

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
}
