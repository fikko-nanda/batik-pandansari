<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Menangani reservasi dan perubahan stok saat pembayaran pesanan.
 *
 * Model stok:
 * - product_variants.stok           : stok fisik/total yang dimiliki.
 * - product_variants.stok_dipesan   : jumlah unit yang sudah direservasi.
 *
 * Stok fisik baru berkurang saat pembayaran terkonfirmasi.
 */
class StockReservationService
{
    public function reserve(Order $order): void
    {
        if ($order->status_pesanan !== 'menunggu_pembayaran') {
            throw new \LogicException('Stok hanya dapat direservasi untuk pesanan yang menunggu pembayaran.');
        }

        if ($order->stok_direservasi) {
            return;
        }

        $items = $order->items->sortBy('id_variant');
        $jumlahPerVariant = $items
            ->groupBy('id_variant')
            ->map(fn ($variantItems): int => (int) $variantItems->sum('jumlah'))
            ->all();
        ksort($jumlahPerVariant);

        $variants = [];

        foreach ($jumlahPerVariant as $idVariant => $jumlah) {
            $variant = ProductVariant::whereKey($idVariant)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'cart' => ['Varian produk tidak tersedia untuk dibeli.'],
                ]);
            }

            $tersedia = max(0, (int) $variant->stok - (int) $variant->stok_dipesan);

            if ($jumlah > $tersedia) {
                throw ValidationException::withMessages([
                    'cart' => ["Stok tidak mencukupi untuk {$variant->sku}. Stok tersedia: {$tersedia}."],
                ]);
            }

            $variants[$idVariant] = [$variant, $jumlah];
        }

        foreach ($variants as [$variant, $jumlah]) {
            $variant->update([
                'stok_dipesan' => (int) $variant->stok_dipesan + $jumlah,
            ]);
        }

        $order->update(['stok_direservasi' => true]);
    }

    /**
     * Lepas reservasi stok pesanan (pembatalan / kedaluwarsa sebelum bayar).
     *
     * Idempotent terhadap pemanggilan berulang untuk pesanan yang sama selama
     * status pesanan masih menandai reservasi aktif — dijaga oleh pemanggil.
     */
    public function release(Order $order): void
    {
        if (! $order->stok_direservasi) {
            return;
        }

        foreach ($order->items->sortBy('id_variant') as $item) {
            $variant = ProductVariant::whereKey($item->id_variant)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                continue;
            }

            $jumlah = (int) $item->jumlah;
            $dipesan = (int) $variant->stok_dipesan;

            // Lindungi dari stok_dipesan negatif bila data sempat tidak konsisten.
            $dipesanBaru = max(0, $dipesan - $jumlah);

            $variant->update(['stok_dipesan' => $dipesanBaru]);
        }

        $order->update(['stok_direservasi' => false]);
    }

    /**
     * Tetapkan pengurangan stok permanen karena pembayaran berhasil.
     *
     * Mengurangi stok fisik secara atomik jika seluruh item masih tersedia.
     * Reservasi lama, bila ada, dilepas sekaligus.
     *
     * @return array<int, array{id_variant: string, sku: string, diminta: int, tersedia: int}>
     */
    public function commit(Order $order, ?string $idUser = null): array
    {
        return $this->commitOrder($order, $idUser, (bool) $order->stok_direservasi);
    }

    /**
     * Tetapkan pengurangan stok permanen untuk pesanan yang pembayarannya
     * terkonfirmasi SETELAH reservasi dilepas (mis. notifikasi terlambat).
     *
     * Aman terhadap overselling: bila stok fisik tidak mencukupi, TIDAK
     * dipaksa negatif — dikembalikan sebagai daftar kekurangan untuk
     * ditindaklanjuti admin (restock / refund).
     *
     * @return array<int, array{id_variant: string, sku: string, diminta: int, tersedia: int}>
     */
    public function commitAfterRelease(Order $order, ?string $idUser = null): array
    {
        return $this->commitOrder($order, $idUser, false);
    }

    /**
     * @return array<int, array{id_variant: string, sku: string, diminta: int, tersedia: int}>
     */
    private function commitOrder(Order $order, ?string $idUser, bool $releaseReservation): array
    {
        $items = $order->items->sortBy('id_variant');
        $jumlahPerVariant = $items
            ->groupBy('id_variant')
            ->map(fn ($variantItems): int => (int) $variantItems->sum('jumlah'))
            ->all();
        ksort($jumlahPerVariant);

        $variants = [];
        $kekurangan = [];

        foreach ($jumlahPerVariant as $idVariant => $jumlah) {
            $variant = ProductVariant::whereKey($idVariant)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                $item = $items->firstWhere('id_variant', $idVariant);
                $kekurangan[] = [
                    'id_variant' => (string) $idVariant,
                    'sku' => (string) $item->sku,
                    'diminta' => $jumlah,
                    'tersedia' => 0,
                ];

                continue;
            }

            $variants[$idVariant] = $variant;
            $stokSebelum = (int) $variant->stok;

            if ($jumlah > $stokSebelum) {
                $kekurangan[] = [
                    'id_variant' => (string) $idVariant,
                    'sku' => $variant->sku,
                    'diminta' => $jumlah,
                    'tersedia' => $stokSebelum,
                ];
            }
        }

        if ($kekurangan === []) {
            foreach ($jumlahPerVariant as $idVariant => $jumlah) {
                $variant = $variants[$idVariant];
                $stokSebelum = (int) $variant->stok;
                $stokSesudah = $stokSebelum - $jumlah;

                $variant->update(['stok' => $stokSesudah]);

                InventoryMovement::create([
                    'id_inventory_movement' => (string) Str::uuid(),
                    'id_variant' => $variant->id_variant,
                    'id_user' => $idUser,
                    'jenis_pergerakan' => 'keluar',
                    'jumlah' => $jumlah,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'referensi' => $order->nomor_pesanan,
                    'catatan' => 'Pengurangan stok pesanan dibayar',
                    'waktu_pergerakan' => now(),
                ]);
            }
        }

        if ($releaseReservation) {
            foreach ($variants as $idVariant => $variant) {
                $variant->update([
                    'stok_dipesan' => max(
                        0,
                        (int) $variant->stok_dipesan - $jumlahPerVariant[$idVariant]
                    ),
                ]);
            }
        }

        $order->update(['stok_direservasi' => false]);

        return $kekurangan;
    }
}
