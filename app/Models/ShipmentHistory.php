<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentHistory extends Model
{
    protected $table = 'shipment_histories';

    protected $primaryKey = 'id_shipment_history';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_shipment_history',
        'id_shipment',
        'status_sebelumnya',
        'status_baru',
        'lokasi',
        'keterangan',
        'waktu_perubahan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_perubahan' => 'datetime',
        ];
    }

    public function shipment()
    {
        return $this->belongsTo(
            Shipment::class,
            'id_shipment',
            'id_shipment'
        );
    }
}
