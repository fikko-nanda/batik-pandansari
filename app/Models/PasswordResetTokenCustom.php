<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetTokenCustom extends Model
{
    protected $table = 'password_reset_tokens_custom';

    protected $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];
}
