<?php

namespace App\Services;

use App\Models\Order;
use App\Support\CaBundle;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$clientKey = (string) config('services.midtrans.client_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Sebagian lingkungan (mis. Windows tanpa CA bundle sistem) gagal
        // memverifikasi SSL saat memanggil Midtrans. Pakai cacert.pem bawaan
        // SDK bila PHP belum mengonfigurasi CA-nya sendiri.
        if (($cacert = CaBundle::path()) !== null) {
            Config::$curlOptions[CURLOPT_CAINFO] = $cacert;
        }

        // SDK mengakses Config::$curlOptions[CURLOPT_HTTPHEADER] tanpa isset();
        // kunci ini harus selalu ada agar tidak memicu peringatan PHP 8.
        Config::$curlOptions[CURLOPT_HTTPHEADER] = Config::$curlOptions[CURLOPT_HTTPHEADER] ?? [];
    }

    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    public function snapScriptUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    public function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production');
    }

    /**
     * Create a Snap transaction for the given order.
     *
     * @return array{token: string|null, redirect_url: string|null, raw: mixed}
     */
    public function createSnapTransaction(Order $order): array
    {
        $params = [
            'transaction_details' => [
                'order_id' => $order->nomor_pesanan,
                'gross_amount' => (int) round((float) $order->total_pembayaran),
            ],
            'customer_details' => $this->customerDetails($order),
            // Gunakan sisa waktu dari batas yang dibuat saat checkout agar
            // pembuatan token tidak memulai ulang masa pembayaran 15 menit.
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => max(
                    1,
                    intdiv(
                        max(0, ($order->kedaluwarsa_pada ?? now()->addMinutes(OrderService::PAYMENT_WINDOW_MINUTES))
                            ->getTimestamp() - now()->getTimestamp()),
                        60
                    )
                ),
            ],
            // Arahkan pengguna kembali ke halaman hasil pembayaran milik sendiri
            // agar tidak berakhir di example.com bawaan akun Midtrans. Status
            // pembayaran TETAP diverifikasi via webhook/notification, bukan dari
            // parameter URL ini.
            'callbacks' => $this->redirectCallbacks(),
        ];

        $result = Snap::createTransaction($params);

        return [
            'token' => $result->token ?? null,
            'redirect_url' => $result->redirect_url ?? null,
            'raw' => $result,
        ];
    }

    /**
     * Read the notification payload from the request body and fetch the
     * authoritative transaction status from Midtrans (official SDK mechanism).
     */
    public function notification(): Notification
    {
        return new Notification;
    }

    /**
     * Query status transaksi terkini langsung ke API Midtrans.
     *
     * Dipakai sebagai fallback/sync manual bila notifikasi webhook tidak
     * diterima (mis. URL notifikasi di dashboard Midtrans belum diarahkan).
     *
     * @return array{order_id: string, transaction_status: string, fraud_status: string, payment_type: string|null, transaction_id: string|null, gross_amount: float, status_code: string}
     */
    public function transactionStatus(Order|string $order): array
    {
        $orderId = $order instanceof Order ? (string) $order->nomor_pesanan : (string) $order;
        $response = Transaction::status($orderId);

        return [
            'order_id' => (string) ($response->order_id ?? $orderId),
            'transaction_status' => (string) ($response->transaction_status ?? ''),
            'fraud_status' => (string) ($response->fraud_status ?? ''),
            'payment_type' => $response->payment_type ?? null,
            'transaction_id' => $response->transaction_id ?? null,
            'gross_amount' => (float) ($response->gross_amount ?? 0),
            'status_code' => (string) ($response->status_code ?? ''),
        ];
    }

    /**
     * URL pengalihan hasil pembayaran yang dikirim ke Snap.
     *
     * @return array{finish: string, unfinish: string, error: string}
     */
    public function redirectCallbacks(): array
    {
        return [
            'finish' => (string) config('services.midtrans.finish_redirect_url'),
            'unfinish' => (string) config('services.midtrans.unfinish_redirect_url'),
            'error' => (string) config('services.midtrans.error_redirect_url'),
        ];
    }

    /**
     * Verify the Midtrans signature_key using the server key.
     *
     * signature = sha512(order_id + status_code + gross_amount + server_key)
     */
    public function verifySignature(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string, mixed>
     */
    private function customerDetails(Order $order): array
    {
        $order->loadMissing('user');

        $details = [
            'first_name' => $order->nama_penerima,
            'phone' => $order->nomor_telepon_penerima,
            'shipping_address' => [
                'first_name' => $order->nama_penerima,
                'phone' => $order->nomor_telepon_penerima,
                'address' => $order->alamat_pengiriman,
            ],
        ];

        if ($order->user && $order->user->email) {
            $details['email'] = $order->user->email;
        }

        return $details;
    }
}
