<?php

namespace Tests\Feature\Endpoints;

use App\Models\GambarUkuran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Endpoint gambar panduan ukuran (size guide):
 * GET    /api/gambar-ukuran             (publik, baca saja)
 * GET    /api/admin/gambar-ukuran       (admin)
 * POST   /api/admin/gambar-ukuran       (admin)
 * PUT    /api/admin/gambar-ukuran/{id}  (admin)
 * DELETE /api/admin/gambar-ukuran/{id}  (admin)
 */
class GambarUkuranEndpointTest extends EndpointTestCase
{
    protected static string $group = 'Panduan Ukuran';

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedGambarUkuran(array $overrides = []): GambarUkuran
    {
        return GambarUkuran::create(array_merge([
            'gambar' => 'panduan-ukuran/sample.jpg',
        ], $overrides));
    }

    // ------------------------------------------------------------------
    // Akses publik
    // ------------------------------------------------------------------

    public function test_publik_daftar_gambar_tanpa_token(): void
    {
        $this->seedGambarUkuran(['gambar' => 'panduan-ukuran/a.jpg']);
        $this->seedGambarUkuran(['gambar' => 'panduan-ukuran/b.jpg']);
        $this->asGuest();

        $this->hit('GET', '/api/gambar-ukuran', [
            'endpoint' => 'GET /api/gambar-ukuran',
            'label' => 'Daftar gambar panduan ukuran (publik)',
            'expected' => 200,
        ]);

        $data = $this->recorded[0]['response']['data'] ?? [];
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('url_gambar', $data[0]);
        $this->assertSame('panduan-ukuran/a.jpg', $data[0]['gambar'] ?? null);

        $this->assertRecorded();
    }

    public function test_publik_daftar_gambar_kosong(): void
    {
        $this->asGuest();

        $this->hit('GET', '/api/gambar-ukuran', [
            'endpoint' => 'GET /api/gambar-ukuran',
            'label' => 'Daftar gambar panduan ukuran saat belum ada data',
            'expected' => 200,
        ]);

        $this->assertSame([], $this->recorded[0]['response']['data']);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Proteksi admin
    // ------------------------------------------------------------------

    public function test_kelola_gambar_butuh_admin(): void
    {
        Storage::fake('public');

        $this->asGuest();
        $this->hit('POST', '/api/admin/gambar-ukuran', [
            'endpoint' => 'POST /api/admin/gambar-ukuran',
            'label' => 'Unggah gambar tanpa token',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('a.png', 100, 100)],
            'expected' => 401,
        ]);
        $this->hit('GET', '/api/admin/gambar-ukuran', [
            'endpoint' => 'GET /api/admin/gambar-ukuran',
            'label' => 'Daftar gambar admin tanpa token',
            'expected' => 401,
        ]);

        $this->actingAsRole('pelanggan');
        $this->hit('POST', '/api/admin/gambar-ukuran', [
            'endpoint' => 'POST /api/admin/gambar-ukuran',
            'label' => 'Unggah gambar oleh pelanggan',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('a.png', 100, 100)],
            'expected' => 403,
        ]);
        $this->hit('DELETE', '/api/admin/gambar-ukuran/1', [
            'endpoint' => 'DELETE /api/admin/gambar-ukuran/{id}',
            'label' => 'Hapus gambar oleh pelanggan',
            'expected' => 403,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // CRUD admin
    // ------------------------------------------------------------------

    public function test_admin_crud_gambar_ukuran(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/gambar-ukuran', [
            'endpoint' => 'POST /api/admin/gambar-ukuran',
            'label' => 'Unggah gambar panduan ukuran',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('ukuran.png', 400, 600)],
            'expected' => 201,
        ]);

        $id = $this->recorded[0]['response']['data']['id_gambar_ukuran'] ?? null;
        $path = $this->recorded[0]['response']['data']['gambar'] ?? null;
        $this->assertNotEmpty($id);
        $this->assertNotEmpty($path);
        $this->assertNotEmpty($this->recorded[0]['response']['data']['url_gambar'] ?? null);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(
            ['gambar_ukuran' => '0 -> 1 (+1)'],
            $this->recorded[0]['db_effect']
        );

        $this->hit('GET', '/api/admin/gambar-ukuran', [
            'endpoint' => 'GET /api/admin/gambar-ukuran',
            'label' => 'Daftar gambar admin',
            'expected' => 200,
        ]);
        $this->assertCount(1, $this->recorded[1]['response']['data'] ?? []);

        $this->hit('PUT', '/api/admin/gambar-ukuran/'.$id, [
            'endpoint' => 'PUT /api/admin/gambar-ukuran/{id}',
            'label' => 'Ganti gambar panduan ukuran',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('ukuran-baru.png', 500, 700)],
            'expected' => 200,
        ]);
        $newPath = $this->recorded[2]['response']['data']['gambar'] ?? null;
        $this->assertNotSame($path, $newPath);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($newPath);

        $this->hit('DELETE', '/api/admin/gambar-ukuran/'.$id, [
            'endpoint' => 'DELETE /api/admin/gambar-ukuran/{id}',
            'label' => 'Hapus gambar panduan ukuran + berkas',
            'expected' => 200,
        ]);
        Storage::disk('public')->assertMissing($newPath);
        $this->assertSame(
            ['gambar_ukuran' => '1 -> 0 (-1)'],
            $this->recorded[3]['db_effect']
        );

        $this->hit('PUT', '/api/admin/gambar-ukuran/tidak-ada', [
            'endpoint' => 'PUT /api/admin/gambar-ukuran/{id}',
            'label' => 'Ganti gambar tidak ditemukan',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('x.png', 100, 100)],
            'expected' => 404,
        ]);

        $this->hit('DELETE', '/api/admin/gambar-ukuran/tidak-ada', [
            'endpoint' => 'DELETE /api/admin/gambar-ukuran/{id}',
            'label' => 'Hapus gambar tidak ditemukan',
            'expected' => 404,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Validasi berkas
    // ------------------------------------------------------------------

    public function test_unggah_gambar_validasi(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/gambar-ukuran', [
            'endpoint' => 'POST /api/admin/gambar-ukuran',
            'label' => 'Unggah tanpa berkas gambar',
            'json' => [],
            'expected' => 422,
        ]);
        $this->assertArrayHasKey('gambar', $this->recorded[0]['response']['errors'] ?? []);

        $this->hit('POST', '/api/admin/gambar-ukuran', [
            'endpoint' => 'POST /api/admin/gambar-ukuran',
            'label' => 'Unggah berkas yang bukan gambar (teks)',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->create('dokumen.txt', 10, 'text/plain')],
            'expected' => 422,
        ]);

        $this->hit('POST', '/api/admin/gambar-ukuran', [
            'endpoint' => 'POST /api/admin/gambar-ukuran',
            'label' => 'Unggah gambar melebihi batas 2 MB',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->create('besar.jpg', 3000, 'image/jpeg')],
            'expected' => 422,
        ]);

        $this->assertRecorded();
    }

    // ------------------------------------------------------------------
    // Ketahanan: upload gagal tidak menghapus gambar lama
    // ------------------------------------------------------------------

    public function test_ganti_gambar_gagal_pertahankan_gambar_lama(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        $this->hit('POST', '/api/admin/gambar-ukuran', [
            'endpoint' => 'POST /api/admin/gambar-ukuran',
            'label' => 'Unggah gambar awal',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->image('awal.png', 300, 300)],
            'expected' => 201,
        ]);
        $id = $this->recorded[0]['response']['data']['id_gambar_ukuran'] ?? null;
        $oldPath = $this->recorded[0]['response']['data']['gambar'] ?? null;
        Storage::disk('public')->assertExists($oldPath);

        // Ganti dengan berkas tidak valid → 422, gambar lama tetap ada.
        $this->hit('PUT', '/api/admin/gambar-ukuran/'.$id, [
            'endpoint' => 'PUT /api/admin/gambar-ukuran/{id}',
            'label' => 'Ganti gambar dengan berkas tidak valid (gagal)',
            'json' => [],
            'files' => ['gambar' => UploadedFile::fake()->create('salah.txt', 5, 'text/plain')],
            'expected' => 422,
        ]);

        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame($oldPath, GambarUkuran::find($id)?->gambar);

        $this->assertRecorded();
    }

    public function test_hapus_gambar_tidak_hapus_berkas_dipakai_baris_lain(): void
    {
        Storage::fake('public');
        $this->actingAsRole('admin');

        // Dua baris menunjuk berkas yang sama (berbagi path).
        Storage::disk('public')->put('panduan-ukuran/dipakai-bersama.jpg', 'x');
        $a = $this->seedGambarUkuran(['gambar' => 'panduan-ukuran/dipakai-bersama.jpg']);
        $b = $this->seedGambarUkuran(['gambar' => 'panduan-ukuran/dipakai-bersama.jpg']);

        $this->hit('DELETE', '/api/admin/gambar-ukuran/'.$a->id_gambar_ukuran, [
            'endpoint' => 'DELETE /api/admin/gambar-ukuran/{id}',
            'label' => 'Hapus satu baris: berkas tetap karena masih dipakai baris lain',
            'expected' => 200,
        ]);
        Storage::disk('public')->assertExists('panduan-ukuran/dipakai-bersama.jpg');

        $this->hit('DELETE', '/api/admin/gambar-ukuran/'.$b->id_gambar_ukuran, [
            'endpoint' => 'DELETE /api/admin/gambar-ukuran/{id}',
            'label' => 'Hapus baris terakhir: berkas ikut terhapus',
            'expected' => 200,
        ]);
        Storage::disk('public')->assertMissing('panduan-ukuran/dipakai-bersama.jpg');

        $this->assertRecorded();
    }
}
