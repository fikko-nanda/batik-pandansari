<?php

namespace App\Mail;

use App\Mail\Concerns\TracksNotificationLog;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email pemberitahuan pembatalan pesanan karena pembayaran melewati batas
 * waktu 12 jam.
 */
class OrderCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TracksNotificationLog;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public string $idOrder) {}

    public function build(): self
    {
        $this->attachNotificationLogHeader();

        $order = Order::with(['items', 'user'])->findOrFail($this->idOrder);

        $data = [
            'order' => $order,
            'namaPelanggan' => $order->user?->nama_pengguna ?? $order->nama_penerima,
            'tanggalPesan' => $order->dibuat_pada,
            'batasMenit' => OrderService::PAYMENT_WINDOW_MINUTES,
            'brand' => config('mail.brand', []),
        ];

        return $this->subject('Pesanan Dibatalkan - '.$order->nomor_pesanan.' | Batik Pandansari')
            ->view('emails.order-cancelled', $data)
            ->text('emails.text.order-cancelled', $data);
    }
}
