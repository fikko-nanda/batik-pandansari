<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    public function index(string $productId): JsonResponse
    {
        $product = Product::where('id_product', $productId)
            ->where('status_produk', 'aktif')
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $variants = ProductVariant::where('id_product', $product->id_product)
            ->where('status_varian', 'aktif')
            ->orderBy('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar varian produk berhasil diambil.',
            'data' => $variants,
        ]);
    }
}
