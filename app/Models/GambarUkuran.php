<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GambarUkuran extends Model
{
    protected $table = 'gambar_ukuran';

    protected $primaryKey = 'id_gambar_ukuran';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'gambar',
    ];

    protected function casts(): array
    {
        return [
            'id_gambar_ukuran' => 'integer',
        ];
    }
}
