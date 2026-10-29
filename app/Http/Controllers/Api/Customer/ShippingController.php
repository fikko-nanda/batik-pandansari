<?php

namespace App\Http\Controllers\Api\Customer;

use App\Exceptions\ShippingException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\UserAddress;
use App\Services\OsmGeocodingService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShippingController extends Controller
{
    public function searchAddresses(Request $request, OsmGeocodingService $geocoding): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        try {
            $results = $geocoding->search($validated['q']);
        } catch (ShippingException $e) {
            return $this->shippingError($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian alamat berhasil diambil.',
            'data' => $results,
        ]);
    }

    public function quote(Request $request, ShippingService $shipping): JsonResponse
    {
        if (! $request->filled('id_user_address') && $request->filled('address_id')) {
            $request->merge(['id_user_address' => $request->input('address_id')]);
        }

        $validated = $request->validate([
            'id_user_address' => ['required', 'string'],
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
            ->first();

        $items = $cart
            ? CartItem::with('variant.product')
                ->where('id_cart', $cart->id_cart)
                ->get()
            : collect();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang Anda kosong.',
                'errors' => ['cart' => ['Keranjang Anda kosong.']],
            ], 422);
        }

        $beratGram = 0;

        foreach ($items as $item) {
            $variant = $item->variant;
            $jumlah = (int) $item->jumlah;

            if (! $variant
                || $variant->status_varian !== 'aktif'
                || ! $variant->product
                || $variant->product->status_produk !== 'aktif') {
                throw ValidationException::withMessages([
                    'cart' => ['Varian produk tidak tersedia untuk dibeli.'],
                ]);
            }

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

            $beratGram += $beratItem * $jumlah;
        }

        try {
            $quote = $shipping->quoteForAddress($address, $beratGram);
        } catch (ShippingException $e) {
            return $this->shippingError($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ongkir berhasil dihitung.',
            'data' => $quote,
        ]);
    }

    private function shippingError(ShippingException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'code' => $e->errorCode(),
        ], $e->statusCode());
    }
}
