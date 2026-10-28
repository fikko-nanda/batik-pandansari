<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function index(Request $request, string $productId): JsonResponse
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

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:produk,size_chart'],
        ]);

        $images = ProductImage::where('id_product', $product->id_product)
            ->when(
                isset($validated['type']),
                fn ($query) => $query->where('type', $validated['type'])
            )
            ->orderByDesc('is_primary')
            ->orderBy('urutan')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar gambar produk berhasil diambil.',
            'data' => $images,
        ]);
    }
}
