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

    'rajaongkir' => [
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'),
        'origin_id' => env('RAJAONGKIR_ORIGIN_ID'),
        'verify' => env('RAJAONGKIR_VERIFY'),
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
