<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id_product';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $appends = [
        'image_url',
    ];

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

    /**
     * Accessor URL Gambar Utama Produk: $product->image_url
     */
    public function getImageUrlAttribute(): string
    {
        $firstImage = $this->images
            ?->where('type', ProductImage::TYPE_PRODUK)
            ?->sortByDesc('is_primary')
            ?->first()
            ?? $this->images?->first();

        $path = $firstImage?->path_gambar ?? $firstImage?->image_path;

        if ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            return Storage::disk('public')->url($path);
        }

        return asset('images/placeholder.jpg');
    }

    /**
     * Alias path berkas gambar: $product->image_path
     */
    public function getImagePathAttribute(): ?string
    {
        $firstImage = $this->images
            ?->where('type', ProductImage::TYPE_PRODUK)
            ?->sortByDesc('is_primary')
            ?->first()
            ?? $this->images?->first();

        return $firstImage?->path_gambar ?? $firstImage?->image_path;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            Category::class,
            'id_category',
            'id_category'
        );
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'product_categories',
            'id_product',
            'id_category',
            'id_product',
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
