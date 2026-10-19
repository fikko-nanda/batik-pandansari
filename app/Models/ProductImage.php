<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $table = 'product_images';

    protected $primaryKey = 'id_image';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_image',
        'id_product',
        'path_gambar',
        'alt_text',
        'is_primary',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'urutan' => 'integer',
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
