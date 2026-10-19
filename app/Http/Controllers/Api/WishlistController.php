<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = WishlistItem::with([
            'product.category',
            'product.variants' => fn ($query) => $query->where('status_varian', 'aktif'),
            'product.images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('urutan'),
        ])
            ->where('id_user', $request->user()->id_user)
            ->orderByDesc('dibuat_pada')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar wishlist berhasil diambil.',
            'data' => $items,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_product' => ['required', 'string', 'exists:products,id_product'],
        ]);

        $product = Product::where('id_product', $validated['id_product'])
            ->where('status_produk', 'aktif')
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan atau tidak tersedia.',
            ], 404);
        }

        $user = $request->user();

        $exists = WishlistItem::where('id_user', $user->id_user)
            ->where('id_product', $product->id_product)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Produk sudah ada di wishlist.',
            ], 422);
        }

        $item = WishlistItem::create([
            'id_wishlist_item' => (string) Str::uuid(),
            'id_user' => $user->id_user,
            'id_product' => $product->id_product,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke wishlist.',
            'data' => $item->load('product'),
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $item = WishlistItem::where('id_wishlist_item', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Item wishlist tidak ditemukan.',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item wishlist berhasil dihapus.',
        ]);
    }
}
