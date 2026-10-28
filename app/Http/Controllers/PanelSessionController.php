<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Menyimpan hak akses panel (admin/teknisi) di cookie terenkripsi HttpOnly.
 * Token API diverifikasi ulang ke backend, jadi peran tidak bisa dipalsukan
 * dari sisi browser.
 */
class PanelSessionController extends Controller
{
    private const COOKIE = 'pandansari_panel';

    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        $profil = $this->profilBackend($validated['token']);

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

    public function destroy()
    {
        Cookie::queue(Cookie::forget(self::COOKIE, '/', config('session.domain')));

        return response()->json([
            'success' => true,
            'message' => 'Sesi panel dihapus.',
        ]);
    }

    /**
     * Verifikasi token ke GET /api/me milik backend.
     *
     * @return array<string, mixed>|null
     */
    private function profilBackend(string $token): ?array
    {
        $base = rtrim((string) config('services.api.url', ''), '/');

        if ($base === '') {
            return null;
        }

        $caBundle = config('services.api.ca_bundle');

        try {
            $response = Http::withOptions([
                // Ikuti CA bundle kustom yang sama dengan MediaProxy (Windows dev/ngrok).
                'verify' => $caBundle ?: true,
            ])->withToken($token)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'ngrok-skip-browser-warning' => 'true',
                ])
                ->connectTimeout(10)
                ->timeout(20)
                ->get($base.'/me');
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();

        if (is_array($json['data'] ?? null)) {
            return $json['data'];
        }

        return is_array($json) ? $json : null;
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
