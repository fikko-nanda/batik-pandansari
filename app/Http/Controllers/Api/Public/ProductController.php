<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:36'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->with([
                'category',
                'categories',
                'images' => fn ($query) => $query
                    ->where('type', ProductImage::TYPE_PRODUK)
                    ->orderByDesc('is_primary')
                    ->orderBy('urutan'),
            ])
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('nama_produk', 'like', "%{$term}%")
                            ->orWhere('deskripsi', 'like', "%{$term}%");
                    });
                }
            )
            ->when(
                ! empty($validated['category']),
                fn ($query) => $query->whereHas(
                    'categories',
                    fn ($categoryQuery) => $categoryQuery->where(
                        'categories.id_category',
                        $validated['category']
                    )
                )
            )
            ->where('status_produk', 'aktif')
            ->orderByDesc('dibuat_pada')
            ->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar produk berhasil diambil.',
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $product = Product::with([
            'category',
            'categories',
            'variants' => fn ($query) => $query->where('status_varian', 'aktif'),
            'images' => fn ($query) => $query
                ->where('type', ProductImage::TYPE_PRODUK)
                ->orderByDesc('is_primary')
                ->orderBy('urutan'),
            'sizeChart' => fn ($query) => $query->orderBy('urutan'),
        ])
            ->where('id_product', $id)
            ->where('status_produk', 'aktif')
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil.',
            'data' => $product,
        ]);
    }
}
