<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

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
        if (empty(ini_get('curl.cainfo')) && empty(ini_get('openssl.cafile'))) {
            $cacert = dirname((new \ReflectionClass(Config::class))->getFileName()).'/../data/cacert.pem';

            if (is_file($cacert)) {
                Config::$curlOptions[CURLOPT_CAINFO] = $cacert;
            }
        }

        // SDK mengakses Config::$curlOptions[CURLOPT_HTTPHEADER] tanpa isset();
        // kunci ini harus selalu ada agar tidak memicu peringatan PHP 8.
        Config::$curlOptions[CURLOPT_HTTPHEADER] = Config::$curlOptions[CURLOPT_HTTPHEADER] ?? [];
    }

    public function clientKey(): ?string
    {
        return config('services.midtrans.client_key');
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
