<?php

namespace App\Http\Controllers\Api\Customer;

use App\Exceptions\ShippingException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\VoucherUsage;
use App\Services\OrderService;
use App\Services\ShippingService;
use App\Services\StockReservationService;
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
        OrderService $orderService,
        StockReservationService $reservations,
        ShippingService $shipping
    ): JsonResponse {
        // Terima `id_user_address` (kontrak resmi) atau `address_id` (alias
        // yang dipakai sebagian frontend) agar kompatibel.
        if (! $request->filled('id_user_address') && $request->filled('address_id')) {
            $request->merge(['id_user_address' => $request->input('address_id')]);
        }

        $validated = $request->validate([
            'id_user_address' => ['required', 'string'],
            'kode_voucher' => ['nullable', 'string', 'max:50'],
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

        return $this->finalizeOrder(
            $validated,
            $address,
            $user,
            $voucherService,
            $orderService,
            $reservations,
            $shipping
        );
    }

    /**
     * Buat pesanan di dalam satu transaksi dan kembalikan respons 201.
     *
     * @param  array<string, mixed>  $validated
     */
    private function finalizeOrder(
        array $validated,
        UserAddress $address,
        $user,
        VoucherService $voucherService,
        OrderService $orderService,
        StockReservationService $reservations,
        ShippingService $shipping
    ): JsonResponse {
        try {
            $hasCart = Cart::where('id_user', $user->id_user)
                ->where('status_cart', 'aktif')
                ->whereHas('items')
                ->exists();

            if (! $hasCart) {
                throw ValidationException::withMessages([
                    'cart' => ['Keranjang Anda kosong.'],
                ]);
            }

            // Panggilan HTTP ke OSRM dilakukan sebelum transaksi DB dimulai agar
            // koneksi lambat tidak menahan lock keranjang/varian.
            $route = $shipping->routeForAddress($address);

            $order = DB::transaction(function () use (
                $address,
                $user,
                $validated,
                $voucherService,
                $orderService,
                $reservations,
                $shipping,
                $route
            ) {
                $cart = Cart::where('id_user', $user->id_user)
                    ->where('status_cart', 'aktif')
                    ->lockForUpdate()
                    ->first();

                if (! $cart) {
                    throw ValidationException::withMessages([
                        'cart' => ['Keranjang Anda kosong.'],
                    ]);
                }

                $cartItems = CartItem::where('id_cart', $cart->id_cart)
                    ->orderBy('id_variant')
                    ->lockForUpdate()
                    ->get();

                if ($cartItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => ['Keranjang Anda kosong.'],
                    ]);
                }

                $subtotal = 0.0;
                $beratGram = 0;
                $lines = [];

                foreach ($cartItems as $cartItem) {
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
                    $tersedia = (int) $variant->stok - (int) $variant->stok_dipesan;

                    if ($jumlah > $tersedia) {
                        throw ValidationException::withMessages([
                            'cart' => ["Stok tidak mencukupi untuk {$variant->sku}. Stok tersedia: {$tersedia}."],
                        ]);
                    }

                    $beratItem = (int) $variant->berat_gram;

                    if ($beratItem <= 0) {
                        throw ValidationException::withMessages([
                            'cart' => ["Berat varian {$variant->sku} belum diatur admin."],
                        ]);
                    }

                    $harga = (float) $variant->harga;
                    $lineSubtotal = round($harga * $jumlah, 2);

                    $subtotal += $lineSubtotal;
                    $beratGram += $beratItem * $jumlah;

                    $lines[] = [
                        'variant' => $variant,
                        'id_product' => $variant->product->id_product,
                        'jumlah' => $jumlah,
                        'harga' => $harga,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                $shippingQuote = $shipping->quoteForDistance($route, $beratGram);

                // Reservasi stok: tambah stok_dipesan (stok fisik belum dikurangi).
                // Stok yang direservasi tidak bisa dibeli pesanan lain sampai
                // pembayaran berhasil (dikurangi permanen) atau kedaluwarsa/dibatal.
                $reservations->reserve(array_map(
                    fn ($line) => [
                        'id_variant' => $line['variant']->id_variant,
                        'jumlah' => $line['jumlah'],
                    ],
                    $lines
                ));

                $subtotal = round($subtotal, 2);

                $voucher = null;
                $diskon = 0.0;

                if (! empty($validated['kode_voucher'])) {
                    $eligibleLines = array_map(
                        fn ($line) => [
                            'id_product' => $line['id_product'],
                            'harga' => $line['harga'],
                            'jumlah' => $line['jumlah'],
                        ],
                        $lines
                    );

                    $resolved = $voucherService->resolve(
                        $validated['kode_voucher'],
                        $subtotal,
                        $user->id_user,
                        $eligibleLines
                    );

                    $voucher = $resolved['voucher'];
                    $diskon = $resolved['diskon'];
                }

                $dasarPph = round(max(0, $subtotal - $diskon), 2);
                $pph = $orderService->pphFrom($dasarPph);
                $biayaPengiriman = $shippingQuote['biaya_pengiriman'];
                $total = round($dasarPph + $pph + $biayaPengiriman, 2);

                $order = $this->createOrder($orderService, [
                    'id_user' => $user->id_user,
                    'status_pesanan' => 'menunggu_pembayaran',
                    'kedaluwarsa_pada' => $orderService->expiryFrom(),
                    'subtotal' => $subtotal,
                    'biaya_pengiriman' => $biayaPengiriman,
                    'jarak_km' => $shippingQuote['jarak_km'],
                    'zona_pengiriman' => $shippingQuote['zona']['nama_zona'],
                    'berat_pengiriman_gram' => $shippingQuote['berat_gram'],
                    'diskon' => $diskon,
                    'pph' => $pph,
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
            $errors = $e->errors();

            if (($errors['cart'][0] ?? null) === 'Keranjang Anda kosong.') {
                return response()->json([
                    'success' => false,
                    'message' => 'Keranjang Anda kosong.',
                    'errors' => $errors,
                ], 422);
            }

            throw $e;
        } catch (ShippingException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->errorCode(),
            ], $e->statusCode());
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
