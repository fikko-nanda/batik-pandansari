<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Reservasi stok pesanan yang menunggu pembayaran (maksimal 10 menit).
 *
 * Model stok:
 * - product_variants.stok           : stok fisik/total yang dimiliki.
 * - product_variants.stok_dipesan   : unit yang sedang direservasi pesanan
 *                                     menunggu pembayaran.
 * - stok tersedia                   : stok - stok_dipesan.
 *
 * Alur:
 * - reserve() : saat checkout, pastikan stok tersedia cukup lalu tambah
 *               stok_dipesan. Stok fisik TIDAK dikurangi.
 * - release() : saat pesanan dibatalkan/kedaluwarsa sebelum dibayar, kurangi
 *               stok_dipesan kembali. Idempotent (aman dipanggil berulang).
 * - commit()  : saat pembayaran terkonfirmasi, kurangi stok fisik DAN
 *               stok_dipesan sekaligus, catat pergerakan 'keluar'.
 *
 * Idempotensi bersumber dari status pesanan (menunggu_pembayaran vs
 * dibayar/dibatalkan) yang diperiksa pemanggil di dalam transaksi dengan row
 * lock; store/commit/release di sini juga mengunci baris varian.
 */
class StockReservationService
{
    /**
     * Reservasi stok untuk seluruh item pesanan.
     *
     * @param  array<int, array{id_variant: string, jumlah: int}>  $lines
     *
     * @throws ValidationException
     */
    public function reserve(array $lines): void
    {
        foreach ($lines as $line) {
            $variant = ProductVariant::whereKey($line['id_variant'])
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'cart' => ['Varian produk tidak tersedia untuk dibeli.'],
                ]);
            }

            $jumlah = (int) $line['jumlah'];
            $tersedia = (int) $variant->stok - (int) $variant->stok_dipesan;

            if ($jumlah > $tersedia) {
                throw ValidationException::withMessages([
                    'cart' => ["Stok tidak mencukupi untuk {$variant->sku}. Stok tersedia: {$tersedia}."],
                ]);
            }

            $variant->update([
                'stok_dipesan' => (int) $variant->stok_dipesan + $jumlah,
            ]);
        }
    }

    /**
     * Lepas reservasi stok pesanan (pembatalan / kedaluwarsa sebelum bayar).
     *
     * Idempotent terhadap pemanggilan berulang untuk pesanan yang sama selama
     * status pesanan masih menandai reservasi aktif — dijaga oleh pemanggil.
     */
    public function release(Order $order): void
    {
        foreach ($order->items as $item) {
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
    }

    /**
     * Tetapkan pengurangan stok permanen karena pembayaran berhasil.
     *
     * Mengurangi stok fisik sekaligus melepas reservasi, mencatat pergerakan
     * 'keluar'. Hanya dijalankan ketika order belum pernah dikonversi
     * (dijaga status pesanan oleh pemanggil + lock di sini).
     */
    public function commit(Order $order, ?string $idUser = null): void
    {
        foreach ($order->items as $item) {
            $variant = ProductVariant::whereKey($item->id_variant)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                continue;
            }

            $jumlah = (int) $item->jumlah;
            $stokSebelum = (int) $variant->stok;
            $stokSesudah = max(0, $stokSebelum - $jumlah);
            $dipesanBaru = max(0, (int) $variant->stok_dipesan - $jumlah);

            $variant->update([
                'stok' => $stokSesudah,
                'stok_dipesan' => $dipesanBaru,
            ]);

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
        $kekurangan = [];

        foreach ($order->items as $item) {
            $variant = ProductVariant::whereKey($item->id_variant)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                continue;
            }

            $jumlah = (int) $item->jumlah;
            $stokSebelum = (int) $variant->stok;

            if ($jumlah > $stokSebelum) {
                $kekurangan[] = [
                    'id_variant' => $variant->id_variant,
                    'sku' => $variant->sku,
                    'diminta' => $jumlah,
                    'tersedia' => $stokSebelum,
                ];

                continue;
            }

            $stokSesudah = $stokSebelum - $jumlah;

            $variant->update([
                'stok' => $stokSesudah,
                'stok_dipesan' => max(0, (int) $variant->stok_dipesan - $jumlah),
            ]);

            InventoryMovement::create([
                'id_inventory_movement' => (string) Str::uuid(),
                'id_variant' => $variant->id_variant,
                'id_user' => $idUser,
                'jenis_pergerakan' => 'keluar',
                'jumlah' => $jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'referensi' => $order->nomor_pesanan,
                'catatan' => 'Pengurangan stok pesanan dibayar (reservasi sudah dilepas)',
                'waktu_pergerakan' => now(),
            ]);
        }

        return $kekurangan;
    }
}
