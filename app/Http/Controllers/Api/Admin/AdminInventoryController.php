<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inventaris admin.
 *
 * Catatan urutan route: route statis (/movements, /low-stock, /summary,
 * /valuation, /export) HARUS didaftarkan sebelum /{variant_id} agar tidak
 * tertangkap oleh wildcard parameternya.
 */
class AdminInventoryController extends Controller
{
    /**
     * GET /api/admin/inventory
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:36'],
            'status_stok' => ['nullable', 'string', 'in:semua,aman,menipis,kritis'],
            'sort' => ['nullable', 'string', 'in:stok,stok_minimum,harga,sku,dibuat_pada'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $variants = $this->baseQuery($validated)
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar inventaris berhasil diambil.',
            'data' => $variants->getCollection()->map(
                fn ($variant) => $this->transformVariant($variant)
            )->values(),
            'meta' => [
                'current_page' => $variants->currentPage(),
                'per_page' => $variants->perPage(),
                'total' => $variants->total(),
                'last_page' => $variants->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/inventory/{variant_id}
     *
     * Didaftarkan setelah route statis agar tidak menangkap URL tersebut.
     */
    public function show(string $variantId): JsonResponse
    {
        $variant = ProductVariant::with([
            'product.category',
            'inventoryMovements' => fn ($query) => $query
                ->with('user')
                ->orderByDesc('waktu_pergerakan')
                ->orderByDesc('dibuat_pada')
                ->limit(50),
        ])
            ->where('id_variant', $variantId)
            ->first();

        if (! $variant) {
            return response()->json([
                'success' => false,
                'message' => 'Varian produk tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail inventaris berhasil diambil.',
            'data' => $this->transformVariant($variant, withProduct: false),
        ]);
    }

    /**
     * GET /api/admin/inventory/movements
     */
    public function movements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_variant' => ['nullable', 'string', 'max:36'],
            'jenis_pergerakan' => [
                'nullable',
                'string',
                'in:'.implode(',', InventoryService::SEMUA_JENIS),
            ],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $movements = InventoryMovement::query()
            ->with([
                'variant.product',
                'user',
            ])
            ->when(
                ! empty($validated['id_variant']),
                fn ($query) => $query->where('id_variant', $validated['id_variant'])
            )
            ->when(
                ! empty($validated['jenis_pergerakan']),
                fn ($query) => $query->where('jenis_pergerakan', $validated['jenis_pergerakan'])
            )
            ->when(
                ! empty($validated['dari']),
                fn ($query) => $query->whereDate('waktu_pergerakan', '>=', $validated['dari'])
            )
            ->when(
                ! empty($validated['sampai']),
                fn ($query) => $query->whereDate('waktu_pergerakan', '<=', $validated['sampai'])
            )
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('referensi', 'like', "%{$term}%")
                            ->orWhere('catatan', 'like', "%{$term}%")
                            ->orWhereHas('variant', fn ($q) => $q->where('sku', 'like', "%{$term}%"));
                    });
                }
            )
            ->orderByDesc('waktu_pergerakan')
            ->orderByDesc('dibuat_pada')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pergerakan stok berhasil diambil.',
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'last_page' => $movements->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/admin/inventory/adjustments
     */
    public function adjust(Request $request, InventoryService $inventoryService): JsonResponse
    {
        $validated = $request->validate([
            'id_variant' => ['required', 'string', 'exists:product_variants,id_variant'],
            'jenis_penyesuaian' => ['required', 'string', 'in:masuk,keluar'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
            'alasan' => ['required', 'string', 'max:500'],
            'referensi' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $inventoryService->adjustStock(
            idVariant: $validated['id_variant'],
            jenisPenyesuaian: $validated['jenis_penyesuaian'],
            jumlah: (int) $validated['jumlah'],
            alasan: $validated['alasan'],
            idUser: $request->user()->id_user,
            referensi: $validated['referensi'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyesuaian stok berhasil dicatat.',
            'data' => [
                'variant' => $this->transformVariant($result['variant'], withProduct: false),
                'movement' => $result['movement']->makeVisible([
                    'id_inventory_movement',
                    'id_variant',
                    'id_user',
                ]),
            ],
        ]);
    }

    /**
     * GET /api/admin/inventory/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['nullable', 'string', 'in:menipis,kritis'],
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        // Varian stok habis selalu kritis. Varian menipis hanya muncul bila
        // stok_minimum memang diatur (> 0) sehingga tidak ada ambang yang
        // dikarang.
        $variants = ProductVariant::query()
            ->with(['product', 'product.category'])
            ->where(function ($query) {
                $query->where('stok', 0)
                    ->orWhere(function ($menipis) {
                        $menipis->where('stok_minimum', '>', 0)
                            ->whereColumn('stok', '<=', 'stok_minimum')
                            ->where('stok', '>', 0);
                    });
            })
            ->when(
                ($validated['level'] ?? null) === 'kritis',
                fn ($query) => $query->where('stok', 0)
            )
            ->when(
                ($validated['level'] ?? null) === 'menipis',
                fn ($query) => $query->where('stok_minimum', '>', 0)
                    ->whereColumn('stok', '<=', 'stok_minimum')
                    ->where('stok', '>', 0)
            )
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('sku', 'like', "%{$term}%")
                            ->orWhere('nama_varian', 'like', "%{$term}%")
                            ->orWhereHas('product', fn ($q) => $q->where('nama_produk', 'like', "%{$term}%"));
                    });
                }
            )
            ->orderByRaw('CASE WHEN stok = 0 THEN 0 ELSE 1 END')
            ->orderBy('stok')
            ->orderBy('sku')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar varian dengan stok menipis berhasil diambil.',
            'data' => $variants->getCollection()->map(function ($variant) {
                $item = $this->transformVariant($variant, withProduct: false);
                $item['level'] = $variant->statusStok();

                return $item;
            })->values(),
            'meta' => [
                'current_page' => $variants->currentPage(),
                'per_page' => $variants->perPage(),
                'total' => $variants->total(),
                'last_page' => $variants->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/inventory/summary
     */
    public function summary(): JsonResponse
    {
        $aggregate = ProductVariant::query()
            ->selectRaw('COUNT(*) as total_varian')
            ->selectRaw('COUNT(DISTINCT id_product) as total_produk')
            ->selectRaw('COALESCE(SUM(stok), 0) as total_unit_stok')
            ->selectRaw('SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) as varian_kritis')
            ->selectRaw(
                'SUM(CASE WHEN stok_minimum > 0 AND stok > 0 AND stok <= stok_minimum THEN 1 ELSE 0 END) as varian_menipis'
            )
            ->selectRaw(
                'SUM(CASE WHEN NOT (stok = 0) AND NOT (stok_minimum > 0 AND stok > 0 AND stok <= stok_minimum) THEN 1 ELSE 0 END) as varian_aman'
            )
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan inventaris berhasil diambil.',
            'data' => [
                // SKU bersifat unik per varian (constraint unique di
                // product_variants.sku), sehingga jumlah SKU sama dengan
                // jumlah varian.
                'total_sku' => (int) $aggregate->total_varian,
                'total_varian' => (int) $aggregate->total_varian,
                'total_produk' => (int) $aggregate->total_produk,
                'total_unit_stok' => (int) $aggregate->total_unit_stok,
                'varian_kritis' => (int) $aggregate->varian_kritis,
                'varian_menipis' => (int) $aggregate->varian_menipis,
                'varian_aman' => (int) $aggregate->varian_aman,
                'jumlah_produk_dihitung_terpisah' => true,
                'keterangan' => 'total_sku sama dengan total_varian karena SKU unik per varian; total_produk dihitung terpisah dari tabel products.',
            ],
        ]);
    }

    /**
     * GET /api/admin/inventory/valuation
     */
    public function valuation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:36'],
        ]);

        $base = ProductVariant::query()
            ->when(
                ! empty($validated['category']),
                fn ($query) => $query->whereHas(
                    'product.categories',
                    fn ($categoryQuery) => $categoryQuery->where(
                        'categories.id_category',
                        $validated['category']
                    )
                )
            );

        $aggregate = (clone $base)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN harga_modal IS NOT NULL THEN stok * harga_modal ELSE 0 END), 0) as total_nilai'
            )
            ->selectRaw('SUM(CASE WHEN harga_modal IS NOT NULL THEN 1 ELSE 0 END) as varian_dihitung')
            ->selectRaw('SUM(CASE WHEN harga_modal IS NULL THEN 1 ELSE 0 END) as varian_belum_dihitung')
            ->selectRaw('COALESCE(SUM(CASE WHEN harga_modal IS NULL THEN stok ELSE 0 END), 0) as unit_belum_dihitung')
            ->first();

        $perProduk = (clone $base)
            ->join('products', 'products.id_product', '=', 'product_variants.id_product')
            ->groupBy('products.id_product', 'products.nama_produk')
            ->orderBy('products.nama_produk')
            ->selectRaw('products.id_product as id_product')
            ->selectRaw('products.nama_produk as nama_produk')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN product_variants.harga_modal IS NOT NULL THEN product_variants.stok * product_variants.harga_modal ELSE 0 END), 0) as total_nilai'
            )
            ->selectRaw('SUM(CASE WHEN product_variants.harga_modal IS NOT NULL THEN 1 ELSE 0 END) as varian_dihitung')
            ->selectRaw('SUM(CASE WHEN product_variants.harga_modal IS NULL THEN 1 ELSE 0 END) as varian_belum_dihitung')
            ->get()
            ->filter(fn ($row) => (int) $row->varian_dihitung > 0 || (int) $row->varian_belum_dihitung > 0)
            ->values();

        $varianDihitung = (int) $aggregate->varian_dihitung;
        $varianBelumDihitung = (int) $aggregate->varian_belum_dihitung;

        return response()->json([
            'success' => true,
            'message' => 'Valuasi persediaan berhasil diambil.',
            'data' => [
                'periode' => [
                    'dari' => null,
                    'sampai' => null,
                    'keterangan' => 'Valuasi adalah saldo stok saat ini, bukan per periode.',
                ],
                'total_nilai_persediaan' => round((float) $aggregate->total_nilai, 2),
                'varian_dihitung' => $varianDihitung,
                'varian_belum_dihitung' => $varianBelumDihitung,
                'unit_belum_dihitung' => (int) $aggregate->unit_belum_dihitung,
                'lengkap' => $varianBelumDihitung === 0,
                'keterangan_nilai' => 'Nilai = stok x harga_modal. Varian dengan harga_modal NULL tidak dihitung (bukan 0) karena harga modal memang belum tersedia di database.',
                'per_produk' => $perProduk->map(fn ($row) => [
                    'id_product' => (string) $row->id_product,
                    'nama_produk' => (string) $row->nama_produk,
                    'total_nilai' => round((float) $row->total_nilai, 2),
                    'varian_dihitung' => (int) $row->varian_dihitung,
                    'varian_belum_dihitung' => (int) $row->varian_belum_dihitung,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/admin/inventory/export
     *
     * Filter identik dengan index, tetapi tanpa paginasi agar seluruh
     * hasil filter ikut terekspor.
     */
    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:36'],
            'status_stok' => ['nullable', 'string', 'in:semua,aman,menipis,kritis'],
        ]);

        $filename = 'inventaris-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($validated) {
            $output = fopen('php://output', 'w');

            // Tanda kutip pemisah dimatikan ($escape = '') mengikuti ekspor
            // produk yang sudah ada, sehingga parser CSV standar membaca
            // berkas ini dengan benar.
            fputcsv($output, [
                'id_variant',
                'sku',
                'nama_produk',
                'kategori',
                'nama_varian',
                'warna',
                'ukuran',
                'stok',
                'stok_minimum',
                'status_stok',
                'harga',
                'harga_modal',
                'nilai_persediaan',
                'status_varian',
                'dibuat_pada',
            ], ',', '"', '');

            $this->baseQuery($validated)
                ->chunk(200, function ($variants) use ($output) {
                    foreach ($variants as $variant) {
                        fputcsv($output, [
                            $variant->id_variant,
                            $this->csvSafe($variant->sku),
                            $this->csvSafe($variant->product?->nama_produk),
                            $this->csvSafe($variant->product?->category?->nama_kategori),
                            $this->csvSafe($variant->nama_varian),
                            $this->csvSafe($variant->warna),
                            $this->csvSafe($variant->ukuran),
                            (int) $variant->stok,
                            (int) $variant->stok_minimum,
                            $variant->statusStok(),
                            (float) $variant->harga,
                            $variant->harga_modal === null ? '' : (float) $variant->harga_modal,
                            $variant->nilaiPersediaan() ?? '',
                            $this->csvSafe($variant->status_varian),
                            $variant->dibuat_pada?->format('Y-m-d H:i:s'),
                        ], ',', '"', '');
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Query dasar daftar & ekspor inventaris (dipakai index dan export).
     *
     * @param  array<string, mixed>  $validated
     */
    private function baseQuery(array $validated): Builder
    {
        $statusStok = $validated['status_stok'] ?? null;

        return ProductVariant::query()
            ->with(['product', 'product.category'])
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('sku', 'like', "%{$term}%")
                            ->orWhere('nama_varian', 'like', "%{$term}%")
                            ->orWhereHas('product', fn ($q) => $q->where('nama_produk', 'like', "%{$term}%"));
                    });
                }
            )
            ->when(
                ! empty($validated['category']),
                fn ($query) => $query->whereHas(
                    'product.categories',
                    fn ($categoryQuery) => $categoryQuery->where(
                        'categories.id_category',
                        $validated['category']
                    )
                )
            )
            ->when(
                $statusStok === 'kritis',
                fn ($query) => $query->where('stok', 0)
            )
            ->when(
                $statusStok === 'menipis',
                fn ($query) => $query->where('stok_minimum', '>', 0)
                    ->whereColumn('stok', '<=', 'stok_minimum')
                    ->where('stok', '>', 0)
            )
            ->when(
                $statusStok === 'aman',
                fn ($query) => $query->where(function ($inner) {
                    $inner->where('stok', '>', 0)
                        ->where(function ($notMenipis) {
                            $notMenipis->where('stok_minimum', 0)
                                ->orWhereColumn('stok', '>', 'stok_minimum');
                        });
                })
            )
            ->when(
                ! empty($validated['sort']),
                fn ($query) => $query->orderBy(
                    $validated['sort'],
                    $validated['order'] ?? 'asc'
                ),
                fn ($query) => $query->orderBy('sku')
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function transformVariant(ProductVariant $variant, bool $withProduct = true): array
    {
        $data = [
            'id_variant' => $variant->id_variant,
            'id_product' => $variant->id_product,
            'sku' => $variant->sku,
            'nama_varian' => $variant->nama_varian,
            'warna' => $variant->warna,
            'ukuran' => $variant->ukuran,
            'stok' => (int) $variant->stok,
            'stok_minimum' => (int) $variant->stok_minimum,
            'status_stok' => $variant->statusStok(),
            'harga' => (float) $variant->harga,
            'harga_modal' => $variant->harga_modal === null ? null : (float) $variant->harga_modal,
            'nilai_persediaan' => $variant->nilaiPersediaan(),
            'berat_gram' => (int) $variant->berat_gram,
            'status_varian' => $variant->status_varian,
            'dibuat_pada' => $variant->dibuat_pada,
            'diperbarui_pada' => $variant->diperbarui_pada,
        ];

        if ($withProduct && $variant->relationLoaded('product')) {
            $data['product'] = $variant->product ? [
                'id_product' => $variant->product->id_product,
                'nama_produk' => $variant->product->nama_produk,
                'slug' => $variant->product->slug,
                'status_produk' => $variant->product->status_produk,
                'kategori' => $variant->product->relationLoaded('category')
                    && $variant->product->category
                    ? [
                        'id_category' => $variant->product->category->id_category,
                        'nama_kategori' => $variant->product->category->nama_kategori,
                    ]
                    : null,
            ] : null;
        }

        if ($variant->relationLoaded('inventoryMovements')) {
            $data['inventory_movements'] = $variant->inventoryMovements;
        }

        return $data;
    }

    /**
     * Melindungi parser spreadsheet dari CSV formula injection (OWASP):
     * nilai yang diawali =, +, -, @, tab, atau CR diberi awalan apostrof.
     */
    private function csvSafe(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (preg_match('/^[\s]*[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
