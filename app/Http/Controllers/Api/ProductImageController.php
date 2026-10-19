<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    public function index(string $productId): JsonResponse
    {
        $product = $this->findActiveProduct($productId);

        if (! $product) {
            return $this->productNotFound();
        }

        $images = ProductImage::where('id_product', $product->id_product)
            ->orderByDesc('is_primary')
            ->orderBy('urutan')
            ->get()
            ->map(fn (ProductImage $image) => $this->withUrl($image));

        return response()->json([
            'success' => true,
            'message' => 'Daftar gambar produk berhasil diambil.',
            'data' => $images,
        ]);
    }

    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return $this->productNotFound();
        }

        $validated = $request->validate([
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:150'],
            'is_primary' => ['nullable', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $path = $request->file('gambar')->store('products', 'public');

        $isFirstImage = ! ProductImage::where('id_product', $product->id_product)->exists();
        $isPrimary = $validated['is_primary'] ?? false;
        $isPrimary = $isPrimary || $isFirstImage;
        $urutan = $validated['urutan'] ?? (ProductImage::where('id_product', $product->id_product)->max('urutan') + 1);

        $image = DB::transaction(function () use ($validated, $product, $path, $isPrimary, $urutan) {
            if ($isPrimary) {
                ProductImage::where('id_product', $product->id_product)
                    ->update(['is_primary' => false]);
            }

            return ProductImage::create([
                'id_image' => (string) Str::uuid(),
                'id_product' => $product->id_product,
                'path_gambar' => $path,
                'alt_text' => $validated['alt_text'] ?? null,
                'is_primary' => $isPrimary,
                'urutan' => $urutan,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Gambar produk berhasil diunggah.',
            'data' => $this->withUrl($image),
        ], 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $image = ProductImage::find($id);

        if (! $image) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar tidak ditemukan.',
            ], 404);
        }

        Storage::disk('public')->delete($image->path_gambar);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gambar produk berhasil dihapus.',
        ]);
    }

    private function withUrl(ProductImage $image): ProductImage
    {
        $image->setAttribute('url', Storage::disk('public')->url($image->path_gambar));

        return $image;
    }

    private function findActiveProduct(string $productId): ?Product
    {
        return Product::where('id_product', $productId)
            ->where('status_produk', 'aktif')
            ->first();
    }

    private function productNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Produk tidak ditemukan.',
        ], 404);
    }
}
