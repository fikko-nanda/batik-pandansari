<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RajaOngkirException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\UserAddress;
use App\Models\VoucherUsage;
use App\Services\OrderService;
use App\Services\RajaOngkirService;
use App\Services\ShippingConfigService;
use App\Services\StockReservationService;
use App\Services\VoucherService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public function store(
        Request $request,
        VoucherService $voucherService,
        OrderService $orderService,
        RajaOngkirService $rajaongkir,
        ShippingConfigService $shippingConfig,
        StockReservationService $reservations
    ): JsonResponse {
        // Terima `id_user_address` (kontrak resmi) atau `address_id` (alias
        // yang dipakai sebagian frontend) agar kompatibel.
        if (! $request->filled('id_user_address') && $request->filled('address_id')) {
            $request->merge(['id_user_address' => $request->input('address_id')]);
        }

        // Mode tarif manual: dipakai saat frontend memilih opsi "Tarif Manual /
        // Fallback Ongkir" (mis. kuota RajaOngkir habis). Bisa ditandai eksplisit
        // lewat `is_manual_shipping`, atau tersirat saat klien mengirim
        // `shipping_cost` tanpa memilih kurir/layanan tertentu.
        $isManualShipping = $request->boolean('is_manual_shipping')
            || ($request->filled('shipping_cost') && ! $request->filled('kurir'));

        $rules = [
            'id_user_address' => ['required', 'string'],
            'kode_voucher' => ['nullable', 'string', 'max:50'],
            'biaya_pengiriman' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_manual_shipping' => ['nullable', 'boolean'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'shipping_courier' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string'],
        ];

        // Kurir & layanan hanya wajib pada mode otomatis (RajaOngkir). Pada mode
        // tarif manual keduanya opsional; ongkir diambil dari `shipping_cost`.
        if (! $isManualShipping) {
            $rules['kurir'] = ['required', 'string', Rule::in($shippingConfig->activeCouriers())];
            $rules['layanan'] = ['required', 'string', 'max:50'];
        } else {
            $rules['kurir'] = ['nullable', 'string', 'max:50'];
            $rules['layanan'] = ['nullable', 'string', 'max:50'];
            $rules['shipping_cost'] = ['required', 'numeric', 'min:0', 'max:9999999999.99'];
        }

        $validated = $request->validate($rules);

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

        // Nilai ongkir manual dari klien yang dipakai sebagai fallback ketika
        // RajaOngkir tidak bisa dihubungi (mis. kuota habis).
        $manualCost = $request->filled('shipping_cost')
            ? round((float) $validated['shipping_cost'], 2)
            : null;

        if ($isManualShipping) {
            // Lewati RajaOngkir sepenuhnya; tidak butuh destination_id
            // (subdistrict) RajaOngkir di alamat.
            $biayaPengiriman = round((float) $validated['shipping_cost'], 2);
        } else {
            // Ongkir dihitung ulang di backend lewat RajaOngkir — nilai
            // biaya_pengiriman dari klien TIDAK dipercaya (hanya kompatibilitas input).
            $destinationId = $address->id_tujuan_rajaongkir;

            if ($destinationId === null || $destinationId === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'ALAMAT_TANPA_TUJUAN_RAJAONGKIR',
                ], 422);
            }

            $variantWeights = ProductVariant::whereIn('id_variant', $cart->items->pluck('id_variant'))
                ->pluck('berat_gram', 'id_variant');

            $weightGram = 0;

            foreach ($cart->items as $cartItem) {
                $weightGram += ((int) ($variantWeights[$cartItem->id_variant] ?? 500)) * (int) $cartItem->jumlah;
            }

            try {
                // Gunakan mode multi-kurir (endpoint yang sama, `courier` tunggal
                // di dalamnya) karena lebih andal daripada pemanggilan per-kurir
                // yang terkadang mengembalikan 404 palsu di endpoint Komerce.
                $services = $rajaongkir->costMany(
                    (string) $destinationId,
                    max(1, $weightGram),
                    [(string) $validated['kurir']],
                );
            } catch (RajaOngkirException $e) {
                // Fallback otomatis: bila klien sudah mengirim tarif manual
                // (mis. saat kuota habis), pakai nilai itu alih-alih menggagalkan
                // transaksi. Kode error lain tetap dikembalikan apa adanya.
                if ($e->getMessage() === 'RAJAONGKIR_QUOTA_EXCEEDED' && $manualCost !== null) {
                    $biayaPengiriman = $manualCost;

                    return $this->finalizeOrder(
                        $validated,
                        $biayaPengiriman,
                        $address,
                        $cart,
                        $user,
                        $voucherService,
                        $orderService,
                        $reservations
                    );
                }

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'pesan' => $e->userMessage(),
                ], $e->statusCode());
            }

            // Hanya pertimbangkan layanan dari kurir yang dipilih.
            $courier = strtolower((string) $validated['kurir']);
            $services = array_values(array_filter(
                $services,
                fn ($service) => strtolower((string) ($service['code'] ?? '')) === $courier
            ));

            $layanan = strtoupper((string) $validated['layanan']);
            $matchedService = null;

            foreach ($services as $service) {
                $serviceCode = strtoupper((string) ($service['service'] ?? ''));
                $serviceName = strtoupper((string) ($service['name'] ?? ''));

                if ($serviceCode === $layanan || $serviceName === $layanan) {
                    $matchedService = $service;
                    break;
                }
            }

            if ($matchedService === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'LAYANAN_TIDAK_TERSEDIA',
                    'pesan' => 'Layanan pengiriman yang dipilih tidak tersedia untuk kurir dan rute ini. Silakan pilih ulang ongkir.',
                ], 422);
            }

            $biayaPengiriman = round((float) ($matchedService['cost'] ?? 0), 2);
        }

        return $this->finalizeOrder(
            $validated,
            $biayaPengiriman,
            $address,
            $cart,
            $user,
            $voucherService,
            $orderService,
            $reservations
        );
    }

    /**
     * Buat pesanan di dalam satu transaksi (dipakai mode RajaOngkir maupun
     * tarif manual) dan kembalikan respons 201.
     *
     * @param  array<string, mixed>  $validated
     */
    private function finalizeOrder(
        array $validated,
        float $biayaPengiriman,
        UserAddress $address,
        Cart $cart,
        $user,
        VoucherService $voucherService,
        OrderService $orderService,
        StockReservationService $reservations
    ): JsonResponse {
        try {
            $order = DB::transaction(function () use (
                $cart,
                $address,
                $user,
                $validated,
                $biayaPengiriman,
                $voucherService,
                $orderService,
                $reservations
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
                    $tersedia = (int) $variant->stok - (int) $variant->stok_dipesan;

                    if ($jumlah > $tersedia) {
                        throw ValidationException::withMessages([
                            'cart' => ["Stok tidak mencukupi untuk {$variant->sku}. Stok tersedia: {$tersedia}."],
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
                    $resolved = $voucherService->resolve(
                        $validated['kode_voucher'],
                        $subtotal,
                        $user->id_user
                    );

                    $voucher = $resolved['voucher'];
                    $diskon = $resolved['diskon'];
                }

                // total = subtotal - diskon + ongkir (baik hasil RajaOngkir
                // maupun tarif manual yang dipilih frontend).
                $total = round(max(0, $subtotal + $biayaPengiriman - $diskon), 2);

                $order = $this->createOrder($orderService, [
                    'id_user' => $user->id_user,
                    'status_pesanan' => 'menunggu_pembayaran',
                    'kedaluwarsa_pada' => $orderService->expiryFrom(),
                    'subtotal' => $subtotal,
                    'biaya_pengiriman' => $biayaPengiriman,
                    'diskon' => $diskon,
                    'total_pembayaran' => $total,
                    'nama_penerima' => $address->nama_penerima,
                    'nomor_telepon_penerima' => $address->nomor_telepon,
                    'alamat_pengiriman' => $this->formatAlamat($address),
                    'kode_tujuan_pengiriman' => Str::limit((string) $address->nama_kota, 100, ''),
                    'catatan' => $this->buildCatatan($validated),
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

    /**
     * Susun catatan pesanan. Saat memakai tarif manual, label kurir manual
     * (mis. "JNE Reguler (Manual)") disisipkan agar jejak pengiriman tetap
     * terekam meski nilai ongkir tidak berasal dari RajaOngkir.
     *
     * @param  array<string, mixed>  $validated
     */
    private function buildCatatan(array $validated): ?string
    {
        $catatan = $validated['catatan'] ?? null;
        $manualCourier = $validated['shipping_courier'] ?? null;

        if ($manualCourier === null || $manualCourier === '') {
            return $catatan;
        }

        $penanda = '[Ongkir Manual: '.$manualCourier.']';

        return $catatan ? $catatan.' '.$penanda : $penanda;
    }
}
