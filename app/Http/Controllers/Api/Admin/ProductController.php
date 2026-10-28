<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Minimal jumlah foto produk (type = 'produk') yang wajib dimiliki
     * produk sebelum berstatus aktif.
     */
    public const MIN_GAMBAR_AKTIF = 1;

    public function adminIndex(Request $request): JsonResponse
    {
        return $this->listProducts($request);
    }

    private function listProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:36'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $adminFilters = $this->validateAdminFilters($request);

        $perPage = $validated['per_page'] ?? 10;

        $products = $this->productQuery($validated, $adminFilters, true)
            ->paginate($perPage);

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

    public function adminExport(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:36'],
        ]);

        $adminFilters = $this->validateAdminFilters($request);

        $filename = 'produk-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($validated, $adminFilters) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'id_product',
                'nama_produk',
                'slug',
                'kategori',
                'status_produk',
                'harga_min',
                'harga_maks',
                'stok_total',
                'jumlah_varian',
                'dibuat_pada',
            ], ',', '"', '');

            $this->productQuery($validated, $adminFilters, false)
                ->withMin('variants as harga_min', 'harga')
                ->withMax('variants as harga_maks', 'harga')
                ->withSum('variants as stok_total', 'stok')
                ->withCount('variants as jumlah_varian')
                ->chunk(200, function ($products) use ($output) {
                    foreach ($products as $product) {
                        fputcsv($output, [
                            $product->id_product,
                            $product->nama_produk,
                            $product->slug,
                            $product->categories->pluck('nama_kategori')->implode(', '),
                            $product->status_produk,
                            $product->harga_min,
                            $product->harga_maks,
                            $product->stok_total,
                            $product->jumlah_varian,
                            $product->dibuat_pada?->format('Y-m-d H:i:s'),
                        ], ',', '"', '');
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Query dasar daftar dan ekspor produk admin.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $adminFilters
     */
    private function productQuery(array $validated, array $adminFilters, bool $withVariants): Builder
    {
        $query = Product::query()
            ->with([
                'category',
                'categories',
                'images' => fn ($query) => $query
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
            );

        if ($withVariants) {
            $query->with('variants');
        }

        if (! empty($adminFilters['status'])) {
            $query->where('status_produk', $adminFilters['status']);
        }

        if (! empty($adminFilters['sort'])) {
            $query->orderBy($adminFilters['sort'], $adminFilters['order'] ?? 'asc');
        } else {
            $query->orderByDesc('dibuat_pada');
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAdminFilters(Request $request): array
    {
        return $request->validate([
            'status' => ['nullable', 'string', 'in:draft,aktif,nonaktif'],
            'sort' => ['nullable', 'string', 'in:nama_produk,dibuat_pada,status_produk'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
        ]);
    }

    public function adminShow(string $id): JsonResponse
    {
        return $this->showProduct($id);
    }

    private function showProduct(string $id): JsonResponse
    {
        $product = Product::with([
            'category',
            'categories',
            'variants',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('urutan'),
            'sizeChart' => fn ($query) => $query->orderBy('urutan'),
        ])
            ->where('id_product', $id)
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

    public function store(Request $request): JsonResponse
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        }

        $validated = $request->validate([
            'id_categories' => ['required_without:id_category', 'array', 'list', 'min:1'],
            'id_categories.*' => ['required', 'string', 'distinct', 'exists:categories,id_category'],
            'id_category' => ['required_without:id_categories', 'string', 'exists:categories,id_category'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:products,slug'],
            'deskripsi' => ['nullable', 'string'],
            'berat_produk' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'bahan_kain' => ['nullable', 'string', 'max:100'],
            'status_produk' => ['nullable', 'string', 'in:draft,aktif,nonaktif'],
        ]);

        $statusProduk = $validated['status_produk'] ?? 'draft';

        // Produk yang langsung dibuat aktif wajib sudah punya foto karena
        // belum ada jalur unggah gambar pada saat pembuatan.
        if ($statusProduk === 'aktif') {
            throw ValidationException::withMessages([
                'gambar' => [
                    'Produk harus memiliki minimal '.self::MIN_GAMBAR_AKTIF.' gambar sebelum diaktifkan. '
                    .'Buat produk dengan status draft, unggah gambarnya, lalu aktifkan.',
                ],
            ]);
        }

        $slug = ! empty($validated['slug'])
            ? $validated['slug']
            : $this->uniqueSlug($validated['nama_produk']);

        $categoryIds = $validated['id_categories'] ?? [$validated['id_category']];

        $product = DB::transaction(function () use ($categoryIds, $slug, $statusProduk, $validated): Product {
            $product = Product::create([
                'id_product' => (string) Str::uuid(),
                'id_category' => $categoryIds[0],
                'nama_produk' => $validated['nama_produk'],
                'sku' => $validated['sku'] ?? null,
                'slug' => $slug,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'berat_produk' => $validated['berat_produk'] ?? null,
                'bahan_kain' => $validated['bahan_kain'] ?? null,
                'status_produk' => $statusProduk,
            ]);

            $product->categories()->sync($categoryIds);

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat.',
            'data' => $product->load('category', 'categories'),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        }

        $validated = $request->validate([
            'id_categories' => ['sometimes', 'required', 'array', 'list', 'min:1'],
            'id_categories.*' => ['required', 'string', 'distinct', 'exists:categories,id_category'],
            'id_category' => ['sometimes', 'required', 'string', 'exists:categories,id_category'],
            'nama_produk' => ['sometimes', 'required', 'string', 'max:150'],
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product->id_product, 'id_product'),
            ],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:180',
                Rule::unique('products', 'slug')->ignore($product->id_product, 'id_product'),
            ],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'berat_produk' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'bahan_kain' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status_produk' => ['sometimes', 'required', 'string', 'in:draft,aktif,nonaktif'],
        ]);

        if (array_key_exists('slug', $validated)
            && ($validated['slug'] === null || $validated['slug'] === '')) {
            unset($validated['slug']);
        }

        $categoryIds = $validated['id_categories'] ?? (
            array_key_exists('id_category', $validated)
                ? [$validated['id_category']]
                : null
        );
        unset($validated['id_categories']);

        if ($categoryIds !== null) {
            $validated['id_category'] = $categoryIds[0];
        }

        // Partial update: field yang tidak dikirim tetap memakai nilai lama,
        // field yang sengaja dikirim null akan ditulis null.
        // Syarat minimal 1 gambar diperiksa hanya saat produk dipindahkan ke
        // status aktif, sehingga produk lama yang sudah aktif tetap bisa
        // diperbarui tanpa harus mengunggah gambar.
        if (($validated['status_produk'] ?? null) === 'aktif' && $product->status_produk !== 'aktif') {
            $totalGambar = ProductImage::where('id_product', $product->id_product)
                ->where('type', ProductImage::TYPE_PRODUK)
                ->count();

            if ($totalGambar < self::MIN_GAMBAR_AKTIF) {
                throw ValidationException::withMessages([
                    'gambar' => [
                        'Produk harus memiliki minimal '.self::MIN_GAMBAR_AKTIF.' gambar sebelum diaktifkan.',
                    ],
                ]);
            }
        }

        DB::transaction(function () use ($categoryIds, $product, $validated): void {
            $product->update($validated);

            if ($categoryIds !== null) {
                $product->categories()->sync($categoryIds);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product->load('category', 'categories'),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $product->update(['status_produk' => 'nonaktif']);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dinonaktifkan.',
            'data' => $product,
        ]);
    }

    private function uniqueSlug(string $source, ?string $ignoreId = null): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'produk';
        }

        $slug = $base;
        $counter = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id_product', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
