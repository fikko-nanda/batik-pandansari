<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $primaryKey = 'id_order';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_order',
        'id_user',
        'nomor_pesanan',
        'status_pesanan',
        'subtotal',
        'biaya_pengiriman',
        'diskon',
        'total_pembayaran',
        'nama_penerima',
        'nomor_telepon_penerima',
        'alamat_pengiriman',
        'kode_tujuan_pengiriman',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'biaya_pengiriman' => 'decimal:2',
            'diskon' => 'decimal:2',
            'total_pembayaran' => 'decimal:2',
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
            OrderItem::class,
            'id_order',
            'id_order'
        );
    }

    public function payments()
    {
        return $this->hasMany(
            Payment::class,
            'id_order',
            'id_order'
        );
    }

    public function voucherUsages()
    {
        return $this->hasMany(
            VoucherUsage::class,
            'id_order',
            'id_order'
        );
    }

    public function statusHistories()
    {
        return $this->hasMany(
            OrderStatusHistory::class,
            'id_order',
            'id_order'
        );
    }
}
