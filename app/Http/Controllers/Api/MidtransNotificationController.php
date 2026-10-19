<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Services\MidtransService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class MidtransNotificationController extends Controller
{
    private const TERMINAL_PAYMENT_STATUSES = ['dibayar', 'ditolak', 'kedaluwarsa', 'dibatalkan'];

    public function handle(
        Request $request,
        MidtransService $midtrans,
        OrderService $orderService
    ): JsonResponse {
        $raw = $request->getContent();
        $payload = json_decode($raw, true);
        $payload = is_array($payload) ? $payload : [];

        $log = WebhookLog::create([
            'id_webhook_log' => (string) Str::uuid(),
            'provider' => 'midtrans',
            'provider_transaction_id' => ($payload['transaction_id'] ?? null) ?: null,
            'id_order' => null,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'payload' => $raw,
            'signature' => (string) ($payload['signature_key'] ?? '') ?: null,
            'status' => 'diterima',
            'http_status' => null,
            'alamat_ip' => $request->ip(),
            'catatan' => null,
        ]);

        if (! $midtrans->verifySignature($payload)) {
            $log->update([
                'status' => 'gagal',
                'http_status' => 403,
                'catatan' => 'Signature notifikasi tidak valid.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Signature notifikasi tidak valid.',
            ], 403);
        }

        try {
            $notification = $midtrans->notification();
        } catch (Throwable $e) {
            report($e);

            $log->update([
                'status' => 'gagal',
                'http_status' => 500,
                'catatan' => 'Gagal memverifikasi status transaksi ke Midtrans.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memverifikasi status transaksi.',
            ], 500);
        }

        $nomorPesanan = (string) $notification->order_id;
        $transactionStatus = (string) $notification->transaction_status;
        $fraudStatus = (string) ($notification->fraud_status ?? '');
        $paymentType = $notification->payment_type ?? null;
        $transactionId = $notification->transaction_id ?? null;
        $grossAmount = (float) ($notification->gross_amount ?? 0);

        $order = Order::where('nomor_pesanan', $nomorPesanan)->first();

        if (! $order) {
            $log->update([
                'status' => 'gagal',
                'http_status' => 200,
                'catatan' => 'Pesanan tidak ditemukan untuk notifikasi ini.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 200);
        }

        $log->update(['id_order' => $order->id_order]);

        if (round($grossAmount, 2) !== round((float) $order->total_pembayaran, 2)) {
            $log->update([
                'status' => 'gagal',
                'http_status' => 200,
                'catatan' => 'Nominal notifikasi tidak cocok dengan total pesanan.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nominal tidak cocok.',
            ], 200);
        }

        $mapping = $this->mapStatus($transactionStatus, $fraudStatus);
        $duplicate = false;

        try {
            DB::transaction(function () use (
                $order,
                $transactionId,
                $paymentType,
                $mapping,
                $orderService,
                &$duplicate
            ) {
                $payment = Payment::where('id_order', $order->id_order)
                    ->orderByDesc('dibuat_pada')
                    ->lockForUpdate()
                    ->first();

                if (! $payment) {
                    $payment = Payment::create([
                        'id_payment' => (string) Str::uuid(),
                        'id_order' => $order->id_order,
                        'provider' => 'midtrans',
                        'provider_transaction_id' => $transactionId,
                        'metode_pembayaran' => $paymentType,
                        'jumlah_pembayaran' => $order->total_pembayaran,
                        'status_pembayaran' => 'menunggu',
                        'payment_url' => null,
                        'payment_reference' => null,
                        'expires_at' => null,
                        'paid_at' => null,
                    ]);
                }

                if (in_array($payment->status_pembayaran, self::TERMINAL_PAYMENT_STATUSES, true)) {
                    $duplicate = true;

                    return;
                }

                $paymentUpdates = [
                    'provider_transaction_id' => $transactionId,
                    'metode_pembayaran' => $paymentType,
                    'status_pembayaran' => $mapping['payment'],
                ];

                if ($mapping['paid'] && $payment->paid_at === null) {
                    $paymentUpdates['paid_at'] = now();
                }

                $payment->update($paymentUpdates);

                if ($mapping['order'] !== null && $order->status_pesanan !== $mapping['order']) {
                    $orderService->changeStatus(
                        $order,
                        $mapping['order'],
                        null,
                        "Pembaruan otomatis dari Midtrans: {$mapping['payment']}."
                    );
                }
            });
        } catch (Throwable $e) {
            report($e);

            $log->update([
                'status' => 'gagal',
                'http_status' => 500,
                'catatan' => 'Gagal memproses notifikasi.',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses notifikasi.',
            ], 500);
        }

        $log->update([
            'provider_transaction_id' => $transactionId,
            'status' => $duplicate ? 'ganda' : 'diproses',
            'http_status' => 200,
            'catatan' => $duplicate
                ? 'Notifikasi duplikat diabaikan.'
                : "Status transaksi: {$transactionStatus}.",
        ]);

        return response()->json([
            'success' => true,
            'message' => $duplicate
                ? 'Notifikasi sudah pernah diproses.'
                : 'Notifikasi berhasil diproses.',
        ]);
    }

    /**
     * @return array{payment: string, order: string|null, paid: bool}
     */
    private function mapStatus(string $transactionStatus, string $fraudStatus): array
    {
        return match ($transactionStatus) {
            'capture' => match ($fraudStatus) {
                'accept' => ['payment' => 'dibayar', 'order' => 'dibayar', 'paid' => true],
                'challenge' => ['payment' => 'menunggu', 'order' => null, 'paid' => false],
                default => ['payment' => 'ditolak', 'order' => 'dibatalkan', 'paid' => false],
            },
            'settlement' => ['payment' => 'dibayar', 'order' => 'dibayar', 'paid' => true],
            'pending' => ['payment' => 'menunggu', 'order' => null, 'paid' => false],
            'deny' => ['payment' => 'ditolak', 'order' => 'dibatalkan', 'paid' => false],
            'expire' => ['payment' => 'kedaluwarsa', 'order' => 'dibatalkan', 'paid' => false],
            'cancel' => ['payment' => 'dibatalkan', 'order' => 'dibatalkan', 'paid' => false],
            default => ['payment' => $transactionStatus, 'order' => null, 'paid' => false],
        };
    }
}
