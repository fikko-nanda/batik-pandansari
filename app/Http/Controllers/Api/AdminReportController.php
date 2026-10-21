<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentMetricService;
use App\Services\ReportPdfService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan keuangan admin.
 *
 * Sumber data dan definisi angka memakai PaymentMetricService yang sama
 * dengan dashboard sehingga isi laporan selalu konsisten dengan
 * GET /api/admin/dashboard/* untuk filter yang sama.
 */
class AdminReportController extends Controller
{
    public function __construct(
        private readonly PaymentMetricService $metrics
    ) {}

    /**
     * GET /api/admin/reports/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $this->validatePeriode($request);

        $dari = $validated['dari'] ?? null;
        $sampai = $validated['sampai'] ?? null;

        $pendapatan = $this->metrics->revenue($dari, $sampai);

        $totalTransaksi = Payment::query()
            ->when($dari !== null, fn ($query) => $query->where('dibuat_pada', '>=', $dari))
            ->when(
                $sampai !== null,
                fn ($query) => $query->where('dibuat_pada', '<', Carbon::parse($sampai)->addDay()->toDateString())
            )
            ->count();

        $pesananQuery = Order::query()
            ->when($dari !== null, fn ($query) => $query->whereDate('dibuat_pada', '>=', $dari))
            ->when($sampai !== null, fn ($query) => $query->whereDate('dibuat_pada', '<=', $sampai));

        $jumlahPesanan = (int) (clone $pesananQuery)->count();
        $totalNilaiPesanan = round((float) (clone $pesananQuery)->sum('total_pembayaran'), 2);

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan laporan keuangan berhasil diambil.',
            'data' => [
                'periode' => [
                    'dari' => $dari,
                    'sampai' => $sampai,
                ],
                'pendapatan' => [
                    'total_pendapatan' => $pendapatan['total_pendapatan'],
                    'jumlah_transaksi_bayar' => $pendapatan['jumlah_transaksi_bayar'],
                ],
                'pembayaran' => [
                    'total_transaksi' => $totalTransaksi,
                    'per_status' => $this->metrics->paymentsPerStatus($dari, $sampai),
                    'nilai_per_status' => $this->metrics->nilaiPerStatus($dari, $sampai),
                ],
                'pesanan' => [
                    'jumlah_pesanan' => $jumlahPesanan,
                    'total_nilai_pesanan' => $totalNilaiPesanan,
                ],
                'keterangan' => 'total_nilai_pesanan adalah akumulasi nilai pesanan, bukan laba bersih, karena data biaya dan pengeluaran belum tersedia. Refund juga belum didukung skema saat ini.',
            ],
        ]);
    }

    /**
     * GET /api/admin/reports/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Payment::STATUSES)],
            'provider' => ['nullable', 'string', 'max:50'],
            'jenis_transaksi' => ['nullable', 'string', 'in:pembayaran'],
            'search' => ['nullable', 'string', 'max:150'],
            'sort' => ['nullable', 'string', 'in:waktu,jumlah_pembayaran,dibuat_pada'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $dari = $validated['dari'] ?? null;
        $sampai = $validated['sampai'] ?? null;

        $query = Payment::query()
            ->with('order:id_order,id_user,nomor_pesanan,status_pesanan,total_pembayaran')
            ->when(
                ! empty($validated['status']),
                fn ($q) => $q->where('status_pembayaran', $validated['status'])
            )
            ->when(
                ! empty($validated['provider']),
                fn ($q) => $q->where('provider', $validated['provider'])
            )
            ->when($dari !== null, function ($q) use ($dari) {
                $q->where(function ($inner) use ($dari) {
                    $inner->where('paid_at', '>=', $dari)
                        ->orWhere(function ($fallback) use ($dari) {
                            $fallback->whereNull('paid_at')
                                ->where('dibuat_pada', '>=', $dari);
                        });
                });
            })
            ->when($sampai !== null, function ($q) use ($sampai) {
                $batas = Carbon::parse($sampai)->addDay()->toDateString();

                $q->where(function ($inner) use ($batas) {
                    $inner->where('paid_at', '<', $batas)
                        ->orWhere(function ($fallback) use ($batas) {
                            $fallback->whereNull('paid_at')
                                ->where('dibuat_pada', '<', $batas);
                        });
                });
            })
            ->when(
                ! empty($validated['search']),
                function ($q) use ($validated) {
                    $term = $validated['search'];
                    $q->where(function ($inner) use ($term) {
                        $inner->where('provider_transaction_id', 'like', "%{$term}%")
                            ->orWhere('metode_pembayaran', 'like', "%{$term}%")
                            ->orWhereHas('order', fn ($o) => $o->where('nomor_pesanan', 'like', "%{$term}%"));
                    });
                }
            );

        $sort = $validated['sort'] ?? 'waktu';
        $order = $validated['order'] ?? 'desc';

        if ($sort === 'waktu') {
            $query->orderByRaw('COALESCE(paid_at, dibuat_pada) '.$order);
        } else {
            $query->orderBy($sort, $order);
        }

        $payments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Buku kas transaksi berhasil diambil.',
            'data' => $payments->getCollection()->map(
                fn ($payment) => $this->transformTransaction($payment)
            )->values(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/admin/reports/transactions/{id}
     */
    public function transactionDetail(string $id): JsonResponse
    {
        $payment = Payment::with([
            'order:id_order,id_user,nomor_pesanan,status_pesanan,subtotal,biaya_pengiriman,diskon,total_pembayaran,nama_penerima,dibuat_pada',
            'order.items:id_order_item,id_order,id_variant,nama_produk,sku,harga_satuan,jumlah,subtotal',
        ])
            ->where('id_payment', $id)
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        // payment_url berisi tautan pembayaran yang bersifat rahasia bagi
        // pelanggan; tidak diekspos ke respons admin.
        $payment->makeHidden(['payment_url']);

        return response()->json([
            'success' => true,
            'message' => 'Detail transaksi berhasil diambil.',
            'data' => [
                'id_payment' => $payment->id_payment,
                'id_order' => $payment->id_order,
                'nomor_pesanan' => $payment->order?->nomor_pesanan,
                'waktu' => $payment->paid_at ?? $payment->dibuat_pada,
                'jenis_transaksi' => 'pembayaran',
                'status_pembayaran' => $payment->status_pembayaran,
                'jumlah_pembayaran' => (float) $payment->jumlah_pembayaran,
                'provider' => $payment->provider,
                'metode_pembayaran' => $payment->metode_pembayaran,
                'provider_transaction_id' => $payment->provider_transaction_id,
                'payment_reference' => $payment->payment_reference,
                'expires_at' => $payment->expires_at,
                'paid_at' => $payment->paid_at,
                'dibuat_pada' => $payment->dibuat_pada,
                'order' => $payment->order,
            ],
        ]);
    }

    /**
     * GET /api/admin/reports/export/pdf
     */
    public function exportPdf(Request $request, ReportPdfService $pdfService): Response
    {
        $validated = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Payment::STATUSES)],
            'provider' => ['nullable', 'string', 'max:50'],
        ]);

        $dari = $validated['dari'] ?? null;
        $sampai = $validated['sampai'] ?? null;

        // Ringkasan memakai metrik yang sama sehingga angka pada PDF
        // identik dengan GET /api/admin/reports/summary.
        $pendapatan = $this->metrics->revenue($dari, $sampai);

        $filtered = Payment::query()
            ->when(! empty($validated['status']), fn ($q) => $q->where('status_pembayaran', $validated['status']))
            ->when(! empty($validated['provider']), fn ($q) => $q->where('provider', $validated['provider']))
            ->when($dari !== null, fn ($q) => $q->where('dibuat_pada', '>=', $dari))
            ->when(
                $sampai !== null,
                fn ($q) => $q->where('dibuat_pada', '<', Carbon::parse($sampai)->addDay()->toDateString())
            );

        $totalTransaksi = (int) (clone $filtered)->count();

        $perStatus = [];
        $nilaiPerStatus = [];

        foreach (Payment::STATUSES as $status) {
            $perStatus[$status] = (int) (clone $filtered)->where('status_pembayaran', $status)->count();
            $nilaiPerStatus[$status] = round(
                (float) (clone $filtered)->where('status_pembayaran', $status)->sum('jumlah_pembayaran'),
                2
            );
        }

        $pesananQuery = Order::query()
            ->when($dari !== null, fn ($q) => $q->whereDate('dibuat_pada', '>=', $dari))
            ->when($sampai !== null, fn ($q) => $q->whereDate('dibuat_pada', '<=', $sampai));

        $jumlahPesanan = (int) (clone $pesananQuery)->count();
        $totalNilaiPesanan = round((float) (clone $pesananQuery)->sum('total_pembayaran'), 2);

        $transactionsQuery = Payment::query()
            ->with('order:id_order,nomor_pesanan')
            ->when(! empty($validated['status']), fn ($q) => $q->where('status_pembayaran', $validated['status']))
            ->when(! empty($validated['provider']), fn ($q) => $q->where('provider', $validated['provider']))
            ->when($dari !== null, fn ($q) => $q->where('dibuat_pada', '>=', $dari))
            ->when(
                $sampai !== null,
                fn ($q) => $q->where('dibuat_pada', '<', Carbon::parse($sampai)->addDay()->toDateString())
            )
            ->orderByRaw('COALESCE(paid_at, dibuat_pada) desc');

        $rows = $transactionsQuery->limit(ReportPdfService::MAX_TRANSACTION_ROWS + 1)
            ->get()
            ->map(fn ($payment) => [
                'nomor_pesanan' => $payment->order?->nomor_pesanan ?? '-',
                'waktu' => optional($payment->paid_at ?? $payment->dibuat_pada)->format('Y-m-d H:i'),
                'jenis_transaksi' => 'pembayaran',
                'status_pembayaran' => $payment->status_pembayaran,
                'provider' => $payment->provider,
                'jumlah_pembayaran' => (float) $payment->jumlah_pembayaran,
            ])
            ->all();

        $periodeTeks = $this->formatPeriode($dari, $sampai);

        $ringkasan = [
            'pendapatan' => $pendapatan,
            'pembayaran' => [
                'total_transaksi' => $totalTransaksi,
                'per_status' => $perStatus,
                'nilai_per_status' => $nilaiPerStatus,
            ],
            'pesanan' => [
                'jumlah_pesanan' => $jumlahPesanan,
                'total_nilai_pesanan' => $totalNilaiPesanan,
            ],
        ];

        $filterTambahan = array_filter([
            'Status pembayaran' => $validated['status'] ?? null,
            'Provider' => $validated['provider'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $result = $pdfService->build(
            'Laporan Keuangan Batik Pandansari',
            $periodeTeks,
            $ringkasan,
            $rows,
            $filterTambahan
        );

        $filename = 'laporan-keuangan-'.now()->format('Y-m-d').'.pdf';

        return response($result['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($result['binary']),
        ]);
    }

    /**
     * @return array{dari: ?string, sampai: ?string}
     */
    private function validatePeriode(Request $request): array
    {
        return $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformTransaction(Payment $payment): array
    {
        return [
            'id_payment' => $payment->id_payment,
            'id_order' => $payment->id_order,
            'nomor_pesanan' => $payment->order?->nomor_pesanan,
            'waktu' => $payment->paid_at ?? $payment->dibuat_pada,
            'jenis_transaksi' => 'pembayaran',
            'status_pembayaran' => $payment->status_pembayaran,
            'jumlah_pembayaran' => (float) $payment->jumlah_pembayaran,
            'provider' => $payment->provider,
            'metode_pembayaran' => $payment->metode_pembayaran,
            'provider_transaction_id' => $payment->provider_transaction_id,
            'dibuat_pada' => $payment->dibuat_pada,
        ];
    }

    private function formatPeriode(?string $dari, ?string $sampai): string
    {
        if ($dari === null && $sampai === null) {
            return 'Seluruh periode';
        }

        $kiri = $dari ?? 'Awal data';
        $kanan = $sampai ?? 'Sekarang';

        return $kiri.' s.d. '.$kanan;
    }
}
