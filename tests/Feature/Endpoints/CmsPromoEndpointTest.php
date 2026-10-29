<?php

namespace Tests\Feature\Endpoints;

use App\Models\CmsPromo;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Pengujian endpoint CMS Promo (Kelola Promo Admin & Publik).
 */
class CmsPromoEndpointTest extends EndpointTestCase
{
    protected static string $group = 'CMS Promo';

    public function test_admin_cms_promo_belum_diatur_mengembalikan_null(): void
    {
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/cms/promo', [
            'endpoint' => 'GET /api/admin/cms/promo',
            'label' => 'Konfigurasi promo belum diatur (data: null)',
            'expected' => 200,
        ]);

        $this->assertSame(200, $this->recorded[0]['actual_status']);
        $this->assertTrue($this->recorded[0]['response']['success']);
        $this->assertNull($this->recorded[0]['response']['data']);

        $this->assertRecorded();
    }

    public function test_admin_cms_promo_auth_guard(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/admin/cms/promo', [
            'endpoint' => 'GET /api/admin/cms/promo',
            'label' => 'Guest akses GET admin promo ditolak',
            'expected' => 401,
        ]);

        $this->hit('PUT', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Guest akses PUT admin promo ditolak',
            'json' => [],
            'expected' => 401,
        ]);

        $this->hit('DELETE', '/api/admin/cms/promo/gambar', [
            'endpoint' => 'DELETE /api/admin/cms/promo/gambar',
            'label' => 'Guest akses DELETE admin promo gambar ditolak',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');

        $this->hit('GET', '/api/admin/cms/promo', [
            'endpoint' => 'GET /api/admin/cms/promo',
            'label' => 'Pelanggan akses GET admin promo ditolak',
            'expected' => 403,
        ]);

        $this->hit('PUT', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Pelanggan akses PUT admin promo ditolak',
            'json' => [],
            'expected' => 403,
        ]);

        $this->hit('DELETE', '/api/admin/cms/promo/gambar', [
            'endpoint' => 'DELETE /api/admin/cms/promo/gambar',
            'label' => 'Pelanggan akses DELETE admin promo gambar ditolak',
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_cms_promo_validasi(): void
    {
        $this->actingAsRole('admin');

        // id_voucher wajib
        $this->hit('PUT', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Validasi id_voucher wajib',
            'json' => [],
            'expected' => 422,
        ]);

        // id_voucher harus ada di tabel vouchers
        $this->hit('PUT', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Validasi id_voucher tidak ditemukan',
            'json' => ['id_voucher' => 'voucher-tidak-ada'],
            'expected' => 422,
        ]);

        // badge maksimal 40 karakter
        $voucher = $this->makeVoucher();
        $this->hit('PUT', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Validasi badge > 40 karakter',
            'json' => [
                'id_voucher' => $voucher->id_voucher,
                'badge' => str_repeat('a', 41),
            ],
            'expected' => 422,
        ]);

        // tautan_tujuan harus path relatif (diawali /) atau URL absolut
        $this->hit('PUT', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Validasi tautan_tujuan bukan path / url',
            'json' => [
                'id_voucher' => $voucher->id_voucher,
                'tautan_tujuan' => 'bukan-url-dan-bukan-path',
            ],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_cms_promo_upsert_dan_ambil(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $voucher = $this->makeVoucher([
            'kode_voucher' => 'DISKON50',
            'nama_voucher' => 'Diskon Spesial 50%',
        ]);

        $this->hit('POST', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Upsert konfigurasi promo admin + upload gambar',
            'json' => [
                '_method' => 'PUT',
                'id_voucher' => $voucher->id_voucher,
                'badge' => 'Promo Terbatas',
                'tautan_tujuan' => '/katalog',
            ],
            'files' => [
                'gambar' => UploadedFile::fake()->image('banner.png', 800, 400),
            ],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame(1, $data['id_cms_promo']);
        $this->assertSame($voucher->id_voucher, $data['id_voucher']);
        $this->assertSame('Promo Terbatas', $data['badge']);
        $this->assertSame('/katalog', $data['tautan_tujuan']);
        $this->assertNotEmpty($data['gambar']);
        $this->assertNotEmpty($data['url_gambar']);
        $this->assertSame($voucher->id_voucher, $data['voucher']['id_voucher']);
        $this->assertSame('DISKON50', $data['voucher']['kode_voucher']);

        Storage::disk('public')->assertExists($data['gambar']);

        // Pastikan GET /api/admin/cms/promo mengembalikan data yang sama
        $this->hit('GET', '/api/admin/cms/promo', [
            'endpoint' => 'GET /api/admin/cms/promo',
            'label' => 'Ambil konfigurasi promo yang sudah diatur',
            'expected' => 200,
        ]);

        $getData = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame($voucher->id_voucher, $getData['id_voucher']);
        $this->assertSame('Promo Terbatas', $getData['badge']);

        $this->assertRecorded();
    }

    public function test_admin_cms_promo_replace_gambar_menghapus_file_lama(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $voucher = $this->makeVoucher();

        // Upload pertama
        $this->hit('POST', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Upload pertama banner promo',
            'json' => [
                '_method' => 'PUT',
                'id_voucher' => $voucher->id_voucher,
            ],
            'files' => [
                'gambar' => UploadedFile::fake()->image('gambar1.png', 100, 100),
            ],
            'expected' => 200,
        ]);

        $gambar1 = $this->recorded[0]['response']['data']['gambar'] ?? null;
        $this->assertNotEmpty($gambar1);
        Storage::disk('public')->assertExists($gambar1);

        // Upload kedua (replace)
        $this->hit('POST', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Upload kedua banner promo (ganti gambar)',
            'json' => [
                '_method' => 'PUT',
                'id_voucher' => $voucher->id_voucher,
            ],
            'files' => [
                'gambar' => UploadedFile::fake()->image('gambar2.webp', 100, 100),
            ],
            'expected' => 200,
        ]);

        $gambar2 = $this->recorded[1]['response']['data']['gambar'] ?? null;
        $this->assertNotEmpty($gambar2);
        $this->assertNotSame($gambar1, $gambar2);

        Storage::disk('public')->assertMissing($gambar1);
        Storage::disk('public')->assertExists($gambar2);

        $this->assertRecorded();
    }

    public function test_admin_cms_promo_hapus_gambar_idempoten(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $voucher = $this->makeVoucher();

        $this->hit('POST', '/api/admin/cms/promo', [
            'endpoint' => 'PUT /api/admin/cms/promo',
            'label' => 'Setup promo dengan gambar',
            'json' => [
                '_method' => 'PUT',
                'id_voucher' => $voucher->id_voucher,
                'badge' => 'Tetap Ada',
            ],
            'files' => [
                'gambar' => UploadedFile::fake()->image('promo.png', 100, 100),
            ],
            'expected' => 200,
        ]);

        $oldFile = $this->recorded[0]['response']['data']['gambar'];
        Storage::disk('public')->assertExists($oldFile);

        // Hapus gambar
        $this->hit('DELETE', '/api/admin/cms/promo/gambar', [
            'endpoint' => 'DELETE /api/admin/cms/promo/gambar',
            'label' => 'Hapus gambar banner promo',
            'expected' => 200,
        ]);

        $delData = $this->recorded[1]['response']['data'] ?? [];
        $this->assertSame($voucher->id_voucher, $delData['id_voucher']);
        $this->assertSame('Tetap Ada', $delData['badge']);
        $this->assertNull($delData['gambar']);
        $this->assertNull($delData['url_gambar']);

        Storage::disk('public')->assertMissing($oldFile);
        $this->assertNull(CmsPromo::find(1)->gambar);

        // Panggilan kedua tetap 200 (idempoten)
        $this->hit('DELETE', '/api/admin/cms/promo/gambar', [
            'endpoint' => 'DELETE /api/admin/cms/promo/gambar',
            'label' => 'Hapus gambar kedua kali (idempoten)',
            'expected' => 200,
        ]);

        $this->assertRecorded();
    }

    public function test_cms_promo_publik_menggunakan_pin_voucher(): void
    {
        Storage::fake('public');

        // Buat voucher lama yang di-pin
        $pinnedVoucher = $this->makeVoucher([
            'kode_voucher' => 'PINNED',
            'nama_voucher' => 'Voucher Unggulan Pin',
            'dibuat_pada' => Carbon::now()->subDays(2),
        ]);

        // Buat voucher baru (secara auto-pick biasanya ini yang terpilih bila tanpa pin)
        $this->makeVoucher([
            'kode_voucher' => 'NEWER',
            'nama_voucher' => 'Voucher Baru',
            'gambar' => 'vouchers/newer.jpg',
            'dibuat_pada' => Carbon::now(),
        ]);

        // Pin voucher di CmsPromo
        CmsPromo::create([
            'id_cms_promo' => 1,
            'id_voucher' => $pinnedVoucher->id_voucher,
            'badge' => 'Badge Spesial',
            'tautan_tujuan' => '/detail-promo',
            'gambar' => 'cms/promo/custom.png',
        ]);

        $this->asGuest();
        $this->hit('GET', '/api/cms/promo', [
            'endpoint' => 'GET /api/cms/promo',
            'label' => 'Promo publik menggunakan voucher pin',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($pinnedVoucher->id_voucher, $data['id_voucher']);
        $this->assertSame('PINNED', $data['kode_voucher']);
        $this->assertSame('Badge Spesial', $data['badge']);
        $this->assertSame('/detail-promo', $data['tautan_tujuan']);
        $this->assertSame('cms/promo/custom.png', $data['gambar']);
        $this->assertStringContainsString('cms/promo/custom.png', $data['url_gambar']);

        $this->assertRecorded();
    }

    public function test_cms_promo_publik_fallback_bila_pin_tidak_aktif(): void
    {
        // Buat voucher pin tetapi status nonaktif
        $pinnedVoucher = $this->makeVoucher([
            'kode_voucher' => 'PINNED_NONAKTIF',
            'status_voucher' => 'nonaktif',
        ]);

        // Buat voucher aktif cadangan
        $activeVoucher = $this->makeVoucher([
            'kode_voucher' => 'CADANGAN',
            'nama_voucher' => 'Voucher Cadangan',
            'gambar' => 'vouchers/cadangan.jpg',
        ]);

        CmsPromo::create([
            'id_cms_promo' => 1,
            'id_voucher' => $pinnedVoucher->id_voucher,
            'badge' => 'Badge Tidak Boleh Tampil',
            'tautan_tujuan' => '/link-batal',
            'gambar' => 'cms/promo/batal.png',
        ]);

        $this->asGuest();
        $this->hit('GET', '/api/cms/promo', [
            'endpoint' => 'GET /api/cms/promo',
            'label' => 'Promo publik fallback ke auto-pick saat pin nonaktif',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($activeVoucher->id_voucher, $data['id_voucher']);
        $this->assertSame('CADANGAN', $data['kode_voucher']);
        $this->assertNull($data['badge']);
        $this->assertNull($data['tautan_tujuan']);
        $this->assertSame('vouchers/cadangan.jpg', $data['gambar']);

        $this->assertRecorded();
    }

    public function test_cms_promo_publik_prioritas_gambar_voucher_jika_cms_promo_tanpa_gambar(): void
    {
        $voucher = $this->makeVoucher([
            'kode_voucher' => 'VOUCHER_GAMBAR',
            'gambar' => 'vouchers/original.jpg',
        ]);

        CmsPromo::create([
            'id_cms_promo' => 1,
            'id_voucher' => $voucher->id_voucher,
            'badge' => 'Voucher Asli',
            'tautan_tujuan' => null,
            'gambar' => null,
        ]);

        $this->asGuest();
        $this->hit('GET', '/api/cms/promo', [
            'endpoint' => 'GET /api/cms/promo',
            'label' => 'Promo publik fallback ke gambar voucher jika promo tanpa gambar',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame($voucher->id_voucher, $data['id_voucher']);
        $this->assertSame('Voucher Asli', $data['badge']);
        $this->assertSame('vouchers/original.jpg', $data['gambar']);
        $this->assertStringContainsString('vouchers/original.jpg', $data['url_gambar']);

        $this->assertRecorded();
    }

    public function test_cms_promo_publik_menyembunyikan_voucher_produk_yang_stoknya_habis(): void
    {
        $catalog = $this->seedCatalog(0);
        $voucher = $this->makeVoucher([
            'id_product_peruntukan' => $catalog['product']->id_product,
        ]);
        CmsPromo::create([
            'id_cms_promo' => 1,
            'id_voucher' => $voucher->id_voucher,
        ]);

        $this->asGuest();
        $this->hit('GET', '/api/cms/promo', [
            'endpoint' => 'GET /api/cms/promo',
            'label' => 'Promo produk habis tidak ditampilkan',
            'expected' => 200,
        ]);
        $this->assertNull($this->recorded[0]['response']['data']);

        $this->hit('GET', '/api/cms/promos', [
            'endpoint' => 'GET /api/cms/promos',
            'label' => 'Daftar promo produk habis tidak ditampilkan',
            'expected' => 200,
        ]);
        $this->assertSame([], $this->recorded[1]['response']['data']);

        $this->assertRecorded();
    }

    public function test_admin_cms_promos_auth_guard(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/admin/cms/promos', [
            'endpoint' => 'GET /api/admin/cms/promos',
            'label' => 'Guest akses daftar promo admin ditolak',
            'expected' => 401,
        ]);

        $this->hit('POST', '/api/admin/cms/promos', [
            'endpoint' => 'POST /api/admin/cms/promos',
            'label' => 'Guest akses tambah promo admin ditolak',
            'json' => [],
            'expected' => 401,
        ]);

        $this->hit('PUT', '/api/admin/cms/promos/1', [
            'endpoint' => 'PUT /api/admin/cms/promos/{id}',
            'label' => 'Guest akses ubah promo admin ditolak',
            'json' => [],
            'expected' => 401,
        ]);

        $this->hit('DELETE', '/api/admin/cms/promos/1', [
            'endpoint' => 'DELETE /api/admin/cms/promos/{id}',
            'label' => 'Guest akses hapus promo admin ditolak',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');

        $this->hit('GET', '/api/admin/cms/promos', [
            'endpoint' => 'GET /api/admin/cms/promos',
            'label' => 'Pelanggan akses daftar promo admin ditolak',
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_cms_promos_crud_deletes_only_promo_configuration(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');
        $voucher = $this->makeVoucher();

        $this->hit('POST', '/api/admin/cms/promos', [
            'endpoint' => 'POST /api/admin/cms/promos',
            'label' => 'Admin membuat promo baru',
            'json' => [
                'id_voucher' => $voucher->id_voucher,
                'badge' => 'Promo Baru',
                'tautan_tujuan' => '/produk',
            ],
            'files' => [
                'gambar' => UploadedFile::fake()->image('promo.png', 100, 100),
            ],
            'expected' => 201,
        ]);

        $created = $this->recorded[0]['response']['data'];
        $promoId = $created['id_cms_promo'];
        $gambar = $created['gambar'];
        $this->assertSame($voucher->id_voucher, $created['id_voucher']);
        $this->assertSame('Promo Baru', $created['badge']);
        Storage::disk('public')->assertExists($gambar);
        $this->assertDatabaseHas('cms_promo', [
            'id_cms_promo' => $promoId,
            'id_voucher' => $voucher->id_voucher,
        ]);

        $this->hit('PUT', '/api/admin/cms/promos/'.$promoId, [
            'endpoint' => 'PUT /api/admin/cms/promos/{id}',
            'label' => 'Admin mengubah badge promo',
            'json' => ['badge' => 'Promo Baru Diperbarui'],
            'expected' => 200,
        ]);

        $updated = $this->recorded[1]['response']['data'];
        $this->assertSame('Promo Baru Diperbarui', $updated['badge']);
        $this->assertDatabaseHas('cms_promo', [
            'id_cms_promo' => $promoId,
            'badge' => 'Promo Baru Diperbarui',
        ]);

        $this->hit('GET', '/api/admin/cms/promos', [
            'endpoint' => 'GET /api/admin/cms/promos',
            'label' => 'Admin membaca daftar promo terbaru lebih dahulu',
            'expected' => 200,
        ]);

        $this->assertSame($promoId, $this->recorded[2]['response']['data'][0]['id_cms_promo']);

        $this->hit('DELETE', '/api/admin/cms/promos/'.$promoId, [
            'endpoint' => 'DELETE /api/admin/cms/promos/{id}',
            'label' => 'Admin menghapus konfigurasi promo',
            'expected' => 200,
        ]);

        $this->assertDatabaseMissing('cms_promo', ['id_cms_promo' => $promoId]);
        $this->assertDatabaseHas('vouchers', ['id_voucher' => $voucher->id_voucher]);
        Storage::disk('public')->assertMissing($gambar);
        $this->assertRecorded();
    }

    public function test_admin_cms_promos_requires_a_valid_voucher(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/cms/promos', [
            'endpoint' => 'POST /api/admin/cms/promos',
            'label' => 'Validasi voucher wajib pada promo jamak',
            'json' => [],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/cms/promos', [
            'endpoint' => 'POST /api/admin/cms/promos',
            'label' => 'Validasi voucher promo harus ada',
            'json' => ['id_voucher' => 'voucher-tidak-ada'],
            'expected' => 422,
        ]);

        $voucher = $this->makeVoucher();

        $this->hit('POST', '/api/admin/cms/promos', [
            'endpoint' => 'POST /api/admin/cms/promos',
            'label' => 'Validasi badge promo maksimal 40 karakter',
            'json' => [
                'id_voucher' => $voucher->id_voucher,
                'badge' => str_repeat('a', 41),
            ],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/cms/promos', [
            'endpoint' => 'POST /api/admin/cms/promos',
            'label' => 'Validasi urutan promo tidak melebihi 65535',
            'json' => [
                'id_voucher' => $voucher->id_voucher,
                'urutan_tampilan' => 65536,
            ],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    public function test_cms_promos_returns_configured_vouchers_that_are_active_and_in_period_in_order(): void
    {
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00'));

        $firstVoucher = $this->makeVoucher([
            'kode_voucher' => 'PROMO_URUTAN_1',
        ]);
        $secondVoucher = $this->makeVoucher([
            'kode_voucher' => 'PROMO_URUTAN_2',
        ]);
        $inactiveVoucher = $this->makeVoucher([
            'kode_voucher' => 'PROMO_NONAKTIF',
            'status_voucher' => 'nonaktif',
        ]);
        $expiredVoucher = $this->makeVoucher([
            'kode_voucher' => 'PROMO_KEDALUWARSA',
            'tanggal_selesai' => Carbon::parse('2026-10-22 23:59:59'),
        ]);
        $futureVoucher = $this->makeVoucher([
            'kode_voucher' => 'PROMO_MENDATANG',
            'tanggal_mulai' => Carbon::parse('2026-10-24 00:00:00'),
        ]);

        CmsPromo::create([
            'id_voucher' => $secondVoucher->id_voucher,
        ]);
        CmsPromo::create([
            'id_voucher' => $firstVoucher->id_voucher,
        ]);
        CmsPromo::create(['id_voucher' => $inactiveVoucher->id_voucher]);
        CmsPromo::create(['id_voucher' => $expiredVoucher->id_voucher]);
        CmsPromo::create(['id_voucher' => $futureVoucher->id_voucher]);

        $this->asGuest();
        $this->hit('GET', '/api/cms/promos', [
            'endpoint' => 'GET /api/cms/promos',
            'label' => 'Promo publik hanya menampilkan voucher aktif sesuai periode, sortir terbaru',
            'expected' => 200,
        ]);

        $promos = $this->recorded[0]['response']['data'];
        $this->assertSame([
            $firstVoucher->id_voucher,
            $secondVoucher->id_voucher,
        ], array_column($promos, 'id_voucher'));
        $this->assertArrayHasKey('urutan_tampilan', $promos[1]);
        $this->assertSame('PROMO_URUTAN_1', $promos[0]['kode_voucher']);
        $this->assertSame('PROMO_URUTAN_2', $promos[1]['kode_voucher']);

        $this->assertRecorded();
    }

    public function test_cms_promos_returns_empty_array_when_no_configured_promo_is_active(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/cms/promos', [
            'endpoint' => 'GET /api/cms/promos',
            'label' => 'Promo publik kosong mengembalikan array kosong',
            'expected' => 200,
        ]);

        $this->assertSame([], $this->recorded[0]['response']['data']);
        $this->assertRecorded();
    }
}
