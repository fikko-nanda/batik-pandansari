<?php

namespace Tests\Feature\Endpoints;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Endpoint: /api/test-json, /api/register, /api/login, /api/logout, /api/me, /api/users
 */
class AuthEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Autentikasi & Pengguna';

    public function test_json_endpoint_tanpa_autentikasi(): void
    {
        $response = $this->hit('GET', '/api/test-json', [
            'endpoint' => 'GET /api/test-json',
            'label' => 'Tes publik sederhana',
            'expected' => 200,
        ]);

        $this->assertSame('TEST LARAVEL BERHASIL', $response->getContent());
        $this->note('Endpoint membalas teks biasa (content-type text/html), bukan JSON.');

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    public function test_register_berhasil(): void
    {
        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi pelanggan baru',
            'json' => [
                'nama_pengguna' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'nomor_telepon' => '081234567890',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
            ],
            'expected' => 201,
        ]);

        $this->assertCount(1, $this->recorded);
        $entry = $this->recorded[0];
        $this->assertSame('budi.santoso@example.com', $entry['response']['data']['email'] ?? null);
        $this->assertSame('pelanggan', $entry['response']['data']['peran'] ?? null);
        $this->assertArrayNotHasKey('kata_sandi', $entry['response']['data'] ?? []);
        $this->assertSame(
            ['users' => '0 -> 1 (+1)', 'registration_otps' => '0 -> 1 (+1)'],
            $entry['db_effect']
        );
        $this->assertTrue($entry['response']['otp_sent'] ?? false);
        $this->note('Registrasi otomatis membuat & mengirim OTP verifikasi email (otp_sent=true).');

        $stored = DB::table('users')->where('email', 'budi.santoso@example.com')->value('kata_sandi');
        $this->assertStringStartsWith('$2y$', (string) $stored);
        $this->note('kata_sandi tersimpan ter-hash (bcrypt), field kata_sandi tidak ikut diserialisasi.');

        $this->assertRecorded();
    }

    public function test_register_validasi_kosong(): void
    {
        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi tanpa data sama sekali',
            'json' => [],
            'expected' => 422,
        ]);

        $errors = $this->recorded[0]['response']['errors'] ?? [];
        foreach (['nama_pengguna', 'email', 'kata_sandi'] as $field) {
            $this->assertArrayHasKey($field, $errors, "Field {$field} tidak dilaporkan.");
        }
        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'User tidak boleh tersimpan saat validasi gagal.');

        $this->assertRecorded();
    }

    public function test_register_input_tidak_valid(): void
    {
        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi email salah & kata sandi < 8 karakter',
            'json' => [
                'nama_pengguna' => 'Pelanggan Baru',
                'email' => 'bukan-email',
                'kata_sandi' => 'abc',
                'kata_sandi_confirmation' => 'abc',
            ],
            'expected' => 422,
        ]);

        $errors = $this->recorded[0]['response']['errors'] ?? [];
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('kata_sandi', $errors);

        $this->assertRecorded();
    }

    public function test_register_konfirmasi_sandi_tidak_cocok(): void
    {
        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi konfirmasi kata sandi tidak cocok',
            'json' => [
                'nama_pengguna' => 'Pelanggan Baru',
                'email' => 'konfirmasi@example.com',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia321',
            ],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('kata_sandi', $this->recorded[0]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    public function test_register_email_duplikat(): void
    {
        $existing = $this->makeUser('pelanggan');

        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi dengan email yang sudah terpakai',
            'json' => [
                'nama_pengguna' => 'Peniru',
                'email' => $existing->email,
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
            ],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('email', $this->recorded[0]['response']['errors'] ?? []);
        $this->assertSame([], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Login
    // ------------------------------------------------------------------

    public function test_login_berhasil(): void
    {
        $user = $this->makeUser('pelanggan');

        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login dengan email & kata sandi benar',
            'json' => ['email' => $user->email, 'kata_sandi' => 'password123'],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertNotEmpty($data['token'] ?? null);
        $this->assertSame('Bearer', $data['token_type'] ?? null);
        $this->assertSame($user->id_user, $data['user']['id_user'] ?? null);
        $this->assertArrayNotHasKey('kata_sandi', $data['user'] ?? []);
        $this->assertArrayHasKey('personal_access_tokens', $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_login_kata_sandi_salah(): void
    {
        $user = $this->makeUser('pelanggan');

        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login dengan kata sandi salah',
            'json' => ['email' => $user->email, 'kata_sandi' => 'salah-sekali'],
            'expected' => 401,
        ]);

        $this->assertFalse($this->recorded[0]['response']['success'] ?? true);
        $this->assertSame([], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_login_email_tidak_terdaftar(): void
    {
        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login dengan email tidak terdaftar',
            'json' => ['email' => 'tidak-ada@example.com', 'kata_sandi' => 'password123'],
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_login_input_kosong(): void
    {
        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login tanpa mengirim email/kata sandi',
            'json' => [],
            'expected' => 422,
        ]);

        $errors = $this->recorded[0]['response']['errors'] ?? [];
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('kata_sandi', $errors);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Logout & Profil
    // ------------------------------------------------------------------

    public function test_logout_dengan_token(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $this->hit('POST', '/api/logout', [
            'endpoint' => 'POST /api/logout',
            'label' => 'Logout dengan token aktif',
            'expected' => 200,
        ]);

        $this->note('Token dicabut, lalu dipakai lagi untuk memastikan sudah tidak berlaku.');

        $this->hit('GET', '/api/me', [
            'endpoint' => 'GET /api/me',
            'label' => 'Token lama dipakai lagi setelah logout',
            'expected' => 401,
        ]);

        $this->assertSame(['personal_access_tokens' => '1 -> 0 (-1)'], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_logout_tanpa_token(): void
    {
        $this->asGuest();

        $this->hit('POST', '/api/logout', [
            'endpoint' => 'POST /api/logout',
            'label' => 'Logout tanpa token',
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_me_dengan_token(): void
    {
        $user = $this->makeUser('pelanggan');
        $this->loginAs($user);

        $this->hit('GET', '/api/me', [
            'endpoint' => 'GET /api/me',
            'label' => 'Ambil profil pengguna login',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($user->id_user, $data['id_user'] ?? null);
        $this->assertArrayNotHasKey('kata_sandi', $data);

        $this->assertRecorded();
    }

    public function test_me_tanpa_token(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/me', [
            'endpoint' => 'GET /api/me',
            'label' => 'Ambil profil tanpa token',
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Daftar pengguna (admin)
    // ------------------------------------------------------------------

    public function test_users_akses_berjenjang(): void
    {
        $this->asGuest();
        $this->hit('GET', '/api/users', [
            'endpoint' => 'GET /api/users',
            'label' => 'Daftar pengguna tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/users', [
            'endpoint' => 'GET /api/users',
            'label' => 'Daftar pengguna dengan peran pelanggan',
            'expected' => 403,
        ]);

        $this->actingAsRole('admin');
        $this->hit('GET', '/api/users?per_page=2', [
            'endpoint' => 'GET /api/users',
            'label' => 'Daftar pengguna dengan peran admin',
            'expected' => 200,
        ]);

        $page = $this->recorded[2]['response']['data'] ?? [];
        $this->assertSame(15, $page['per_page'] ?? null);
        $this->assertNotEmpty($page['total'] ?? 0);
        $this->assertIsArray($page['data'] ?? null);
        $this->note('Temuan: parameter per_page pada /api/users diabaikan (nilai pagination dikodekan keras 15), sehingga ?per_page=2 tidak berpengaruh.');

        $this->assertRecorded();
    }

    public function test_users_per_page_besar_tidak_divalidasi(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/users?per_page=100000', [
            'endpoint' => 'GET /api/users',
            'label' => 'Daftar pengguna dengan per_page=100000',
            'expected' => 200,
        ]);

        $page = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(15, $page['per_page'] ?? null);
        $this->note('Temuan: /api/users tidak memiliki validasi query sama sekali; per_page tidak divalidasi dan justru diabaikan, berbeda dengan endpoint admin lain yang membatasi per_page maksimal 100.');

        $this->assertRecorded();
    }

    public function test_register_role_tidak_bisa_dipalsukan(): void
    {
        $this->hit('POST', '/api/register', [
            'endpoint' => 'POST /api/register',
            'label' => 'Registrasi mengirim peran=admin (harus diabaikan)',
            'json' => [
                'nama_pengguna' => 'Penyusup',
                'email' => 'penyusup'.Str::random(4).'@example.com',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
                'peran' => 'admin',
            ],
            'expected' => 201,
        ]);

        $peran = $this->recorded[0]['response']['data']['peran'] ?? null;
        $this->assertSame('pelanggan', $peran, 'Field peran ikut terisi saat registrasi.');
        $this->note('Registrasi mengabaikan field peran dan selalu menetapkan peran pelanggan.');

        $this->assertRecorded();
    }
}
