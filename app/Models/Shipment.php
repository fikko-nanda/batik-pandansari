<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    /**
     * Status perjalanan paket. Berbeda dari status pesanan
     * (OrderService::STATUSES) dan status fulfillment.
     */
    public const STATUSES = [
        'menunggu',
        'diproses',
        'dikirim',
        'dalam_perjalanan',
        'diterima',
        'dibatalkan',
    ];

    protected $table = 'shipments';

    protected $primaryKey = 'id_shipment';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_shipment',
        'id_order',
        'provider',
        'kurir',
        'layanan_pengiriman',
        'kode_layanan',
        'berat_gram',
        'biaya_pengiriman',
        'nomor_resi',
        'status_pengiriman',
        'tanggal_dikirim',
        'tanggal_diterima',
    ];

    protected function casts(): array
    {
        return [
            'biaya_pengiriman' => 'decimal:2',
            'tanggal_dikirim' => 'datetime',
            'tanggal_diterima' => 'datetime',
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

    public function histories()
    {
        return $this->hasMany(
            ShipmentHistory::class,
            'id_shipment',
            'id_shipment'
        );
    }
}
