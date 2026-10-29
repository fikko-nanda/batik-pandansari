<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ZonaPengiriman extends Model
{
    protected $table = 'zona_pengiriman';

    protected $primaryKey = 'id_zona';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_zona',
        'nama_zona',
        'jarak_min_km',
        'jarak_maks_km',
        'biaya_dasar',
        'biaya_per_kg',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'jarak_min_km' => 'decimal:2',
            'jarak_maks_km' => 'decimal:2',
            'biaya_dasar' => 'decimal:2',
            'biaya_per_kg' => 'decimal:2',
            'status_aktif' => 'boolean',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_aktif', true);
    }

    /**
     * Zona aktif yang mencakup jarak tertentu.
     *
     * Batas bawah inklusif dan batas atas eksklusif agar zona yang bersambung
     * tidak saling tumpang tindih pada titik batas.
     */
    public static function untukJarak(float $jarakKm): ?self
    {
        return static::query()
            ->aktif()
            ->where('jarak_min_km', '<=', $jarakKm)
            ->where('jarak_maks_km', '>', $jarakKm)
            ->orderBy('jarak_min_km')
            ->first();
    }

    /**
     * Berat dibulatkan ke atas per kilogram, dengan minimum 1 kg.
     */
    public static function beratDibulatkanKg(int $beratGram): int
    {
        return max(1, (int) ceil($beratGram / 1000));
    }

    public function biayaUntuk(int $beratGram): float
    {
        $kg = self::beratDibulatkanKg($beratGram);

        return round(
            (float) $this->biaya_dasar + ($kg * (float) $this->biaya_per_kg),
            2
        );
    }
}
