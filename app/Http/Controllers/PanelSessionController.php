<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Menyimpan hak akses panel (admin/teknisi) di cookie terenkripsi HttpOnly.
 * Token API diverifikasi langsung dari database, jadi peran tidak bisa
 * dipalsukan dari sisi browser dan tidak membutuhkan request HTTP ke diri
 * sendiri.
 */
class PanelSessionController extends Controller
{
    private const COOKIE = 'pandansari_panel';

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $profil = $this->profilDariToken($validated['token']);

        if ($profil === null) {
            Cookie::queue(Cookie::forget(self::COOKIE, '/', config('session.domain')));

            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sesi backend sudah berakhir. Silakan login ulang.',
            ], 401);
        }

        $role = $this->normalisasiPeran($profil);

        if (! in_array($role, ['admin', 'teknisi'], true)) {
            Cookie::queue(Cookie::forget(self::COOKIE, '/', config('session.domain')));

            return response()->json([
                'success' => true,
                'role' => 'pelanggan',
            ]);
        }

        $ttl = (int) config('services.panel.ttl', 720); // menit
        $domain = config('session.domain');

        Cookie::queue(cookie()->make(
            name: self::COOKIE,
            value: json_encode([
                'role' => $role,
                'exp' => now()->addMinutes($ttl)->timestamp,
            ], JSON_THROW_ON_ERROR),
            minutes: $ttl,
            path: '/',
            domain: $domain,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));

        return response()->json([
            'success' => true,
            'role' => $role,
            'expires_in' => $ttl * 60,
        ]);
    }

    public function destroy(): JsonResponse
    {
        Cookie::queue(Cookie::forget(self::COOKIE, '/', config('session.domain')));

        return response()->json([
            'success' => true,
            'message' => 'Sesi panel dihapus.',
        ]);
    }

    /**
     * Ambil profil dari token Sanctum yang masih berlaku.
     *
     * @return array<string, mixed>|null
     */
    private function profilDariToken(string $token): ?array
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ! $accessToken->tokenable) {
            return null;
        }

        if ($accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
            return null;
        }

        return [
            'peran' => $accessToken->tokenable->peran,
        ];
    }

    /** @param array<string, mixed> $profil */
    private function normalisasiPeran(array $profil): string
    {
        $nilai = $profil['peran'] ?? $profil['role'] ?? $profil['roles'] ?? '';
        $daftar = is_array($nilai) ? $nilai : [$nilai];

        foreach ($daftar as $item) {
            $peran = strtolower(trim((string) (is_array($item) ? ($item['slug'] ?? $item['nama'] ?? $item['name'] ?? '') : $item)));

            if (in_array($peran, ['admin', 'administrator', 'super_admin', 'superadmin', 'owner'], true)) {
                return 'admin';
            }

            if (in_array($peran, ['teknisi', 'technician', 'engineer', 'engineering'], true)) {
                return 'teknisi';
            }
        }

        return 'pelanggan';
    }
}
