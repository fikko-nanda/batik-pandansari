<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua respons.
 *
 * Catatan CSP: halaman masih memakai <script> inline, Alpine CDN (butuh
 * 'unsafe-eval'), dan Tailwind CDN, sehingga CSP saat ini bersifat
 * pertahanan berlapis (membatasi asal script), bukan CSP ketat.
 * Origin API (ngrok) dimasukkan otomatis dari config('services.api.url').
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan cookie sesi Laravel memakai Secure saat request HTTPS (ngrok/produksi),
        // meskipun SESSION_SECURE_COOKIE tidak diisi. Dijalankan sebelum StartSession.
        if ($request->isSecure()) {
            config(['session.secure' => true]);
        }

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');
        // allow-popups agar popup Google Identity Services tetap bisa berkomunikasi.
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS hanya untuk request HTTPS (ngrok/produksi). HTTP lokal tidak di-HSTS.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->csp($request));
        }

        // Halaman berisi data akun/panel tidak boleh disimpan di cache browser/proxy.
        if ($request->is('admin*', 'teknisi*', 'profile', 'orders*', 'address', 'security', 'cart', 'checkout', 'wishlist')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        // Jangan bocorkan versi PHP.
        $response->headers->remove('X-Powered-By');
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        return $response;
    }

    private function csp(Request $request): string
    {
        $host = $request->getHost();
        $hostLokal = in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
            || (filter_var($host, FILTER_VALIDATE_IP) !== false
                && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false);

        // Jangan longgarkan CSP untuk tunnel publik (ngrok) meski APP_ENV=local.
        $lokal = app()->environment('local') && (! $request->isSecure() || $hostLokal);

        $script = [
            "'self'", "'unsafe-inline'", "'unsafe-eval'",
            'https://accounts.google.com',
            'https://apis.google.com',
            'https://cdn.jsdelivr.net',
            'https://cdn.tailwindcss.com',
            'https://cdnjs.cloudflare.com',
        ];
        $style = [
            "'self'", "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://cdnjs.cloudflare.com',
            'https://cdn.tailwindcss.com',
        ];
        $font = [
            "'self'", 'data:',
            'https://fonts.gstatic.com',
            'https://cdnjs.cloudflare.com',
        ];
        $img = ["'self'", 'data:', 'blob:', 'https:'];
        $connect = ["'self'", 'https:'];
        $frame = ['https://accounts.google.com', 'https://content.googleapis.com'];

        // Vite dev server (HMR) hanya diizinkan saat development lokal.
        if ($lokal) {
            $dev = [
                'http://localhost:5173', 'http://127.0.0.1:5173', 'http://[::1]:5173',
                'ws://localhost:5173', 'ws://127.0.0.1:5173', 'ws://[::1]:5173',
            ];

            // Akses dev via IP LAN (mis. dari ponsel) memakai host yang sama, port 5173.
            $kandidat = [
                parse_url((string) config('app.url'), PHP_URL_HOST),
                $host,
            ];

            foreach (array_unique(array_filter($kandidat)) as $host) {
                $ip = filter_var($host, FILTER_VALIDATE_IP);
                if ($ip === false && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                    continue;
                }

                $host = str_contains($host, ':') && ! str_starts_with($host, '[') ? '['.$host.']' : $host;
                $dev[] = 'http://'.$host.':5173';
                $dev[] = 'ws://'.$host.':5173';
            }

            $script = array_merge($script, $dev);
            $connect = array_merge($connect, $dev);
        }

        // Origin API backend (termasuk domain ngrok yang sedang dipakai).
        $api = (string) config('services.api.url', '');
        if ($api !== '') {
            $parts = parse_url($api);
            if (! empty($parts['scheme']) && ! empty($parts['host'])) {
                $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
                $connect[] = $origin;
            }
        }

        $direktif = [
            "default-src 'self'",
            'script-src '.implode(' ', array_unique($script)),
            'style-src '.implode(' ', array_unique($style)),
            'img-src '.implode(' ', array_unique($img)),
            'font-src '.implode(' ', array_unique($font)),
            'connect-src '.implode(' ', array_values(array_unique($connect))),
            'frame-src '.implode(' ', array_unique($frame)),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if ($request->isSecure()) {
            $direktif[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $direktif);
    }
}
