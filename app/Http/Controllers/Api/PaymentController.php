<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\OrderService;
use App\Services\SyncPaymentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        string $id,
        MidtransService $midtrans,
        OrderService $orderService
    ): JsonResponse {
        $order = Order::where('id_order', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();

        if (! $order) {
            return $this->orderNotFound();
        }

        // Validasi kedaluwarsa: bila batas waktu pembayaran sudah lewat,
        // batalkan pesanan + lepas reservasi stok sebelum menolak.
        if ($orderService->isExpired($order)) {
            $orderService->expireIfUnpaid($order);

            return response()->json([
                'success' => false,
                'message' => 'Pesanan telah kedaluwarsa karena melewati batas waktu pembayaran.',
            ], 422);
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
                // Batas waktu pembayaran mengikuti kedaluwarsa pesanan (10 menit).
                'expires_at' => $order->kedaluwarsa_pada ?? now()->addMinutes(OrderService::PAYMENT_WINDOW_MINUTES),
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

    public function show(Request $request, string $id, SyncPaymentStatusService $sync): JsonResponse
    {
        $order = Order::where('id_order', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();

        if (! $order) {
            return $this->orderNotFound();
        }

        // Fallback: bila webhook belum sampai, tanyakan status terkini ke
        // Midtrans untuk pesanan yang masih menunggu pembayaran.
        $sync->syncOrder($order);
        $order->refresh();

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

    /**
     * Ringkasan pembayaran untuk admin (dapat difilter rentang tanggal/status/provider).
     */
    public function adminSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', Payment::STATUSES)],
            'provider' => ['nullable', 'string', 'max:50'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $query = Payment::query()
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_pembayaran', $validated['status'])
            )
            ->when(
                ! empty($validated['provider']),
                fn ($query) => $query->where('provider', $validated['provider'])
            )
            ->when(
                ! empty($validated['dari']),
                fn ($query) => $query->whereDate('dibuat_pada', '>=', $validated['dari'])
            )
            ->when(
                ! empty($validated['sampai']),
                fn ($query) => $query->whereDate('dibuat_pada', '<=', $validated['sampai'])
            );

        $totalPembayaran = (clone $query)->count();
        $totalNilai = (float) (clone $query)->sum('jumlah_pembayaran');
        $nilaiDibayar = (float) (clone $query)->where('status_pembayaran', 'dibayar')->sum('jumlah_pembayaran');

        $perStatus = [];

        foreach (Payment::STATUSES as $status) {
            $perStatus[$status] = (clone $query)->where('status_pembayaran', $status)->count();
        }

        $perProvider = (clone $query)
            ->selectRaw('provider, COUNT(*) as jumlah')
            ->groupBy('provider')
            ->pluck('jumlah', 'provider')
            ->map(fn ($jumlah) => (int) $jumlah)
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan pembayaran berhasil diambil.',
            'data' => [
                'total_pembayaran' => $totalPembayaran,
                'total_nilai' => $totalNilai,
                'nilai_dibayar' => $nilaiDibayar,
                'per_status' => $perStatus,
                'per_provider' => $perProvider,
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
