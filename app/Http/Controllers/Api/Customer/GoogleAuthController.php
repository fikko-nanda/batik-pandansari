<?php

namespace App\Http\Controllers\Api\Customer;

use App\Exceptions\GoogleAuthException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Konfigurasi publik Google Sign-In untuk klien (Client ID OAuth memang
     * publik / aman dibagikan). Dipakai halaman bantu & frontend agar Client
     * ID yang dipakai browser selalu sinkron dengan yang diverifikasi server.
     */
    public function config(): JsonResponse
    {
        $clientId = (string) config('services.google.client_id');

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi Google Sign-In.',
            'data' => [
                'client_id' => $clientId,
                'configured' => $clientId !== '',
            ],
        ]);
    }

    /**
     * Login/daftar lewat ID Token Google (Sign-In with Google).
     *
     * - google_id sudah tertaut → langsung login.
     * - Email terdaftar → otomatis ditautkan; google_id lama ditimpa dan
     *   email ditandai terverifikasi karena Google sudah memverifikasinya.
     * - Email belum terdaftar → pengguna baru dibuat dengan peran pelanggan.
     */
    public function login(Request $request, GoogleTokenVerifier $verifier): JsonResponse
    {
        if (! $this->konfigurasiSiap()) {
            return response()->json([
                'success' => false,
                'message' => 'Login Google belum dikonfigurasi di server.',
            ], 503);
        }

        $kredensial = $this->bacaKredensial($request);
        if ($kredensial['error'] !== null) {
            return $kredensial['error'];
        }

        try {
            $payload = $this->verifikasiKredensial($verifier, $kredensial['id_token'], $kredensial['access_token']);
        } catch (GoogleAuthException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->statusCode());
        }

        if ($payload === null) {
            return response()->json([
                'success' => false,
                'message' => 'Token Google tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        if (($payload['email_verified'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Email Google belum terverifikasi. Selesaikan verifikasi email pada akun Google terlebih dahulu.',
            ], 422);
        }

        $email = (string) $payload['email'];
        $googleId = (string) $payload['sub'];

        $user = User::where('google_id', $googleId)->first();

        if ($user === null) {
            $user = User::where('email', $email)->first();

            if ($user === null) {
                $user = $this->buatPenggunaGoogle($payload);
            } else {
                // Auto-tautkan: email sudah ada, sehingga akun lokal ini
                // dihubungkan ke Google dan email ditandai terverifikasi.
                $belumTerverifikasi = $user->email_verified_at === null;

                $user->forceFill([
                    'google_id' => $googleId,
                    'avatar' => $payload['picture'] ?? $user->avatar,
                    'email_verified_at' => now(),
                ]);

                // Cegah account pre-hijacking: akun lokal yang belum terverifikasi
                // bisa saja didaftarkan pihak lain memakai email korban. Karena
                // Google sudah membuktikan kepemilikan email, cabut kata sandi dan
                // seluruh token lama sebelum akun ditautkan.
                if ($belumTerverifikasi) {
                    $user->kata_sandi = null;
                    $user->tokens()->delete();
                }

                $user->save();
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Google berhasil',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->fresh(),
            ],
        ]);
    }

    /**
     * Tautkan akun Google ke akun yang sedang login (metode lama dulu).
     */
    public function linkGoogle(Request $request, GoogleTokenVerifier $verifier): JsonResponse
    {
        if (! $this->konfigurasiSiap()) {
            return response()->json([
                'success' => false,
                'message' => 'Login Google belum dikonfigurasi di server.',
            ], 503);
        }

        $kredensial = $this->bacaKredensial($request);
        if ($kredensial['error'] !== null) {
            return $kredensial['error'];
        }

        try {
            $payload = $this->verifikasiKredensial($verifier, $kredensial['id_token'], $kredensial['access_token']);
        } catch (GoogleAuthException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->statusCode());
        }

        if ($payload === null) {
            return response()->json([
                'success' => false,
                'message' => 'Token Google tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        if (($payload['email_verified'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'Email Google belum terverifikasi. Selesaikan verifikasi email pada akun Google terlebih dahulu.',
            ], 422);
        }

        $googleId = (string) $payload['sub'];
        $user = $request->user();

        $pemilik = User::where('google_id', $googleId)->first();

        if ($pemilik !== null && $pemilik->id_user !== $user->id_user) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Google ini sudah tertaut ke pengguna lain.',
            ], 409);
        }

        $user->forceFill([
            'google_id' => $googleId,
            'avatar' => $payload['picture'] ?? $user->avatar,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Akun Google berhasil ditautkan.',
            'data' => $user->fresh(),
        ]);
    }

    private function konfigurasiSiap(): bool
    {
        return (string) config('services.google.client_id') !== '';
    }

    /**
     * Baca kredensial Google dari request. Klien dapat mengirim salah satu:
     * - `id_token`   : JWT hasil Google Identity Services.
     * - `access_token`: OAuth access token (alur popup/redirect).
     *
     * @return array{id_token: ?string, access_token: ?string, error: ?JsonResponse}
     */
    private function bacaKredensial(Request $request): array
    {
        $idToken = $request->input('id_token');
        $accessToken = $request->input('access_token');

        $idToken = is_string($idToken) && trim($idToken) !== '' ? trim($idToken) : null;
        $accessToken = is_string($accessToken) && trim($accessToken) !== '' ? trim($accessToken) : null;

        if ($idToken === null && $accessToken === null) {
            return [
                'id_token' => null,
                'access_token' => null,
                'error' => response()->json([
                    'success' => false,
                    'message' => 'Sertakan id_token atau access_token dari Google.',
                ], 422),
            ];
        }

        return [
            'id_token' => $idToken,
            'access_token' => $accessToken,
            'error' => null,
        ];
    }

    /**
     * Verifikasi kredensial: utamakan id_token (JWT) bila ada, jika tidak
     * pakai access_token lewat endpoint userinfo Google.
     *
     * @return array<string, mixed>|null
     *
     * @throws GoogleAuthException
     */
    private function verifikasiKredensial(GoogleTokenVerifier $verifier, ?string $idToken, ?string $accessToken): ?array
    {
        if ($idToken !== null) {
            return $verifier->verify($idToken);
        }

        return $verifier->verifyAccessToken($accessToken);
    }

    /**
     * Buat pengguna baru dari payload Google. google_id/avatar sengaja
     * tidak mass-assignable; peran selalu pelanggan (tidak pernah dari token).
     */
    private function buatPenggunaGoogle(array $payload): User
    {
        $email = (string) $payload['email'];

        $nama = trim((string) ($payload['name'] ?? ''));
        if ($nama === '') {
            $nama = explode('@', $email)[0];
        }

        $user = new User;
        $user->forceFill([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => Str::limit($nama, 100, ''),
            'email' => $email,
            'nomor_telepon' => null,
            'kata_sandi' => null,
            'peran' => 'pelanggan',
            'email_verified_at' => now(),
            'google_id' => (string) $payload['sub'],
            'avatar' => $payload['picture'] ?? null,
        ]);
        $user->save();

        return $user;
    }
}
