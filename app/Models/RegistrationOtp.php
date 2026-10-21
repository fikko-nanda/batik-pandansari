<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationOtp extends Model
{
    protected $table = 'registration_otps';

    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'sent_at',
        'attempts',
        'max_attempts',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'used' => 'boolean',
    ];
}
