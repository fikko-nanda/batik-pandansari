<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\Feature\Endpoints\Concerns\CreatesApiSchema;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use CreatesApiSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createApiSchema();
    }

    public function test_respons_api_memuat_header_keamanan(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'; base-uri 'none'"
        );
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    public function test_login_dibatasi_setelah_lima_percobaan_per_email(): void
    {
        $user = User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => 'Pengguna Uji',
            'email' => 'rate-limit@example.com',
            'kata_sandi' => 'kata-sandi-benar',
            'peran' => 'pelanggan',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'kata_sandi' => 'kata-sandi-salah',
            ])->assertStatus(401);
        }

        $this->postJson('/api/login', [
            'email' => $user->email,
            'kata_sandi' => 'kata-sandi-salah',
        ])->assertStatus(429);
    }

    public function test_registrasi_dibatasi_setelah_lima_percobaan_per_email(): void
    {
        $payload = [
            'nama_pengguna' => 'Pendaftar Uji',
            'email' => 'daftar-rate-limit@example.com',
            'kata_sandi' => 'kata-sandi-kuat',
            'kata_sandi_confirmation' => 'kata-sandi-kuat',
        ];

        $this->postJson('/api/register', $payload)->assertStatus(201);

        for ($attempt = 2; $attempt <= 5; $attempt++) {
            $this->postJson('/api/register', $payload)->assertStatus(422);
        }

        $this->postJson('/api/register', $payload)->assertStatus(429);
    }

    public function test_cors_hanya_mengizinkan_origin_terdaftar(): void
    {
        $allowed = $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:8000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $allowed->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8000');

        $blocked = $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'https://penyerang.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $this->assertFalse($blocked->headers->has('Access-Control-Allow-Origin'));
    }
}
