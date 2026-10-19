<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $primaryKey = 'id_variant';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_variant',
        'id_product',
        'sku',
        'nama_varian',
        'warna',
        'ukuran',
        'harga',
        'stok',
        'status_varian',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'stok' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'id_product',
            'id_product'
        );
    }
}
