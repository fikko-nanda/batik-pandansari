<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WebhookLog;
use App\Services\MidtransService;
use App\Services\SyncPaymentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MidtransNotificationController extends Controller
{
    public function handle(
        Request $request,
        MidtransService $midtrans,
        SyncPaymentStatusService $sync
    ): JsonResponse {
        $raw = $request->getContent();
        $payload = json_decode($raw, true);
        $payload = is_array($payload) ? $payload : [];

        // Verifikasi signature lebih dulu: request tanpa signature valid tidak
        // boleh menyentuh database (hanya dicatat ke application log).
        if (! $midtrans->verifySignature($payload)) {
            Log::warning('Notifikasi Midtrans dengan signature tidak valid ditolak.', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Signature notifikasi tidak valid.',
            ], 403);
        }

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

        $duplicate = $result['duplicate'];

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
}
