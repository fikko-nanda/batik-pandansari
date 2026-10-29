<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\VoucherUsage;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

class OrderService
{
    public const STATUSES = [
        'menunggu_pembayaran',
        'dibayar',
        'diproses',
        'dikirim',
        'selesai',
        'dibatalkan',
    ];

    /**
     * Batas waktu pembayaran sejak pesanan dibuat. Berlaku untuk seluruh
     * metode pembayaran Midtrans.
     */
    public const PAYMENT_WINDOW_MINUTES = 15;

    /**
     * Tarif PPh yang dibebankan ke pembeli.
     */
    public const PPH_PERCENTAGE = 11.0;

    public function __construct(
        private readonly StockReservationService $reservations,
        private readonly OrderMailService $orderMail,
        private readonly WhatsAppService $whatsApp
    ) {}

    /**
     * Waktu kedaluwarsa pembayaran untuk sebuah pesanan.
     */
    public function expiryFrom(?DateTimeInterface $base = null): CarbonImmutable
    {
        $base = $base ?? now();

        return CarbonImmutable::instance($base)->addMinutes(self::PAYMENT_WINDOW_MINUTES);
    }

    /**
     * PPh dihitung dari nilai setelah diskon dan dibulatkan ke rupiah terdekat.
     */
    public function pphFrom(float $dasarPengenaan): float
    {
        return (float) round(max(0, $dasarPengenaan) * self::PPH_PERCENTAGE / 100);
    }

    /**
     * Apakah pesanan menunggu pembayaran sudah melewati batas waktu.
     */
    public function isExpired(Order $order): bool
    {
        return $order->status_pesanan === 'menunggu_pembayaran'
            && $order->kedaluwarsa_pada !== null
            && $order->kedaluwarsa_pada->isPast();
    }

    /**
     * Batalkan pesanan yang menunggu pembayaran bila batas waktu sudah lewat,
     * dengan memverifikasi ulang status DI DALAM lock.
     *
     * Mencegah race: pesanan yang sudah dibayar (mis. notifikasi Midtrans
     * settlement masuk tepat di antara query dan penguncian) TIDAK boleh
     * dibatalkan oleh penjadwal. Baris pembayaran yang masih menunggu ikut
     * ditandai kedaluwarsa agar endpoint detail tidak menampilkan status basi.
     *
     * @return bool true bila pesanan benar-benar dibatalkan
     */
    public function expireIfUnpaid(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id_order)->lockForUpdate()->first();

            if (! $locked || $locked->status_pesanan !== 'menunggu_pembayaran') {
                return false;
            }

            if ($locked->kedaluwarsa_pada === null || ! $locked->kedaluwarsa_pada->isPast()) {
                return false;
            }

            Payment::where('id_order', $locked->id_order)
                ->where('status_pembayaran', 'menunggu')
                ->update(['status_pembayaran' => 'kedaluwarsa']);

            $this->changeStatus(
                $locked,
                'dibatalkan',
                null,
                'Pembayaran melewati batas waktu '.self::PAYMENT_WINDOW_MINUTES.' menit.'
            );

            return true;
        });
    }

    public function generateNomorPesanan(int $offset = 0): string
    {
        $prefix = 'ORD-'.now()->format('Ymd').'-';

        $last = Order::where('nomor_pesanan', 'like', $prefix.'%')
            ->orderByDesc('nomor_pesanan')
            ->value('nomor_pesanan');

        $sequence = ($last ? ((int) substr($last, strlen($prefix))) : 0) + 1 + $offset;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Apply an order status transition: record history, cancel voucher usage
     * and restore stock when the order is cancelled.
     */
    public function changeStatus(
        Order $order,
        string $newStatus,
        ?string $changedBy = null,
        ?string $catatan = null
    ): Order {
        $transisi = false;

        $fresh = DB::transaction(function () use ($order, $newStatus, $changedBy, $catatan, &$transisi) {
            $order = Order::whereKey($order->id_order)->lockForUpdate()->firstOrFail();

            $statusSebelumnya = $order->status_pesanan;

            if ($statusSebelumnya === $newStatus) {
                return $order;
            }

            // Tandai bahwa ini transisi nyata (bukan pemanggilan berulang dengan
            // status yang sama) agar email tidak terkirim duplikat.
            $transisi = true;

            if ($newStatus === 'dibatalkan') {
                if ($statusSebelumnya === 'menunggu_pembayaran') {
                    $this->reservations->release($order);
                } elseif ($statusSebelumnya === 'dibayar') {
                    $hasStockMovement = InventoryMovement::where('referensi', $order->nomor_pesanan)
                        ->where('jenis_pergerakan', 'keluar')
                        ->exists();

                    if ($hasStockMovement) {
                        $this->restoreStock($order);
                    }
                }

                VoucherUsage::where('id_order', $order->id_order)
                    ->whereIn('status_penggunaan', ['menunggu', 'digunakan'])
                    ->update(['status_penggunaan' => 'dibatalkan']);
            }

            if ($newStatus === 'dibayar') {
                if (in_array($statusSebelumnya, ['menunggu_pembayaran', 'dibatalkan'], true)) {
                    $kekurangan = $statusSebelumnya === 'menunggu_pembayaran'
                        ? $this->reservations->commit($order, $changedBy)
                        : $this->reservations->commitAfterRelease($order, $changedBy);

                    if ($kekurangan !== []) {
                        OrderStatusHistory::create([
                            'id_order_status_history' => (string) Str::uuid(),
                            'id_order' => $order->id_order,
                            'status_sebelumnya' => $statusSebelumnya,
                            'status_baru' => $newStatus,
                            'catatan' => 'PERINGATAN: pembayaran diterima tetapi stok tidak mencukupi; tidak ada stok yang dikurangi dan perlu tindak lanjut admin/refund: '.json_encode($kekurangan),
                            'diubah_oleh' => $changedBy,
                            'waktu_perubahan' => now(),
                        ]);
                    }
                }

                VoucherUsage::where('id_order', $order->id_order)
                    ->where('status_penggunaan', 'menunggu')
                    ->update([
                        'status_penggunaan' => 'digunakan',
                        'digunakan_pada' => now(),
                    ]);
            }

            OrderStatusHistory::create([
                'id_order_status_history' => (string) Str::uuid(),
                'id_order' => $order->id_order,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => $newStatus,
                'catatan' => $catatan,
                'diubah_oleh' => $changedBy,
                'waktu_perubahan' => now(),
            ]);

            $order->update(['status_pesanan' => $newStatus]);

            return $order->fresh();
        });

        // Kirim notifikasi hanya saat transisi nyata dan setelah response,
        // agar layanan eksternal tidak menunda pembaruan status.
        if ($transisi) {
            if ($newStatus === 'dibayar') {
                defer(function () use ($fresh) {
                    $this->orderMail->sendPaid($fresh);
                    $this->whatsApp->sendOrderPaid($fresh);
                });
            } elseif ($newStatus === 'dibatalkan') {
                defer(fn () => $this->orderMail->sendCancelled($fresh));
            }
        }

        return $fresh;
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items->sortBy('id_variant') as $item) {
            $variant = ProductVariant::whereKey($item->id_variant)->lockForUpdate()->first();

            if (! $variant) {
                continue;
            }

            $stokSebelum = (int) $variant->stok;
            $stokSesudah = $stokSebelum + (int) $item->jumlah;

            $variant->update(['stok' => $stokSesudah]);

            InventoryMovement::create([
                'id_inventory_movement' => (string) Str::uuid(),
                'id_variant' => $variant->id_variant,
                'id_user' => null,
                'jenis_pergerakan' => 'masuk',
                'jumlah' => (int) $item->jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'referensi' => $order->nomor_pesanan,
                'catatan' => 'Pengembalian stok pembatalan pesanan',
                'waktu_pergerakan' => now(),
            ]);
        }
    }
}
