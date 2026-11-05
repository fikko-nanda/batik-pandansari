<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function adminIndex(Request $request, OrderService $orderService): JsonResponse
    {
        $orderService->expirePendingOrders();

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(OrderService::STATUSES)],
            'search' => ['nullable', 'string', 'max:150'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $orders = $this->adminOrdersQuery($validated)
            ->with(['items', 'user'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pesanan berhasil diambil.',
            'data' => $orders->items(),
            'meta' => $this->meta($orders),
        ]);
    }

    public function adminExport(Request $request, OrderService $orderService): StreamedResponse
    {
        $orderService->expirePendingOrders();

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(OrderService::STATUSES)],
            'search' => ['nullable', 'string', 'max:150'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['dari'] = $validated['start_date'] ?? null;
        $validated['sampai'] = $validated['end_date'] ?? null;

        $filename = 'pesanan-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($validated) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'nomor_pesanan',
                'dibuat_pada',
                'nama_penerima',
                'kode_tujuan_pengiriman',
                'total_pembayaran',
                'biaya_pengiriman',
                'status_pesanan',
            ], ',', '"', '');

            $this->adminOrdersQuery($validated)
                ->chunk(200, function ($orders) use ($output) {
                    foreach ($orders as $order) {
                        fputcsv($output, [
                            $order->nomor_pesanan,
                            $order->dibuat_pada?->format('Y-m-d H:i:s'),
                            $order->nama_penerima,
                            $order->kode_tujuan_pengiriman,
                            $order->total_pembayaran,
                            $order->biaya_pengiriman,
                            $order->status_pesanan,
                        ], ',', '"', '');
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function adminShow(string $id, OrderService $orderService): JsonResponse
    {
        $orderService->expirePendingOrders();

        $order = Order::with([
            'user',
            'items.variant.product',
            'payments',
            'voucherUsages.voucher',
            'statusHistories.changedBy',
        ])->where('id_order', $id)->first();

        if (! $order) {
            return $this->notFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil.',
            'data' => $order,
        ]);
    }

    public function updateStatus(Request $request, string $id, OrderService $orderService): JsonResponse
    {
        $validated = $request->validate([
            'status_pesanan' => ['required', 'string', Rule::in(OrderService::STATUSES)],
            'catatan' => ['nullable', 'string'],
        ]);

        $order = Order::where('id_order', $id)->first();

        if (! $order) {
            return $this->notFound();
        }

        $order = $orderService->changeStatus(
            $order,
            $validated['status_pesanan'],
            $request->user()->id_user,
            $validated['catatan'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Detail pesanan berhasil diambil.',
            'data' => $order,
        ]);
    }

    /**
     * Ringkasan pesanan untuk admin (dapat difilter rentang tanggal/status).
     */
    public function adminSummary(Request $request, OrderService $orderService): JsonResponse
    {
        $orderService->expirePendingOrders();

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(OrderService::STATUSES)],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $query = Order::query()
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_pesanan', $validated['status'])
            )
            ->when(
                ! empty($validated['dari']),
                fn ($query) => $query->whereDate('dibuat_pada', '>=', $validated['dari'])
            )
            ->when(
                ! empty($validated['sampai']),
                fn ($query) => $query->whereDate('dibuat_pada', '<=', $validated['sampai'])
            );

        $totalPesanan = (clone $query)->count();
        $totalNilai = (float) (clone $query)->sum('total_pembayaran');

        $perStatus = [];

        foreach (OrderService::STATUSES as $status) {
            $perStatus[$status] = (clone $query)->where('status_pesanan', $status)->count();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan pesanan berhasil diambil.',
            'data' => [
                'total_pesanan' => $totalPesanan,
                'total_nilai' => $totalNilai,
                'rata_rata_nilai' => $totalPesanan > 0 ? round($totalNilai / $totalPesanan, 2) : 0,
                'pesanan_perlu_diproses' => (int) (clone $query)->whereIn('status_pesanan', ['dibayar', 'diproses'])->count(),
                'per_status' => $perStatus,
            ],
        ]);
    }

    /**
     * Query dasar daftar dan ekspor pesanan admin.
     *
     * @param  array<string, mixed>  $validated
     */
    private function adminOrdersQuery(array $validated): Builder
    {
        return Order::query()
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_pesanan', $validated['status'])
            )
            ->when(
                ! empty($validated['search']),
                function ($query) use ($validated) {
                    $term = $validated['search'];
                    $query->where(function ($inner) use ($term) {
                        $inner->where('nomor_pesanan', 'like', "%{$term}%")
                            ->orWhere('nama_penerima', 'like', "%{$term}%");
                    });
                }
            )
            ->when(
                ! empty($validated['dari']),
                fn ($query) => $query->whereDate('dibuat_pada', '>=', $validated['dari'])
            )
            ->when(
                ! empty($validated['sampai']),
                fn ($query) => $query->whereDate('dibuat_pada', '<=', $validated['sampai'])
            )
            ->orderByDesc('dibuat_pada');
    }

    /**
     * @return array<string, mixed>
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
