<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HSTS Max Age
    |--------------------------------------------------------------------------
    |
    | Only sent on HTTPS responses. Satu tahun (31536000 detik) adalah nilai
    | yang direkomendasikan untuk produksi.
    |
    */

    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Login
    |--------------------------------------------------------------------------
    |
    | Batas percobaan login per email+IP dan per IP guna mencegah brute force.
    |
    */

    'login_per_email_per_minute' => (int) env('SECURITY_LOGIN_PER_EMAIL', 5),

    'login_per_ip_per_minute' => (int) env('SECURITY_LOGIN_PER_IP', 30),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Registrasi
    |--------------------------------------------------------------------------
    */

    'register_per_email_per_minute' => (int) env('SECURITY_REGISTER_PER_EMAIL', 5),

    'register_per_ip_per_minute' => (int) env('SECURITY_REGISTER_PER_IP', 20),

];
