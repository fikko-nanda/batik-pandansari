<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->listProducts($request, onlyActive: true);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        return $this->listProducts($request, onlyActive: false);
    }

    private function listProducts(Request $request, bool $onlyActive): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:36'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 10;

        $products = Product::query()
            ->with([
                'category',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('urutan'),
            ])
            ->when($onlyActive, fn ($query) => $query->where('status_produk', 'aktif'))
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
                fn ($query) => $query->where('id_category', $validated['category'])
            )
            ->orderByDesc('dibuat_pada')
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

    public function show(string $id): JsonResponse
    {
        return $this->showProduct($id, onlyActive: true);
    }

    public function adminShow(string $id): JsonResponse
    {
        return $this->showProduct($id, onlyActive: false);
    }

    private function showProduct(string $id, bool $onlyActive): JsonResponse
    {
        $product = Product::with([
            'category',
            'variants' => fn ($query) => $onlyActive
                ? $query->where('status_varian', 'aktif')
                : $query,
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('urutan'),
        ])
            ->where('id_product', $id)
            ->when($onlyActive, fn ($query) => $query->where('status_produk', 'aktif'))
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
            'id_category' => ['required', 'string', 'exists:categories,id_category'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:products,slug'],
            'deskripsi' => ['nullable', 'string'],
            'status_produk' => ['nullable', 'string', 'in:draft,aktif,nonaktif'],
        ]);

        $slug = ! empty($validated['slug'])
            ? $validated['slug']
            : $this->uniqueSlug($validated['nama_produk']);

        $product = Product::create([
            'id_product' => (string) Str::uuid(),
            'id_category' => $validated['id_category'],
            'nama_produk' => $validated['nama_produk'],
            'slug' => $slug,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'status_produk' => $validated['status_produk'] ?? 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dibuat.',
            'data' => $product->load('category'),
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
            'id_category' => ['sometimes', 'required', 'string', 'exists:categories,id_category'],
            'nama_produk' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:180',
                Rule::unique('products', 'slug')->ignore($product->id_product, 'id_product'),
            ],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'status_produk' => ['sometimes', 'required', 'string', 'in:draft,aktif,nonaktif'],
        ]);

        if (array_key_exists('slug', $validated)
            && ($validated['slug'] === null || $validated['slug'] === '')) {
            unset($validated['slug']);
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product->load('category'),
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
