<?php

namespace Tests\Feature\Endpoints;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Endpoint profil pelanggan:
 * PUT /api/me            {nama_pengguna?, email?, nomor_telepon?}
 * PUT /api/me/password   {kata_sandi_saat_ini, kata_sandi, kata_sandi_confirmation}
 */
class ProfileEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Profil Pelanggan';

    // ------------------------------------------------------------------
    // PUT /api/me
    // ------------------------------------------------------------------

    public function test_profil_update_akses(): void
    {
        $this->asGuest();
        $this->hit('PUT', '/api/me', [
            'endpoint' => 'PUT /api/me',
            'label' => 'Perbarui profil tanpa token',
            'json' => ['nama_pengguna' => 'Hantu'],
            'expected' => 401,
        ]);

        $user = $this->actingAsRole('pelanggan');
        $this->hit('PUT', '/api/me', [
            'endpoint' => 'PUT /api/me',
            'label' => 'Perbarui profil oleh pelanggan (boleh untuk diri sendiri)',
            'json' => ['nama_pengguna' => 'Pelanggan Baru'],
            'expected' => 200,
        ]);

        $response = $this->recorded[1]['response'] ?? [];
        $this->assertTrue($response['success'] ?? false);
        $this->assertSame('Pelanggan Baru', $response['data']['nama_pengguna'] ?? null);
        $this->assertArrayNotHasKey('kata_sandi', $response['data'] ?? []);
        $this->assertSame(
            'Pelanggan Baru',
            User::whereKey($user->id_user)->value('nama_pengguna'),
            'Perubahan nama harus benar-benar tersimpan di database.'
        );

        $this->assertRecorded();
    }

    public function test_profil_update_email_dan_telepon(): void
    {
        $user = $this->actingAsRole('pelanggan');

        $this->hit('PUT', '/api/me', [
            'endpoint' => 'PUT /api/me',
            'label' => 'Perbarui email & nomor telepon',
            'json' => [
                'email' => 'pelanggan.baru@example.com',
                'nomor_telepon' => '089999999999',
            ],
            'expected' => 200,
        ]);

        $fresh = User::whereKey($user->id_user)->first();
        $this->assertSame('pelanggan.baru@example.com', $fresh->email);
        $this->assertSame('089999999999', $fresh->nomor_telepon);

        $this->hit('PUT', '/api/me', [
            'endpoint' => 'PUT /api/me',
            'label' => 'Perbarui profil dengan email tidak valid (422)',
            'json' => ['email' => 'bukan-email'],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('email', $this->recorded[1]['response']['errors'] ?? []);

        $this->hit('PUT', '/api/me', [
            'endpoint' => 'PUT /api/me',
            'label' => 'Perbarui profil dengan nama kosong (422)',
            'json' => ['nama_pengguna' => ''],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('nama_pengguna', $this->recorded[2]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    public function test_profil_update_email_duplikat(): void
    {
        $this->actingAsRole('pelanggan');

        User::create([
            'id_user' => (string) Str::uuid(),
            'nama_pengguna' => 'Pemilik Email',
            'email' => 'milik.orang@example.com',
            'nomor_telepon' => null,
            'kata_sandi' => 'password123',
            'peran' => 'pelanggan',
        ]);

        $this->hit('PUT', '/api/me', [
            'endpoint' => 'PUT /api/me',
            'label' => 'Perbarui profil dengan email milik pengguna lain (422 unique)',
            'json' => ['email' => 'milik.orang@example.com'],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('email', $this->recorded[0]['response']['errors'] ?? []);
        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // PUT /api/me/password
    // ------------------------------------------------------------------

    public function test_ganti_password_sukses(): void
    {
        $user = $this->actingAsRole('pelanggan');

        $this->hit('PUT', '/api/me/password', [
            'endpoint' => 'PUT /api/me/password',
            'label' => 'Ganti kata sandi dengan kata sandi saat ini yang benar',
            'json' => [
                'kata_sandi_saat_ini' => 'password123',
                'kata_sandi' => 'rahasia456',
                'kata_sandi_confirmation' => 'rahasia456',
            ],
            'expected' => 200,
        ]);

        $this->assertTrue($this->recorded[0]['response']['success'] ?? false);
        $this->assertTrue(
            Hash::check('rahasia456', User::whereKey($user->id_user)->value('kata_sandi')),
            'Kata sandi baru harus tersimpan (ter-hash) di database.'
        );

        // Kata sandi baru dipakai untuk login, yang lama sudah tidak berlaku.
        $this->asGuest();
        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login dengan kata sandi lama setelah diganti (401)',
            'json' => ['email' => $user->email, 'kata_sandi' => 'password123'],
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/login', [
            'endpoint' => 'POST /api/login',
            'label' => 'Login dengan kata sandi baru setelah diganti (200)',
            'json' => ['email' => $user->email, 'kata_sandi' => 'rahasia456'],
            'expected' => 200,
        ]);
        $this->assertNotEmpty($this->recorded[2]['response']['data']['token'] ?? null, 'Login dengan kata sandi baru harus menghasilkan token.');

        $this->assertRecorded();
    }

    public function test_ganti_password_salah(): void
    {
        $user = $this->actingAsRole('pelanggan');

        $this->hit('PUT', '/api/me/password', [
            'endpoint' => 'PUT /api/me/password',
            'label' => 'Ganti kata sandi dengan kata sandi saat ini yang salah (422)',
            'json' => [
                'kata_sandi_saat_ini' => 'salah-banget',
                'kata_sandi' => 'rahasia456',
                'kata_sandi_confirmation' => 'rahasia456',
            ],
            'expected' => 422,
        ]);

        $this->assertTrue(
            Hash::check('password123', User::whereKey($user->id_user)->value('kata_sandi')),
            'Kata sandi tidak boleh berubah bila kata sandi saat ini salah.'
        );

        $this->assertRecorded();
    }

    public function test_ganti_password_akses_dan_validasi(): void
    {
        $this->asGuest();
        $this->hit('PUT', '/api/me/password', [
            'endpoint' => 'PUT /api/me/password',
            'label' => 'Ganti kata sandi tanpa token (401)',
            'json' => ['kata_sandi_saat_ini' => 'password123', 'kata_sandi' => 'rahasia456'],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');

        $this->hit('PUT', '/api/me/password', [
            'endpoint' => 'PUT /api/me/password',
            'label' => 'Ganti kata sandi tanpa kata sandi saat ini (422)',
            'json' => ['kata_sandi' => 'rahasia456', 'kata_sandi_confirmation' => 'rahasia456'],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('kata_sandi_saat_ini', $this->recorded[1]['response']['errors'] ?? []);

        $this->hit('PUT', '/api/me/password', [
            'endpoint' => 'PUT /api/me/password',
            'label' => 'Ganti kata sandi terlalu pendek (422)',
            'json' => [
                'kata_sandi_saat_ini' => 'password123',
                'kata_sandi' => 'abc',
                'kata_sandi_confirmation' => 'abc',
            ],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('kata_sandi', $this->recorded[2]['response']['errors'] ?? []);

        $this->hit('PUT', '/api/me/password', [
            'endpoint' => 'PUT /api/me/password',
            'label' => 'Ganti kata sandi tanpa konfirmasi (422)',
            'json' => ['kata_sandi_saat_ini' => 'password123', 'kata_sandi' => 'rahasia456'],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('kata_sandi', $this->recorded[3]['response']['errors'] ?? []);

        $this->assertRecorded();
    }
}
