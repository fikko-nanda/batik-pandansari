<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function adminIndex(Request $request, OrderService $orderService): JsonResponse
    {
        $orderService->expirePendingOrders();

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'provider' => ['nullable', 'string', 'max:50'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;

        $payments = $this->adminPaymentsQuery($validated)
            ->with(['order.user'])
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

    public function adminExport(Request $request, OrderService $orderService): StreamedResponse
    {
        $orderService->expirePendingOrders();

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:30'],
            'provider' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $validated['dari'] = $validated['start_date'] ?? null;
        $validated['sampai'] = $validated['end_date'] ?? null;

        $filename = 'pembayaran-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($validated) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'id_payment',
                'nomor_pesanan',
                'nama_pengguna',
                'provider',
                'metode_pembayaran',
                'jumlah_pembayaran',
                'status_pembayaran',
                'dibuat_pada',
                'paid_at',
            ], ',', '"', '');

            $this->adminPaymentsQuery($validated)
                ->with(['order.user'])
                ->chunk(200, function ($payments) use ($output) {
                    foreach ($payments as $payment) {
                        fputcsv($output, [
                            $payment->id_payment,
                            $payment->order?->nomor_pesanan,
                            $payment->order?->user?->nama_pengguna,
                            $payment->provider,
                            $payment->metode_pembayaran,
                            $payment->jumlah_pembayaran,
                            $payment->status_pembayaran,
                            $payment->dibuat_pada?->format('Y-m-d H:i:s'),
                            $payment->paid_at,
                        ], ',', '"', '');
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Query dasar daftar dan ekspor pembayaran admin.
     *
     * @param  array<string, mixed>  $validated
     */
    private function adminPaymentsQuery(array $validated): Builder
    {
        return Payment::query()
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where('status_pembayaran', $validated['status'])
            )
            ->when(
                ! empty($validated['provider']),
                fn ($query) => $this->applyProviderFilter($query, $validated['provider'])
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
     * Ringkasan pembayaran untuk admin (dapat difilter rentang tanggal/status/provider).
     */
    public function adminSummary(Request $request, OrderService $orderService): JsonResponse
    {
        $orderService->expirePendingOrders();

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
                fn ($query) => $this->applyProviderFilter($query, $validated['provider'])
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
        $jumlahTransaksiDibayar = (int) (clone $query)->where('status_pembayaran', 'dibayar')->count();
        $jumlahPesananDibayar = (int) (clone $query)
            ->where('status_pembayaran', 'dibayar')
            ->select('id_order')
            ->distinct()
            ->count('id_order');
        $feePercentage = max(0, (float) config('services.midtrans.fee_percentage', 0));
        $feeFixed = max(0, (float) config('services.midtrans.fee_fixed', 0));
        $midtransSandbox = ! (bool) config('services.midtrans.is_production', false);
        $biayaMidtransTerkonfigurasi = $feePercentage > 0 || $feeFixed > 0 || $midtransSandbox;
        $biayaMidtrans = $biayaMidtransTerkonfigurasi
            ? round(($nilaiDibayar * $feePercentage / 100) + ($feeFixed * $jumlahTransaksiDibayar), 2)
            : null;

        $perStatus = [];

        foreach (Payment::STATUSES as $status) {
            $perStatus[$status] = (clone $query)->where('status_pembayaran', $status)->count();
        }

        $perProvider = [];
        foreach ((clone $query)->selectRaw('provider, COUNT(*) as jumlah')->groupBy('provider')->get() as $row) {
            $provider = in_array(strtolower((string) $row->provider), ['midtrans', 'sandbox'], true)
                ? 'midtrans'
                : (string) $row->provider;
            $perProvider[$provider] = ($perProvider[$provider] ?? 0) + (int) $row->jumlah;
        }

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan pembayaran berhasil diambil.',
            'data' => [
                'total_pembayaran' => $totalPembayaran,
                'total_nilai' => $totalNilai,
                'nilai_dibayar' => $nilaiDibayar,
                'jumlah_pesanan_dibayar' => $jumlahPesananDibayar,
                'biaya_midtrans' => $biayaMidtrans,
                'dana_bersih' => $biayaMidtrans === null ? null : round(max(0, $nilaiDibayar - $biayaMidtrans), 2),
                'tarif_midtrans_persen' => $feePercentage,
                'tarif_midtrans_tetap' => $feeFixed,
                'biaya_midtrans_terkonfigurasi' => $biayaMidtransTerkonfigurasi,
                'midtrans_sandbox' => $midtransSandbox,
                'per_status' => $perStatus,
                'per_provider' => $perProvider,
            ],
        ]);
    }

    private function isMidtransFilter(?string $provider): bool
    {
        return $provider === 'midtrans' || $provider === 'sandbox';
    }

    private function applyProviderFilter(Builder $query, string $provider): Builder
    {
        return $this->isMidtransFilter($provider)
            ? $query->whereIn('provider', ['midtrans', 'sandbox'])
            : $query->where('provider', $provider);
    }
}
