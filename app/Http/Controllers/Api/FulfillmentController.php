<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFulfillment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Pemenuhan pesanan (fulfillment) oleh admin.
 *
 * GET  /api/admin/order-fulfillments           ?status=&id_order=&page=&per_page=
 * POST /api/admin/orders/{id}/fulfillments     {status_fulfillment, catatan?}
 *
 * Aturan bisnis:
 * - pesanan harus sudah dibayar (tidak menunggu_pembayaran / dibatalkan / selesai);
 * - catatan terakhir per pesanan tidak boleh berstatus sama (harus bergerak maju).
 */
class FulfillmentController extends Controller
{
    private const ORDER_BLOCKED = [
        'menunggu_pembayaran' => 'Pesanan belum dibayar dan belum dapat dipenuhi.',
        'dibatalkan' => 'Pesanan yang dibatalkan tidak dapat dipenuhi.',
        'selesai' => 'Pesanan sudah selesai dan tidak perlu dipenuhi lagi.',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(OrderFulfillment::STATUSES)],
            'id_order' => ['nullable', 'string', 'max:36'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $fulfillments = OrderFulfillment::with(['order:id_order,nomor_pesanan,status_pesanan', 'changedBy:id_user,nama_pengguna'])
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_fulfillment', $validated['status'])
            )
            ->when(
                ! empty($validated['id_order']),
                fn ($query) => $query->where('id_order', $validated['id_order'])
            )
            ->orderByDesc('dibuat_pada')
            ->orderByDesc('urutan')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pemenuhan pesanan berhasil diambil.',
            'data' => $fulfillments->items(),
            'meta' => [
                'current_page' => $fulfillments->currentPage(),
                'per_page' => $fulfillments->perPage(),
                'total' => $fulfillments->total(),
                'last_page' => $fulfillments->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status_fulfillment' => ['required', 'string', Rule::in(OrderFulfillment::STATUSES)],
            'catatan' => ['nullable', 'string'],
        ]);

        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        if (isset(self::ORDER_BLOCKED[$order->status_pesanan])) {
            return response()->json([
                'success' => false,
                'message' => self::ORDER_BLOCKED[$order->status_pesanan],
            ], 422);
        }

        $lastStatus = OrderFulfillment::where('id_order', $order->id_order)
            ->orderByDesc('urutan')
            ->orderByDesc('dibuat_pada')
            ->value('status_fulfillment');

        if ($lastStatus === $validated['status_fulfillment']) {
            return response()->json([
                'success' => false,
                'message' => 'Status pemenuhan sama dengan catatan terakhir.',
            ], 422);
        }

        $fulfillment = DB::transaction(function () use ($order, $validated, $request) {
            $urutan = ((int) OrderFulfillment::where('id_order', $order->id_order)
                ->lockForUpdate()
                ->max('urutan')) + 1;

            return OrderFulfillment::create([
                'id_fulfillment' => (string) Str::uuid(),
                'id_order' => $order->id_order,
                'status_fulfillment' => $validated['status_fulfillment'],
                'catatan' => $validated['catatan'] ?? null,
                'diubah_oleh' => $request->user()->id_user,
                'urutan' => $urutan,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pemenuhan pesanan berhasil dicatat.',
            'data' => $fulfillment->load(['order:id_order,nomor_pesanan,status_pesanan']),
        ], 201);
    }
}
