<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    public function __construct(private readonly ImageUploadService $images) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $vouchers = Voucher::query()
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
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_voucher' => ['nullable', 'string', 'in:aktif,nonaktif'],
        ]);

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
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'status_voucher' => $validated['status_voucher'] ?? 'aktif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dibuat.',
            'data' => $this->withUrl($voucher),
        ], 201);
    }

    public function available(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $now = Carbon::now();
        $user = $request->user();

        $vouchers = Voucher::query()
            ->where('status_voucher', 'aktif')
            ->where('tanggal_mulai', '<=', $now)
            ->where('tanggal_selesai', '>=', $now)
            ->when(
                isset($validated['subtotal']),
                fn ($query) => $query->where('minimal_pembelian', '<=', $validated['subtotal'])
            )
            ->orderBy('minimal_pembelian')
            ->get()
            ->filter(fn (Voucher $voucher) => $this->passesQuota($voucher, $user->id_user))
            ->map(fn (Voucher $voucher) => $this->withUrl($voucher))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar voucher tersedia berhasil diambil.',
            'data' => $vouchers,
        ]);
    }

    public function validateVoucher(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_voucher' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $voucher = Voucher::where('kode_voucher', $validated['kode_voucher'])->first();

        if (! $voucher) {
            return $this->invalidVoucher('Kode voucher tidak ditemukan.');
        }

        if ($voucher->status_voucher !== 'aktif') {
            return $this->invalidVoucher('Voucher tidak aktif.');
        }

        $now = Carbon::now();

        if ($voucher->tanggal_mulai > $now) {
            return $this->invalidVoucher('Voucher belum berlaku.');
        }

        if ($voucher->tanggal_selesai < $now) {
            return $this->invalidVoucher('Voucher sudah kedaluwarsa.');
        }

        $subtotal = (float) $validated['subtotal'];

        if ($subtotal < (float) $voucher->minimal_pembelian) {
            $minimal = number_format((float) $voucher->minimal_pembelian, 0, ',', '.');

            return $this->invalidVoucher("Minimal pembelian untuk voucher ini adalah Rp{$minimal}.");
        }

        if (! $this->passesQuota($voucher, $request->user()->id_user)) {
            return $this->invalidVoucher('Kuota voucher sudah habis atau sudah Anda gunakan.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher valid.',
            'data' => [
                'id_voucher' => $voucher->id_voucher,
                'kode_voucher' => $voucher->kode_voucher,
                'nama_voucher' => $voucher->nama_voucher,
                'tipe_diskon' => $voucher->tipe_diskon,
                'nilai_diskon' => (float) $voucher->nilai_diskon,
                'minimal_pembelian' => (float) $voucher->minimal_pembelian,
                'maksimal_diskon' => $voucher->maksimal_diskon !== null ? (float) $voucher->maksimal_diskon : null,
                'subtotal' => $subtotal,
                'jumlah_diskon' => $this->calculateDiscount($voucher, $subtotal),
            ],
        ]);
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
            'tanggal_mulai' => ['sometimes', 'required', 'date'],
            'tanggal_selesai' => ['sometimes', 'required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_voucher' => ['sometimes', 'required', 'string', 'in:aktif,nonaktif'],
        ]);

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
        $voucher->setAttribute('url_gambar', $this->images->url($voucher->gambar));

        return $voucher;
    }

    private function passesQuota(Voucher $voucher, string $idUser): bool
    {
        $statuses = ['menunggu', 'digunakan'];

        if ($voucher->kuota_total !== null) {
            $totalUsed = VoucherUsage::where('id_voucher', $voucher->id_voucher)
                ->whereIn('status_penggunaan', $statuses)
                ->count();

            if ($totalUsed >= $voucher->kuota_total) {
                return false;
            }
        }

        $userUsed = VoucherUsage::where('id_voucher', $voucher->id_voucher)
            ->where('id_user', $idUser)
            ->whereIn('status_penggunaan', $statuses)
            ->count();

        return $userUsed < $voucher->kuota_per_pengguna;
    }

    private function calculateDiscount(Voucher $voucher, float $subtotal): float
    {
        if ($voucher->tipe_diskon === 'persentase') {
            $diskon = $subtotal * ((float) $voucher->nilai_diskon / 100);

            if ($voucher->maksimal_diskon !== null) {
                $diskon = min($diskon, (float) $voucher->maksimal_diskon);
            }

            return round(min($diskon, $subtotal), 2);
        }

        return round(min((float) $voucher->nilai_diskon, $subtotal), 2);
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
