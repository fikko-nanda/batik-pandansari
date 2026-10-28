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
        'url' => env('VITE_API_URL', 'http://localhost:8000/api'),
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

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
        'admin_numbers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('WA_ADMIN_NUMBER', ''))
        ))),
    ],

    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'webhook_throttle' => env('MIDTRANS_WEBHOOK_THROTTLE', 60),

        // URL pengalihan (callbacks) yang dikirim ke Snap saat membuat
        // transaksi. Bila kosong, Midtrans memakai finish_redirect_url bawaan
        // akun yang seringkali masih example.com. Isi dengan halaman hasil
        // pembayaran milik sendiri. Nilai bawaan mengarah ke halaman web
        // backend yang memang tersedia (routes/web.php).
        'finish_redirect_url' => env('MIDTRANS_FINISH_REDIRECT_URL')
            ?: rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/pembayaran/selesai',
        'unfinish_redirect_url' => env('MIDTRANS_UNFINISH_REDIRECT_URL')
            ?: rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/pembayaran/tertunda',
        'error_redirect_url' => env('MIDTRANS_ERROR_REDIRECT_URL')
            ?: rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/pembayaran/gagal',
    ],

    // Client ID/Secret OAuth 2.0 dari Google Cloud Console → Credentials.
    // Dipakai memverifikasi ID Token (aud) dan access token (userinfo) pada
    // login/tautan Google.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
