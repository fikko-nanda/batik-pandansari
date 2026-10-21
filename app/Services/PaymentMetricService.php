<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sumber kebenaran tunggal untuk metrik pendapatan, transaksi, dan AOV.
 *
 * Dipakai bersama oleh dashboard admin dan laporan keuangan sehingga
 * angka pada kedua kelompok endpoint tersebut selalu konsisten.
 *
 * Definisi yang dipakai:
 * - Pendapatan dihitung dari tabel `payments` dengan status_pembayaran
 *   'dibayar' (satu-satunya status sukses hasil pemetaan notifikasi
 *   Midtrans di MidtransNotificationController::mapStatus). Pembayaran
 *   berstatus menunggu/ditolak/kedaluwarsa/dibatalkan TIDAK dihitung.
 * - Waktu pembayaran memakai COALESCE(paid_at, dibuat_pada). Midtrans
 *   selalu mengisi paid_at saat sukses; fallback dibutuhkan untuk data
 *   lama yang sudah ditandai dibayar tanpa paid_at.
 * - AOV memakai pesanan yang minimal memiliki satu pembayaran berstatus
 *   'dibayar'. Per pesanan hanya dihitung satu kali meski punya banyak
 *   pembayaran, sehingga tidak terjadi duplikasi.
 */
class PaymentMetricService
{
    public const GROUPS = ['hari', 'minggu', 'bulan'];

    /**
     * Nama kolom waktu pembayaran yang sudah memuat fallback.
     */
    public function waktuPembayaranColumn(): string
    {
        return 'COALESCE(payments.paid_at, payments.dibuat_pada)';
    }

    /**
     * Query pembayaran berstatus 'dibayar' pada rentang tanggal tertentu.
     *
     * @param  string|null  $dari  tanggal minimal (Y-m-d), nullable
     * @param  string|null  $sampai  tanggal maksimal (Y-m-d), nullable
     */
    public function paidInRange(?string $dari = null, ?string $sampai = null): Builder
    {
        return Payment::query()
            ->where('status_pembayaran', 'dibayar')
            ->when(
                $dari !== null,
                fn ($query) => $query->where(
                    fn ($inner) => $inner
                        ->where('paid_at', '>=', $dari)
                        ->orWhere(function ($fallback) use ($dari) {
                            $fallback->whereNull('paid_at')
                                ->where('dibuat_pada', '>=', $dari);
                        })
                )
            )
            ->when(
                $sampai !== null,
                fn ($query) => $query->where(
                    fn ($inner) => $inner
                        ->where('paid_at', '<', $this->tanggalEsok($sampai))
                        ->orWhere(function ($fallback) use ($sampai) {
                            $fallback->whereNull('paid_at')
                                ->where('dibuat_pada', '<', $this->tanggalEsok($sampai));
                        })
                )
            );
    }

    /**
     * Ringkasan pendapatan pada rentang tanggal.
     *
     * @return array{total_pendapatan: float, jumlah_transaksi_bayar: int}
     */
    public function revenue(?string $dari = null, ?string $sampai = null): array
    {
        $query = $this->paidInRange($dari, $sampai);

        return [
            'total_pendapatan' => round((float) (clone $query)->sum('jumlah_pembayaran'), 2),
            'jumlah_transaksi_bayar' => (int) (clone $query)->count(),
        ];
    }

    /**
     * Average Order Value pada rentang tanggal.
     *
     * Kriteria: pesanan yang minimal memiliki satu pembayaran berstatus
     * 'dibayar' pada rentang tersebut. Setiap pesanan dihitung satu kali
     * (COUNT DISTINCT / SELECT DISTINCT) meski punya banyak pembayaran.
     *
     * @return array{jumlah_pesanan: int, total_nilai_pesanan: float, aov: float, kriteria: string}
     */
    public function aov(?string $dari = null, ?string $sampai = null): array
    {
        $paidOrders = $this->paidInRange($dari, $sampai)
            ->select('id_order')
            ->distinct();

        $jumlahPesanan = (int) (clone $paidOrders)->distinct()->count('id_order');

        $totalNilai = $jumlahPesanan > 0
            ? round((float) Order::query()
                ->whereIn('id_order', $paidOrders)
                ->sum('total_pembayaran'), 2)
            : 0.0;

        return [
            'jumlah_pesanan' => $jumlahPesanan,
            'total_nilai_pesanan' => $totalNilai,
            'aov' => $jumlahPesanan > 0 ? round($totalNilai / $jumlahPesanan, 2) : 0.0,
            'kriteria' => 'Pesanan yang minimal memiliki satu pembayaran berstatus "dibayar" pada periode tersebut.',
        ];
    }

    /**
     * Daftar nilai pembayaran sukses per periode (untuk grafik dashboard).
     *
     * @return array<int, array{periode: string, pendapatan: float, jumlah_transaksi: int}>
     */
    public function revenueSeries(string $group, ?string $dari = null, ?string $sampai = null): array
    {
        $expression = $this->periodExpression($group);

        $rows = $this->paidInRange($dari, $sampai)
            ->selectRaw(
                $expression.' as periode, SUM(jumlah_pembayaran) as pendapatan, COUNT(*) as jumlah'
            )
            ->groupByRaw($expression)
            ->orderBy('periode')
            ->get();

        return $rows->map(fn ($row) => [
            'periode' => (string) $row->periode,
            'pendapatan' => round((float) $row->pendapatan, 2),
            'jumlah_transaksi' => (int) $row->jumlah,
        ])->all();
    }

    /**
     * Jumlah pembayaran per status pada rentang tanggal (semua status
     * ikut dihitung, termasuk yang nol, agar frontend punya daftar lengkap).
     *
     * @return array<string, int>
     */
    public function paymentsPerStatus(?string $dari = null, ?string $sampai = null): array
    {
        $base = Payment::query()
            ->when(
                $dari !== null,
                fn ($query) => $query->where('dibuat_pada', '>=', $dari)
            )
            ->when(
                $sampai !== null,
                fn ($query) => $query->where('dibuat_pada', '<', $this->tanggalEsok($sampai))
            );

        $result = [];

        foreach (Payment::STATUSES as $status) {
            $result[$status] = (int) (clone $base)
                ->where('status_pembayaran', $status)
                ->count();
        }

        return $result;
    }

    /**
     * Nilai pembayaran per status pada rentang tanggal.
     *
     * @return array<string, float>
     */
    public function nilaiPerStatus(?string $dari = null, ?string $sampai = null): array
    {
        $base = Payment::query()
            ->when(
                $dari !== null,
                fn ($query) => $query->where('dibuat_pada', '>=', $dari)
            )
            ->when(
                $sampai !== null,
                fn ($query) => $query->where('dibuat_pada', '<', $this->tanggalEsok($sampai))
            );

        $result = [];

        foreach (Payment::STATUSES as $status) {
            $result[$status] = round((float) (clone $base)
                ->where('status_pembayaran', $status)
                ->sum('jumlah_pembayaran'), 2);
        }

        return $result;
    }

    /**
     * Ekspresi SQL pengelompokan periode yang kompatibel SQLite (testing)
     * dan MySQL (produksi). Format hasil:
     * - hari  : YYYY-MM-DD
     * - minggu: YYYY-Www
     * - bulan : YYYY-MM
     */
    public function periodExpression(string $group): string
    {
        $column = $this->waktuPembayaranColumn();

        $driver = (string) config('database.default');

        $formats = [
            'hari' => [
                'sqlite' => "strftime('%Y-%m-%d', $column)",
                'mysql' => "DATE_FORMAT($column, '%Y-%m-%d')",
            ],
            'minggu' => [
                'sqlite' => "strftime('%Y-W%W', $column)",
                'mysql' => "DATE_FORMAT($column, '%x-W%v')",
            ],
            'bulan' => [
                'sqlite' => "strftime('%Y-%m', $column)",
                'mysql' => "DATE_FORMAT($column, '%Y-%m')",
            ],
        ];

        $entry = $formats[$group] ?? $formats['hari'];

        return $driver === 'sqlite' ? $entry['sqlite'] : $entry['mysql'];
    }

    /**
     * Ekspresi SQL pengelompokan periode untuk tanggal pembuatan pesanan
     * (dipakai statistik jumlah pesanan di dashboard).
     */
    public function orderPeriodExpression(string $group): string
    {
        $column = 'orders.dibuat_pada';

        $driver = (string) config('database.default');

        $formats = [
            'hari' => [
                'sqlite' => "strftime('%Y-%m-%d', $column)",
                'mysql' => "DATE_FORMAT($column, '%Y-%m-%d')",
            ],
            'minggu' => [
                'sqlite' => "strftime('%Y-W%W', $column)",
                'mysql' => "DATE_FORMAT($column, '%x-W%v')",
            ],
            'bulan' => [
                'sqlite' => "strftime('%Y-%m', $column)",
                'mysql' => "DATE_FORMAT($column, '%Y-%m')",
            ],
        ];

        $entry = $formats[$group] ?? $formats['hari'];

        return $driver === 'sqlite' ? $entry['sqlite'] : $entry['mysql'];
    }

    /**
     * Batas atas tanggal (exclusive) sehingga filter "sampai = 2026-10-20"
     * tetap memuat seluruh hari tersebut, apa pun zona waktu DB.
     */
    private function tanggalEsok(string $tanggal): string
    {
        return Carbon::parse($tanggal)->addDay()->toDateString();
    }
}
