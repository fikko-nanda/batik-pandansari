<?php

namespace Tests\Feature\Endpoints;

use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Support\Str;
use Tests\Support\FakeGoogleTokenVerifier;

/**
 * Endpoint: /api/auth/google (login/daftar via ID Token Google),
 *           /api/me/google (tautkan Google ke akun login).
 *
 * Seluruh verifikasi ID Token dipalsukan lewat FakeGoogleTokenVerifier;
 * tidak ada panggilan jaringan ke Google dan tidak perlu akun Google nyata.
 */
class GoogleAuthEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Autentikasi & Pengguna';

    private const CLIENT_ID = 'test-client-id.apps.googleusercontent.com';

    protected FakeGoogleTokenVerifier $fakeVerifier;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => self::CLIENT_ID]);

        $this->fakeVerifier = new FakeGoogleTokenVerifier;
        $this->app->instance(GoogleTokenVerifier::class, $this->fakeVerifier);
    }

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------

    /**
     * Payload Google yang valid menurut kebijakan keamanan verifier
     * (aud, iss, exp, sub, email, email_verified wajib ada/benar).
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'aud' => self::CLIENT_ID,
            'iss' => 'https://accounts.google.com',
            'exp' => time() + 3600,
            'sub' => 'g-'.Str::lower(Str::random(12)),
            'email' => 'google_'.Str::lower(Str::random(10)).'@example.com',
            'email_verified' => true,
            'name' => 'Pengguna Google',
            'picture' => 'https://lh3.googleusercontent.com/a/avatar-example',
        ], $overrides);
    }

    /**
     * Daftarkan satu id_token tertentu sebagai valid pada fake verifier.
     *
     * @return array payload yang dipakai
     */
    private function registerToken(string $idToken, array $overrides = []): array
    {
        $payload = $this->payload($overrides);
        $this->fakeVerifier->responses[$idToken] = $payload;

        return $payload;
    }

    /**
     * Buat pengguna Google-saja (tanpa kata sandi) langsung ke database.
     */
    private function makeGoogleUser(array $overrides = []): User
    {
        $user = new User;
        $user->forceFill(array_merge([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => 'Google User',
            'email' => 'googler_'.Str::lower(Str::random(10)).'@example.com',
            'nomor_telepon' => null,
            'kata_sandi' => null,
            'peran' => 'pelanggan',
            'email_verified_at' => now(),
            'google_id' => 'g-'.Str::lower(Str::random(12)),
            'avatar' => 'https://lh3.googleusercontent.com/a/avatar-example',
        ], $overrides));
        $user->save();

        return $user;
    }

    // ------------------------------------------------------------------
    // Login publik
    // ------------------------------------------------------------------

    public function test_konfigurasi_google_publik(): void
    {
        $this->asGuest();
        $this->hit('GET', '/api/auth/google/config', [
            'endpoint' => 'GET /api/auth/google/config',
            'label' => 'Ambil konfigurasi publik Google Sign-In (Client ID)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(self::CLIENT_ID, $data['client_id'] ?? null);
        $this->assertTrue($data['configured'] ?? false);
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->note('Halaman bantu/frontend mengambil Client ID dari server agar selalu sinkron dengan yang diverifikasi backend.');

        $this->assertRecorded();
    }

    public function test_konfigurasi_google_publik_saat_kosong(): void
    {
        config(['services.google.client_id' => '']);

        $this->asGuest();
        $this->hit('GET', '/api/auth/google/config', [
            'endpoint' => 'GET /api/auth/google/config',
            'label' => 'Konfigurasi Google saat GOOGLE_CLIENT_ID kosong',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('', $data['client_id'] ?? null);
        $this->assertFalse($data['configured'] ?? true);
        $this->assertRecorded();
    }

    public function test_login_google_pengguna_baru_berhasil(): void
    {
        $token = 'valid-new-user-token';
        $payload = $this->registerToken($token);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google untuk pengguna baru (registrasi otomatis)',
            'json' => ['id_token' => $token],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertNotEmpty($data['token'] ?? null);
        $this->assertSame('Bearer', $data['token_type'] ?? null);
        $user = $data['user'] ?? [];
        $this->assertSame($payload['email'], $user['email'] ?? null);
        $this->assertSame('pelanggan', $user['peran'] ?? null);
        $this->assertSame($payload['sub'], $user['google_id'] ?? null);
        $this->assertSame($payload['picture'], $user['avatar'] ?? null);
        $this->assertNotEmpty($user['email_verified_at'] ?? null);
        $this->assertArrayNotHasKey('kata_sandi', $user);
        $this->assertSame(
            ['users' => '0 -> 1 (+1)', 'personal_access_tokens' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect']
        );
        $this->note('Pengguna Google baru dibuat dengan peran pelanggan; token Sanctum diterbitkan.');

        $this->assertRecorded();
    }

    public function test_login_google_tanpa_kredensial(): void
    {
        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google tanpa id_token/access_token',
            'json' => [],
            'expected' => 422,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    public function test_login_google_akses_token_pengguna_baru_berhasil(): void
    {
        $accessToken = 'ya29.access-token-contoh';
        $payload = $this->payload(['sub' => 'g-'.Str::lower(Str::random(12))]);
        $this->fakeVerifier->accessTokenResponses[$accessToken] = $payload;

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google via access_token (popup/redirect OAuth)',
            'json' => ['access_token' => $accessToken],
            'expected' => 200,
        ]);

        $this->assertTrue($this->recorded[0]['response']['success'] ?? false);
        $this->assertSame(
            ['users' => '0 -> 1 (+1)', 'personal_access_tokens' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect']
        );
        $this->assertRecorded();
    }

    public function test_login_google_akses_token_tidak_valid(): void
    {
        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google dengan access_token tak valid',
            'json' => ['access_token' => 'token-tidak-valid'],
            'expected' => 401,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    public function test_login_google_id_token_tidak_valid(): void
    {
        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google dengan id_token tak dikenal/tidak valid',
            'json' => ['id_token' => 'token-tidak-valid'],
            'expected' => 401,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    public function test_login_google_konfigurasi_kosong(): void
    {
        config(['services.google.client_id' => '']);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google ketika GOOGLE_CLIENT_ID kosong (503)',
            'json' => ['id_token' => 'apa-saja'],
            'expected' => 503,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    public function test_login_google_layanan_google_tidak_tersedia(): void
    {
        $this->fakeVerifier->unavailable = true;

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google ketika layanan Google tak terhubungi (502)',
            'json' => ['id_token' => 'token-apa'],
            'expected' => 502,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    public function test_login_google_email_belum_terverifikasi_di_google(): void
    {
        $token = 'valid-but-unverified-email';
        $this->registerToken($token, ['email_verified' => false]);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google dengan email belum terverifikasi (422)',
            'json' => ['id_token' => $token],
            'expected' => 422,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Akun tidak boleh dibuat bila email Google belum terverifikasi.');
        $this->assertRecorded();
    }

    public function test_login_google_peran_tidak_bisa_dipalsukan(): void
    {
        $token = 'token-dengan-peran-admin';
        $this->registerToken($token, ['peran' => 'admin', 'role' => 'admin']);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google mengirim klaim peran=admin (harus diabaikan)',
            'json' => ['id_token' => $token],
            'expected' => 200,
        ]);

        $peran = $this->recorded[0]['response']['data']['user']['peran'] ?? null;
        $this->assertSame('pelanggan', $peran, 'Peran pengguna Google tidak boleh diambil dari token.');
        $this->note('Klaim peran/role pada token Google diabaikan; peran selalu pelanggan.');
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Auto-taut & konflik pada login
    // ------------------------------------------------------------------

    public function test_login_google_email_terdaftar_terverifikasi_otomatis_tertaut(): void
    {
        $existing = $this->makeUser('pelanggan');
        $existing->forceFill(['email_verified_at' => now()])->save();

        $token = 'valid-token-existing-user';
        $payload = $this->registerToken($token, ['email' => $existing->email]);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google pada email terdaftar & terverifikasi (auto-taut)',
            'json' => ['id_token' => $token],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($existing->id_user, $data['user']['id_user'] ?? null);
        $this->assertSame($payload['sub'], $data['user']['google_id'] ?? null);
        $this->assertSame(['personal_access_tokens' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);
        $this->assertSame(
            $payload['sub'],
            $existing->fresh()->google_id,
            'google_id tidak tersimpan ke pengguna lama.'
        );
        $this->note('Akun lama yang sudah terverifikasi otomatis ditautkan ke Google tanpa membuat akun baru.');
        $this->assertRecorded();
    }

    public function test_login_google_email_terdaftar_belum_terverifikasi(): void
    {
        $existing = $this->makeUser('pelanggan'); // email_verified_at NULL

        $token = 'valid-token-unverified-local';
        $this->registerToken($token, ['email' => $existing->email]);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google pada email terdaftar tapi belum terverifikasi (409 requires_linking)',
            'json' => ['id_token' => $token],
            'expected' => 409,
        ]);

        $response = $this->recorded[0]['response'] ?? [];
        $this->assertFalse($response['success'] ?? true);
        $this->assertTrue($response['requires_linking'] ?? false, 'Bendera requires_linking harus true.');
        $this->assertNull($existing->fresh()->google_id, 'Akun tak terverifikasi tidak boleh otomatis ditautkan.');
        $this->assertRecorded();
    }

    public function test_login_google_email_tertaut_akun_lain(): void
    {
        $owner = $this->makeGoogleUser(['google_id' => 'g-pemilik-asli']);

        $token = 'valid-token-conflicting-owner';
        $this->registerToken($token, ['sub' => 'g-pemilik-lain', 'email' => $owner->email]);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google ketika email sudah tertaut ke google_id lain (409)',
            'json' => ['id_token' => $token],
            'expected' => 409,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame('g-pemilik-asli', $owner->fresh()->google_id);
        $this->assertRecorded();
    }

    public function test_login_google_pengguna_exist_sudah_tertaut_sama(): void
    {
        $googleSub = 'g-sudah-tertaut';
        $user = $this->makeGoogleUser(['google_id' => $googleSub]);

        $token = 'valid-token-relogin';
        $this->registerToken($token, ['sub' => $googleSub, 'email' => $user->email]);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google ulang untuk pengguna yang sudah tertaut',
            'json' => ['id_token' => $token],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($user->id_user, $data['user']['id_user'] ?? null);
        $this->assertSame(['personal_access_tokens' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Login sandi pada akun khusus Google (tanpa kata sandi)
    // ------------------------------------------------------------------

    public function test_login_kata_sandi_pengguna_google_saja_ditolak(): void
    {
        $user = $this->makeGoogleUser();

        $this->asGuest();
        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login kata sandi pada akun khusus Google (kata_sandi NULL) → 401',
            'json' => ['email' => $user->email, 'kata_sandi' => 'password123'],
            'expected' => 401,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect']);
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Siklus login Google → logout
    // ------------------------------------------------------------------

    public function test_siklus_login_google_lalu_logout(): void
    {
        $token = 'valid-token-siklus';
        $payload = $this->registerToken($token);

        $this->asGuest();
        $this->hit('POST', '/api/auth/google', [
            'endpoint' => 'POST /api/auth/google',
            'label' => 'Login Google (memulai siklus login/logout)',
            'json' => ['id_token' => $token],
            'expected' => 200,
        ]);

        $accessToken = $this->recorded[0]['response']['data']['token'] ?? null;
        $this->assertIsString($accessToken);
        $this->assertNotEmpty($accessToken);

        // Pakai token hasil login Google untuk request terautentikasi.
        $this->token = $accessToken;
        $this->role = 'pelanggan';

        $this->hit('GET', '/api/me', [
            'endpoint' => 'GET /api/me',
            'label' => 'Akses /api/me memakai token hasil login Google',
            'expected' => 200,
        ]);

        $me = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame($payload['email'], $me['email'] ?? null);

        $this->hit('POST', '/api/logout', [
            'endpoint' => 'POST /api/logout',
            'label' => 'Logout memakai token hasil login Google',
            'expected' => 200,
        ]);

        $this->hit('GET', '/api/me', [
            'endpoint' => 'GET /api/me',
            'label' => 'Token hasil login Google dipakai lagi setelah logout → 401',
            'expected' => 401,
        ]);

        $this->assertSame(
            ['personal_access_tokens' => '1 -> 0 (-1)'],
            $this->recorded[2]['db_effect'],
            'Logout harus mencabut token Sanctum.'
        );
        $this->note('Siklus penuh: login Google menerbitkan token, token dipakai untuk /api/me, lalu dicabut oleh logout.');

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Tautkan Google (POST /api/me/google)
    // ------------------------------------------------------------------

    public function test_link_google_berhasil(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $token = 'valid-token-link';
        $payload = $this->registerToken($token);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan akun Google ke akun login',
            'json' => ['id_token' => $token],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($user->id_user, $data['id_user'] ?? null);
        $this->assertSame($payload['sub'], $data['google_id'] ?? null);
        $this->assertSame($payload['picture'], $data['avatar'] ?? null);
        $this->assertSame($payload['sub'], $user->fresh()->google_id);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Tautan tidak membuat baris baru.');
        $this->assertRecorded();
    }

    public function test_link_google_tanpa_sesi_login(): void
    {
        $this->asGuest();
        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google tanpa token (401)',
            'json' => ['id_token' => 'token-apa'],
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_link_google_tanpa_kredensial(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google tanpa id_token/access_token (422)',
            'json' => [],
            'expected' => 422,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertRecorded();
    }

    public function test_link_google_akses_token_berhasil(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $accessToken = 'ya29.link-access-token';
        $this->fakeVerifier->accessTokenResponses[$accessToken] = $this->payload([
            'sub' => 'g-'.Str::lower(Str::random(12)),
        ]);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google via access_token berhasil (200)',
            'json' => ['access_token' => $accessToken],
            'expected' => 200,
        ]);

        $this->assertTrue($this->recorded[0]['response']['success'] ?? false);
        $this->assertRecorded();
    }

    public function test_link_google_id_token_tidak_valid(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google dengan id_token tidak valid (401)',
            'json' => ['id_token' => 'token-salah'],
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_link_google_email_belum_terverifikasi_di_google(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $token = 'valid-token-unverified-email-link';
        $this->registerToken($token, ['email_verified' => false]);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google dengan email belum terverifikasi (422)',
            'json' => ['id_token' => $token],
            'expected' => 422,
        ]);

        $this->assertNull($user->fresh()->google_id);
        $this->assertRecorded();
    }

    public function test_link_google_sudah_dimiliki_pengguna_lain(): void
    {
        $owner = $this->makeGoogleUser(['google_id' => 'g-pemilik-lain']);
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $token = 'valid-token-owned-by-other';
        $this->registerToken($token, ['sub' => 'g-pemilik-lain', 'email' => $owner->email]);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google yang sudah dimiliki pengguna lain (409)',
            'json' => ['id_token' => $token],
            'expected' => 409,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertNull($user->fresh()->google_id, 'Pengguna login tidak boleh mencuri tautan Google milik orang lain.');
        $this->assertSame('g-pemilik-lain', $owner->fresh()->google_id);
        $this->assertRecorded();
    }

    public function test_link_google_ganti_tautan_sendiri(): void
    {
        $user = $this->makeGoogleUser(['google_id' => 'g-lama']);
        // Pengguna khusus Google tidak punya kata sandi; token diterbitkan
        // langsung (setara hasil login Google) alih-alih lewat /api/login.
        $this->token = $user->createToken('auth_token')->plainTextToken;
        $this->role = 'pelanggan';
        $this->app->make('auth')->forgetGuards();

        $token = 'valid-token-relink';
        $this->registerToken($token, ['sub' => 'g-baru', 'email' => $user->email]);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google berbeda ke akun sendiri (relink)',
            'json' => ['id_token' => $token],
            'expected' => 200,
        ]);

        $this->assertSame('g-baru', $user->fresh()->google_id);
        $this->assertRecorded();
    }

    public function test_link_google_konfigurasi_kosong(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);
        config(['services.google.client_id' => '']);

        $this->hit('POST', '/api/me/google', [
            'endpoint' => 'POST /api/me/google',
            'label' => 'Tautkan Google ketika GOOGLE_CLIENT_ID kosong (503)',
            'json' => ['id_token' => 'apa-saja'],
            'expected' => 503,
        ]);

        $this->assertRecorded();
    }
}
