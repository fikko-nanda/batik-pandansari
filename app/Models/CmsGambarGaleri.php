<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsGambarGaleri extends Model
{
    protected $table = 'cms_gambar_galeri';

    protected $primaryKey = 'id_gambar_galeri';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_gambar_galeri',
        'id_galeri',
        'gambar',
        'teks_alternatif',
        'urutan_tampilan',
    ];

    protected function casts(): array
    {
        return [
            'id_galeri' => 'integer',
            'urutan_tampilan' => 'integer',
        ];
    }

    public function galeri()
    {
        return $this->belongsTo(
            CmsGaleri::class,
            'id_galeri',
            'id_galeri'
        );
    }
}
