<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(OrderService::STATUSES)],
            'search' => ['nullable', 'string', 'max:150'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $filename = 'pesanan-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($validated) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'nomor_pesanan',
                'tanggal',
                'penerima',
                'kota',
                'total',
                'ongkir',
                'status',
            ], ',', '"', '');

            Order::query()
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
                    ! empty($validated['start_date']),
                    fn ($query) => $query->whereDate('dibuat_pada', '>=', $validated['start_date'])
                )
                ->when(
                    ! empty($validated['end_date']),
                    fn ($query) => $query->whereDate('dibuat_pada', '<=', $validated['end_date'])
                )
                ->orderByDesc('dibuat_pada')
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

    public function adminIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(OrderService::STATUSES)],
            'search' => ['nullable', 'string', 'max:150'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $orders = Order::with(['items', 'user'])
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
            ->orderByDesc('dibuat_pada')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pesanan berhasil diambil.',
            'data' => $orders->items(),
            'meta' => $this->meta($orders),
        ]);
    }

    public function adminShow(string $id): JsonResponse
    {
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
    public function adminSummary(Request $request): JsonResponse
    {
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
                'per_status' => $perStatus,
            ],
        ]);
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
