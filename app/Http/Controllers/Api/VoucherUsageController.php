<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VoucherUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherUsageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $usages = VoucherUsage::with(['voucher', 'user'])
            ->orderByDesc('dibuat_pada')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar penggunaan voucher berhasil diambil.',
            'data' => $usages->items(),
            'meta' => [
                'current_page' => $usages->currentPage(),
                'per_page' => $usages->perPage(),
                'total' => $usages->total(),
                'last_page' => $usages->lastPage(),
            ],
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $usages = VoucherUsage::with('voucher')
            ->where('id_user', $request->user()->id_user)
            ->orderByDesc('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat penggunaan voucher berhasil diambil.',
            'data' => $usages,
        ]);
    }
}
