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
        'harga_modal',
        'stok',
        'stok_dipesan',
        'stok_minimum',
        'berat_gram',
        'status_varian',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'harga_modal' => 'decimal:2',
            'stok' => 'integer',
            'stok_dipesan' => 'integer',
            'stok_minimum' => 'integer',
            'berat_gram' => 'integer',
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

    public function inventoryMovements()
    {
        return $this->hasMany(
            InventoryMovement::class,
            'id_variant',
            'id_variant'
        );
    }

    public function images()
    {
        return $this->hasMany(
            ProductImage::class,
            'id_variant',
            'id_variant'
        );
    }

    /**
     * Status stok varian berdasarkan stok dan batas minimum varian.
     *
     * - kritis : stok habis (stok == 0).
     * - menipis: stok masih ada tetapi tidak lebih besar dari stok_minimum,
     *            dan stok_minimum memang diatur (lebih besar dari 0).
     *            Varian dengan stok_minimum 0 tidak pernah ditandai menipis
     *            sampai admin mengaturnya.
     * - aman   : selainnya.
     */
    public function statusStok(): string
    {
        $stok = (int) $this->stok;

        if ($stok === 0) {
            return 'kritis';
        }

        $minimum = (int) $this->stok_minimum;

        if ($minimum > 0 && $stok <= $minimum) {
            return 'menipis';
        }

        return 'aman';
    }

    /**
     * Nilai persediaan varian. NULL ketika harga_modal belum diinput —
     * NULL tidak diperlakukan sebagai 0 agar valuasi tidak menyesatkan.
     */
    public function nilaiPersediaan(): ?float
    {
        if ($this->harga_modal === null) {
            return null;
        }

        return round((int) $this->stok * (float) $this->harga_modal, 2);
    }
}
