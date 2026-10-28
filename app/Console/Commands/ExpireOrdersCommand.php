<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Batalkan pesanan yang menunggu pembayaran dan sudah melewati batas waktu
 * (orders.kedaluwarsa_pada). Pembatalan ini juga melepas reservasi stok
 * (product_variants.stok_dipesan) sehingga unit kembali tersedia.
 *
 * Dijadwalkan tiap menit. Endpoint yang mengakses pesanan juga memvalidasi
 * kedaluwarsa secara langsung, sehingga pesanan basi tidak bergantung pada
 * penjadwal yang harus selalu berjalan.
 */
class ExpireOrdersCommand extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Batalkan pesanan menunggu pembayaran yang sudah kedaluwarsa (12 jam)';

    public function handle(OrderService $orderService): int
    {
        $now = now();

        $orders = Order::where('status_pesanan', 'menunggu_pembayaran')
            ->whereNotNull('kedaluwarsa_pada')
            ->where('kedaluwarsa_pada', '<=', $now)
            ->get();

        $jumlah = 0;

        foreach ($orders as $order) {
            if ($orderService->expireIfUnpaid($order)) {
                $jumlah++;
            }
        }

        $this->info("Pesanan kedaluwarsa dibatalkan: {$jumlah}.");

        return self::SUCCESS;
    }
}
