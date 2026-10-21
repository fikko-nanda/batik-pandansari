<?php

namespace App\Services;

use App\Mail\OrderCancelledMail;
use App\Mail\OrderPaidMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Titik kirim email transaksional pesanan.
 *
 * Dirancang idempoten: pemanggil hanya memanggilnya pada transisi status yang
 * benar-benar terjadi (bukan saat status sama), sehingga notifikasi ganda dari
 * Midtrans atau sync berulang tidak menghasilkan email duplikat.
 *
 * Pengiriman di-queue (Mailable implements ShouldQueue) dan kegagalan email
 * TIDAK boleh menggagalkan transaksi pesanan — semua error dicatat lewat
 * report() dan ditelan.
 */
class OrderMailService
{
    /**
     * Kirim email konfirmasi pembayaran berhasil (dengan invoice PDF).
     */
    public function sendPaid(Order $order): void
    {
        $this->dispatch(new OrderPaidMail($order->id_order), $order, 'paid');
    }

    /**
     * Kirim email pembatalan pesanan (pembayaran kedaluwarsa / dibatalkan).
     */
    public function sendCancelled(Order $order): void
    {
        $this->dispatch(new OrderCancelledMail($order->id_order), $order, 'cancelled');
    }

    private function dispatch(object $mailable, Order $order, string $jenis): void
    {
        $email = $order->user?->email;

        if ($email === null || $email === '') {
            return;
        }

        try {
            Mail::to($email)->queue($mailable);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
