<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\VoucherUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        return DB::transaction(function () use ($order, $newStatus, $changedBy, $catatan) {
            $order = Order::whereKey($order->id_order)->lockForUpdate()->firstOrFail();

            $statusSebelumnya = $order->status_pesanan;

            if ($statusSebelumnya === $newStatus) {
                return $order;
            }

            if ($newStatus === 'dibatalkan' && in_array($statusSebelumnya, ['menunggu_pembayaran', 'dibayar'], true)) {
                $this->restoreStock($order);

                VoucherUsage::where('id_order', $order->id_order)
                    ->whereIn('status_penggunaan', ['menunggu', 'digunakan'])
                    ->update(['status_penggunaan' => 'dibatalkan']);
            }

            if ($newStatus === 'dibayar') {
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
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
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
