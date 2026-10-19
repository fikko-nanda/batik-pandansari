<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function store(Request $request, string $id, MidtransService $midtrans): JsonResponse
    {
        $order = Order::where('id_order', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();

        if (! $order) {
            return $this->orderNotFound();
        }

        if ($order->status_pesanan !== 'menunggu_pembayaran') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan ini tidak dapat dibayar.',
            ], 422);
        }

        $payment = Payment::where('id_order', $order->id_order)
            ->where('status_pembayaran', 'menunggu')
            ->orderByDesc('dibuat_pada')
            ->first();

        $reused = $payment
            && $payment->payment_reference
            && (! $payment->expires_at || $payment->expires_at->isFuture());

        if (! $reused) {
            $payment = Payment::create([
                'id_payment' => (string) Str::uuid(),
                'id_order' => $order->id_order,
                'provider' => 'midtrans',
                'provider_transaction_id' => null,
                'metode_pembayaran' => null,
                'jumlah_pembayaran' => $order->total_pembayaran,
                'status_pembayaran' => 'menunggu',
                'payment_url' => null,
                'payment_reference' => null,
                'expires_at' => null,
                'paid_at' => null,
            ]);

            try {
                $result = $midtrans->createSnapTransaction($order);
            } catch (Throwable $e) {
                report($e);

                $payment->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat transaksi pembayaran.',
                ], 502);
            }

            if (empty($result['token'])) {
                $payment->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat transaksi pembayaran.',
                ], 502);
            }

            $payment->update([
                'payment_reference' => $result['token'],
                'payment_url' => $result['redirect_url'],
                'expires_at' => now()->addDay(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi pembayaran berhasil dibuat.',
            'data' => [
                'id_payment' => $payment->id_payment,
                'id_order' => $order->id_order,
                'nomor_pesanan' => $order->nomor_pesanan,
                'provider' => $payment->provider,
                'status_pembayaran' => $payment->status_pembayaran,
                'jumlah_pembayaran' => (float) $payment->jumlah_pembayaran,
                'snap_token' => $payment->payment_reference,
                'redirect_url' => $payment->payment_url,
                'client_key' => $midtrans->clientKey(),
                'expires_at' => $payment->expires_at,
            ],
        ], $reused ? 200 : 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::where('id_order', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();

        if (! $order) {
            return $this->orderNotFound();
        }

        $payment = Payment::with('order')
            ->where('id_order', $order->id_order)
            ->orderByDesc('dibuat_pada')
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran untuk pesanan ini belum dibuat.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pembayaran berhasil diambil.',
            'data' => $payment,
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'provider' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $payments = Payment::with(['order.user'])
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_pembayaran', $validated['status'])
            )
            ->when(
                ! empty($validated['provider']),
                fn ($query) => $query->where('provider', $validated['provider'])
            )
            ->orderByDesc('dibuat_pada')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pembayaran berhasil diambil.',
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    private function orderNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan.',
        ], 404);
    }
}
