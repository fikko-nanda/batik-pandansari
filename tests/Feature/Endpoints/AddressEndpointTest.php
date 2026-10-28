<?php

namespace Tests\Feature\Endpoints;

use App\Models\UserAddress;

/**
 * Endpoint alamat: /api/addresses (CRUD milik sendiri)
 */
class AddressEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Alamat Pengguna';

    public function test_alamat_akses_tanpa_token(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/addresses', [
            'endpoint' => 'GET /api/addresses',
            'label' => 'Daftar alamat tanpa token',
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/addresses', [
            'endpoint' => 'POST /api/addresses',
            'label' => 'Tambah alamat tanpa token',
            'json' => ['label_alamat' => 'Rumah'],
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_alamat_index_hanya_milik_sendiri(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $other = $this->makeUser('pelanggan');
        $this->makeAddress($user);
        $this->makeAddress($other, false);

        $this->hit('GET', '/api/addresses', [
            'endpoint' => 'GET /api/addresses',
            'label' => 'Daftar alamat (hanya milik sendiri)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data, 'Alamat pengguna lain ikut tampil.');
        $this->assertSame($user->id_user, $data[0]['id_user'] ?? null);
        $this->assertTrue((bool) ($data[0]['alamat_utama'] ?? false));

        $this->assertRecorded();
    }

    public function test_alamat_store_pertama_jadi_alamat_utama(): void
    {
        $user = $this->actingAsRole('pelanggan');

        $this->hit('POST', '/api/addresses', [
            'endpoint' => 'POST /api/addresses',
            'label' => 'Buat alamat pertama (otomatis jadi utama)',
            'json' => [
                'label_alamat' => 'Rumah',
                'nama_penerima' => $user->nama_pengguna,
                'nomor_telepon' => '081234567890',
                'nama_provinsi' => 'Jawa Tengah',
                'nama_kota' => 'Pekalongan',
                'nama_kecamatan' => 'Pekalongan Barat',
                'kode_pos' => '51133',
                'alamat_lengkap' => 'Jl. Batik No. 12',
            ],
            'expected' => 201,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertTrue((bool) ($data['alamat_utama'] ?? false), 'Alamat pertama seharusnya menjadi utama.');
        $this->assertSame(['user_addresses' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);

        $this->assertRecorded();
    }

    public function test_alamat_store_kedua_sampai_keempat(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $this->makeAddress($user);
        $payload = [
            'label_alamat' => 'Kantor',
            'nama_penerima' => $user->nama_pengguna,
            'nomor_telepon' => '081299999999',
            'nama_provinsi' => 'DKI Jakarta',
            'nama_kota' => 'Jakarta Pusat',
            'nama_kecamatan' => 'Menteng',
            'kode_pos' => '10310',
            'alamat_lengkap' => 'Jl. Menteng Raya 5',
        ];

        $this->hit('POST', '/api/addresses', [
            'endpoint' => 'POST /api/addresses',
            'label' => 'Buat alamat kedua tanpa flag alamat_utama',
            'json' => $payload,
            'expected' => 201,
        ]);

        $this->assertFalse((bool) ($this->recorded[0]['response']['data']['alamat_utama'] ?? true));

        $this->hit('POST', '/api/addresses', [
            'endpoint' => 'POST /api/addresses',
            'label' => 'Buat alamat ketiga dengan alamat_utama=true',
            'json' => array_merge($payload, ['alamat_utama' => true]),
            'expected' => 201,
        ]);

        $utama = UserAddress::where('id_user', $user->id_user)->where('alamat_utama', true)->count();
        $this->assertSame(1, $utama, 'Harusnya hanya ada satu alamat utama.');
        $this->note('Alamat lama otomatis diturunkan menjadi bukan utama saat alamat baru ditandai utama.');

        $this->assertRecorded();
    }

    public function test_alamat_store_validasi(): void
    {
        $this->actingAsRole('pelanggan');

        $this->hit('POST', '/api/addresses', [
            'endpoint' => 'POST /api/addresses',
            'label' => 'Buat alamat tanpa data apa pun',
            'json' => [],
            'expected' => 422,
        ]);

        $errors = $this->recorded[0]['response']['errors'] ?? [];
        foreach (['label_alamat', 'nama_penerima', 'nomor_telepon', 'nama_provinsi', 'nama_kota', 'nama_kecamatan', 'kode_pos', 'alamat_lengkap'] as $field) {
            $this->assertArrayHasKey($field, $errors, "Field {$field} tidak divalidasi.");
        }

        $this->hit('POST', '/api/addresses', [
            'endpoint' => 'POST /api/addresses',
            'label' => 'Buat alamat dengan label > 30 karakter',
            'json' => [
                'label_alamat' => str_repeat('L', 31),
                'nama_penerima' => 'Budi',
                'nomor_telepon' => '0812',
                'nama_provinsi' => 'Jateng',
                'nama_kota' => 'Pekalongan',
                'nama_kecamatan' => 'Barat',
                'kode_pos' => '51133',
                'alamat_lengkap' => 'Jl. A',
            ],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/addresses', [
            'endpoint' => 'POST /api/addresses',
            'label' => 'Buat alamat dengan kode_pos > 10 karakter',
            'json' => [
                'label_alamat' => 'Rumah',
                'nama_penerima' => 'Budi',
                'nomor_telepon' => '0812',
                'nama_provinsi' => 'Jateng',
                'nama_kota' => 'Pekalongan',
                'nama_kecamatan' => 'Barat',
                'kode_pos' => str_repeat('9', 11),
                'alamat_lengkap' => 'Jl. A',
            ],
            'expected' => 422,
        ]);

        $this->assertSame([], $this->recorded[0]['db_effect'], 'Tidak boleh ada baris tersimpan saat validasi gagal.');
        $this->assertSame([], $this->recorded[1]['db_effect']);
        $this->assertSame([], $this->recorded[2]['db_effect']);

        $this->assertRecorded();
    }

    public function test_alamat_show(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $address = $this->makeAddress($user);
        $other = $this->makeUser('pelanggan');
        $otherAddress = $this->makeAddress($other, false);

        $this->hit('GET', '/api/addresses/'.$address->id_user_address, [
            'endpoint' => 'GET /api/addresses/{id}',
            'label' => 'Detail alamat milik sendiri',
            'expected' => 200,
        ]);

        $this->hit('GET', '/api/addresses/'.$otherAddress->id_user_address, [
            'endpoint' => 'GET /api/addresses/{id}',
            'label' => 'Detail alamat milik pengguna lain',
            'expected' => 404,
        ]);

        $this->hit('GET', '/api/addresses/tidak-ada', [
            'endpoint' => 'GET /api/addresses/{id}',
            'label' => 'Detail alamat tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertSame($address->id_user_address, $this->recorded[0]['response']['data']['id_user_address'] ?? null);

        $this->assertRecorded();
    }

    public function test_alamat_update(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $address = $this->makeAddress($user, true);
        $second = $this->makeAddress($user, false);
        $second->update(['label_alamat' => 'Kantor']);

        $this->hit('PUT', '/api/addresses/'.$second->id_user_address, [
            'endpoint' => 'PUT /api/addresses/{id}',
            'label' => 'Perbarui sebagian field alamat',
            'json' => ['nama_kota' => 'Tegal', 'alamat_utama' => true],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('Tegal', $data['nama_kota'] ?? null);
        $this->assertSame('Kantor', $data['label_alamat'] ?? null, 'Field yang tidak dikirim ikut berubah.');
        $this->assertTrue((bool) ($data['alamat_utama'] ?? false));

        $utamaCount = UserAddress::where('id_user', $user->id_user)->where('alamat_utama', true)->count();
        $this->assertSame(1, $utamaCount, 'Alamat utama lama tidak diturunkan.');
        $this->assertFalse((bool) $address->fresh()->alamat_utama);

        $this->hit('PUT', '/api/addresses/'.$address->id_user_address, [
            'endpoint' => 'PUT /api/addresses/{id}',
            'label' => 'Perbarui alamat dengan data tidak valid',
            'json' => ['kode_pos' => str_repeat('1', 15)],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/addresses/tidak-ada', [
            'endpoint' => 'PUT /api/addresses/{id}',
            'label' => 'Perbarui alamat tidak ditemukan',
            'json' => ['nama_kota' => 'Semarang'],
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_alamat_update_milik_pengguna_lain(): void
    {
        $me = $this->actingAsRole('pelanggan');
        $other = $this->makeUser('pelanggan');
        $otherAddress = $this->makeAddress($other, false);

        $this->hit('PUT', '/api/addresses/'.$otherAddress->id_user_address, [
            'endpoint' => 'PUT /api/addresses/{id}',
            'label' => 'Perbarui alamat pengguna lain',
            'json' => ['nama_kota' => 'Disabotase'],
            'expected' => 404,
        ]);

        $this->assertSame('Pekalongan', $otherAddress->fresh()->nama_kota);

        $this->hit('DELETE', '/api/addresses/'.$otherAddress->id_user_address, [
            'endpoint' => 'DELETE /api/addresses/{id}',
            'label' => 'Hapus alamat pengguna lain',
            'expected' => 404,
        ]);

        $this->assertNotNull($otherAddress->fresh());
        $this->assertSame($me->id_user, $me->fresh()->id_user);

        $this->assertRecorded();
    }

    public function test_alamat_destroy(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $address = $this->makeAddress($user);

        $this->hit('DELETE', '/api/addresses/'.$address->id_user_address, [
            'endpoint' => 'DELETE /api/addresses/{id}',
            'label' => 'Hapus alamat milik sendiri',
            'expected' => 200,
        ]);

        $this->assertSame(['user_addresses' => '1 -> 0 (-1)'], $this->recorded[0]['db_effect']);
        $this->assertNull($address->fresh());

        $this->hit('DELETE', '/api/addresses/tidak-ada', [
            'endpoint' => 'DELETE /api/addresses/{id}',
            'label' => 'Hapus alamat tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }
}
