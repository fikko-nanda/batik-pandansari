<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilUsaha extends Model
{
    protected $table = 'profil_usaha';

    protected $primaryKey = 'id_profil_usaha';

    public $incrementing = false;

    protected $keyType = 'int';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_profil_usaha',
        'nama_usaha',
        'deskripsi_singkat',
        'deskripsi_lengkap',
        'logo',
        'email_resmi',
        'nomor_whatsapp',
        'nomor_telepon',
        'alamat_lengkap',
        'tautan_google_maps',
        'jam_operasional',
    ];

    protected function casts(): array
    {
        return [
            'id_profil_usaha' => 'integer',
            'jam_operasional' => 'array',
        ];
    }
}
