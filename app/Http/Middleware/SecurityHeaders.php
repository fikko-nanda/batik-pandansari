<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan header keamanan pada setiap respons (API maupun halaman web).
 *
 * Header ini melengkapi konfigurasi server dan tidak menggantikannya: HSTS
 * tetap membutuhkan HTTPS yang valid, sedangkan CSP membatasi sumber daya yang
 * boleh dimuat bila respons berupa HTML.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        $response->headers->set(
            'Content-Security-Policy',
            $request->is('api/*')
                ? "default-src 'none'; frame-ancestors 'none'; base-uri 'none'"
                : $this->webContentSecurityPolicy()
        );

        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.(int) config('security.hsts_max_age', 31536000).'; includeSubDomains'
            );
        }

        return $response;
    }

    /**
     * CSP untuk halaman web. Aset Vite dev server diizinkan hanya di luar
     * produksi agar HMR tetap berjalan tanpa melemahkan CSP produksi.
     */
    private function webContentSecurityPolicy(): string
    {
        $script = "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://accounts.google.com https://cdn.tailwindcss.com";
        $style = "'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com";
        $connect = "'self' https: http:";
        $img = "'self' data: https: http: blob:";
        $font = "'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com";

        if (! app()->environment('production')) {
            $dev = 'http://localhost:5173 http://127.0.0.1:5173';

            $script .= ' '.$dev;
            $style .= ' '.$dev;
            $connect .= ' '.$dev.' ws://localhost:5173 ws://127.0.0.1:5173';
            $img .= ' '.$dev.' blob:';
            $font .= ' '.$dev;
        }

        return "default-src 'self'; img-src {$img}; style-src {$style}; script-src {$script}; font-src {$font}; connect-src {$connect}; frame-ancestors 'none'; base-uri 'self'; form-action 'self'";
    }
}
