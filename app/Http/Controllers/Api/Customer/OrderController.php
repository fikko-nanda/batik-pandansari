<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Services\SyncPaymentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request, OrderService $orderService): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(OrderService::STATUSES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        Order::where('id_user', $request->user()->id_user)
            ->where('status_pesanan', 'menunggu_pembayaran')
            ->whereNotNull('kedaluwarsa_pada')
            ->where('kedaluwarsa_pada', '<=', now())
            ->chunkById(100, function ($expiredOrders) use ($orderService) {
                foreach ($expiredOrders as $expiredOrder) {
                    $orderService->expireIfUnpaid($expiredOrder);
                }
            }, 'id_order');

        $orders = Order::with('items')
            ->withExists('payments')
            ->where('id_user', $request->user()->id_user)
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_pesanan', $validated['status'])
            )
            ->orderByDesc('dibuat_pada')
            ->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pesanan berhasil diambil.',
            'data' => $orders->items(),
            'meta' => $this->meta($orders),
        ]);
    }

    public function show(
        Request $request,
        string $id,
        SyncPaymentStatusService $sync,
        OrderService $orderService
    ): JsonResponse {
        $order = Order::with([
            'items.variant.product',
            'payments',
            'voucherUsages.voucher',
            'statusHistories',
        ])
            ->where('id_order', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();

        if (! $order) {
            return $this->notFound();
        }

        if ($sync->syncOrder($order)) {
            $order->refresh();
        }

        if ($orderService->isExpired($order)) {
            $orderService->expireIfUnpaid($order);
            $order->refresh();
        }

        $order->load([
            'items.variant.product',
            'payments',
            'voucherUsages.voucher',
            'statusHistories',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil.',
            'data' => $order,
        ]);
    }

    public function invoice(Request $request, string $id, InvoiceService $invoices): Response
    {
        $order = Order::with(['items', 'user', 'payments', 'voucherUsages.voucher'])
            ->where('id_order', $id)
            ->where('id_user', $request->user()->id_user)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $binary = $invoices->build($order);
        $filename = $invoices->filename($order);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($binary),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function meta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan.',
        ], 404);
    }
}
