<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $primaryKey = 'id_order_item';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_order_item',
        'id_order',
        'id_variant',
        'nama_produk',
        'sku',
        'warna',
        'ukuran',
        'harga_satuan',
        'jumlah',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'jumlah' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(
            Order::class,
            'id_order',
            'id_order'
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
