<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_histories';

    protected $primaryKey = 'id_order_status_history';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_order_status_history',
        'id_order',
        'status_sebelumnya',
        'status_baru',
        'catatan',
        'diubah_oleh',
        'waktu_perubahan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_perubahan' => 'datetime',
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

    public function changedBy()
    {
        return $this->belongsTo(
            User::class,
            'diubah_oleh',
            'id_user'
        );
    }
}
