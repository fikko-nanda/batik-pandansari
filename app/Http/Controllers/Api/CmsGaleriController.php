<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsGaleri;
use App\Models\CmsGambarGaleri;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CMS Galeri (halaman galeri: header + kumpulan gambar).
 *
 * Tabel `cms_galeri` dirancang satu baris (id_galeri = 1) berisi judul,
 * subjudul, deskripsi, dan blok lokasi. Gambar-gambarnya ada di tabel
 * `cms_gambar_galeri` (banyak baris per galeri).
 *
 * GET    /api/cms/galeri                      galeri aktif + gambar (publik)
 * GET    /api/admin/cms/galeri                galeri + gambar (admin)
 * PUT    /api/admin/cms/galeri                ubah info galeri (admin)
 * POST   /api/admin/cms/galeri/gambar         tambah gambar (admin)
 * PUT    /api/admin/cms/galeri/gambar/{id}    ubah gambar/urutan (admin)
 * DELETE /api/admin/cms/galeri/gambar/{id}    hapus gambar (admin)
 */
class CmsGaleriController extends Controller
{
    private const SINGLETON_ID = 1;

    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        $galeri = CmsGaleri::find(self::SINGLETON_ID);

        if (! $galeri || ! $galeri->status_aktif) {
            return response()->json([
                'success' => true,
                'message' => 'Galeri belum tersedia.',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Galeri berhasil diambil.',
            'data' => $this->present($galeri),
        ]);
    }

    public function adminShow(): JsonResponse
    {
        $galeri = CmsGaleri::find(self::SINGLETON_ID);

        return response()->json([
            'success' => true,
            'message' => 'Galeri berhasil diambil.',
            'data' => $galeri ? $this->present($galeri) : null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'subjudul' => ['sometimes', 'nullable', 'string', 'max:255'],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'label_lokasi' => ['sometimes', 'nullable', 'string', 'max:100'],
            'judul_lokasi' => ['sometimes', 'nullable', 'string', 'max:150'],
            'deskripsi_lokasi' => ['sometimes', 'nullable', 'string'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
        ]);

        $galeri = CmsGaleri::find(self::SINGLETON_ID);

        if (! $galeri) {
            if (empty($validated['judul'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Judul galeri wajib diisi saat pertama kali dibuat.',
                ], 422);
            }

            $galeri = CmsGaleri::create(array_merge([
                'id_galeri' => self::SINGLETON_ID,
                'judul' => $validated['judul'],
            ], $this->fillDefaults($validated)));
        } else {
            $galeri->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Galeri berhasil diperbarui.',
            'data' => $this->present($galeri->fresh()),
        ]);
    }

    public function storeImage(Request $request): JsonResponse
    {
        $galeri = CmsGaleri::find(self::SINGLETON_ID);

        if (! $galeri) {
            return response()->json([
                'success' => false,
                'message' => 'Galeri belum dibuat.',
            ], 404);
        }

        $validated = $request->validate([
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'teks_alternatif' => ['nullable', 'string', 'max:255'],
            'urutan_tampilan' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $path = $this->images->store($request->file('gambar'), 'cms/galeri');

        $gambar = CmsGambarGaleri::create([
            'id_gambar_galeri' => (string) Str::uuid(),
            'id_galeri' => self::SINGLETON_ID,
            'gambar' => $path,
            'teks_alternatif' => $validated['teks_alternatif'] ?? null,
            'urutan_tampilan' => $validated['urutan_tampilan'] ?? $this->nextUrutan(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gambar galeri berhasil diunggah.',
            'data' => $this->withUrl($gambar),
        ], 201);
    }

    public function updateImage(Request $request, string $id): JsonResponse
    {
        $gambar = $this->findImage($id);

        if (! $gambar) {
            return $this->imageNotFound();
        }

        $validated = $request->validate([
            'gambar' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'teks_alternatif' => ['sometimes', 'nullable', 'string', 'max:255'],
            'urutan_tampilan' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
        ]);

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $this->images->replace(
                $request->file('gambar'),
                'cms/galeri',
                $gambar->gambar
            );
        }

        $gambar->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Gambar galeri berhasil diperbarui.',
            'data' => $this->withUrl($gambar->fresh()),
        ]);
    }

    public function destroyImage(string $id): JsonResponse
    {
        $gambar = $this->findImage($id);

        if (! $gambar) {
            return $this->imageNotFound();
        }

        $this->images->delete($gambar->gambar);
        $gambar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gambar galeri berhasil dihapus.',
        ]);
    }

    private function present(CmsGaleri $galeri): array
    {
        $galeri->load('gambar');

        return [
            'id_galeri' => $galeri->id_galeri,
            'judul' => $galeri->judul,
            'subjudul' => $galeri->subjudul,
            'deskripsi' => $galeri->deskripsi,
            'label_lokasi' => $galeri->label_lokasi,
            'judul_lokasi' => $galeri->judul_lokasi,
            'deskripsi_lokasi' => $galeri->deskripsi_lokasi,
            'status_aktif' => $galeri->status_aktif,
            'gambar' => $galeri->gambar->map(
                fn (CmsGambarGaleri $item) => $this->withUrl($item)
            )->values(),
            'dibuat_pada' => $galeri->dibuat_pada,
            'diperbarui_pada' => $galeri->diperbarui_pada,
        ];
    }

    private function withUrl(CmsGambarGaleri $item): CmsGambarGaleri
    {
        $item->setAttribute('url_gambar', $this->images->url($item->gambar));

        return $item;
    }

    private function findImage(string $id): ?CmsGambarGaleri
    {
        return CmsGambarGaleri::where('id_gambar_galeri', $id)->first();
    }

    private function nextUrutan(): int
    {
        return (int) CmsGambarGaleri::where('id_galeri', self::SINGLETON_ID)->max('urutan_tampilan') + 1;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function fillDefaults(array $validated): array
    {
        return [
            'subjudul' => $validated['subjudul'] ?? null,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'label_lokasi' => $validated['label_lokasi'] ?? null,
            'judul_lokasi' => $validated['judul_lokasi'] ?? null,
            'deskripsi_lokasi' => $validated['deskripsi_lokasi'] ?? null,
            'status_aktif' => $validated['status_aktif'] ?? true,
        ];
    }

    private function imageNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Gambar galeri tidak ditemukan.',
        ], 404);
    }
}
