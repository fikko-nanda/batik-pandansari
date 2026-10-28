<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FasilitasUsaha extends Model
{
    protected $table = 'fasilitas_usaha';

    protected $primaryKey = 'id_fasilitas';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_fasilitas',
        'nama_fasilitas',
        'deskripsi',
        'urutan_tampilan',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'urutan_tampilan' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }
}
