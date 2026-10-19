<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function index(string $productId): JsonResponse
    {
        $product = $this->findActiveProduct($productId);

        if (! $product) {
            return $this->productNotFound();
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

    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return $this->productNotFound();
        }

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'nama_varian' => ['nullable', 'string', 'max:100'],
            'warna' => ['nullable', 'string', 'max:50'],
            'ukuran' => ['nullable', 'string', 'max:30'],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'stok' => ['nullable', 'integer', 'min:0'],
            'status_varian' => ['nullable', 'string', 'in:aktif,nonaktif'],
        ]);

        $variant = ProductVariant::create([
            'id_variant' => (string) Str::uuid(),
            'id_product' => $product->id_product,
            'sku' => $validated['sku'],
            'nama_varian' => $validated['nama_varian'] ?? null,
            'warna' => $validated['warna'] ?? null,
            'ukuran' => $validated['ukuran'] ?? null,
            'harga' => $validated['harga'],
            'stok' => $validated['stok'] ?? 0,
            'status_varian' => $validated['status_varian'] ?? 'aktif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil dibuat.',
            'data' => $variant,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $variant = ProductVariant::find($id);

        if (! $variant) {
            return $this->variantNotFound();
        }

        $validated = $request->validate([
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($variant->id_variant, 'id_variant'),
            ],
            'nama_varian' => ['sometimes', 'nullable', 'string', 'max:100'],
            'warna' => ['sometimes', 'nullable', 'string', 'max:50'],
            'ukuran' => ['sometimes', 'nullable', 'string', 'max:30'],
            'harga' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999.99'],
            'stok' => ['sometimes', 'required', 'integer', 'min:0'],
            'status_varian' => ['sometimes', 'required', 'string', 'in:aktif,nonaktif'],
        ]);

        $variant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil diperbarui.',
            'data' => $variant,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $variant = ProductVariant::find($id);

        if (! $variant) {
            return $this->variantNotFound();
        }

        $variant->update(['status_varian' => 'nonaktif']);

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil dinonaktifkan.',
            'data' => $variant,
        ]);
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

    private function variantNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Varian tidak ditemukan.',
        ], 404);
    }
}
