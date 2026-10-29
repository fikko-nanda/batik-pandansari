<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cart = $this->currentCart($request->user()->id_user);

        return response()->json([
            'success' => true,
            'message' => 'Keranjang berhasil diambil.',
            'data' => $cart->load('items.variant.product'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_variant' => ['required', 'string', 'exists:product_variants,id_variant'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::with('product')->find($validated['id_variant']);

        if (! $this->isPurchasable($variant)) {
            return response()->json([
                'success' => false,
                'message' => 'Varian produk tidak tersedia untuk dibeli.',
            ], 422);
        }

        $cart = $this->currentCart($request->user()->id_user);

        $item = CartItem::where('id_cart', $cart->id_cart)
            ->where('id_variant', $variant->id_variant)
            ->first();

        $newJumlah = $validated['jumlah'] + ($item->jumlah ?? 0);
        $tersedia = $this->availableStock($variant);

        if ($newJumlah > $tersedia) {
            return $this->insufficientStock($tersedia);
        }

        if ($item) {
            $item->update(['jumlah' => $newJumlah]);
            $status = 200;
            $message = 'Jumlah item keranjang berhasil diperbarui.';
        } else {
            $item = CartItem::create([
                'id_cart_item' => (string) Str::uuid(),
                'id_cart' => $cart->id_cart,
                'id_variant' => $variant->id_variant,
                'jumlah' => $validated['jumlah'],
            ]);
            $status = 201;
            $message = 'Item berhasil ditambahkan ke keranjang.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $item->load('variant.product'),
        ], $status);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1'],
        ]);

        $item = $this->findOwnedItem($request, $id);

        if (! $item) {
            return $this->itemNotFound();
        }

        $variant = $item->variant;

        if (! $this->isPurchasable($variant)) {
            return response()->json([
                'success' => false,
                'message' => 'Varian produk tidak tersedia untuk dibeli.',
            ], 422);
        }

        $tersedia = $this->availableStock($variant);

        if ($validated['jumlah'] > $tersedia) {
            return $this->insufficientStock($tersedia);
        }

        $item->update(['jumlah' => $validated['jumlah']]);

        return response()->json([
            'success' => true,
            'message' => 'Jumlah item keranjang berhasil diperbarui.',
            'data' => $item->load('variant.product'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $item = $this->findOwnedItem($request, $id);

        if (! $item) {
            return $this->itemNotFound();
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item keranjang berhasil dihapus.',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->currentCart($request->user()->id_user);

        CartItem::where('id_cart', $cart->id_cart)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Keranjang berhasil dikosongkan.',
        ]);
    }

    private function currentCart(string $idUser): Cart
    {
        return Cart::firstOrCreate(
            ['id_user' => $idUser, 'status_cart' => 'aktif'],
            ['id_cart' => (string) Str::uuid()],
        );
    }

    private function findOwnedItem(Request $request, string $id): ?CartItem
    {
        return CartItem::where('id_cart_item', $id)
            ->whereHas('cart', fn ($query) => $query->where('id_user', $request->user()->id_user))
            ->first();
    }

    private function isPurchasable(?ProductVariant $variant): bool
    {
        return $variant
            && $variant->status_varian === 'aktif'
            && $variant->product
            && $variant->product->status_produk === 'aktif';
    }

    private function availableStock(ProductVariant $variant): int
    {
        return max(0, (int) $variant->stok - (int) $variant->stok_dipesan);
    }

    private function insufficientStock(int $stok): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "Stok tidak mencukupi. Stok tersedia: {$stok}.",
        ], 422);
    }

    private function itemNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Item keranjang tidak ditemukan.',
        ], 404);
    }
}
