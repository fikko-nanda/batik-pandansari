<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\VoucherUsage;
use App\Services\OrderService;
use App\Services\VoucherService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public function store(
        Request $request,
        VoucherService $voucherService,
        OrderService $orderService
    ): JsonResponse {
        $validated = $request->validate([
            'id_user_address' => ['required', 'string'],
            'kode_voucher' => ['nullable', 'string', 'max:50'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'catatan' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $address = UserAddress::where('id_user_address', $validated['id_user_address'])
            ->where('id_user', $user->id_user)
            ->first();

        if (! $address) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat pengiriman tidak ditemukan.',
            ], 404);
        }

        $cart = Cart::where('id_user', $user->id_user)
            ->where('status_cart', 'aktif')
            ->with('items')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang Anda kosong.',
            ], 422);
        }

        $biayaPengiriman = round((float) ($validated['biaya_pengiriman'] ?? 0), 2);

        try {
            $order = DB::transaction(function () use (
                $cart,
                $address,
                $user,
                $validated,
                $biayaPengiriman,
                $voucherService,
                $orderService
            ) {
                $subtotal = 0.0;
                $lines = [];

                foreach ($cart->items as $cartItem) {
                    $variant = ProductVariant::whereKey($cartItem->id_variant)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant
                        || $variant->status_varian !== 'aktif'
                        || ! $variant->product
                        || $variant->product->status_produk !== 'aktif') {
                        throw ValidationException::withMessages([
                            'cart' => ['Varian produk tidak tersedia untuk dibeli.'],
                        ]);
                    }

                    $jumlah = (int) $cartItem->jumlah;

                    if ($jumlah > (int) $variant->stok) {
                        throw ValidationException::withMessages([
                            'cart' => ["Stok tidak mencukupi untuk {$variant->sku}. Stok tersedia: {$variant->stok}."],
                        ]);
                    }

                    $harga = (float) $variant->harga;
                    $lineSubtotal = round($harga * $jumlah, 2);

                    $subtotal += $lineSubtotal;

                    $lines[] = [
                        'variant' => $variant,
                        'jumlah' => $jumlah,
                        'harga' => $harga,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                $subtotal = round($subtotal, 2);

                $voucher = null;
                $diskon = 0.0;

                if (! empty($validated['kode_voucher'])) {
                    $resolved = $voucherService->resolve(
                        $validated['kode_voucher'],
                        $subtotal,
                        $user->id_user
                    );

                    $voucher = $resolved['voucher'];
                    $diskon = $resolved['diskon'];
                }

                $total = round(max(0, $subtotal + $biayaPengiriman - $diskon), 2);

                $order = $this->createOrder($orderService, [
                    'id_user' => $user->id_user,
                    'status_pesanan' => 'menunggu_pembayaran',
                    'subtotal' => $subtotal,
                    'biaya_pengiriman' => $biayaPengiriman,
                    'diskon' => $diskon,
                    'total_pembayaran' => $total,
                    'nama_penerima' => $address->nama_penerima,
                    'nomor_telepon_penerima' => $address->nomor_telepon,
                    'alamat_pengiriman' => $this->formatAlamat($address),
                    'kode_tujuan_pengiriman' => Str::limit((string) $address->nama_kota, 100, ''),
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                foreach ($lines as $line) {
                    /** @var ProductVariant $variant */
                    $variant = $line['variant'];

                    OrderItem::create([
                        'id_order_item' => (string) Str::uuid(),
                        'id_order' => $order->id_order,
                        'id_variant' => $variant->id_variant,
                        'nama_produk' => $variant->product->nama_produk,
                        'sku' => $variant->sku,
                        'warna' => $variant->warna,
                        'ukuran' => $variant->ukuran,
                        'harga_satuan' => $line['harga'],
                        'jumlah' => $line['jumlah'],
                        'subtotal' => $line['subtotal'],
                    ]);

                    $stokSebelum = (int) $variant->stok;
                    $stokSesudah = $stokSebelum - $line['jumlah'];

                    $variant->update(['stok' => $stokSesudah]);

                    InventoryMovement::create([
                        'id_inventory_movement' => (string) Str::uuid(),
                        'id_variant' => $variant->id_variant,
                        'id_user' => $user->id_user,
                        'jenis_pergerakan' => 'keluar',
                        'jumlah' => $line['jumlah'],
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'referensi' => $order->nomor_pesanan,
                        'catatan' => 'Pengurangan stok checkout pesanan',
                        'waktu_pergerakan' => now(),
                    ]);
                }

                if ($voucher) {
                    VoucherUsage::create([
                        'id_voucher_usage' => (string) Str::uuid(),
                        'id_voucher' => $voucher->id_voucher,
                        'id_user' => $user->id_user,
                        'id_order' => $order->id_order,
                        'jumlah_diskon' => $diskon,
                        'status_penggunaan' => 'menunggu',
                        'digunakan_pada' => null,
                    ]);
                }

                OrderStatusHistory::create([
                    'id_order_status_history' => (string) Str::uuid(),
                    'id_order' => $order->id_order,
                    'status_sebelumnya' => null,
                    'status_baru' => 'menunggu_pembayaran',
                    'catatan' => 'Pesanan dibuat.',
                    'diubah_oleh' => $user->id_user,
                    'waktu_perubahan' => now(),
                ]);

                $cart->update(['status_cart' => 'checkout']);

                return $order->load('items');
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal diproses. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat.',
            'data' => $order,
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createOrder(OrderService $orderService, array $attributes): Order
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return Order::create([
                    'id_order' => (string) Str::uuid(),
                    'nomor_pesanan' => $orderService->generateNomorPesanan($attempt),
                ] + $attributes);
            } catch (QueryException $e) {
                if ($attempt === 4) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Gagal membuat nomor pesanan unik.');
    }

    private function formatAlamat(UserAddress $address): string
    {
        $parts = array_filter([
            $address->alamat_lengkap,
            $address->nama_kecamatan,
            $address->nama_kota,
            $address->nama_provinsi,
            $address->kode_pos,
        ], fn ($value) => $value !== null && $value !== '');

        return implode(', ', $parts);
    }
}
