<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\FasilitasUsaha;
use Illuminate\Http\JsonResponse;

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
}
