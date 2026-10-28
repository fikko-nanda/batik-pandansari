<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlideHero;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CMS Banner Hero (korsel halaman utama).
 *
 * GET    /api/admin/cms/hero      daftar semua slide (admin)
 * POST   /api/admin/cms/hero      tambah slide + gambar (admin)
 * PUT    /api/admin/cms/hero/{id} ubah parsial, ganti gambar opsional (admin)
 * DELETE /api/admin/cms/hero/{id} hapus slide + gambar (admin)
 */
class CmsHeroController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    public function adminIndex(): JsonResponse
    {
        $slides = SlideHero::orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get()
            ->map(fn (SlideHero $slide) => $this->withUrl($slide));

        return response()->json([
            'success' => true,
            'message' => 'Daftar banner hero berhasil diambil.',
            'data' => $slides,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'subjudul' => ['nullable', 'string', 'max:255'],
            'label_badge' => ['nullable', 'string', 'max:100'],
            'teks_tombol' => ['required', 'string', 'max:80'],
            'tautan_tombol' => ['required', 'string', 'max:500'],
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'urutan_tampilan' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        $path = $this->images->store($request->file('gambar'), 'cms/hero');

        $slide = SlideHero::create([
            'id_slide_hero' => (string) Str::uuid(),
            'judul' => $validated['judul'],
            'subjudul' => $validated['subjudul'] ?? null,
            'label_badge' => $validated['label_badge'] ?? null,
            'teks_tombol' => $validated['teks_tombol'],
            'tautan_tombol' => $validated['tautan_tombol'],
            'gambar' => $path,
            'urutan_tampilan' => $validated['urutan_tampilan'] ?? $this->nextUrutan(),
            'status_aktif' => $validated['status_aktif'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner hero berhasil dibuat.',
            'data' => $this->withUrl($slide),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $slide = SlideHero::find($id);

        if (! $slide) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'judul' => ['sometimes', 'required', 'string', 'max:150'],
            'subjudul' => ['sometimes', 'nullable', 'string', 'max:255'],
            'label_badge' => ['sometimes', 'nullable', 'string', 'max:100'],
            'teks_tombol' => ['sometimes', 'required', 'string', 'max:80'],
            'tautan_tombol' => ['sometimes', 'required', 'string', 'max:500'],
            'gambar' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'urutan_tampilan' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
        ]);

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $this->images->replace(
                $request->file('gambar'),
                'cms/hero',
                $slide->gambar
            );
        }

        $slide->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Banner hero berhasil diperbarui.',
            'data' => $this->withUrl($slide->fresh()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $slide = SlideHero::find($id);

        if (! $slide) {
            return $this->notFound();
        }

        $this->images->delete($slide->gambar);
        $slide->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner hero berhasil dihapus.',
        ]);
    }

    private function withUrl(SlideHero $slide): SlideHero
    {
        $slide->setAttribute('url_gambar', $this->images->url($slide->gambar));

        return $slide;
    }

    private function nextUrutan(): int
    {
        return (int) SlideHero::max('urutan_tampilan') + 1;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Banner hero tidak ditemukan.',
        ], 404);
    }
}
