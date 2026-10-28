<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\CmsGaleri;
use App\Models\CmsGambarGaleri;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;

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
}
