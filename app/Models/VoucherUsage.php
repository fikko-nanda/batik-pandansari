<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
    protected $table = 'voucher_usages';

    protected $primaryKey = 'id_voucher_usage';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_voucher_usage',
        'id_voucher',
        'id_user',
        'id_order',
        'jumlah_diskon',
        'status_penggunaan',
        'digunakan_pada',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diskon' => 'decimal:2',
            'digunakan_pada' => 'datetime',
        ];
    }

    public function voucher()
    {
        return $this->belongsTo(
            Voucher::class,
            'id_voucher',
            'id_voucher'
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
