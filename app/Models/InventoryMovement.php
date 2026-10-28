<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    protected $primaryKey = 'id_inventory_movement';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_inventory_movement',
        'id_variant',
        'id_user',
        'jenis_pergerakan',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'referensi',
        'catatan',
        'waktu_pergerakan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'stok_sebelum' => 'integer',
            'stok_sesudah' => 'integer',
            'waktu_pergerakan' => 'datetime',
        ];
    }

    public function variant()
    {
        return $this->belongsTo(
            ProductVariant::class,
            'id_variant',
            'id_variant'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'id_user',
            'id_user'
        );
    }
}
