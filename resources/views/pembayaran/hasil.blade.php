<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>Hasil Pembayaran - {{ config('app.name', 'Batik Pandansari') }}</title>

        <style>
            :root { color-scheme: light dark; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                background: #f5f5f4;
                color: #1b1b18;
            }
            .card {
                width: 100%;
                max-width: 30rem;
                background: #ffffff;
                border: 1px solid #e5e5e2;
                border-radius: 0.75rem;
                padding: 2rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            }
            .badge {
                display: inline-block;
                font-size: 0.75rem;
                font-weight: 600;
                padding: 0.25rem 0.625rem;
                border-radius: 9999px;
                margin-bottom: 1rem;
            }
            .badge--selesai { background: #dcfce7; color: #166534; }
            .badge--tertunda { background: #fef9c3; color: #854d0e; }
            .badge--gagal { background: #fee2e2; color: #991b1b; }
            h1 { font-size: 1.25rem; margin: 0 0 0.75rem; }
            p { font-size: 0.9375rem; line-height: 1.5; color: #3f3f3a; margin: 0 0 0.75rem; }
            .note {
                margin-top: 1.25rem;
                font-size: 0.8125rem;
                color: #6b7280;
                border-top: 1px solid #eee;
                padding-top: 1rem;
            }
            code {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 0.8125rem;
                background: #f1f1ef;
                padding: 0.1rem 0.3rem;
                border-radius: 0.25rem;
            }
        </style>
    </head>
    <body>
        <main class="card">
            @php
                $judul = [
                    'selesai' => 'Pembayaran Diterima',
                    'tertunda' => 'Pembayaran Tertunda',
                    'gagal' => 'Pembayaran Gagal',
                ][$keadaan] ?? 'Status Pembayaran';

                $pesan = [
                    'selesai' => 'Terima kasih. Pembayaran Anda sudah kami terima. Silakan cek status pesanan pada aplikasi untuk melihat pembaruannya.',
                    'tertunda' => 'Pembayaran Anda belum kami terima. Silakan selesaikan pembayaran sebelum batas waktu yang tertera pada aplikasi.',
                    'gagal' => 'Pembayaran tidak berhasil. Silakan coba lagi atau gunakan metode pembayaran lain.',
                ][$keadaan] ?? 'Silakan periksa status pembayaran pesanan Anda pada aplikasi.';
            @endphp

            <span class="badge badge--{{ $keadaan }}">{{ $judul }}</span>
            <h1>{{ $judul }}</h1>
            <p>{{ $pesan }}</p>

            <p class="note">
                Halaman ini hanya bersifat informasi. Status pembayaran resmi ditetapkan oleh
                server melalui notifikasi Midtrans yang tervalidasi, bukan dari parameter pada
                URL browser ini. Untuk memeriksa status terbaru, buka detail pesanan di aplikasi
                (mis. melalui <code>GET /api/orders/{id}/payment</code>).
            </p>
        </main>
    </body>
</html>
