<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlideHero extends Model
{
    protected $table = 'cms_slide_hero';

    protected $primaryKey = 'id_slide_hero';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_slide_hero',
        'judul',
        'subjudul',
        'label_badge',
        'teks_tombol',
        'tautan_tombol',
        'gambar',
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
