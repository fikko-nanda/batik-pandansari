<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $table = 'user_addresses';

    protected $primaryKey = 'id_user_address';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_user_address',
        'id_user',
        'label_alamat',
        'nama_penerima',
        'nomor_telepon',
        'nama_provinsi',
        'nama_kota',
        'nama_kecamatan',
        'kode_pos',
        'alamat_lengkap',
        'alamat_utama',
        'id_tujuan_rajaongkir',
    ];

    protected function casts(): array
    {
        return [
            'alamat_utama' => 'boolean',
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
}
