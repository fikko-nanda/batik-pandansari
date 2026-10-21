<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsGaleri extends Model
{
    protected $table = 'cms_galeri';

    protected $primaryKey = 'id_galeri';

    public $incrementing = false;

    protected $keyType = 'int';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_galeri',
        'judul',
        'subjudul',
        'deskripsi',
        'label_lokasi',
        'judul_lokasi',
        'deskripsi_lokasi',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'id_galeri' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }

    public function gambar()
    {
        return $this->hasMany(
            CmsGambarGaleri::class,
            'id_galeri',
            'id_galeri'
        )->orderBy('urutan_tampilan');
    }
}
