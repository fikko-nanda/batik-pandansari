<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Kategori produk.
 *
 * POST   /api/admin/categories         tambah (admin)
 * PUT    /api/admin/categories/{id}    ubah parsial (admin)
 * DELETE /api/admin/categories/{id}    nonaktifkan (admin, baris tetap ada)
 */
class CategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        }

        $validated = $request->validate([
            'nama_kategori' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:categories,slug'],
            'deskripsi' => ['nullable', 'string'],
            'lokasi_gambar' => ['nullable', 'string', 'max:200'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);

        $slug = ! empty($validated['slug'])
            ? $validated['slug']
            : $this->uniqueSlug($validated['nama_kategori']);

        $category = Category::create([
            'id_category' => (string) Str::uuid(),
            'nama_kategori' => $validated['nama_kategori'],
            'slug' => $slug,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'lokasi_gambar' => $validated['lokasi_gambar'] ?? null,
            'status_aktif' => $validated['status_aktif'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dibuat.',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::where('id_category', $id)->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        }

        $validated = $request->validate([
            'nama_kategori' => ['sometimes', 'required', 'string', 'max:80'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('categories', 'slug')->ignore($category->id_category, 'id_category'),
            ],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'lokasi_gambar' => ['sometimes', 'nullable', 'string', 'max:200'],
            'status_aktif' => ['sometimes', 'required', 'boolean'],
        ]);

        if (array_key_exists('slug', $validated)
            && ($validated['slug'] === null || $validated['slug'] === '')) {
            unset($validated['slug']);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = Category::where('id_category', $id)->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        $category->update(['status_aktif' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dinonaktifkan.',
            'data' => $category,
        ]);
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'kategori';
        }

        $slug = $base;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
