<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Logging\WebhookLogger;
use App\Services\MidtransService;
use App\Services\SyncPaymentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransNotificationController extends Controller
{
    public function handle(
        Request $request,
        MidtransService $midtrans,
        SyncPaymentStatusService $sync,
        WebhookLogger $webhookLogger
    ): JsonResponse {
        $raw = $request->getContent();
        $payload = json_decode($raw, true);
        $payload = is_array($payload) ? $payload : [];

        // Verifikasi signature lebih dulu: request tanpa signature valid tidak
        // boleh memproses pembayaran. Percobaannya tetap dicatat untuk audit.
        if (! $midtrans->verifySignature($payload)) {
            $log = $webhookLogger->received($request, $payload, false);
            $response = [
                'success' => false,
                'message' => 'Signature notifikasi tidak valid.',
            ];

            $webhookLogger->markRejected(
                $log,
                403,
                'Signature notifikasi tidak valid.',
                $response
            );

            Log::warning('Notifikasi Midtrans dengan signature tidak valid ditolak.', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
            ]);

            return response()->json($response, 403);
        }

        $log = $webhookLogger->received($request, $payload, true);

        try {
            $notification = $midtrans->notification();
        } catch (Throwable $e) {
            report($e);

            $response = [
                'success' => false,
                'message' => 'Gagal memverifikasi status transaksi.',
            ];

            $webhookLogger->markFailed(
                $log,
                500,
                $e->getMessage(),
                'Gagal memverifikasi status transaksi ke Midtrans.',
                $response
            );

            return response()->json($response, 500);
        }

        $nomorPesanan = (string) $notification->order_id;
        $transactionStatus = (string) $notification->transaction_status;
        $fraudStatus = (string) ($notification->fraud_status ?? '');
        $paymentType = $notification->payment_type ?? null;
        $transactionId = $notification->transaction_id ?? null;
        $grossAmount = (float) ($notification->gross_amount ?? 0);

        $order = Order::where('nomor_pesanan', $nomorPesanan)->first();

        if (! $order) {
            $response = [
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ];

            $webhookLogger->markFailed(
                $log,
                200,
                'Pesanan tidak ditemukan untuk notifikasi ini.',
                'Pesanan tidak ditemukan untuk notifikasi ini.',
                $response
            );

            return response()->json($response, 200);
        }

        if (round($grossAmount, 2) !== round((float) $order->total_pembayaran, 2)) {
            $response = [
                'success' => false,
                'message' => 'Nominal tidak cocok.',
            ];

            $webhookLogger->markFailed(
                $log,
                200,
                'Nominal notifikasi tidak cocok dengan total pesanan.',
                'Nominal notifikasi tidak cocok dengan total pesanan.',
                $response,
                $order->id_order
            );

            return response()->json($response, 200);
        }

        try {
            $result = $sync->apply(
                $order,
                $transactionStatus,
                $fraudStatus,
                $paymentType,
                $transactionId
            );
        } catch (Throwable $e) {
            report($e);

            $response = [
                'success' => false,
                'message' => 'Gagal memproses notifikasi.',
            ];

            $webhookLogger->markFailed(
                $log,
                500,
                $e->getMessage(),
                'Gagal memproses notifikasi.',
                $response,
                $order->id_order
            );

            return response()->json($response, 500);
        }

        $duplicate = $result['duplicate'];
        $response = [
            'success' => true,
            'message' => $duplicate
                ? 'Notifikasi sudah pernah diproses.'
                : 'Notifikasi berhasil diproses.',
        ];

        $webhookLogger->markProcessed(
            $log,
            $result,
            200,
            $duplicate
                ? 'Notifikasi duplikat diabaikan.'
                : "Status transaksi: {$transactionStatus}.",
            $response,
            $order->id_order,
            $transactionId
        );

        return response()->json($response);
    }
}
