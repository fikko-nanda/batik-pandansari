<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Logging\NotificationLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifikasi WhatsApp ke admin lewat Fonnte.
 *
 * Kegagalan pengiriman tidak boleh menggagalkan transaksi pesanan. Setiap
 * percobaan dicatat ke notification_logs dengan channel `whatsapp`.
 */
class WhatsAppService
{
    public function __construct(private readonly NotificationLogger $notificationLogger) {}

    public function sendOrderPaid(Order $order): void
    {
        $token = trim((string) config('services.fonnte.token', ''));
        $targets = (array) config('services.fonnte.admin_numbers', []);

        if ($token === '' || $targets === []) {
            Log::info('Notifikasi WhatsApp admin dilewati: FONNTE_TOKEN atau WA_ADMIN_NUMBER belum diisi.');

            return;
        }

        $order->loadMissing(['items']);

        $message = $this->paidMessage($order);
        $url = (string) config('services.fonnte.url', 'https://api.fonnte.com/send');

        foreach ($targets as $rawTarget) {
            $target = $this->normalizeNumber((string) $rawTarget);

            if ($target === '') {
                continue;
            }

            $log = $this->notificationLogger->queued(
                'order_paid_admin',
                null,
                $target,
                'order',
                $order->id_order,
                'whatsapp',
                'fonnte'
            );

            try {
                $this->notificationLogger->markAttempt($log?->id_notification_log);

                $response = Http::withHeaders([
                    'Authorization' => $token,
                ])->asForm()->timeout(15)->post($url, [
                    'target' => $target,
                    'message' => $message,
                ]);

                if ($response->failed()) {
                    $this->notificationLogger->markFailed(
                        $log?->id_notification_log,
                        'HTTP '.$response->status(),
                        'FonnteHttpError'
                    );

                    continue;
                }

                $payload = $response->json();
                $accepted = is_array($payload) && ($payload['status'] ?? true) !== false;

                if (! $accepted) {
                    $this->notificationLogger->markFailed(
                        $log?->id_notification_log,
                        (string) ($payload['reason'] ?? 'Fonnte menolak pesan.'),
                        'FonnteRejected'
                    );

                    continue;
                }

                $providerId = $payload['id'] ?? $payload['requestid'] ?? null;

                if (is_array($providerId)) {
                    $providerId = implode(',', array_map('strval', $providerId));
                }

                $this->notificationLogger->markSent(
                    $log?->id_notification_log,
                    $providerId !== null ? (string) $providerId : null
                );
            } catch (Throwable $e) {
                $this->notificationLogger->markFailed($log?->id_notification_log, $e);
                report($e);
            }
        }
    }

    private function paidMessage(Order $order): string
    {
        $lines = [
            '*PEMBAYARAN BERHASIL*',
            '',
            '📦 *INFORMASI PESANAN*',
            '• ID Pesanan : '.$order->nomor_pesanan,
            '• Nama Pemesan : '.$order->nama_penerima,
            '• No. WA Pemesan : '.($order->nomor_telepon_penerima ?: '-'),
            '',
            '🛍️ *BARANG DIPESAN*',
        ];

        foreach ($order->items as $item) {
            $varian = trim(implode(' / ', array_filter([$item->warna, $item->ukuran])));
            $line = '• '.$item->nama_produk.($varian !== '' ? ' ('.$varian.')' : '').' x *'.(int) $item->jumlah.'*';
            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = '🚚 *ALAMAT PENGIRIMAN*';
        $lines[] = $order->alamat_pengiriman;
        $lines[] = '';
        $lines[] = '💰 *TOTAL PEMBAYARAN*';
        $lines[] = $this->rupiah($order->total_pembayaran);

        return implode("\n", $lines);
    }

    private function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);

        if ($digits === null || $digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return strlen($digits) >= 10 ? $digits : '';
    }

    private function rupiah($value): string
    {
        return 'Rp '.number_format((float) $value, 2, ',', '.');
    }
}
