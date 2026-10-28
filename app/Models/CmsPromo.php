<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPromo extends Model
{
    public const SINGLETON_ID = 1;

    protected $table = 'cms_promo';

    protected $primaryKey = 'id_cms_promo';

    public $incrementing = true;

    protected $keyType = 'int';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_cms_promo',
        'id_voucher',
        'badge',
        'tautan_tujuan',
        'gambar',
        'urutan_tampilan',
    ];

    protected function casts(): array
    {
        return [
            'id_cms_promo' => 'integer',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'id_voucher', 'id_voucher');
    }
}
