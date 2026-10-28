<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\ImageUploadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $images
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $vouchers = Voucher::query()
            ->with('productPeruntukan')
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('kode_voucher', 'like', "%{$term}%")
                            ->orWhere('nama_voucher', 'like', "%{$term}%");
                    });
                }
            )
            ->orderByDesc('dibuat_pada')
            ->paginate($perPage);

        $items = collect($vouchers->items())
            ->map(fn (Voucher $voucher) => $this->withUrl($voucher))
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher berhasil diambil.',
            'data' => $items,
            'meta' => [
                'current_page' => $vouchers->currentPage(),
                'per_page' => $vouchers->perPage(),
                'total' => $vouchers->total(),
                'last_page' => $vouchers->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_voucher' => ['required', 'string', 'max:50', 'unique:vouchers,kode_voucher'],
            'nama_voucher' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tipe_diskon' => ['required', 'string', 'in:persentase,nominal'],
            'nilai_diskon' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'minimal_pembelian' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'maksimal_diskon' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'kuota_total' => ['nullable', 'integer', 'min:0'],
            'kuota_per_pengguna' => ['nullable', 'integer', 'min:0'],
            'id_product_peruntukan' => ['nullable', 'string', 'uuid', 'exists:products,id_product'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_voucher' => ['nullable', 'string', 'in:aktif,nonaktif'],
        ], [
            'id_product_peruntukan.exists' => 'id_product_peruntukan tidak ditemukan.',
        ]);

        $status = $validated['status_voucher'] ?? 'aktif';
        $idProductPeruntukan = $validated['id_product_peruntukan'] ?? null;

        if ($status === 'aktif'
            && ($error = $this->invalidActiveProduct($idProductPeruntukan)) !== null) {
            return $error;
        }

        $gambar = $request->hasFile('gambar')
            ? $this->images->store($request->file('gambar'), 'vouchers')
            : null;

        $voucher = Voucher::create([
            'id_voucher' => (string) Str::uuid(),
            'kode_voucher' => $validated['kode_voucher'],
            'nama_voucher' => $validated['nama_voucher'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'gambar' => $gambar,
            'tipe_diskon' => $validated['tipe_diskon'],
            'nilai_diskon' => $validated['nilai_diskon'],
            'minimal_pembelian' => $validated['minimal_pembelian'] ?? 0,
            'maksimal_diskon' => $validated['maksimal_diskon'] ?? null,
            'kuota_total' => $validated['kuota_total'] ?? null,
            'kuota_per_pengguna' => $validated['kuota_per_pengguna'] ?? 1,
            'id_product_peruntukan' => $idProductPeruntukan,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'status_voucher' => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dibuat.',
            'data' => $this->withUrl($voucher),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (! $voucher) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail voucher berhasil diambil.',
            'data' => $this->withUrl($voucher),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (! $voucher) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'kode_voucher' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'kode_voucher')->ignore($voucher->id_voucher, 'id_voucher'),
            ],
            'nama_voucher' => ['sometimes', 'required', 'string', 'max:100'],
            'deskripsi' => ['sometimes', 'nullable', 'string'],
            'gambar' => ['sometimes', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'tipe_diskon' => ['sometimes', 'required', 'string', 'in:persentase,nominal'],
            'nilai_diskon' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999.99'],
            'minimal_pembelian' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'maksimal_diskon' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'kuota_total' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'kuota_per_pengguna' => ['sometimes', 'required', 'integer', 'min:0'],
            'id_product_peruntukan' => ['sometimes', 'nullable', 'string', 'uuid', 'exists:products,id_product'],
            'tanggal_mulai' => ['sometimes', 'required', 'date'],
            'tanggal_selesai' => ['sometimes', 'required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_voucher' => ['sometimes', 'required', 'string', 'in:aktif,nonaktif'],
        ], [
            'id_product_peruntukan.exists' => 'id_product_peruntukan tidak ditemukan.',
        ]);

        $status = $validated['status_voucher'] ?? $voucher->status_voucher;
        $idProductPeruntukan = array_key_exists('id_product_peruntukan', $validated)
            ? $validated['id_product_peruntukan']
            : $voucher->id_product_peruntukan;
        $mengaktifkan = array_key_exists('status_voucher', $validated)
            && $validated['status_voucher'] === 'aktif';
        $mengubahProduk = array_key_exists('id_product_peruntukan', $validated);

        if ($status === 'aktif'
            && ($mengaktifkan || $mengubahProduk)
            && ($error = $this->invalidActiveProduct($idProductPeruntukan)) !== null) {
            return $error;
        }

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $this->images->replace(
                $request->file('gambar'),
                'vouchers',
                $voucher->gambar
            );
        }

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diperbarui.',
            'data' => $this->withUrl($voucher),
        ]);
    }

    /**
     * Hapus gambar voucher (tanpa menghapus vouchernya).
     */
    public function destroyImage(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (! $voucher) {
            return $this->notFound();
        }

        $this->images->delete($voucher->gambar);
        $voucher->update(['gambar' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Gambar voucher berhasil dihapus.',
            'data' => $this->withUrl($voucher->fresh()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (! $voucher) {
            return $this->notFound();
        }

        $voucher->update(['status_voucher' => 'nonaktif']);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dinonaktifkan.',
            'data' => $this->withUrl($voucher),
        ]);
    }

    /**
     * Publikasikan voucher (status_voucher: nonaktif -> aktif).
     */
    public function publish(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (! $voucher) {
            return $this->notFound();
        }

        if (Carbon::parse($voucher->tanggal_selesai)->lt(Carbon::now())) {
            return $this->invalidVoucher('Voucher sudah kedaluwarsa dan tidak dapat dipublikasikan.');
        }

        if (($error = $this->invalidActiveProduct($voucher->id_product_peruntukan)) !== null) {
            return $error;
        }

        if ($voucher->status_voucher !== 'aktif') {
            $voucher->update(['status_voucher' => 'aktif']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dipublikasikan.',
            'data' => $this->withUrl($voucher),
        ]);
    }

    /**
     * Tarik voucher dari publikasi (status_voucher: aktif -> nonaktif).
     */
    public function unpublish(string $id): JsonResponse
    {
        $voucher = Voucher::find($id);

        if (! $voucher) {
            return $this->notFound();
        }

        if ($voucher->status_voucher !== 'nonaktif') {
            $voucher->update(['status_voucher' => 'nonaktif']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil ditarik dari publikasi.',
            'data' => $this->withUrl($voucher),
        ]);
    }

    /**
     * Ringkasan voucher untuk admin.
     */
    public function summary(): JsonResponse
    {
        $now = Carbon::now();

        $statusesUsed = ['digunakan'];
        $total = Voucher::count();
        $aktif = Voucher::where('status_voucher', 'aktif')->count();

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan voucher berhasil diambil.',
            'data' => [
                'total_voucher' => $total,
                'aktif' => $aktif,
                'nonaktif' => Voucher::where('status_voucher', 'nonaktif')->count(),
                'berjalan' => Voucher::where('status_voucher', 'aktif')
                    ->where('tanggal_mulai', '<=', $now)
                    ->where('tanggal_selesai', '>=', $now)
                    ->count(),
                'terjadwal' => Voucher::where('status_voucher', 'aktif')
                    ->where('tanggal_mulai', '>', $now)
                    ->count(),
                'kadaluarsa' => Voucher::where('status_voucher', 'aktif')
                    ->where('tanggal_selesai', '<', $now)
                    ->count(),
                'total_penggunaan' => VoucherUsage::whereIn('status_penggunaan', $statusesUsed)->count(),
                'total_diskon_diberikan' => (float) VoucherUsage::whereIn('status_penggunaan', $statusesUsed)
                    ->sum('jumlah_diskon'),
            ],
        ]);
    }

    private function withUrl(Voucher $voucher): Voucher
    {
        $voucher->loadMissing('productPeruntukan');
        $product = $voucher->productPeruntukan;

        $voucher->setAttribute('url_gambar', $this->images->url($voucher->gambar));
        $voucher->setAttribute('nama_produk_peruntukan', $product?->nama_produk);
        $voucher->setAttribute('status_produk_peruntukan', $product?->status_produk);
        $voucher->unsetRelation('productPeruntukan');

        return $voucher;
    }

    private function invalidActiveProduct(?string $idProduct): ?JsonResponse
    {
        if ($idProduct === null) {
            return null;
        }

        $product = Product::find($idProduct);

        if ($product && $product->status_produk === 'aktif') {
            return null;
        }

        return $this->invalidVoucher(
            $product !== null
                ? "Voucher hanya berlaku untuk produk {$product->nama_produk}."
                : 'Produk peruntukan voucher tidak ditemukan.'
        );
    }

    private function invalidVoucher(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 422);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Voucher tidak ditemukan.',
        ], 404);
    }
}
