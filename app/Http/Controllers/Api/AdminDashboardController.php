<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentMetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard admin.
 *
 * Seluruh metrik pendapatan/AOV memakai PaymentMetricService sehingga
 * konsisten dengan endpoint laporan keuangan.
 */
class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly PaymentMetricService $metrics
    ) {}

    /**
     * GET /api/admin/dashboard/summary
     */
    public function summary(): JsonResponse
    {
        $variantAggregate = ProductVariant::query()
            ->selectRaw('COUNT(*) as total_varian')
            ->selectRaw('SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) as varian_kritis')
            ->selectRaw(
                'SUM(CASE WHEN stok_minimum > 0 AND stok > 0 AND stok <= stok_minimum THEN 1 ELSE 0 END) as varian_menipis'
            )
            ->first();

        $revenue = $this->metrics->revenue();

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dashboard berhasil diambil.',
            'data' => [
                'total_produk' => (int) Product::count(),
                'total_varian' => (int) $variantAggregate->total_varian,
                'total_pengguna' => (int) User::count(),
                'total_pesanan' => (int) Order::count(),
                // Pesanan yang perlu diproses tim operasional: sudah dibayar
                // tetapi belum dikirim (dibayar / diproses).
                'pesanan_perlu_diproses' => (int) Order::whereIn('status_pesanan', ['dibayar', 'diproses'])->count(),
                'varian_kritis' => (int) $variantAggregate->varian_kritis,
                'varian_menipis' => (int) $variantAggregate->varian_menipis,
                'total_pembayaran' => (int) Payment::count(),
                'total_pendapatan' => $revenue['total_pendapatan'],
                'keterangan_pendapatan' => 'Pendapatan dihitung dari pembayaran berstatus "dibayar" sepanjang waktu.',
            ],
        ]);
    }

    /**
     * GET /api/admin/dashboard/revenue
     */
    public function revenue(Request $request): JsonResponse
    {
        $validated = $this->validatePeriode($request);

        $group = $validated['group'] ?? 'hari';

        $revenue = $this->metrics->revenue($validated['dari'] ?? null, $validated['sampai'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Statistik pendapatan berhasil diambil.',
            'data' => [
                'periode' => [
                    'dari' => $validated['dari'] ?? null,
                    'sampai' => $validated['sampai'] ?? null,
                    'group' => $group,
                ],
                'total_pendapatan' => $revenue['total_pendapatan'],
                'jumlah_transaksi_bayar' => $revenue['jumlah_transaksi_bayar'],
                'seri' => $this->metrics->revenueSeries(
                    $group,
                    $validated['dari'] ?? null,
                    $validated['sampai'] ?? null
                ),
                'sumber' => 'Pembayaran berstatus "dibayar"; pembayaran menunggu/ditolak/kedaluwarsa/dibatalkan tidak dihitung.',
            ],
        ]);
    }

    /**
     * GET /api/admin/dashboard/orders
     */
    public function orders(Request $request): JsonResponse
    {
        $validated = $this->validatePeriode($request);

        $group = $validated['group'] ?? 'hari';

        $base = Order::query()
            ->when(
                ! empty($validated['dari']),
                fn ($query) => $query->whereDate('dibuat_pada', '>=', $validated['dari'])
            )
            ->when(
                ! empty($validated['sampai']),
                fn ($query) => $query->whereDate('dibuat_pada', '<=', $validated['sampai'])
            );

        // Semua status resmi selalu ditampilkan meski jumlahnya nol.
        $perStatus = [];

        foreach (OrderService::STATUSES as $status) {
            $perStatus[$status] = (int) (clone $base)
                ->where('status_pesanan', $status)
                ->count();
        }

        $expression = $this->metrics->orderPeriodExpression($group);

        $tren = (clone $base)
            ->selectRaw($expression.' as periode, COUNT(*) as jumlah')
            ->groupByRaw($expression)
            ->orderBy('periode')
            ->get()
            ->map(fn ($row) => [
                'periode' => (string) $row->periode,
                'jumlah' => (int) $row->jumlah,
            ])
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Statistik pesanan berhasil diambil.',
            'data' => [
                'periode' => [
                    'dari' => $validated['dari'] ?? null,
                    'sampai' => $validated['sampai'] ?? null,
                    'group' => $group,
                ],
                'total_pesanan' => array_sum($perStatus),
                'per_status' => $perStatus,
                'tren' => $tren,
            ],
        ]);
    }

    /**
     * GET /api/admin/dashboard/aov
     */
    public function aov(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $aov = $this->metrics->aov($validated['dari'] ?? null, $validated['sampai'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Average Order Value berhasil dihitung.',
            'data' => [
                'periode' => [
                    'dari' => $validated['dari'] ?? null,
                    'sampai' => $validated['sampai'] ?? null,
                ],
                'jumlah_pesanan' => $aov['jumlah_pesanan'],
                'total_nilai_pesanan' => $aov['total_nilai_pesanan'],
                'aov' => $aov['aov'],
                'kriteria' => $aov['kriteria'],
            ],
        ]);
    }

    /**
     * @return array{dari: ?string, sampai: ?string, group: ?string}
     */
    private function validatePeriode(Request $request): array
    {
        return $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'group' => ['nullable', 'string', 'in:'.implode(',', PaymentMetricService::GROUPS)],
        ]);
    }
}
