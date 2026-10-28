<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\SlideHero;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;

class CmsHeroController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        $slides = SlideHero::where('status_aktif', true)
            ->orderBy('urutan_tampilan')
            ->orderBy('dibuat_pada')
            ->get()
            ->map(fn (SlideHero $slide) => $this->withUrl($slide));

        return response()->json([
            'success' => true,
            'message' => 'Daftar banner hero berhasil diambil.',
            'data' => $slides,
        ]);
    }

    private function withUrl(SlideHero $slide): SlideHero
    {
        $slide->setAttribute('url_gambar', $this->images->url($slide->gambar));

        return $slide;
    }
}
