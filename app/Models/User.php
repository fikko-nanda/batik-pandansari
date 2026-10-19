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
    'kata_sandi',
    'peran',
])]
#[Hidden(['kata_sandi', 'remember_token'])]
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
