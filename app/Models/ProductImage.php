<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $table = 'product_images';

    protected $primaryKey = 'id_image';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    /**
     * Jenis gambar: 'produk' = foto produk, 'size_chart' = tabel ukuran.
     */
    const TYPE_PRODUK = 'produk';

    const TYPE_SIZE_CHART = 'size_chart';

    /**
     * Nilai bawaan kolom type agar model di memori konsisten dengan database
     * (kolom have default 'produk').
     */
    protected $attributes = [
        'type' => self::TYPE_PRODUK,
    ];

    /**
     * URL publik gambar ikut disertakan pada seluruh serialisasi model.
     */
    protected $appends = ['url'];

    protected $fillable = [
        'id_image',
        'id_product',
        'id_variant',
        'path_gambar',
        'alt_text',
        'type',
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

    /**
     * URL publik berkas gambar. Disimpan sebagai accessor (bukan kolom)
     * supaya field "url" selalu tersedia di seluruh response API.
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->path_gambar));
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'id_product',
            'id_product'
        );
    }

    public function variant()
    {
        return $this->belongsTo(
            ProductVariant::class,
            'id_variant',
            'id_variant'
        );
    }
}
