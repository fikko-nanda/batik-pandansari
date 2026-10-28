<?php

namespace Tests\Feature\Endpoints;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Endpoint manajemen pengguna (admin):
 * POST /api/users, GET /api/users/{id},
 * GET /api/admin/users/summary, GET /api/admin/users/export
 */
class AdminUserManagementEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Manajemen Pengguna (Admin)';

    public function test_admin_membuat_pengguna(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna baru (peran default pelanggan)',
            'json' => [
                'nama_pengguna' => 'Pengguna Baru',
                'email' => 'pengguna.baru@example.com',
                'nomor_telepon' => '081234567890',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('Pengguna Baru', $data['nama_pengguna'] ?? null);
        $this->assertSame('pengguna.baru@example.com', $data['email'] ?? null);
        $this->assertSame('pelanggan', $data['peran'] ?? null, 'Tanpa peran eksplisit harus menjadi pelanggan.');
        $this->assertArrayNotHasKey('kata_sandi', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
        $this->assertSame(['users' => '1 -> 2 (+1)'], $this->recorded[0]['db_effect']);

        $user = User::where('email', 'pengguna.baru@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotSame('rahasia123', $user->kata_sandi, 'Kata sandi harus tersimpan ter-hash.');
        $this->assertTrue(Hash::check('rahasia123', $user->kata_sandi));

        $this->assertRecorded();
    }

    public function test_admin_membuat_pengguna_peran_admin(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna dengan peran admin',
            'json' => [
                'nama_pengguna' => 'Admin Kedua',
                'email' => 'admin.kedua@example.com',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
                'peran' => 'admin',
            ],
            'expected' => 201,
        ]);

        $this->assertSame('admin', $this->recorded[0]['response']['data']['peran'] ?? null);

        $this->assertRecorded();
    }

    public function test_admin_membuat_pengguna_validasi(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna tanpa data wajib',
            'json' => [],
            'expected' => 422,
        ]);

        $errors = $this->recorded[0]['response']['errors'] ?? [];
        $this->assertArrayHasKey('nama_pengguna', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('kata_sandi', $errors);
        $this->assertSame([], $this->recorded[0]['db_effect'], 'Data gagal validasi tidak boleh tersimpan.');

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna dengan email tidak valid',
            'json' => [
                'nama_pengguna' => 'Email Salah',
                'email' => 'bukan-email',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
            ],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('email', $this->recorded[1]['response']['errors'] ?? []);

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna dengan sandi < 8 karakter & konfirmasi tidak cocok',
            'json' => [
                'nama_pengguna' => 'Sandi Pendek',
                'email' => 'sandi.pendek@example.com',
                'kata_sandi' => 'pendek',
                'kata_sandi_confirmation' => 'beda',
            ],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('kata_sandi', $this->recorded[2]['response']['errors'] ?? []);

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna dengan peran di luar enum',
            'json' => [
                'nama_pengguna' => 'Super User',
                'email' => 'super.user@example.com',
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
                'peran' => 'superadmin',
            ],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('peran', $this->recorded[3]['response']['errors'] ?? []);

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna dengan nomor telepon > 20 karakter',
            'json' => [
                'nama_pengguna' => 'Telepon Panjang',
                'email' => 'telepon.panjang@example.com',
                'nomor_telepon' => str_repeat('0', 21),
                'kata_sandi' => 'rahasia123',
                'kata_sandi_confirmation' => 'rahasia123',
            ],
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('nomor_telepon', $this->recorded[4]['response']['errors'] ?? []);

        $this->assertRecorded();
    }

    public function test_admin_membuat_pengguna_email_duplikat(): void
    {
        $this->actingAsRole('admin');

        $payload = [
            'nama_pengguna' => 'Pengguna Pertama',
            'email' => 'duplikat@example.com',
            'kata_sandi' => 'rahasia123',
            'kata_sandi_confirmation' => 'rahasia123',
        ];

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Setup: tambah pengguna pertama',
            'json' => $payload,
            'expected' => 201,
        ]);

        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna dengan email yang sudah dipakai',
            'json' => $payload,
            'expected' => 422,
        ]);

        $this->assertArrayHasKey('email', $this->recorded[1]['response']['errors'] ?? []);
        $this->assertSame([], $this->recorded[1]['db_effect'], 'Email duplikat tidak boleh menambah baris.');

        $this->assertRecorded();
    }

    public function test_detail_pengguna(): void
    {
        $target = $this->makeUser('pelanggan');
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/users/'.$target->id_user, [
            'endpoint' => 'GET /api/users/{id}',
            'label' => 'Detail pengguna oleh admin',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($target->id_user, $data['id_user'] ?? null);
        $this->assertSame($target->email, $data['email'] ?? null);
        $this->assertSame('pelanggan', $data['peran'] ?? null);
        $this->assertArrayNotHasKey('kata_sandi', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
        $this->assertArrayNotHasKey('token', $data);

        $this->hit('GET', '/api/users/'.Str::uuid(), [
            'endpoint' => 'GET /api/users/{id}',
            'label' => 'Detail pengguna tidak ditemukan',
            'expected' => 404,
        ]);

        $this->hit('GET', '/api/users/bukan-id', [
            'endpoint' => 'GET /api/users/{id}',
            'label' => 'Detail pengguna dengan id tidak valid',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_akses_pengguna_non_admin(): void
    {
        $target = $this->makeUser('pelanggan');
        $payload = [
            'nama_pengguna' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'kata_sandi' => 'rahasia123',
            'kata_sandi_confirmation' => 'rahasia123',
        ];

        $this->asGuest();
        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna tanpa token',
            'json' => $payload,
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/users/'.$target->id_user, [
            'endpoint' => 'GET /api/users/{id}',
            'label' => 'Detail pengguna tanpa token',
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/admin/users/summary', [
            'endpoint' => 'GET /api/admin/users/summary',
            'label' => 'Ringkasan pengguna tanpa token',
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/admin/users/export', [
            'endpoint' => 'GET /api/admin/users/export',
            'label' => 'Ekspor pengguna tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('POST', '/api/users', [
            'endpoint' => 'POST /api/users',
            'label' => 'Tambah pengguna oleh pelanggan',
            'json' => $payload,
            'expected' => 403,
        ]);
        $this->hit('GET', '/api/users/'.$target->id_user, [
            'endpoint' => 'GET /api/users/{id}',
            'label' => 'Detail pengguna oleh pelanggan',
            'expected' => 403,
        ]);
        $this->hit('GET', '/api/admin/users/summary', [
            'endpoint' => 'GET /api/admin/users/summary',
            'label' => 'Ringkasan pengguna oleh pelanggan',
            'expected' => 403,
        ]);
        $this->hit('GET', '/api/admin/users/export', [
            'endpoint' => 'GET /api/admin/users/export',
            'label' => 'Ekspor pengguna oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertSame(0, User::where('email', 'penyusup@example.com')->count());

        $this->assertRecorded();
    }

    public function test_ringkasan_pengguna(): void
    {
        $this->makeUser('pelanggan');
        $this->makeUser('pelanggan');

        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/users/summary', [
            'endpoint' => 'GET /api/admin/users/summary',
            'label' => 'Ringkasan pengguna oleh admin',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(3, $data['total_pengguna'] ?? null);
        $this->assertSame(3, $data['pengguna_aktif'] ?? null, 'Tabel tanpa kolom status: semua pengguna dihitung aktif.');
        $this->assertSame(2, $data['per_peran']['pelanggan'] ?? null);
        $this->assertSame(1, $data['per_peran']['admin'] ?? null);

        $this->assertRecorded();
    }

    public function test_ekspor_pengguna_csv(): void
    {
        $first = $this->makeUser('pelanggan');
        $second = $this->makeUser('pelanggan');
        $this->actingAsRole('admin');

        $response = $this->hit('GET', '/api/admin/users/export', [
            'endpoint' => 'GET /api/admin/users/export',
            'label' => 'Ekspor daftar pengguna sebagai CSV',
            'expected' => 200,
        ]);

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertMatchesRegularExpression(
            '/pengguna-\d{4}-\d{2}-\d{2}\.csv/',
            (string) $response->headers->get('content-disposition')
        );

        $rows = $this->csvRows($response);
        $this->assertSame(
            ['id_user', 'nama_pengguna', 'email', 'nomor_telepon', 'peran', 'dibuat_pada'],
            $rows[0]
        );
        $this->assertCount(4, $rows, 'Header + 3 pengguna (2 seed + 1 admin login).');

        $csv = $response->streamedContent();
        $this->assertStringContainsString($first->email, $csv);
        $this->assertStringContainsString($second->email, $csv);
        $this->assertStringNotContainsString('kata_sandi', $csv);
        $this->assertStringNotContainsString('auth_token', $csv);
        $this->assertStringNotContainsString('$2y$', $csv);
        $this->assertStringNotContainsString('remember_token', $csv);

        foreach (array_slice($rows, 1) as $row) {
            $this->assertCount(6, $row, 'Setiap baris CSV harus memiliki 6 kolom.');
        }

        $this->assertRecorded();
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function csvRows(TestResponse $response): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\n|\r/', trim($response->streamedContent())) as $line) {
            if ($line === '') {
                continue;
            }

            $rows[] = str_getcsv($line, ',', '"', '');
        }

        return $rows;
    }
}
