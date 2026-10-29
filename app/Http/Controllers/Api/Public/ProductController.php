<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:50'],
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
                function ($query) use ($validated) {
                    $val = $validated['category'];
                    $query->where(function ($inner) use ($val) {
                        $inner->where('id_category', $val)
                            ->orWhereHas('categories', fn ($q) => $q->where('categories.id_category', $val)->orWhere('categories.slug', $val))
                            ->orWhereHas('category', fn ($q) => $q->where('id_category', $val)->orWhere('slug', $val));
                    });
                }
            )
            ->where('status_produk', 'aktif')
            ->orderByDesc('dibuat_pada')
            ->paginate($validated['per_page'] ?? 10);

        // Sertakan atribut boolean `is_liked` jika user sedang login
        $user = auth('sanctum')->user() ?? $request->user();
        $userWishlistIds = [];
        if ($user) {
            $userWishlistIds = WishlistItem::where('id_user', $user->id_user)
                ->pluck('id_product')
                ->all();
        }

        $items = $products->getCollection()->map(function ($product) use ($userWishlistIds) {
            $product->setAttribute('is_liked', in_array($product->id_product, $userWishlistIds));

            return $product;
        });
        $products->setCollection($items);

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

    public function show(Request $request, string $id): JsonResponse
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

        $user = auth('sanctum')->user() ?? $request->user();
        $isLiked = false;
        if ($user) {
            $isLiked = WishlistItem::where('id_user', $user->id_user)
                ->where('id_product', $product->id_product)
                ->exists();
        }
        $product->setAttribute('is_liked', $isLiked);

        return response()->json([
            'success' => true,
            'message' => 'Detail produk berhasil diambil.',
            'data' => $product,
        ]);
    }
}
