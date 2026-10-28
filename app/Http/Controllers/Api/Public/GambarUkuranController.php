<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\GambarUkuran;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;

class GambarUkuranController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        $gambar = GambarUkuran::orderBy('id_gambar_ukuran')
            ->get()
            ->map(fn (GambarUkuran $item) => [
                'id_gambar_ukuran' => $item->id_gambar_ukuran,
                'gambar' => $item->gambar,
                'url_gambar' => $this->images->url($item->gambar),
                'dibuat_pada' => $item->dibuat_pada,
                'diperbarui_pada' => $item->diperbarui_pada,
            ])
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Daftar gambar panduan ukuran berhasil diambil.',
            'data' => $gambar,
        ]);
    }
}
