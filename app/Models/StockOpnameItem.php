<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    protected $table = 'stock_opname_items';

    protected $primaryKey = 'id_stock_opname_item';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_stock_opname_item',
        'id_stock_opname',
        'id_variant',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    public function opname()
    {
        return $this->belongsTo(
            StockOpname::class,
            'id_stock_opname',
            'id_stock_opname'
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
