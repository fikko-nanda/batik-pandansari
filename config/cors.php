<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Origin yang diizinkan ditentukan lewat CORS_ALLOWED_ORIGINS (dipisah
    | koma). Jangan memakai "*" untuk endpoint yang memakai kredensial.
    | Contoh: CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'CORS_ALLOWED_ORIGINS',
            // 'http://localhost:8000,http://127.0.0.1:8000'
            '*'
        ))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Correlation-ID'],

    'max_age' => 86400,

    'supports_credentials' => false,

];
