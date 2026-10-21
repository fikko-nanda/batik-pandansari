<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FasilitasUsaha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CMS Fasilitas Usaha (daftar fasilitas yang dimiliki toko).
 *
 * GET    /api/cms/fasilitas              daftar aktif (publik, terurut)
 * GET    /api/admin/cms/fasilitas        daftar semua (admin)
 * POST   /api/admin/cms/fasilitas        tambah (admin)
 * PUT    /api/admin/cms/fasilitas/{id}   ubah parsial (admin)
 * DELETE /api/admin/cms/fasilitas/{id}   hapus (admin)
 */
class CmsFasilitasController extends Controller
{
    public function index(): JsonResponse
    {
        $fasilitas = FasilitasUsaha::where('status_aktif', true)
            ->orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar fasilitas usaha berhasil diambil.',
            'data' => $fasilitas,
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $fasilitas = FasilitasUsaha::orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar fasilitas usaha berhasil diambil.',
            'data' => $fasilitas,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_fasilitas' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'urutan_tampilan' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        $fasilitas = FasilitasUsaha::create([
            'id_fasilitas' => (string) Str::uuid(),
            'nama_fasilitas' => $validated['nama_fasilitas'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'urutan_tampilan' => $validated['urutan_tampilan'] ?? $this->nextUrutan(),
            'status_aktif' => $validated['status_aktif'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas usaha berhasil dibuat.',
            'data' => $fasilitas,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $fasilitas = FasilitasUsaha::find($id);

        if (! $fasilitas) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'nama_fasilitas' => ['sometimes', 'required', 'string', 'max:100'],
            'deskripsi' => ['sometimes', 'nullable', 'string', 'max:255'],
            'urutan_tampilan' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
        ]);

        $fasilitas->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas usaha berhasil diperbarui.',
            'data' => $fasilitas,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $fasilitas = FasilitasUsaha::find($id);

        if (! $fasilitas) {
            return $this->notFound();
        }

        $fasilitas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas usaha berhasil dihapus.',
        ]);
    }

    private function nextUrutan(): int
    {
        return (int) FasilitasUsaha::max('urutan_tampilan') + 1;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Fasilitas usaha tidak ditemukan.',
        ], 404);
    }
}
