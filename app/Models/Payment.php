<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /**
     * Status pembayaran yang dihasilkan pemetaan notifikasi Midtrans
     * (MidtransNotificationController::mapStatus).
     */
    public const STATUSES = [
        'menunggu',
        'dibayar',
        'ditolak',
        'kedaluwarsa',
        'dibatalkan',
    ];

    protected $table = 'payments';

    protected $primaryKey = 'id_payment';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_payment',
        'id_order',
        'provider',
        'provider_transaction_id',
        'metode_pembayaran',
        'jumlah_pembayaran',
        'status_pembayaran',
        'payment_url',
        'payment_reference',
        'expires_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_pembayaran' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
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
}
