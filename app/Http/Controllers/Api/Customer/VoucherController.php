<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Voucher;
use App\Services\ImageUploadService;
use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VoucherController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $images,
        private readonly VoucherService $voucherService
    ) {}

    public function available(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $now = Carbon::now();
        $user = $request->user();
        $eligibleLines = $this->eligibleCartLines($user->id_user);
        $subtotal = isset($validated['subtotal'])
            ? (float) $validated['subtotal']
            : null;

        $vouchers = Voucher::query()
            ->with('productPeruntukan')
            ->where('status_voucher', 'aktif')
            ->where('tanggal_mulai', '<=', $now)
            ->where('tanggal_selesai', '>=', $now)
            ->orderBy('minimal_pembelian')
            ->get()
            ->filter(function (Voucher $voucher) use ($user, $subtotal, $eligibleLines) {
                if (! $this->voucherService->passesQuota($voucher, $user->id_user)) {
                    return false;
                }

                if ($voucher->id_product_peruntukan === null) {
                    return $subtotal === null
                        || $subtotal >= (float) $voucher->minimal_pembelian;
                }

                $product = $voucher->productPeruntukan;

                if (! $product || $product->status_produk !== 'aktif') {
                    return false;
                }

                $subtotalPeruntukan = $this->subtotalForProduct(
                    $eligibleLines,
                    $voucher->id_product_peruntukan
                );

                return $subtotalPeruntukan > 0
                    && $subtotalPeruntukan >= (float) $voucher->minimal_pembelian;
            })
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

        $user = $request->user();
        $subtotal = (float) $validated['subtotal'];

        try {
            $resolved = $this->voucherService->resolve(
                $validated['kode_voucher'],
                $subtotal,
                $user->id_user,
                $this->eligibleCartLines($user->id_user)
            );
        } catch (ValidationException $e) {
            return $this->invalidVoucher(
                $e->errors()['kode_voucher'][0] ?? 'Voucher tidak valid.'
            );
        }

        $voucher = $resolved['voucher'];

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
                'id_product_peruntukan' => $resolved['id_product_peruntukan'],
                'nama_produk_peruntukan' => $resolved['nama_produk_peruntukan'],
                'subtotal_peruntukan' => $resolved['subtotal_peruntukan'],
                'jumlah_diskon' => $resolved['diskon'],
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

    /**
     * @return array<int, array{id_product: string, harga: float, jumlah: int}>
     */
    private function eligibleCartLines(string $idUser): array
    {
        $cart = Cart::where('id_user', $idUser)
            ->where('status_cart', 'aktif')
            ->with(['items.variant.product'])
            ->first();

        if (! $cart) {
            return [];
        }

        $lines = [];

        foreach ($cart->items as $item) {
            $variant = $item->variant;

            if (! $variant
                || ! $variant->product
                || $variant->status_varian !== 'aktif'
                || $variant->product->status_produk !== 'aktif') {
                continue;
            }

            $lines[] = [
                'id_product' => $variant->product->id_product,
                'harga' => (float) $variant->harga,
                'jumlah' => (int) $item->jumlah,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<int, array{id_product: string, harga: float, jumlah: int}>  $lines
     */
    private function subtotalForProduct(array $lines, string $idProduct): float
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            if ($line['id_product'] === $idProduct) {
                $subtotal += $line['harga'] * $line['jumlah'];
            }
        }

        return round($subtotal, 2);
    }

    private function invalidVoucher(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 422);
    }
}
