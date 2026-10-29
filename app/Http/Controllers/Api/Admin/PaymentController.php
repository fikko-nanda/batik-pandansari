<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(Payment::STATUSES)],
            'provider' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'format' => ['nullable', 'string', 'in:pembayaran,buku-kas'],
        ]);

        $format = $validated['format'] ?? 'pembayaran';
        $filename = $format === 'buku-kas' ? 'buku-kas.csv' : 'pembayaran.csv';

        return response()->streamDownload(function () use ($validated, $format) {
            $output = fopen('php://output', 'w');

            $headers = $format === 'buku-kas'
                ? ['nomor_pesanan', 'pelanggan', 'provider', 'metode', 'jumlah', 'status', 'dibuat_pada', 'paid_at']
                : ['nomor_pesanan', 'provider', 'metode', 'jumlah', 'status', 'dibuat_pada', 'paid_at'];

            fputcsv($output, $headers, ',', '"', '');

            Payment::query()
                ->with(['order.user'])
                ->when(
                    ! empty($validated['status']),
                    fn ($query) => $query->where('status_pembayaran', $validated['status'])
                )
                ->when(
                    ! empty($validated['provider']),
                    fn ($query) => $query->where('provider', $validated['provider'])
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
                ->chunk(200, function ($payments) use ($output, $format) {
                    foreach ($payments as $payment) {
                        $row = [
                            $payment->order?->nomor_pesanan,
                        ];

                        if ($format === 'buku-kas') {
                            $row[] = $payment->order?->user?->nama_pengguna;
                        }

                        array_push($row,
                            $payment->provider,
                            $payment->metode_pembayaran,
                            $payment->jumlah_pembayaran,
                            $payment->status_pembayaran,
                            $payment->dibuat_pada?->format('Y-m-d H:i:s'),
                            $payment->paid_at?->format('Y-m-d H:i:s')
                        );

                        fputcsv($output, $row, ',', '"', '');
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
}
