<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    /**
     * Maksimal jumlah varian yang boleh dikirim dalam satu permintaan bulk.
     */
    public const MAX_BULK_ITEMS = 500;

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
            'berat_gram' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'status_varian' => ['nullable', 'string', 'in:aktif,nonaktif'],
        ]);

        $variant = DB::transaction(function () use ($validated, $product) {
            $variant = ProductVariant::create([
                'id_variant' => (string) Str::uuid(),
                'id_product' => $product->id_product,
                'sku' => $validated['sku'],
                'nama_varian' => $validated['nama_varian'] ?? null,
                'warna' => $validated['warna'] ?? null,
                'ukuran' => $validated['ukuran'] ?? null,
                'harga' => $validated['harga'],
                'stok' => $validated['stok'] ?? 0,
                'berat_gram' => $validated['berat_gram'] ?? 500,
                'status_varian' => $validated['status_varian'] ?? 'aktif',
            ]);

            $this->pastikanProdukPunyaUkuranValid($product);

            return $variant;
        });

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil dibuat.',
            'data' => $variant,
        ], 201);
    }

    /**
     * Simpan banyak varian sekaligus untuk satu produk.
     *
     * Item tanpa id_variant dibuat sebagai varian baru, item dengan
     * id_variant diperbarui (partial: hanya field yang dikirim yang
     * diubah). SKU wajib unik — SKU milik varian itu sendiri tetap boleh
     * dipakai saat varian tersebut diperbarui.
     */
    public function bulkStore(Request $request, string $productId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product) {
            return $this->productNotFound();
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_BULK_ITEMS],
            'items.*.id_variant' => ['nullable', 'string', 'exists:product_variants,id_variant'],
            'items.*.sku' => ['required', 'string', 'max:100'],
            'items.*.nama_varian' => ['nullable', 'string', 'max:100'],
            'items.*.warna' => ['nullable', 'string', 'max:50'],
            'items.*.ukuran' => ['nullable', 'string', 'max:30'],
            'items.*.harga' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.stok' => ['nullable', 'integer', 'min:0'],
            'items.*.berat_gram' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.status_varian' => ['nullable', 'string', 'in:aktif,nonaktif'],
            'hapus_yang_tidak_disediakan' => ['nullable', 'boolean'],
        ]);

        $items = $validated['items'];
        $hapusYangTidakDisediakan = (bool) ($validated['hapus_yang_tidak_disediakan'] ?? false);

        $indexedIds = [];
        foreach ($items as $index => $item) {
            if (! empty($item['id_variant'])) {
                $indexedIds[$item['id_variant']] = $index;
            }
        }

        // Varian yang dirujuk harus milik produk ini.
        $ownedIds = ProductVariant::where('id_product', $product->id_product)
            ->whereIn('id_variant', array_keys($indexedIds))
            ->pluck('id_variant')
            ->all();

        foreach (array_keys($indexedIds) as $idVariant) {
            if (! in_array($idVariant, $ownedIds, true)) {
                $index = $indexedIds[$idVariant];

                throw ValidationException::withMessages([
                    "items.{$index}.id_variant" => ['Varian tidak ditemukan pada produk ini.'],
                ]);
            }
        }

        $this->pastikanSkuUnikDalamPayload($items);
        $this->pastikanSkuTidakBentrokDenganDatabase($items, $indexedIds);

        $result = DB::transaction(function () use ($items, $product, $hapusYangTidakDisediakan) {
            $dibuat = 0;
            $diperbarui = 0;
            $dinonaktifkan = 0;
            $saved = [];

            foreach ($items as $index => $item) {
                $data = $this->dataVarianDariItem($item);

                if (! empty($item['id_variant'])) {
                    $variant = ProductVariant::where('id_variant', $item['id_variant'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->pastikanStokTidakDiBawahReservasi($variant, $data['stok'] ?? null, "items.{$index}.stok");

                    $variant->update($data);
                    $diperbarui++;
                } else {
                    $variant = ProductVariant::create(array_merge($data, [
                        'id_variant' => (string) Str::uuid(),
                        'id_product' => $product->id_product,
                    ]));
                    $dibuat++;
                }

                $saved[] = $variant;
            }

            $keepIds = collect($saved)->pluck('id_variant')->all();

            if ($hapusYangTidakDisediakan) {
                $toDisable = ProductVariant::where('id_product', $product->id_product)
                    ->where('status_varian', 'aktif')
                    ->whereNotIn('id_variant', $keepIds)
                    ->get();

                foreach ($toDisable as $variant) {
                    $variant->update(['status_varian' => 'nonaktif']);
                    $dinonaktifkan++;
                }
            }

            $this->pastikanProdukPunyaUkuranValid($product);

            return [
                'dibuat' => $dibuat,
                'diperbarui' => $diperbarui,
                'dinonaktifkan' => $dinonaktifkan,
                'items' => ProductVariant::where('id_product', $product->id_product)
                    ->orderBy('dibuat_pada')
                    ->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil disimpan.',
            'data' => $result,
        ]);
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
            'berat_gram' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000000'],
            'status_varian' => ['sometimes', 'required', 'string', 'in:aktif,nonaktif'],
        ]);

        $variant = DB::transaction(function () use ($id, $validated) {
            $variant = ProductVariant::whereKey($id)->lockForUpdate()->first();

            if (! $variant) {
                return null;
            }

            $this->pastikanStokTidakDiBawahReservasi($variant, $validated['stok'] ?? null, 'stok');

            $variant->update($validated);

            return $variant;
        });

        if (! $variant) {
            return $this->variantNotFound();
        }

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

        DB::transaction(function () use ($variant) {
            $variant->update(['status_varian' => 'nonaktif']);

            $product = $variant->product;

            if ($product) {
                $this->pastikanProdukPunyaUkuranValid($product);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Varian produk berhasil dinonaktifkan.',
            'data' => $variant,
        ]);
    }

    /**
     * Memetakan item payload menjadi kolom varian. Hanya field yang benar-benar
     * dikirim yang ikut terisi (partial update).
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function dataVarianDariItem(array $item): array
    {
        $data = [];

        foreach (['sku', 'nama_varian', 'warna', 'ukuran', 'harga', 'stok', 'berat_gram', 'status_varian'] as $field) {
            if (array_key_exists($field, $item)) {
                $data[$field] = $item[$field];
            }
        }

        return $data;
    }

    /**
     * SKU harus unik di dalam payload yang sama.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function pastikanSkuUnikDalamPayload(array $items): void
    {
        $terlihat = [];

        foreach ($items as $index => $item) {
            $sku = $item['sku'];

            if (isset($terlihat[$sku])) {
                throw ValidationException::withMessages([
                    "items.{$index}.sku" => [
                        "SKU {$sku} sudah dipakai pada items.{$terlihat[$sku]}.",
                    ],
                ]);
            }

            $terlihat[$sku] = $index;
        }
    }

    /**
     * SKU pada payload tidak boleh bentrok dengan varian lain di database.
     * Varian yang SKUnya ikut dipertahankan (varian itu sendiri) tetap
     * boleh memakai SKU-nya sendiri.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, int>  $indexedIds
     */
    private function pastikanSkuTidakBentrokDenganDatabase(array $items, array $indexedIds): void
    {
        $skus = array_values(array_unique(array_column($items, 'sku')));

        if ($skus === []) {
            return;
        }

        $pemilikSku = ProductVariant::whereIn('sku', $skus)
            ->pluck('id_variant', 'sku');

        foreach ($items as $index => $item) {
            $sku = $item['sku'];
            $ownerId = $pemilikSku->get($sku);

            if ($ownerId === null) {
                continue;
            }

            $ownerIndex = $indexedIds[$ownerId] ?? null;

            if ($ownerIndex === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.sku" => ["SKU {$sku} sudah dipakai varian lain."],
                ]);
            }

            // Pemilik SKU ikut dalam payload: hanya boleh bila ia tetap memakai
            // SKU tersebut (bukan mengunci SKU milik varian lain).
            if (($items[$ownerIndex]['sku'] ?? null) !== $sku) {
                throw ValidationException::withMessages([
                    "items.{$index}.sku" => ["SKU {$sku} sedang dipakai varian lain pada payload yang sama."],
                ]);
            }
        }
    }

    /**
     * Produk yang sudah memiliki varian minimal satu varian aktif dengan
     * ukuran yang valid. Produk tanpa varian sama sekali tetap diperbolehkan
     * (produk yang memang tidak menggunakan ukuran).
     */
    private function pastikanProdukPunyaUkuranValid(Product $product): void
    {
        $totalAktif = ProductVariant::where('id_product', $product->id_product)
            ->where('status_varian', 'aktif')
            ->count();

        if ($totalAktif === 0) {
            return;
        }

        $punyaUkuran = ProductVariant::where('id_product', $product->id_product)
            ->where('status_varian', 'aktif')
            ->whereNotNull('ukuran')
            ->where('ukuran', '!=', '')
            ->exists();

        if ($punyaUkuran) {
            return;
        }

        throw ValidationException::withMessages([
            'ukuran' => ['Produk harus memiliki minimal 1 varian dengan ukuran yang valid.'],
        ]);
    }

    private function pastikanStokTidakDiBawahReservasi(
        ProductVariant $variant,
        mixed $stokBaru,
        string $field
    ): void {
        if ($stokBaru === null) {
            return;
        }

        $dipesan = (int) $variant->stok_dipesan;

        if ((int) $stokBaru >= $dipesan) {
            return;
        }

        throw ValidationException::withMessages([
            $field => ["Stok tidak boleh lebih kecil dari jumlah yang sedang dipesan ({$dipesan})."],
        ]);
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
