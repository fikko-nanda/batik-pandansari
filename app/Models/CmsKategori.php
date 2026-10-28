<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsKategori extends Model
{
    protected $table = 'cms_kategori';

    protected $primaryKey = 'id_kategori';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_kategori',
        'nama_kategori',
        'judul',
        'deskripsi',
        'gambar',
        'tautan_tujuan',
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
