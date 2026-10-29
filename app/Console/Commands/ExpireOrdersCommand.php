<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\SyncPaymentStatusService;
use Illuminate\Console\Command;

/**
 * Batalkan pesanan yang menunggu pembayaran dan sudah melewati batas waktu
 * (orders.kedaluwarsa_pada). Reservasi stok baru dilepas satu menit setelah
 * pembatalan sehingga pembayaran yang terlambat masih dapat direkonsiliasi.
 *
 * Dijadwalkan tiap menit. Endpoint yang mengakses pesanan juga memvalidasi
 * kedaluwarsa secara langsung, sehingga pesanan basi tidak bergantung pada
 * penjadwal yang harus selalu berjalan.
 */
class ExpireOrdersCommand extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Batalkan pesanan yang melewati batas pembayaran 15 menit';

    public function handle(
        OrderService $orderService,
        SyncPaymentStatusService $syncPaymentStatus
    ): int {
        $now = now();

        $orders = Order::where('status_pesanan', 'menunggu_pembayaran')
            ->whereNotNull('kedaluwarsa_pada')
            ->where('kedaluwarsa_pada', '<=', $now)
            ->orderBy('kedaluwarsa_pada')
            ->limit(100)
            ->get();

        $jumlah = 0;

        foreach ($orders as $order) {
            if (! $syncPaymentStatus->verifyBeforeExpiry($order)) {
                continue;
            }

            if ($orderService->expireIfUnpaid($order)) {
                $jumlah++;
            }
        }

        $reservationsReleased = $orderService->releaseDueReservations();

        $this->info("Pesanan kedaluwarsa dibatalkan: {$jumlah}; reservasi dilepas: {$reservationsReleased}.");

        return self::SUCCESS;
    }
}
