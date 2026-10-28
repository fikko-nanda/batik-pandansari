<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\CmsKategori;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;

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

    private function withUrl(CmsKategori $item): CmsKategori
    {
        $item->setAttribute('url_gambar', $this->images->url($item->gambar));

        return $item;
    }
}
