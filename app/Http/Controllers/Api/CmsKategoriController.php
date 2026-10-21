<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CmsKategori;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CMS Kategori (kartu kategori pada halaman utama).
 *
 * Tabel terpisah dari kategori produk (`categories`).
 *
 * GET    /api/cms/kategori            daftar aktif (publik, terurut)
 * GET    /api/admin/cms/kategori      daftar semua (admin)
 * POST   /api/admin/cms/kategori      tambah + gambar (admin)
 * PUT    /api/admin/cms/kategori/{id} ubah parsial, ganti gambar opsional (admin)
 * DELETE /api/admin/cms/kategori/{id} hapus + gambar (admin)
 */
class CmsKategoriController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        $kategori = CmsKategori::where('status_aktif', true)
            ->orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get()
            ->map(fn (CmsKategori $item) => $this->withUrl($item));

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori berhasil diambil.',
            'data' => $kategori,
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $kategori = CmsKategori::orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get()
            ->map(fn (CmsKategori $item) => $this->withUrl($item));

        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori berhasil diambil.',
            'data' => $kategori,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:100', 'unique:cms_kategori,nama_kategori'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tautan_tujuan' => ['required', 'string', 'max:500'],
            'urutan_tampilan' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        $path = $this->images->store($request->file('gambar'), 'cms/kategori');

        $kategori = CmsKategori::create([
            'id_kategori' => (string) Str::uuid(),
            'nama_kategori' => $validated['nama_kategori'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'gambar' => $path,
            'tautan_tujuan' => $validated['tautan_tujuan'],
            'urutan_tampilan' => $validated['urutan_tampilan'] ?? $this->nextUrutan(),
            'status_aktif' => $validated['status_aktif'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dibuat.',
            'data' => $this->withUrl($kategori),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $kategori = CmsKategori::find($id);

        if (! $kategori) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'nama_kategori' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('cms_kategori', 'nama_kategori')->ignore($kategori->id_kategori, 'id_kategori'),
            ],
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'gambar' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tautan_tujuan' => ['sometimes', 'required', 'string', 'max:500'],
            'urutan_tampilan' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
        ]);

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $this->images->replace(
                $request->file('gambar'),
                'cms/kategori',
                $kategori->gambar
            );
        }

        $kategori->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $this->withUrl($kategori->fresh()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $kategori = CmsKategori::find($id);

        if (! $kategori) {
            return $this->notFound();
        }

        $this->images->delete($kategori->gambar);
        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    private function withUrl(CmsKategori $item): CmsKategori
    {
        $item->setAttribute('url_gambar', $this->images->url($item->gambar));

        return $item;
    }

    private function nextUrutan(): int
    {
        return (int) CmsKategori::max('urutan_tampilan') + 1;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Kategori tidak ditemukan.',
        ], 404);
    }
}
