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
        'kedaluwarsa_pada',
        'subtotal',
        'biaya_pengiriman',
        'jarak_km',
        'zona_pengiriman',
        'berat_pengiriman_gram',
        'diskon',
        'pph',
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
            'kedaluwarsa_pada' => 'datetime',
            'subtotal' => 'decimal:2',
            'biaya_pengiriman' => 'decimal:2',
            'jarak_km' => 'decimal:3',
            'berat_pengiriman_gram' => 'integer',
            'diskon' => 'decimal:2',
            'pph' => 'decimal:2',
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

    public function shipments()
    {
        return $this->hasMany(
            Shipment::class,
            'id_order',
            'id_order'
        );
    }

    public function fulfillments()
    {
        return $this->hasMany(
            OrderFulfillment::class,
            'id_order',
            'id_order'
        );
    }
}
