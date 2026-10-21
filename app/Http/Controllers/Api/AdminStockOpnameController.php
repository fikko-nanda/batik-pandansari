<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Catatan stock opname (pemeriksaan stok fisik gudang) untuk admin.
 *
 * Pembuatan opname HANYA mencatat hasil hitung (stok sistem vs stok fisik
 * beserta selisihnya) dan TIDAK mengubah product_variants.stok. Selisih dapat
 * diterapkan dengan dua cara yang sama-sama mencatat inventory_movements:
 * per varian lewat POST /api/admin/inventory/adjustments, atau sekaligus lewat
 * POST /api/admin/stock-opnames/{id}/complete yang memfinalisasi seluruh item
 * opname dalam satu transaksi.
 */
class AdminStockOpnameController extends Controller
{
    /**
     * GET /api/admin/stock-opnames
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status_opname' => ['nullable', 'string', 'in:'.implode(',', StockOpname::STATUSES)],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $opnames = StockOpname::query()
            ->with('user')
            ->withCount('items')
            ->when(
                ! empty($validated['status_opname']),
                fn ($query) => $query->where('status_opname', $validated['status_opname'])
            )
            ->when(
                ! empty($validated['dari']),
                fn ($query) => $query->whereDate('dibuat_pada', '>=', $validated['dari'])
            )
            ->when(
                ! empty($validated['sampai']),
                fn ($query) => $query->whereDate('dibuat_pada', '<=', $validated['sampai'])
            )
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('kode_opname', 'like', "%{$term}%")
                            ->orWhere('catatan', 'like', "%{$term}%");
                    });
                }
            )
            ->orderByDesc('dibuat_pada')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar stock opname berhasil diambil.',
            'data' => $opnames->items(),
            'meta' => [
                'current_page' => $opnames->currentPage(),
                'per_page' => $opnames->perPage(),
                'total' => $opnames->total(),
                'last_page' => $opnames->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/admin/stock-opnames
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.id_variant' => ['required', 'string', 'exists:product_variants,id_variant'],
            'items.*.stok_fisik' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'items.*.catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $opname = DB::transaction(function () use ($validated, $user) {
            $itemsInput = collect($validated['items']);

            // Kunci ganda dicek manual di level request (bukan lewat aturan
            // unique DB) agar pesan 422 jelas dan tidak bergantung pada
            // baris yang belum pernah dibuat.
            $duplikat = $itemsInput
                ->pluck('id_variant')
                ->duplicates()
                ->values();

            if ($duplikat->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Terdapat varian yang sama lebih dari satu kali dalam daftar item.'],
                ]);
            }

            // Baca stok sistem dengan row lock agar konsisten dengan stok
            // yang berlaku saat opname dibuat.
            $variants = ProductVariant::whereIn(
                'id_variant',
                $itemsInput->pluck('id_variant')->all()
            )
                ->lockForUpdate()
                ->get()
                ->keyBy('id_variant');

            $opnameItems = [];

            foreach ($itemsInput as $item) {
                /** @var ProductVariant $variant */
                $variant = $variants[$item['id_variant']];

                $stokSistem = (int) $variant->stok;
                $stokFisik = (int) $item['stok_fisik'];

                $opnameItems[] = [
                    'id_stock_opname_item' => (string) Str::uuid(),
                    'id_variant' => $variant->id_variant,
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $stokFisik - $stokSistem,
                    'catatan' => $item['catatan'] ?? null,
                ];
            }

            $opname = StockOpname::create([
                'id_stock_opname' => (string) Str::uuid(),
                'kode_opname' => $this->generateKodeOpname(),
                'status_opname' => 'draf',
                'id_user' => $user->id_user,
                'catatan' => $validated['catatan'] ?? null,
                'total_item' => count($opnameItems),
                'total_selisih' => array_sum(array_column($opnameItems, 'selisih')),
            ]);

            foreach ($opnameItems as $opnameItem) {
                StockOpnameItem::create($opnameItem + [
                    'id_stock_opname' => $opname->id_stock_opname,
                ]);
            }

            return $opname;
        });

        return response()->json([
            'success' => true,
            'message' => 'Catatan stock opname berhasil dibuat. Stok sistem belum diubah; terapkan selisih melalui penyesuaian inventaris bila diperlukan.',
            'data' => $opname->load(['user', 'items.variant']),
        ], 201);
    }

    /**
     * GET /api/admin/stock-opnames/{id}
     */
    public function show(string $id): JsonResponse
    {
        $opname = StockOpname::with([
            'user',
            'items.variant.product',
        ])
            ->where('id_stock_opname', $id)
            ->first();

        if (! $opname) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan stock opname tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail stock opname berhasil diambil.',
            'data' => $opname,
        ]);
    }

    /**
     * POST /api/admin/stock-opnames/{id}/items
     *
     * Menambahkan satu item hitung ke opname yang masih berstatus draf.
     * stok_sistem diambil dari stok terkini saat item ditambahkan, lalu
     * total_item/total_selisih header dihitung ulang dari seluruh item.
     */
    public function storeItem(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'id_variant' => ['required', 'string', 'exists:product_variants,id_variant'],
            'stok_fisik' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($validated, $id) {
            $opname = StockOpname::where('id_stock_opname', $id)
                ->lockForUpdate()
                ->first();

            if (! $opname) {
                return null;
            }

            if ($opname->status_opname !== 'draf') {
                throw ValidationException::withMessages([
                    'status_opname' => ['Hanya opname berstatus draf yang dapat diubah.'],
                ]);
            }

            $sudahAda = StockOpnameItem::where('id_stock_opname', $opname->id_stock_opname)
                ->where('id_variant', $validated['id_variant'])
                ->exists();

            if ($sudahAda) {
                throw ValidationException::withMessages([
                    'id_variant' => ['Varian ini sudah tercatat pada opname.'],
                ]);
            }

            $variant = ProductVariant::whereKey($validated['id_variant'])
                ->lockForUpdate()
                ->first();

            $stokSistem = (int) $variant->stok;
            $stokFisik = (int) $validated['stok_fisik'];

            StockOpnameItem::create([
                'id_stock_opname_item' => (string) Str::uuid(),
                'id_stock_opname' => $opname->id_stock_opname,
                'id_variant' => $variant->id_variant,
                'stok_sistem' => $stokSistem,
                'stok_fisik' => $stokFisik,
                'selisih' => $stokFisik - $stokSistem,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $this->recalculateTotals($opname);

            return $opname->fresh();
        });

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan stock opname tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item stock opname berhasil ditambahkan.',
            'data' => $result->load(['user', 'items.variant']),
        ], 201);
    }

    /**
     * POST /api/admin/stock-opnames/{id}/complete
     *
     * Memfinalisasi opname: seluruh selisih diterapkan ke product_variants.stok
     * dan dicatat sebagai inventory_movements dalam satu transaksi. stok_sistem
     * pada setiap item disegarkan dari stok terkini sebelum selisih dihitung
     * ulang, sehingga perubahan stok yang terjadi setelah opname dibuat tetap
     * terhitung benar. Opname berstatus selesai tidak dapat difinalisasi ulang.
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($id, $user) {
            $opname = StockOpname::where('id_stock_opname', $id)
                ->lockForUpdate()
                ->first();

            if (! $opname) {
                return null;
            }

            if ($opname->status_opname !== 'draf') {
                throw ValidationException::withMessages([
                    'status_opname' => ['Opname ini sudah difinalisasi.'],
                ]);
            }

            $items = StockOpnameItem::where('id_stock_opname', $opname->id_stock_opname)
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['Opname tanpa item tidak dapat difinalisasi.'],
                ]);
            }

            $variants = ProductVariant::whereIn('id_variant', $items->pluck('id_variant'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id_variant');

            $totalSelisih = 0;
            $selisihDiterapkan = 0;

            foreach ($items as $item) {
                /** @var ProductVariant $variant */
                $variant = $variants[$item->id_variant];

                $stokSebelum = (int) $variant->stok;
                $selisih = (int) $item->stok_fisik - $stokSebelum;

                // Segarkan nilai sistem pada item agar laporan akhir
                // mencerminkan stok yang benar-benar diberlakukan.
                $item->update([
                    'stok_sistem' => $stokSebelum,
                    'selisih' => $selisih,
                ]);

                $totalSelisih += $selisih;

                if ($selisih === 0) {
                    continue;
                }

                $stokSesudah = $stokSebelum + $selisih;

                $variant->update(['stok' => $stokSesudah]);

                InventoryMovement::create([
                    'id_inventory_movement' => (string) Str::uuid(),
                    'id_variant' => $variant->id_variant,
                    'id_user' => $user->id_user,
                    'jenis_pergerakan' => $selisih > 0
                        ? 'penyesuaian_masuk'
                        : 'penyesuaian_keluar',
                    'jumlah' => abs($selisih),
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'referensi' => $opname->kode_opname,
                    'catatan' => 'Selisih hasil stock opname '.$opname->kode_opname,
                    'waktu_pergerakan' => now(),
                ]);

                $selisihDiterapkan++;
            }

            $opname->update([
                'status_opname' => 'selesai',
                'total_item' => $items->count(),
                'total_selisih' => $totalSelisih,
            ]);

            return [
                'opname' => $opname->fresh(),
                'total_selisih' => $totalSelisih,
                'selisih_diterapkan' => $selisihDiterapkan,
            ];
        });

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan stock opname tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock opname berhasil difinalisasi; selisih telah diterapkan ke stok.',
            'data' => [
                'opname' => $result['opname']->load(['user', 'items.variant']),
                'total_selisih' => $result['total_selisih'],
                'selisih_diterapkan' => $result['selisih_diterapkan'],
            ],
        ]);
    }

    /**
     * Menghitung ulang total_item dan total_selisih header dari seluruh item
     * yang saat ini tersimpan pada opname.
     */
    private function recalculateTotals(StockOpname $opname): void
    {
        $items = StockOpnameItem::where('id_stock_opname', $opname->id_stock_opname)->get();

        $opname->update([
            'total_item' => $items->count(),
            'total_selisih' => (int) $items->sum('selisih'),
        ]);
    }

    /**
     * Menghasilkan kode opname unik berformat OPN-YYYYMMDD-NNNN.
     *
     * Pola retry diambil dari OrderService::generateNomorPesanan karena
     * kolomnya unique dan dua permintaan bisa bersamaan pada detik yang sama.
     */
    private function generateKodeOpname(): string
    {
        $prefix = 'OPN-'.now()->format('Ymd').'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $last = StockOpname::where('kode_opname', 'like', $prefix.'%')
                    ->orderByDesc('kode_opname')
                    ->value('kode_opname');

                $sequence = ($last ? ((int) substr($last, strlen($prefix))) : 0) + 1;

                return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            } catch (QueryException) {
                // Kode bentrok dengan permintaan bersamaan; coba urutan
                // berikutnya.
            }
        }

        // Pemecah keadaan darurat: bila lima percobaan bentrok juga,
        // gunakan bagian acak agar tetap unik.
        return $prefix.Str::upper(Str::random(4));
    }
}
