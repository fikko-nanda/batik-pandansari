<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'id_user',
    'nama_pengguna',
    'email',
    'nomor_telepon',
    'nomor_telepon_normalized',
    'kata_sandi',
    'peran',
])]
#[Hidden(['kata_sandi', 'remember_token', 'nomor_telepon_normalized'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id_user';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $authPasswordName = 'kata_sandi';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected function casts(): array
    {
        return [
            'kata_sandi' => 'hashed',
        ];
    }

    public static function normalizeWhatsAppNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public function addresses()
    {
        return $this->hasMany(
            UserAddress::class,
            'id_user',
            'id_user'
        );
    }

    public function orders()
    {
        return $this->hasMany(
            Order::class,
            'id_user',
            'id_user'
        );
    }
}
