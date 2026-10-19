<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'vouchers';

    protected $primaryKey = 'id_voucher';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_voucher',
        'kode_voucher',
        'nama_voucher',
        'deskripsi',
        'tipe_diskon',
        'nilai_diskon',
        'minimal_pembelian',
        'maksimal_diskon',
        'kuota_total',
        'kuota_per_pengguna',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_voucher',
    ];

    protected function casts(): array
    {
        return [
            'nilai_diskon' => 'decimal:2',
            'minimal_pembelian' => 'decimal:2',
            'maksimal_diskon' => 'decimal:2',
            'kuota_total' => 'integer',
            'kuota_per_pengguna' => 'integer',
            'tanggal_mulai' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function usages()
    {
        return $this->hasMany(
            VoucherUsage::class,
            'id_voucher',
            'id_voucher'
        );
    }
}
