<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $primaryKey = 'id_cart_item';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_cart_item',
        'id_cart',
        'id_variant',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function cart()
    {
        return $this->belongsTo(
            Cart::class,
            'id_cart',
            'id_cart'
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
