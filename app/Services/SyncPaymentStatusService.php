<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sumber kebenaran tunggal untuk menerapkan hasil status transaksi Midtrans
 * ke database (tabel payments + status pesanan).
 *
 * Dipakai bersama oleh:
 * - MidtransNotificationController (webhook resmi Midtrans), dan
 * - endpoint sync manual yang mem-query Transaction::status() saat status
 *   pesanan lokal masih menunggu_pembayaran (fallback bila webhook tidak
 *   sampai, mis. URL notifikasi dashboard belum diarahkan).
 *
 * Penerapan bersifat idempoten: status pembayaran yang sudah terminal
 * (dibayar/ditolak/kedaluwarsa/dibatalkan) tidak diproses ulang, sehingga
 * notifikasi ganda atau sync berulang tidak membuat riwayat/email duplikat.
 * Pengecualian: settlement yang datang setelah pembayaran/ pesanan
 * kedaluwarsa atau dibatalkan tetap diterapkan sekali agar dana yang benar-
 * benar diterima tidak hilang dari catatan.
 */
class SyncPaymentStatusService
{
    public const TERMINAL_PAYMENT_STATUSES = ['dibayar', 'ditolak', 'kedaluwarsa', 'dibatalkan'];

    public function __construct(
        private readonly OrderService $orderService,
        private readonly MidtransService $midtrans
    ) {}

    /**
     * Fallback/sync manual: bila pesanan masih menunggu pembayaran, query
     * status terkini ke Midtrans lalu terapkan bila sudah terminal.
     *
     * Fail-safe: kegagalan memanggil Midtrans (tanpa kredensial/jaringan,
     * atau transaksi belum ada di Midtrans) hanya dicatat, tidak dilempar,
     * sehingga endpoint baca tetap mengembalikan data lokal.
     *
     * @return bool true bila ada perubahan status yang diterapkan
     */
    public function syncOrder(Order $order): bool
    {
        if ($order->status_pesanan !== 'menunggu_pembayaran') {
            return false;
        }

        try {
            $status = $this->midtrans->transactionStatus($order);
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        $transactionStatus = $status['transaction_status'] ?? '';

        if ($transactionStatus === '' || $transactionStatus === 'pending') {
            return false;
        }

        $result = $this->apply(
            $order,
            $transactionStatus,
            (string) ($status['fraud_status'] ?? ''),
            $status['payment_type'] ?? null,
            $status['transaction_id'] ?? null
        );

        return $result['applied'];
    }

    /**
     * Terapkan status transaksi Midtrans (mentah) ke sebuah pesanan.
     *
     * @return array{applied: bool, duplicate: bool, payment: string, order: string|null}
     */
    public function apply(
        Order $order,
        string $transactionStatus,
        string $fraudStatus = '',
        ?string $paymentType = null,
        ?string $transactionId = null
    ): array {
        $mapping = $this->mapStatus($transactionStatus, $fraudStatus);
        $duplicate = false;

        DB::transaction(function () use (
            $order,
            $transactionId,
            $paymentType,
            $mapping,
            &$duplicate
        ) {
            $freshOrder = Order::whereKey($order->id_order)->lockForUpdate()->firstOrFail();

            $payment = Payment::where('id_order', $freshOrder->id_order)
                ->orderByDesc('dibuat_pada')
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                $payment = Payment::create([
                    'id_payment' => (string) Str::uuid(),
                    'id_order' => $freshOrder->id_order,
                    'provider' => 'midtrans',
                    'provider_transaction_id' => $transactionId,
                    'metode_pembayaran' => $paymentType,
                    'jumlah_pembayaran' => $freshOrder->total_pembayaran,
                    'status_pembayaran' => 'menunggu',
                    'payment_url' => null,
                    'payment_reference' => null,
                    'expires_at' => null,
                    'paid_at' => null,
                ]);
            }

            $lateSettlement = $mapping['paid']
                && in_array($payment->status_pembayaran, ['kedaluwarsa', 'dibatalkan'], true)
                && in_array($freshOrder->status_pesanan, ['menunggu_pembayaran', 'dibatalkan'], true);

            if (in_array($payment->status_pembayaran, self::TERMINAL_PAYMENT_STATUSES, true)
                && ! $lateSettlement) {
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

            if ($mapping['order'] !== null && $freshOrder->status_pesanan !== $mapping['order']) {
                $this->orderService->changeStatus(
                    $freshOrder,
                    $mapping['order'],
                    null,
                    "Pembaruan otomatis dari Midtrans: {$mapping['payment']}."
                );
            }
        });

        return [
            'applied' => ! $duplicate,
            'duplicate' => $duplicate,
            'payment' => $mapping['payment'],
            'order' => $mapping['order'],
        ];
    }

    /**
     * Pemetaan status transaksi Midtrans ke status internal aplikasi.
     *
     * @return array{payment: string, order: string|null, paid: bool}
     */
    public function mapStatus(string $transactionStatus, string $fraudStatus = ''): array
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
            default => ['payment' => 'menunggu', 'order' => null, 'paid' => false],
        };
    }
}
