<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang server-side untuk halaman /admin dan /teknisi.
 *
 * Token API tetap disimpan di localStorage (dipakai request ke backend),
 * sedangkan hak akses panel disimpan di cookie terenkripsi (HttpOnly) yang
 * hanya diisi setelah token diverifikasi ke backend lewat POST /session/panel.
 */
class EnsurePanelRole
{
    public const COOKIE = 'pandansari_panel';

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $panel = $request->cookie(self::COOKIE);
        $data = is_string($panel) && $panel !== '' ? json_decode($panel, true) : null;

        $valid = is_array($data)
            && ($data['exp'] ?? 0) >= time()
            && ($data['role'] ?? '') === $role;

        if (! $valid) {
            // Bersihkan cookie panel yang rusak/kedaluwarsa agar tidak dikirim terus.
            Cookie::queue(Cookie::forget(self::COOKIE, '/', config('session.domain')));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Silakan login dengan akun yang sesuai.',
                ], 403);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
