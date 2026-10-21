<?php

namespace Tests\Feature\Endpoints;

use App\Models\Voucher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint voucher: pelanggan (/vouchers/available, /vouchers/validate,
 * /voucher-usages/me) dan admin (/vouchers CRUD, /voucher-usages).
 */
class VoucherEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Voucher';

    // ------------------------------------------------------------------
    // Pelanggan
    // ------------------------------------------------------------------

    public function test_voucher_pelanggan_akses_tanpa_token(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/vouchers/available', [
            'endpoint' => 'GET /api/vouchers/available',
            'label' => 'Voucher tersedia tanpa token',
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/vouchers/validate', [
            'endpoint' => 'POST /api/vouchers/validate',
            'label' => 'Validasi voucher tanpa token',
            'json' => ['kode_voucher' => 'X', 'subtotal' => 1000],
            'expected' => 401,
        ]);

        $this->hit('GET', '/api/voucher-usages/me', [
            'endpoint' => 'GET /api/voucher-usages/me',
            'label' => 'Riwayat voucher tanpa token',
            'expected' => 401,
        ]);

        $this->assertRecorded();
    }

    public function test_voucher_available(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $aktif = $this->makeVoucher([
            'kode_voucher' => 'AKTIF10',
            'minimal_pembelian' => 50000,
        ]);
        $this->makeVoucher([
            'kode_voucher' => 'NONAKTIF',
            'status_voucher' => 'nonaktif',
            'minimal_pembelian' => 0,
        ]);
        $this->makeVoucher([
            'kode_voucher' => 'BELUMWAKTU',
            'tanggal_mulai' => now()->addDay(),
            'tanggal_selesai' => now()->addDays(2),
            'minimal_pembelian' => 0,
        ]);
        $this->makeVoucher([
            'kode_voucher' => 'MELEWATI',
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_selesai' => now()->subDay(),
            'minimal_pembelian' => 0,
        ]);
        $this->makeVoucher([
            'kode_voucher' => 'MINBELI1JT',
            'minimal_pembelian' => 1000000,
        ]);
        $habis = $this->makeVoucher([
            'kode_voucher' => 'KUOTAHABIS',
            'kuota_total' => 1,
            'kuota_per_pengguna' => 1,
        ]);
        $this->makeUsage($habis, $this->makeUser('pelanggan'));

        $this->hit('GET', '/api/vouchers/available', [
            'endpoint' => 'GET /api/vouchers/available',
            'label' => 'Daftar voucher tersedia tanpa subtotal',
            'expected' => 200,
        ]);

        $kode = array_column($this->recorded[0]['response']['data'] ?? [], 'kode_voucher');
        $this->assertContains('AKTIF10', $kode);
        $this->assertNotContains('NONAKTIF', $kode, 'Voucher nonaktif ikut tampil.');
        $this->assertNotContains('BELUMWAKTU', $kode, 'Voucher belum berlaku ikut tampil.');
        $this->assertNotContains('MELEWATI', $kode, 'Voucher kedaluwarsa ikut tampil.');
        $this->assertNotContains('KUOTAHABIS', $kode, 'Voucher kuota habis ikut tampil.');

        $this->hit('GET', '/api/vouchers/available?subtotal=10000', [
            'endpoint' => 'GET /api/vouchers/available',
            'label' => 'Voucher tersedia dengan subtotal rendah (terfilter minimal beli)',
            'expected' => 200,
        ]);

        $kode = array_column($this->recorded[1]['response']['data'] ?? [], 'kode_voucher');
        $this->assertNotContains('AKTIF10', $kode, 'Voucher minimal beli 50rb ikut tampil untuk subtotal 10rb.');
        $this->assertNotContains('MINBELI1JT', $kode);

        $this->hit('GET', '/api/vouchers/available?subtotal=200000', [
            'endpoint' => 'GET /api/vouchers/available',
            'label' => 'Voucher tersedia dengan subtotal besar',
            'expected' => 200,
        ]);

        $kode = array_column($this->recorded[2]['response']['data'] ?? [], 'kode_voucher');
        $this->assertContains('AKTIF10', $kode);
        $this->assertNotContains('MINBELI1JT', $kode, 'Voucher minimal beli 1 jt tampil untuk subtotal 200 rb.');

        $this->hit('GET', '/api/vouchers/available?subtotal=2000000', [
            'endpoint' => 'GET /api/vouchers/available',
            'label' => 'Voucher tersedia dengan subtotal 2 jt (semua lolos minimal beli)',
            'expected' => 200,
        ]);

        $kode = array_column($this->recorded[3]['response']['data'] ?? [], 'kode_voucher');
        $this->assertContains('MINBELI1JT', $kode);

        $this->note('Kuota voucher dihitung dari baris voucher_usages berstatus menunggu/digunakan.');

        $this->assertRecorded();
    }

    public function test_voucher_available_validasi(): void
    {
        $this->actingAsRole('pelanggan');

        $this->hit('GET', '/api/vouchers/available?subtotal=-1', [
            'endpoint' => 'GET /api/vouchers/available',
            'label' => 'Subtotal negatif ditolak',
            'expected' => 422,
        ]);

        $this->hit('GET', '/api/vouchers/available?subtotal=abc', [
            'endpoint' => 'GET /api/vouchers/available',
            'label' => 'Subtotal bukan angka ditolak',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_voucher_validate(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $persen = $this->makeVoucher([
            'kode_voucher' => 'PCT10',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 10,
            'maksimal_diskon' => null,
            'minimal_pembelian' => 0,
            'kuota_per_pengguna' => 5,
        ]);

        $this->hit('POST', '/api/vouchers/validate', [
            'endpoint' => 'POST /api/vouchers/validate',
            'label' => 'Validasi voucher persentase (diskon 10% x 100.000)',
            'json' => ['kode_voucher' => 'PCT10', 'subtotal' => 100000],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(10000.0, (float) ($data['jumlah_diskon'] ?? 0));

        $this->hit('POST', '/api/vouchers/validate', [
            'endpoint' => 'POST /api/vouchers/validate',
            'label' => 'Validasi voucher persentase dengan batas maksimal diskon',
            'json' => [
                'kode_voucher' => $this->makeVoucher([
                    'kode_voucher' => 'PCTCAP',
                    'tipe_diskon' => 'persentase',
                    'nilai_diskon' => 50,
                    'maksimal_diskon' => 20000,
                    'kuota_per_pengguna' => 5,
                ])->kode_voucher,
                'subtotal' => 100000,
            ],
            'expected' => 200,
        ]);

        $this->assertSame(20000.0, (float) ($this->recorded[1]['response']['data']['jumlah_diskon'] ?? 0));

        $this->hit('POST', '/api/vouchers/validate', [
            'endpoint' => 'POST /api/vouchers/validate',
            'label' => 'Validasi voucher nominal lebih besar dari subtotal (dibatasi subtotal)',
            'json' => [
                'kode_voucher' => $this->makeVoucher([
                    'kode_voucher' => 'NOM5000',
                    'tipe_diskon' => 'nominal',
                    'nilai_diskon' => 5000,
                    'minimal_pembelian' => 0,
                    'kuota_per_pengguna' => 5,
                ])->kode_voucher,
                'subtotal' => 3000,
            ],
            'expected' => 200,
        ]);

        $this->assertSame(3000.0, (float) ($this->recorded[2]['response']['data']['jumlah_diskon'] ?? 0));
        $this->note('Diskon tidak pernah melebihi subtotal yang dikirim.');

        $this->assertSame($persen->id_voucher, $this->recorded[0]['response']['data']['id_voucher'] ?? null);

        $this->assertRecorded();
    }

    public function test_voucher_validate_gagal(): void
    {
        $user = $this->actingAsRole('pelanggan');

        $this->makeVoucher(['kode_voucher' => 'NONAKTIFX', 'status_voucher' => 'nonaktif']);
        $this->makeVoucher([
            'kode_voucher' => 'BELUMX',
            'tanggal_mulai' => now()->addDay(),
            'tanggal_selesai' => now()->addDays(3),
        ]);
        $this->makeVoucher([
            'kode_voucher' => 'LEWATX',
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_selesai' => now()->subDay(),
        ]);
        $mahal = $this->makeVoucher([
            'kode_voucher' => 'MINX',
            'minimal_pembelian' => 500000,
        ]);
        $habis = $this->makeVoucher([
            'kode_voucher' => 'HABISX',
            'kuota_total' => 2,
            'kuota_per_pengguna' => 2,
        ]);
        $this->makeUsage($habis, $this->makeUser('pelanggan'));
        $this->makeUsage($habis, $this->makeUser('pelanggan'));
        $sendiri = $this->makeVoucher([
            'kode_voucher' => 'SEBAX',
            'kuota_per_pengguna' => 1,
        ]);
        $this->makeUsage($sendiri, $user, 'digunakan');

        $cases = [
            'KODETIDAKADA' => ['subtotal' => 100000, 'pesan' => 'Kode voucher tidak ditemukan.'],
            'NONAKTIFX' => ['subtotal' => 100000, 'pesan' => 'Voucher tidak aktif.'],
            'BELUMX' => ['subtotal' => 100000, 'pesan' => 'Voucher belum berlaku.'],
            'LEWATX' => ['subtotal' => 100000, 'pesan' => 'Voucher sudah kedaluwarsa.'],
            'MINX' => ['subtotal' => 10000, 'pesan' => 'Minimal pembelian'],
            'HABISX' => ['subtotal' => 100000, 'pesan' => 'Kuota voucher sudah habis'],
            'SEBAX' => ['subtotal' => 100000, 'pesan' => 'Kuota voucher sudah habis'],
        ];

        foreach ($cases as $kode => $case) {
            $this->hit('POST', '/api/vouchers/validate', [
                'endpoint' => 'POST /api/vouchers/validate',
                'label' => 'Validasi voucher gagal: '.$kode,
                'json' => ['kode_voucher' => $kode, 'subtotal' => $case['subtotal']],
                'expected' => 422,
            ]);
        }

        foreach ($this->recorded as $i => $entry) {
            $kode = array_keys($cases)[$i];
            $this->assertStringContainsString(
                $cases[$kode]['pesan'],
                (string) ($entry['response']['message'] ?? ''),
                'Pesan kegagalan tidak sesuai: '.$entry['label']
            );
            $this->assertSame([], $entry['db_effect'], 'Validasi tidak boleh mengubah database: '.$entry['label']);
        }

        $this->hit('POST', '/api/vouchers/validate', [
            'endpoint' => 'POST /api/vouchers/validate',
            'label' => 'Validasi voucher tanpa field wajib',
            'json' => [],
            'expected' => 422,
        ]);

        $errors = $this->recorded[7]['response']['errors'] ?? [];
        $this->assertArrayHasKey('kode_voucher', $errors);
        $this->assertArrayHasKey('subtotal', $errors);

        $this->assertRecorded();
    }

    public function test_voucher_usages_me(): void
    {
        $user = $this->actingAsRole('pelanggan');
        $other = $this->makeUser('pelanggan');
        $voucher = $this->makeVoucher(['kode_voucher' => 'MILIKSAYA']);
        $this->makeUsage($voucher, $user, 'digunakan');
        $this->makeUsage($voucher, $other, 'menunggu');

        $this->hit('GET', '/api/voucher-usages/me', [
            'endpoint' => 'GET /api/voucher-usages/me',
            'label' => 'Riwayat penggunaan voucher milik sendiri',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data, 'Riwayat pengguna milik lain ikut tampil.');
        $this->assertSame($user->id_user, $data[0]['id_user'] ?? null);
        $this->assertSame('MILIKSAYA', $data[0]['voucher']['kode_voucher'] ?? null);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Admin
    // ------------------------------------------------------------------

    public function test_admin_voucher_akses(): void
    {
        $this->asGuest();
        $this->hit('GET', '/api/vouchers', [
            'endpoint' => 'GET /api/vouchers',
            'label' => 'Daftar voucher (admin) tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('GET', '/api/vouchers', [
            'endpoint' => 'GET /api/vouchers',
            'label' => 'Daftar voucher (admin) oleh pelanggan',
            'expected' => 403,
        ]);

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher oleh pelanggan',
            'json' => ['kode_voucher' => 'X', 'nama_voucher' => 'X'],
            'expected' => 403,
        ]);

        $this->hit('GET', '/api/voucher-usages', [
            'endpoint' => 'GET /api/voucher-usages',
            'label' => 'Daftar penggunaan voucher (admin) oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_voucher_index(): void
    {
        $this->actingAsRole('admin');
        $this->makeVoucher(['kode_voucher' => 'IDX1']);
        $this->makeVoucher(['kode_voucher' => 'IDX2', 'nama_voucher' => 'Voucher Kedua']);

        $this->hit('GET', '/api/vouchers', [
            'endpoint' => 'GET /api/vouchers',
            'label' => 'Daftar voucher (admin) dengan meta paginasi',
            'expected' => 200,
        ]);

        $meta = $this->recorded[0]['response']['meta'] ?? [];
        $this->assertSame(15, (int) ($meta['per_page'] ?? 0), 'per_page default seharusnya 15.');
        $this->assertSame(2, (int) ($meta['total'] ?? 0));

        $this->hit('GET', '/api/vouchers?per_page=1&search=IDX2', [
            'endpoint' => 'GET /api/vouchers',
            'label' => 'Pencarian voucher + per_page=1',
            'expected' => 200,
        ]);

        $data = $this->recorded[1]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame('IDX2', $data[0]['kode_voucher'] ?? null);
        $this->assertSame(1, (int) ($this->recorded[1]['response']['meta']['per_page'] ?? 0));

        $this->hit('GET', '/api/vouchers?per_page=101', [
            'endpoint' => 'GET /api/vouchers',
            'label' => 'per_page melebihi batas 100',
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_voucher_store(): void
    {
        $this->actingAsRole('admin');
        $payload = [
            'kode_voucher' => 'BARU01',
            'nama_voucher' => 'Voucher Baru',
            'tipe_diskon' => 'persentase',
            'nilai_diskon' => 15,
            'minimal_pembelian' => 10000,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDays(7)->toDateString(),
        ];

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher (admin)',
            'json' => $payload,
            'expected' => 201,
        ]);

        $this->assertSame(['vouchers' => '0 -> 1 (+1)'], $this->recorded[0]['db_effect']);
        $this->assertSame('aktif', $this->recorded[0]['response']['data']['status_voucher'] ?? null);

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher dengan kode duplikat',
            'json' => $payload,
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher tanpa data wajib',
            'json' => [],
            'expected' => 422,
        ]);

        $errors = $this->recorded[2]['response']['errors'] ?? [];
        foreach (['kode_voucher', 'nama_voucher', 'tipe_diskon', 'nilai_diskon', 'tanggal_mulai', 'tanggal_selesai'] as $field) {
            $this->assertArrayHasKey($field, $errors, "Field {$field} tidak divalidasi.");
        }

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher tipe diskon tidak dikenal',
            'json' => array_merge($payload, ['kode_voucher' => 'TIPEX', 'tipe_diskon' => 'fix']),
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher tanggal selesai sebelum tanggal mulai',
            'json' => array_merge($payload, [
                'kode_voucher' => 'TANGGALX',
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->subDay()->toDateString(),
            ]),
            'expected' => 422,
        ]);

        $this->assertSame([], $this->recorded[3]['db_effect']);
        $this->assertSame([], $this->recorded[4]['db_effect']);
        $this->assertSame([], $this->recorded[1]['db_effect'], 'Kode duplikat tidak menambah baris.');

        $this->assertRecorded();
    }

    public function test_admin_voucher_show_update_destroy(): void
    {
        $this->actingAsRole('admin');
        $voucher = $this->makeVoucher(['kode_voucher' => 'SHOWME']);

        $this->hit('GET', '/api/vouchers/'.$voucher->id_voucher, [
            'endpoint' => 'GET /api/vouchers/{id}',
            'label' => 'Detail voucher (admin)',
            'expected' => 200,
        ]);

        $this->hit('GET', '/api/vouchers/tidak-ada', [
            'endpoint' => 'GET /api/vouchers/{id}',
            'label' => 'Detail voucher tidak ditemukan',
            'expected' => 404,
        ]);

        $this->hit('PUT', '/api/vouchers/'.$voucher->id_voucher, [
            'endpoint' => 'PUT /api/vouchers/{id}',
            'label' => 'Perbarui sebagian field voucher',
            'json' => ['nama_voucher' => 'Voucher Diubah', 'nilai_diskon' => 25],
            'expected' => 200,
        ]);

        $data = $this->recorded[2]['response']['data'] ?? [];
        $this->assertSame('Voucher Diubah', $data['nama_voucher'] ?? null);
        $this->assertSame('SHOWME', $data['kode_voucher'] ?? null, 'Field yang tidak dikirim ikut berubah.');
        $this->assertSame(25.0, (float) ($data['nilai_diskon'] ?? 0));

        $this->hit('PUT', '/api/vouchers/'.$voucher->id_voucher, [
            'endpoint' => 'PUT /api/vouchers/{id}',
            'label' => 'Perbarui voucher dengan kode milik voucher lain (duplikat)',
            'json' => ['kode_voucher' => $this->makeVoucher(['kode_voucher' => 'PUNYAORANG'])->kode_voucher],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/vouchers/tidak-ada', [
            'endpoint' => 'PUT /api/vouchers/{id}',
            'label' => 'Perbarui voucher tidak ditemukan',
            'json' => ['nama_voucher' => 'X'],
            'expected' => 404,
        ]);

        $this->hit('DELETE', '/api/vouchers/'.$voucher->id_voucher, [
            'endpoint' => 'DELETE /api/vouchers/{id}',
            'label' => 'Nonaktifkan voucher (DELETE bersifat soft delete)',
            'expected' => 200,
        ]);

        $this->assertSame('nonaktif', $voucher->fresh()->status_voucher);
        $this->assertSame([], $this->recorded[5]['db_effect'], 'Baris voucher tetap ada (dinonaktifkan).');
        $this->note('Endpoint DELETE /api/vouchers menonaktifkan voucher, tidak menghapus baris.');

        $this->hit('DELETE', '/api/vouchers/tidak-ada', [
            'endpoint' => 'DELETE /api/vouchers/{id}',
            'label' => 'Nonaktifkan voucher tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_voucher_usages_index(): void
    {
        $this->actingAsRole('admin');
        $voucher = $this->makeVoucher(['kode_voucher' => 'RIWAYAT']);
        $user = $this->makeUser('pelanggan');
        $this->makeUsage($voucher, $user, 'digunakan');

        $this->hit('GET', '/api/voucher-usages', [
            'endpoint' => 'GET /api/voucher-usages',
            'label' => 'Daftar penggunaan voucher (admin) + relasi',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame('RIWAYAT', $data[0]['voucher']['kode_voucher'] ?? null);
        $this->assertSame($user->email, $data[0]['user']['email'] ?? null);

        $this->assertRecorded();
    }

    public function test_admin_voucher_gambar(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher dengan gambar promo',
            'json' => [
                'kode_voucher' => 'GAMBAR01',
                'nama_voucher' => 'Promo Bergambar',
                'deskripsi' => 'Voucher banner beranda',
                'tipe_diskon' => 'persentase',
                'nilai_diskon' => 20,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->addDays(7)->toDateString(),
            ],
            'files' => ['gambar' => UploadedFile::fake()->image('promo.png', 300, 150)],
            'expected' => 201,
        ]);

        $id = $this->recorded[0]['response']['data']['id_voucher'] ?? null;
        $path = $this->recorded[0]['response']['data']['gambar'] ?? null;
        $this->assertNotEmpty($id);
        $this->assertNotEmpty($this->recorded[0]['response']['data']['url_gambar'] ?? null);
        Storage::disk('public')->assertExists($path);

        $this->hit('PUT', '/api/vouchers/'.$id, [
            'endpoint' => 'PUT /api/vouchers/{id}',
            'label' => 'Ganti gambar voucher',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('promo2.png', 300, 150)],
            'expected' => 200,
        ]);
        $newPath = $this->recorded[1]['response']['data']['gambar'] ?? null;
        $this->assertNotSame($path, $newPath);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($newPath);

        $this->hit('DELETE', '/api/vouchers/'.$id.'/gambar', [
            'endpoint' => 'DELETE /api/vouchers/{id}/gambar',
            'label' => 'Hapus gambar voucher (voucher tetap ada)',
            'expected' => 200,
        ]);
        $this->assertArrayHasKey('gambar', $this->recorded[2]['response']['data']);
        $this->assertNull($this->recorded[2]['response']['data']['gambar']);
        Storage::disk('public')->assertMissing($newPath);
        $this->assertNotNull(Voucher::find($id), 'Voucher ikut terhapus.');

        $this->hit('DELETE', '/api/vouchers/tidak-ada/gambar', [
            'endpoint' => 'DELETE /api/vouchers/{id}/gambar',
            'label' => 'Hapus gambar voucher tidak ditemukan',
            'expected' => 404,
        ]);

        $this->hit('POST', '/api/vouchers', [
            'endpoint' => 'POST /api/vouchers',
            'label' => 'Buat voucher dengan berkas bukan gambar',
            'json' => [
                'kode_voucher' => 'BUKANGAMBAR',
                'nama_voucher' => 'Salah Berkas',
                'tipe_diskon' => 'nominal',
                'nilai_diskon' => 10000,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->addDays(7)->toDateString(),
            ],
            'files' => ['gambar' => UploadedFile::fake()->create('catatan.txt', 10, 'text/plain')],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }
}
