<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\ProfilUsaha;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;

class CmsProfilUsahaController extends Controller
{
    private const SINGLETON_ID = 1;

    public function __construct(private readonly ImageUploadService $images) {}

    public function index(): JsonResponse
    {
        $profil = ProfilUsaha::find(self::SINGLETON_ID);

        return response()->json([
            'success' => true,
            'message' => $profil ? 'Profil usaha berhasil diambil.' : 'Profil usaha belum tersedia.',
            'data' => $profil ? $this->withUrl($profil) : null,
        ]);
    }

    private function withUrl(ProfilUsaha $profil): ProfilUsaha
    {
        $profil->setAttribute('url_logo', $this->images->url($profil->logo));

        return $profil;
    }
}
