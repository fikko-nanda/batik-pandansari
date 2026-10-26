<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // Base URL API backend (dipakai MediaProxyController). WAJIB lewat config()
    // agar tetap terbaca setelah `php artisan config:cache` (env() = null di runtime).
    'api' => [
        'url' => env('VITE_API_URL'),
        // Opsional: path CA bundle untuk verifikasi TLS server-side (mis. Windows dev).
        // Kosongkan di production agar memakai CA sistem.
        'ca_bundle' => env('MEDIA_PROXY_CA_BUNDLE'),
    ],

    // Masa berlaku cookie sesi panel admin/teknisi (menit).
    'panel' => [
        'ttl' => env('PANEL_SESSION_TTL', 720),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
