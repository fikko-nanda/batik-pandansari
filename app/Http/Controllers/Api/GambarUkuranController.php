<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GambarUkuran;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengelolaan gambar panduan ukuran (size guide).
 *
 * GET    /api/gambar-ukuran             daftar gambar (publik, baca saja)
 * GET    /api/admin/gambar-ukuran       daftar gambar (admin)
 * POST   /api/admin/gambar-ukuran       unggah gambar baru (admin)
 * PUT    /api/admin/gambar-ukuran/{id}  ganti gambar (admin)
 * DELETE /api/admin/gambar-ukuran/{id}  hapus gambar (admin)
 *
 * Path gambar disimpan relatif (disk "public"), bukan binary. Saat mengganti
 * gambar, berkas lama dihapus hanya setelah perubahan database berhasil dan
 * hanya bila tidak dipakai baris lain.
 */
class GambarUkuranController extends Controller
{
    private const FOLDER = 'panduan-ukuran';

    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar gambar panduan ukuran berhasil diambil.',
            'data' => $this->all(),
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar gambar panduan ukuran berhasil diambil.',
            'data' => $this->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $this->images->store($request->file('gambar'), self::FOLDER);

        $gambar = GambarUkuran::create([
            'gambar' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gambar panduan ukuran berhasil diunggah.',
            'data' => $this->present($gambar),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $gambar = GambarUkuran::find($id);

        if (! $gambar) {
            return $this->notFound();
        }

        // Validasi SEBELUM menyentuh berkas: bila unggahan tidak valid,
        // gambar lama tetap utuh (422, database belum berubah).
        $request->validate([
            'gambar' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if (! $request->hasFile('gambar')) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada gambar baru; data tidak berubah.',
                'data' => $this->present($gambar),
            ]);
        }

        $oldPath = $gambar->gambar;

        // Unggah berkas baru lebih dulu, baru ubah database. Bila unggah
        // gagal, exception menghentikan proses dan gambar lama tetap utuh.
        $newPath = $this->images->store($request->file('gambar'), self::FOLDER);

        $gambar->update(['gambar' => $newPath]);

        // Hapus berkas lama HANYA setelah perubahan database berhasil, dan
        // hanya bila tidak direferensikan baris lain (aman untuk berbagi file).
        $this->deleteFileIfUnreferenced($oldPath, $gambar->id_gambar_ukuran);

        return response()->json([
            'success' => true,
            'message' => 'Gambar panduan ukuran berhasil diperbarui.',
            'data' => $this->present($gambar->fresh()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $gambar = GambarUkuran::find($id);

        if (! $gambar) {
            return $this->notFound();
        }

        $path = $gambar->gambar;

        // Hapus baris database lebih dulu; berkas baru dihapus setelahnya dan
        // hanya bila tidak dipakai baris lain agar tidak menghapus file yang
        // masih digunakan di tempat lain.
        $gambar->delete();

        $this->deleteFileIfUnreferenced($path, null);

        return response()->json([
            'success' => true,
            'message' => 'Gambar panduan ukuran berhasil dihapus.',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function all(): array
    {
        return GambarUkuran::orderBy('id_gambar_ukuran')
            ->get()
            ->map(fn (GambarUkuran $gambar) => $this->present($gambar))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(GambarUkuran $gambar): array
    {
        return [
            'id_gambar_ukuran' => $gambar->id_gambar_ukuran,
            'gambar' => $gambar->gambar,
            'url_gambar' => $this->images->url($gambar->gambar),
            'dibuat_pada' => $gambar->dibuat_pada,
            'diperbarui_pada' => $gambar->diperbarui_pada,
        ];
    }

    /**
     * Hapus berkas hanya bila tidak ada baris lain yang memakai path sama.
     * `$exceptId` dipakai saat penggantian agar baris yang baru saja diubah
     * tidak ikut menghalangi penghapusan berkas lama.
     */
    private function deleteFileIfUnreferenced(?string $path, ?int $exceptId): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $query = GambarUkuran::where('gambar', $path);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            return;
        }

        $this->images->delete($path);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Gambar panduan ukuran tidak ditemukan.',
        ], 404);
    }
}
