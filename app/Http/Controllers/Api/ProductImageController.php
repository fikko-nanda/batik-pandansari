<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    /**
     * Maksimal foto produk per produk. Size chart (type = 'size_chart')
     * disimpan terpisah sehingga tidak dihitung ke dalam kuota ini.
     */
    public const MAX_GAMBAR_PRODUK = 5;

    /**
     * Maksimal size chart per produk (tabel ukuran bersifat tunggal).
     */
    public const MAX_SIZE_CHART = 1;

    public function index(Request $request, string $productId): JsonResponse
    {
        $product = $this->findActiveProduct($productId);

        if (! $product) {
            return $this->productNotFound();
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

    /**
     * Daftar gambar untuk admin: produk berstatus draft/nonaktif tetap bisa
     * dilihat (gambar diunggah sebelum produk diaktifkan).
     */
    public function adminIndex(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return $this->productNotFound();
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

    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return $this->productNotFound();
        }

        $validated = $request->validate([
            'gambar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'type' => ['nullable', 'string', 'in:produk,size_chart'],
            'id_variant' => ['nullable', 'string', 'exists:product_variants,id_variant'],
            'alt_text' => ['nullable', 'string', 'max:150'],
            'is_primary' => ['nullable', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = $validated['type'] ?? ProductImage::TYPE_PRODUK;
        $idVariant = $validated['id_variant'] ?? null;

        // Size chart disimpan pada level produk sehingga id_variant harus kosong.
        if ($type === ProductImage::TYPE_SIZE_CHART && $idVariant !== null) {
            return $this->validationError(
                'Size chart tidak boleh ditempelkan pada varian.',
                ['id_variant' => ['Size chart disimpan pada level produk, id_variant harus kosong.']]
            );
        }

        if ($idVariant !== null) {
            $variant = ProductVariant::where('id_variant', $idVariant)
                ->where('id_product', $product->id_product)
                ->first();

            if (! $variant) {
                return $this->validationError(
                    'Varian tidak ditemukan pada produk ini.',
                    ['id_variant' => ['Varian tidak ditemukan pada produk ini.']]
                );
            }
        }

        if ($type === ProductImage::TYPE_PRODUK) {
            $total = ProductImage::where('id_product', $product->id_product)
                ->where('type', ProductImage::TYPE_PRODUK)
                ->count();

            if ($total >= self::MAX_GAMBAR_PRODUK) {
                return $this->validationError(
                    'Maksimal '.self::MAX_GAMBAR_PRODUK.' gambar per produk.'
                );
            }
        }

        if ($type === ProductImage::TYPE_SIZE_CHART) {
            $total = ProductImage::where('id_product', $product->id_product)
                ->where('type', ProductImage::TYPE_SIZE_CHART)
                ->count();

            if ($total >= self::MAX_SIZE_CHART) {
                return $this->validationError(
                    'Size chart produk ini sudah ada. Hapus dulu sebelum mengunggah yang baru.'
                );
            }
        }

        $path = $request->file('gambar')->store('products', 'public');

        $isFirstImage = ! ProductImage::where('id_product', $product->id_product)
            ->where('type', $type)
            ->exists();
        $isPrimary = $type === ProductImage::TYPE_PRODUK && (bool) ($validated['is_primary'] ?? false);
        $isPrimary = $isPrimary || ($type === ProductImage::TYPE_PRODUK && $isFirstImage);
        $urutan = $validated['urutan'] ?? ((int) ProductImage::where('id_product', $product->id_product)
            ->where('type', $type)
            ->max('urutan') + 1);

        $image = DB::transaction(function () use ($validated, $product, $path, $isPrimary, $urutan, $type, $idVariant) {
            if ($isPrimary) {
                ProductImage::where('id_product', $product->id_product)
                    ->where('type', ProductImage::TYPE_PRODUK)
                    ->update(['is_primary' => false]);
            }

            return ProductImage::create([
                'id_image' => (string) Str::uuid(),
                'id_product' => $product->id_product,
                'id_variant' => $idVariant,
                'path_gambar' => $path,
                'alt_text' => $validated['alt_text'] ?? null,
                'type' => $type,
                'is_primary' => $isPrimary,
                'urutan' => $urutan,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Gambar produk berhasil diunggah.',
            'data' => $image,
        ], 201);
    }

    /**
     * Hapus gambar tanpa scoping produk (endpoint lama, tetap dipertahankan
     * demi kompatibilitas).
     */
    public function destroy(string $id): JsonResponse
    {
        $image = ProductImage::find($id);

        if (! $image) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar tidak ditemukan.',
            ], 404);
        }

        $this->deleteImage($image);

        return response()->json([
            'success' => true,
            'message' => 'Gambar produk berhasil dihapus.',
        ]);
    }

    /**
     * Hapus gambar yang scoping-nya ke produk: gambar harus benar-benar
     * milik produk pada path tersebut, jika tidak -> 404.
     */
    public function destroyScoped(string $productId, string $imageId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return $this->productNotFound();
        }

        $image = ProductImage::where('id_image', $imageId)
            ->where('id_product', $product->id_product)
            ->first();

        if (! $image) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar tidak ditemukan pada produk ini.',
            ], 404);
        }

        $this->deleteImage($image);

        return response()->json([
            'success' => true,
            'message' => 'Gambar produk berhasil dihapus.',
        ]);
    }

    /**
     * Menghapus berkas gambar dari storage lalu baris datanya, dan Promote
     * gambar utama berikutnya bila gambar utama ikut terhapus.
     */
    private function deleteImage(ProductImage $image): void
    {
        $wasPrimary = (bool) $image->is_primary;
        $type = $image->type ?: ProductImage::TYPE_PRODUK;

        DB::transaction(function () use ($image, $wasPrimary, $type) {
            if (filled($image->path_gambar)) {
                Storage::disk('public')->delete($image->path_gambar);
            }

            $image->delete();

            if ($wasPrimary && $type === ProductImage::TYPE_PRODUK) {
                $next = ProductImage::where('id_product', $image->id_product)
                    ->where('type', ProductImage::TYPE_PRODUK)
                    ->orderBy('urutan')
                    ->first();

                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }
        });
    }

    private function validationError(string $message, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
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
