<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFulfillment extends Model
{
    /**
     * Tahap pemenuhan pesanan oleh admin: dari menyiapkan barang,
     * mengemas, hingga siap dikirim kurir.
     */
    public const STATUSES = [
        'persiapan',
        'dikemas',
        'siap_kirim',
        'selesai',
    ];

    protected $table = 'order_fulfillments';

    protected $primaryKey = 'id_fulfillment';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_fulfillment',
        'id_order',
        'status_fulfillment',
        'catatan',
        'diubah_oleh',
        'urutan',
    ];

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
