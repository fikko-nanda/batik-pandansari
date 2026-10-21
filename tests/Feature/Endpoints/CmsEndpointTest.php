<?php

namespace Tests\Feature\Endpoints;

use App\Models\CmsGaleri;
use App\Models\CmsGambarGaleri;
use App\Models\CmsKategori;
use App\Models\FasilitasUsaha;
use App\Models\ProfilUsaha;
use App\Models\SlideHero;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Endpoint CMS: konten halaman utama (hero, kategori, promo, galeri,
 * profil usaha, fasilitas) beserta CRUD admin-nya.
 */
class CmsEndpointTest extends EndpointTestCase
{
    protected static string $group = 'CMS';

    // ------------------------------------------------------------------
    // Akses publik
    // ------------------------------------------------------------------

    public function test_cms_publik_akses_tanpa_token(): void
    {
        $this->asGuest();

        foreach ([
            'GET /api/cms/homepage' => '/api/cms/homepage',
            'GET /api/cms/promo' => '/api/cms/promo',
            'GET /api/cms/hero' => '/api/cms/hero',
            'GET /api/cms/kategori' => '/api/cms/kategori',
            'GET /api/cms/galeri' => '/api/cms/galeri',
            'GET /api/cms/profil-usaha' => '/api/cms/profil-usaha',
            'GET /api/cms/fasilitas' => '/api/cms/fasilitas',
        ] as $endpoint => $url) {
            $this->hit('GET', $url, [
                'endpoint' => $endpoint,
                'label' => $endpoint.' (publik)',
                'expected' => 200,
            ]);
        }

        $this->assertRecorded();
    }

    public function test_cms_homepage_menggabungkan_konten(): void
    {
        $this->seedCmsHero(['judul' => 'Slide Satu']);
        $this->seedCmsKategori(['nama_kategori' => 'Batik Tulis']);
        $this->seedCmsKategori(['nama_kategori' => 'Batik Cap']);
        $this->seedCmsKategori(['nama_kategori' => 'Aksesoris']);
        $this->seedCmsGaleri();
        $this->seedCmsGambarGaleri();
        $this->seedCmsProfil();
        $this->seedCmsFasilitas(['nama_fasilitas' => 'Parkir Luas']);
        $this->makeVoucher(['kode_voucher' => 'PROMO10', 'nama_voucher' => 'Promo Spesial']);
        $this->asGuest();

        $this->hit('GET', '/api/cms/homepage', [
            'endpoint' => 'GET /api/cms/homepage',
            'label' => 'Agregat konten beranda',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data['hero'] ?? []);
        $this->assertCount(3, $data['kategori'] ?? []);
        $this->assertSame('Promo Spesial', $data['promo']['nama_voucher'] ?? null);
        $this->assertNotNull($data['galeri'] ?? null);
        $this->assertCount(1, $data['galeri']['gambar'] ?? []);
        $this->assertSame('Batik Pandansari', $data['profil_usaha']['nama_usaha'] ?? null);
        $this->assertCount(1, $data['fasilitas'] ?? []);

        $this->assertRecorded();
    }

    public function test_cms_homepage_kosong_tetap_sukses(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/cms/homepage', [
            'endpoint' => 'GET /api/cms/homepage',
            'label' => 'Beranda saat belum ada konten',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame([], $data['hero'] ?? null);
        $this->assertArrayHasKey('promo', $data);
        $this->assertNull($data['promo']);
        $this->assertArrayHasKey('galeri', $data);
        $this->assertNull($data['galeri']);
        $this->assertArrayHasKey('profil_usaha', $data);
        $this->assertNull($data['profil_usaha']);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Promo (mengambil 1 voucher aktif)
    // ------------------------------------------------------------------

    public function test_cms_promo_memilih_voucher_aktif(): void
    {
        $this->makeVoucher(['kode_voucher' => 'NONAKTIF', 'status_voucher' => 'nonaktif']);
        $this->makeVoucher([
            'kode_voucher' => 'KEDALUWARSA',
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_selesai' => now()->subDay(),
        ]);
        $unggulan = $this->makeVoucher([
            'kode_voucher' => 'PILIHAN',
            'nama_voucher' => 'Dipilih',
            'gambar' => 'vouchers/pilihan.jpg',
        ]);
        $this->asGuest();

        $this->hit('GET', '/api/cms/promo', [
            'endpoint' => 'GET /api/cms/promo',
            'label' => 'Promo mengambil voucher aktif bergambar',
            'expected' => 200,
        ]);

        $this->assertSame($unggulan->id_voucher, $this->recorded[0]['response']['data']['id_voucher'] ?? null);
        $this->assertNotEmpty($this->recorded[0]['response']['data']['url_gambar'] ?? null);

        $this->assertRecorded();
    }

    public function test_cms_promo_tanpa_voucher_aktif(): void
    {
        $this->makeVoucher(['kode_voucher' => 'NONAKTIF', 'status_voucher' => 'nonaktif']);
        $this->asGuest();

        $this->hit('GET', '/api/cms/promo', [
            'endpoint' => 'GET /api/cms/promo',
            'label' => 'Promo tanpa voucher aktif',
            'expected' => 200,
        ]);

        $this->assertArrayHasKey('data', $this->recorded[0]['response']);
        $this->assertNull($this->recorded[0]['response']['data']);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Hero
    // ------------------------------------------------------------------

    public function test_hero_publik_hanya_slide_aktif(): void
    {
        $this->seedCmsHero(['judul' => 'Tampil', 'status_aktif' => true, 'urutan_tampilan' => 2]);
        $this->seedCmsHero(['judul' => 'Disembunyikan', 'status_aktif' => false, 'urutan_tampilan' => 1]);
        $this->asGuest();

        $this->hit('GET', '/api/cms/hero', [
            'endpoint' => 'GET /api/cms/hero',
            'label' => 'Hero publik hanya slide aktif',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(1, $data);
        $this->assertSame('Tampil', $data[0]['judul'] ?? null);

        $this->assertRecorded();
    }

    public function test_admin_hero_crud(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/cms/hero', [
            'endpoint' => 'POST /api/admin/cms/hero',
            'label' => 'Buat banner hero',
            'json' => [
                'judul' => 'Koleksi Baru',
                'subjudul' => 'Batik premium',
                'label_badge' => 'Baru',
                'teks_tombol' => 'Belanja',
                'tautan_tombol' => '/produk',
            ],
            'files' => ['gambar' => UploadedFile::fake()->image('hero.png', 300, 150)],
            'expected' => 201,
        ]);

        $id = $this->recorded[0]['response']['data']['id_slide_hero'] ?? null;
        $path = $this->recorded[0]['response']['data']['gambar'] ?? null;
        $this->assertNotEmpty($id);
        Storage::disk('public')->assertExists($path);

        $this->hit('GET', '/api/admin/cms/hero', [
            'endpoint' => 'GET /api/admin/cms/hero',
            'label' => 'Daftar hero admin',
            'expected' => 200,
        ]);

        $this->hit('PUT', '/api/admin/cms/hero/'.$id, [
            'endpoint' => 'PUT /api/admin/cms/hero/{id}',
            'label' => 'Ubah judul hero',
            'json' => ['judul' => 'Koleksi Terbaru'],
            'expected' => 200,
        ]);
        $this->assertSame('Koleksi Terbaru', $this->recorded[2]['response']['data']['judul'] ?? null);

        $this->hit('PUT', '/api/admin/cms/hero/'.$id, [
            'endpoint' => 'PUT /api/admin/cms/hero/{id}',
            'label' => 'Ganti gambar hero',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('hero2.png', 300, 150)],
            'expected' => 200,
        ]);
        $newPath = $this->recorded[3]['response']['data']['gambar'] ?? null;
        $this->assertNotSame($path, $newPath);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($newPath);

        $this->hit('DELETE', '/api/admin/cms/hero/'.$id, [
            'endpoint' => 'DELETE /api/admin/cms/hero/{id}',
            'label' => 'Hapus hero + berkas',
            'expected' => 200,
        ]);
        Storage::disk('public')->assertMissing($newPath);

        $this->hit('DELETE', '/api/admin/cms/hero/tidak-ada', [
            'endpoint' => 'DELETE /api/admin/cms/hero/{id}',
            'label' => 'Hapus hero tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertSame(
            ['cms_slide_hero' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect']
        );
        $this->assertSame(
            ['cms_slide_hero' => '1 -> 0 (-1)'],
            $this->recorded[4]['db_effect']
        );

        $this->assertRecorded();
    }

    public function test_admin_hero_validasi(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/cms/hero', [
            'endpoint' => 'POST /api/admin/cms/hero',
            'label' => 'Hero tanpa gambar & field wajib',
            'json' => [],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Kategori CMS
    // ------------------------------------------------------------------

    public function test_admin_kategori_crud(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/cms/kategori', [
            'endpoint' => 'POST /api/admin/cms/kategori',
            'label' => 'Buat kategori CMS',
            'json' => [
                'nama_kategori' => 'Batik Tulis',
                'judul' => 'Koleksi Batik Tulis',
                'tautan_tujuan' => '/kategori/batik-tulis',
            ],
            'files' => ['gambar' => UploadedFile::fake()->image('kat.png', 200, 200)],
            'expected' => 201,
        ]);

        $id = $this->recorded[0]['response']['data']['id_kategori'] ?? null;
        $this->assertNotEmpty($id);

        $this->hit('POST', '/api/admin/cms/kategori', [
            'endpoint' => 'POST /api/admin/cms/kategori',
            'label' => 'Nama kategori duplikat',
            'json' => [
                'nama_kategori' => 'Batik Tulis',
                'judul' => 'Duplikat',
                'tautan_tujuan' => '/x',
            ],
            'files' => ['gambar' => UploadedFile::fake()->image('kat2.png', 200, 200)],
            'expected' => 422,
        ]);

        $this->hit('PUT', '/api/admin/cms/kategori/'.$id, [
            'endpoint' => 'PUT /api/admin/cms/kategori/{id}',
            'label' => 'Ubah kategori CMS',
            'json' => ['judul' => 'Judul Baru', 'status_aktif' => false],
            'expected' => 200,
        ]);
        $this->assertFalse((bool) ($this->recorded[2]['response']['data']['status_aktif'] ?? true));

        $this->asGuest();
        $this->hit('GET', '/api/cms/kategori', [
            'endpoint' => 'GET /api/cms/kategori',
            'label' => 'Kategori publik menyembunyikan nonaktif',
            'expected' => 200,
        ]);
        $this->assertSame([], $this->recorded[3]['response']['data']);

        $this->actingAsRole('admin');
        $this->hit('DELETE', '/api/admin/cms/kategori/'.$id, [
            'endpoint' => 'DELETE /api/admin/cms/kategori/{id}',
            'label' => 'Hapus kategori CMS',
            'expected' => 200,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Galeri
    // ------------------------------------------------------------------

    public function test_admin_galeri_dan_gambar(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('GET', '/api/admin/cms/galeri', [
            'endpoint' => 'GET /api/admin/cms/galeri',
            'label' => 'Galeri admin sebelum dibuat',
            'expected' => 200,
        ]);
        $this->assertArrayHasKey('data', $this->recorded[0]['response']);
        $this->assertNull($this->recorded[0]['response']['data']);

        $this->hit('PUT', '/api/admin/cms/galeri', [
            'endpoint' => 'PUT /api/admin/cms/galeri',
            'label' => 'Buat info galeri',
            'json' => [
                'judul' => 'Galeri Pandansari',
                'subjudul' => 'Suasana workshop',
                'label_lokasi' => 'Lokasi',
                'judul_lokasi' => 'Workshop Batik',
            ],
            'expected' => 200,
        ]);
        $this->assertSame('Galeri Pandansari', $this->recorded[1]['response']['data']['judul'] ?? null);

        $this->hit('POST', '/api/admin/cms/galeri/gambar', [
            'endpoint' => 'POST /api/admin/cms/galeri/gambar',
            'label' => 'Unggah gambar galeri',
            'json' => ['teks_alternatif' => 'Suasana 1'],
            'files' => ['gambar' => UploadedFile::fake()->image('galeri.png', 200, 150)],
            'expected' => 201,
        ]);
        $imageId = $this->recorded[2]['response']['data']['id_gambar_galeri'] ?? null;
        $imagePath = $this->recorded[2]['response']['data']['gambar'] ?? null;
        $this->assertNotEmpty($imageId);
        Storage::disk('public')->assertExists($imagePath);

        $this->hit('PUT', '/api/admin/cms/galeri/gambar/'.$imageId, [
            'endpoint' => 'PUT /api/admin/cms/galeri/gambar/{id}',
            'label' => 'Ubah teks alternatif gambar galeri',
            'json' => ['teks_alternatif' => 'Suasana Baru'],
            'expected' => 200,
        ]);
        $this->assertSame('Suasana Baru', $this->recorded[3]['response']['data']['teks_alternatif'] ?? null);

        $this->asGuest();
        $this->hit('GET', '/api/cms/galeri', [
            'endpoint' => 'GET /api/cms/galeri',
            'label' => 'Galeri publik',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[4]['response']['data']['gambar'] ?? []);

        $this->actingAsRole('admin');
        $this->hit('DELETE', '/api/admin/cms/galeri/gambar/'.$imageId, [
            'endpoint' => 'DELETE /api/admin/cms/galeri/gambar/{id}',
            'label' => 'Hapus gambar galeri',
            'expected' => 200,
        ]);
        Storage::disk('public')->assertMissing($imagePath);

        $this->hit('DELETE', '/api/admin/cms/galeri/gambar/tidak-ada', [
            'endpoint' => 'DELETE /api/admin/cms/galeri/gambar/{id}',
            'label' => 'Hapus gambar galeri tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    public function test_admin_galeri_gambar_tanpa_galeri(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/cms/galeri/gambar', [
            'endpoint' => 'POST /api/admin/cms/galeri/gambar',
            'label' => 'Unggah gambar sebelum galeri dibuat',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('x.png', 10, 10)],
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Profil usaha
    // ------------------------------------------------------------------

    public function test_admin_profil_usaha(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/admin/cms/profil-usaha', [
            'endpoint' => 'PUT /api/admin/cms/profil-usaha',
            'label' => 'Buat profil usaha + logo',
            'json' => [
                'nama_usaha' => 'Batik Pandansari',
                'deskripsi_singkat' => 'Batik khas Pekalongan',
                'email_resmi' => 'halo@pandansari.id',
                'nomor_whatsapp' => '08123456789',
                'jam_operasional' => [
                    ['hari' => 'Senin', 'jam_buka' => '08:00', 'jam_tutup' => '17:00'],
                    ['hari' => 'Minggu', 'tutup' => true],
                ],
            ],
            'files' => ['logo' => UploadedFile::fake()->image('logo.png', 100, 100)],
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertSame('Batik Pandansari', $data['nama_usaha'] ?? null);
        $this->assertNotEmpty($data['url_logo'] ?? null);
        $this->assertCount(2, $data['jam_operasional'] ?? []);
        Storage::disk('public')->assertExists($data['logo'] ?? '-');

        $this->asGuest();
        $this->hit('GET', '/api/cms/profil-usaha', [
            'endpoint' => 'GET /api/cms/profil-usaha',
            'label' => 'Profil usaha publik',
            'expected' => 200,
        ]);
        $this->assertSame('Batik Pandansari', $this->recorded[1]['response']['data']['nama_usaha'] ?? null);

        $this->assertRecorded();
    }

    public function test_admin_profil_usaha_tanpa_nama_saat_buat(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('PUT', '/api/admin/cms/profil-usaha', [
            'endpoint' => 'PUT /api/admin/cms/profil-usaha',
            'label' => 'Buat profil tanpa nama usaha',
            'json' => ['deskripsi_singkat' => 'Tanpa nama'],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Fasilitas
    // ------------------------------------------------------------------

    public function test_admin_fasilitas_crud(): void
    {
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/cms/fasilitas', [
            'endpoint' => 'POST /api/admin/cms/fasilitas',
            'label' => 'Buat fasilitas',
            'json' => ['nama_fasilitas' => 'Parkir Luas', 'deskripsi' => 'Gratis'],
            'expected' => 201,
        ]);
        $id = $this->recorded[0]['response']['data']['id_fasilitas'] ?? null;
        $this->assertNotEmpty($id);

        $this->hit('GET', '/api/admin/cms/fasilitas', [
            'endpoint' => 'GET /api/admin/cms/fasilitas',
            'label' => 'Daftar fasilitas admin',
            'expected' => 200,
        ]);

        $this->hit('PUT', '/api/admin/cms/fasilitas/'.$id, [
            'endpoint' => 'PUT /api/admin/cms/fasilitas/{id}',
            'label' => 'Ubah fasilitas jadi nonaktif',
            'json' => ['status_aktif' => false],
            'expected' => 200,
        ]);

        $this->asGuest();
        $this->hit('GET', '/api/cms/fasilitas', [
            'endpoint' => 'GET /api/cms/fasilitas',
            'label' => 'Fasilitas publik sembunyikan nonaktif',
            'expected' => 200,
        ]);
        $this->assertSame([], $this->recorded[3]['response']['data']);

        $this->actingAsRole('admin');
        $this->hit('DELETE', '/api/admin/cms/fasilitas/'.$id, [
            'endpoint' => 'DELETE /api/admin/cms/fasilitas/{id}',
            'label' => 'Hapus fasilitas',
            'expected' => 200,
        ]);

        $this->hit('DELETE', '/api/admin/cms/fasilitas/tidak-ada', [
            'endpoint' => 'DELETE /api/admin/cms/fasilitas/{id}',
            'label' => 'Hapus fasilitas tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Proteksi admin
    // ------------------------------------------------------------------

    public function test_cms_tulis_akses_non_admin(): void
    {
        Storage::fake('public');

        $this->asGuest();
        $this->hit('POST', '/api/admin/cms/hero', [
            'endpoint' => 'POST /api/admin/cms/hero',
            'label' => 'Buat hero tanpa token',
            'json' => [],
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('POST', '/api/admin/cms/fasilitas', [
            'endpoint' => 'POST /api/admin/cms/fasilitas',
            'label' => 'Buat fasilitas oleh pelanggan',
            'json' => ['nama_fasilitas' => 'X'],
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Helper data
    // ------------------------------------------------------------------

    private function seedCmsHero(array $overrides = []): string
    {
        $id = (string) Str::uuid();
        SlideHero::create(array_merge([
            'id_slide_hero' => $id,
            'judul' => 'Judul Hero',
            'teks_tombol' => 'Belanja',
            'tautan_tombol' => '/produk',
            'gambar' => 'cms/hero/sample.jpg',
            'urutan_tampilan' => 1,
            'status_aktif' => true,
        ], $overrides));

        return $id;
    }

    private function seedCmsKategori(array $overrides = []): string
    {
        $id = (string) Str::uuid();
        $nama = $overrides['nama_kategori'] ?? ('Kategori '.Str::random(6));
        CmsKategori::create(array_merge([
            'id_kategori' => $id,
            'nama_kategori' => $nama,
            'judul' => 'Judul '.$nama,
            'tautan_tujuan' => '/kategori/'.$nama,
            'gambar' => 'cms/kategori/sample.jpg',
            'urutan_tampilan' => 1,
            'status_aktif' => true,
        ], $overrides));

        return $id;
    }

    private function seedCmsGaleri(array $overrides = []): CmsGaleri
    {
        return CmsGaleri::create(array_merge([
            'id_galeri' => 1,
            'judul' => 'Galeri Pandansari',
            'status_aktif' => true,
        ], $overrides));
    }

    private function seedCmsGambarGaleri(array $overrides = []): string
    {
        $id = (string) Str::uuid();
        CmsGambarGaleri::create(array_merge([
            'id_gambar_galeri' => $id,
            'id_galeri' => 1,
            'gambar' => 'cms/galeri/sample.jpg',
            'urutan_tampilan' => 1,
        ], $overrides));

        return $id;
    }

    private function seedCmsProfil(array $overrides = []): void
    {
        ProfilUsaha::create(array_merge([
            'id_profil_usaha' => 1,
            'nama_usaha' => 'Batik Pandansari',
            'deskripsi_singkat' => 'Batik khas Pekalongan',
        ], $overrides));
    }

    private function seedCmsFasilitas(array $overrides = []): string
    {
        $id = (string) Str::uuid();
        FasilitasUsaha::create(array_merge([
            'id_fasilitas' => $id,
            'nama_fasilitas' => 'Fasilitas',
            'urutan_tampilan' => 1,
            'status_aktif' => true,
        ], $overrides));

        return $id;
    }
}
