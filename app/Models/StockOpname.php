<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    /**
     * Status catatan stock opname.
     *
     * - 'draf'    : hasil hitung sudah tercatat, stok sistem BELUM disesuaikan.
     * - 'selesai' : selisih sudah diterapkan ke product_variants.stok. Dapat
     *               dicapai lewat POST /api/admin/stock-opnames/{id}/complete
     *               (finalisasi seluruh item sekaligus dalam satu transaksi)
     *               atau per varian lewat POST /api/admin/inventory/adjustments.
     */
    public const STATUSES = [
        'draf',
        'selesai',
    ];

    protected $table = 'stock_opnames';

    protected $primaryKey = 'id_stock_opname';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_stock_opname',
        'kode_opname',
        'status_opname',
        'id_user',
        'catatan',
        'total_item',
        'total_selisih',
    ];

    protected function casts(): array
    {
        return [
            'total_item' => 'integer',
            'total_selisih' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'id_user',
            'id_user'
        );
    }

    public function items()
    {
        return $this->hasMany(
            StockOpnameItem::class,
            'id_stock_opname',
            'id_stock_opname'
        );
    }
}
