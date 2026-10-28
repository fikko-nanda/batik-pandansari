<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\VoucherUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherUsageController extends Controller
{
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
