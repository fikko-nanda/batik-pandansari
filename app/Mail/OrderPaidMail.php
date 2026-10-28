<?php

namespace App\Mail;

use App\Mail\Concerns\TracksNotificationLog;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email konfirmasi pembayaran berhasil beserta invoice PDF terlampir.
 *
 * Dikirim dari OrderMailService hanya sekali untuk transisi pesanan ke status
 * "dibayar". Data invoice dibangun dari database (InvoiceService), bukan dari
 * data yang dibekukan di frontend.
 */
class OrderPaidMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TracksNotificationLog;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(public string $idOrder) {}

    public function build(): self
    {
        $this->attachNotificationLogHeader();

        $order = Order::with(['items', 'user', 'payments', 'voucherUsages.voucher'])
            ->findOrFail($this->idOrder);

        $payment = $order->payments->sortByDesc('dibuat_pada')->first();

        $data = [
            'order' => $order,
            'namaPelanggan' => $order->user?->nama_pengguna ?? $order->nama_penerima,
            'tanggalPesan' => $order->dibuat_pada,
            'tanggalBayar' => $payment?->paid_at,
            'metodePembayaran' => $payment?->metode_pembayaran,
            'statusPembayaran' => $payment?->status_pembayaran ?? 'dibayar',
            'voucherUsage' => $order->voucherUsages
                ->whereIn('status_penggunaan', ['menunggu', 'digunakan'])
                ->first(),
            'brand' => config('mail.brand', []),
        ];

        $mail = $this->subject('Pembayaran Berhasil - '.$order->nomor_pesanan.' | Batik Pandansari')
            ->view('emails.order-paid', $data)
            ->text('emails.text.order-paid', $data);

        try {
            $invoice = app(InvoiceService::class)->build($order);
            $mail->attachData($invoice, app(InvoiceService::class)->filename($order), [
                'mime' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return $mail;
    }
}
