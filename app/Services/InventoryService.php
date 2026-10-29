<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Operasi perubahan stok yang tercatat rapi di inventory_movements.
 *
 * Setiap perubahan stok WAJIB disertai pencatatan pergerakan agar angka
 * pada product_variants.stok dan riwayat di inventory_movements tidak
 * pernah berbeda (sesuai pedoman di dokumentasi api.txt).
 */
class InventoryService
{
    /**
     * Jenis pergerakan yang sudah dipakai kode lama (checkout & pembatalan).
     */
    public const JENIS_EXISTING = ['masuk', 'keluar'];

    /**
     * Jenis pergerakan khusus penyesuaian inventaris oleh admin.
     */
    public const JENIS_PENYESUAIAN = ['penyesuaian_masuk', 'penyesuaian_keluar'];

    /**
     * Semua nilai yang valid untuk kolom inventory_movements.jenis_pergerakan.
     */
    public const SEMUA_JENIS = [
        'masuk',
        'keluar',
        'penyesuaian_masuk',
        'penyesuaian_keluar',
    ];

    /**
     * Menerapkan penyesuaian stok satu varian dan mencatat pergerakannya.
     *
     * Dijalankan dalam satu transaksi database dengan row lock pada baris
     * varian sehingga beberapa permintaan yang mengubah stok varian yang
     * sama tidak saling menimpa (race condition).
     *
     * Penyesuaian yang menyebabkan stok negatif ditolak dengan 422 dan
     * tidak menyisakan perubahan apa pun (transaksi dibatalkan).
     *
     * @return array{variant: ProductVariant, movement: InventoryMovement}
     *
     * @throws ValidationException
     */
    public function adjustStock(
        string $idVariant,
        string $jenisPenyesuaian,
        int $jumlah,
        string $alasan,
        ?string $idUser = null,
        ?string $referensi = null
    ): array {
        return DB::transaction(function () use (
            $idVariant,
            $jenisPenyesuaian,
            $jumlah,
            $alasan,
            $idUser,
            $referensi
        ) {
            $variant = ProductVariant::whereKey($idVariant)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                throw ValidationException::withMessages([
                    'id_variant' => ['Varian produk tidak ditemukan.'],
                ]);
            }

            $stokSebelum = (int) $variant->stok;

            if ($jenisPenyesuaian === 'masuk') {
                $stokSesudah = $stokSebelum + $jumlah;
                $jenisPergerakan = 'penyesuaian_masuk';
            } else {
                $tersedia = max(0, $stokSebelum - (int) $variant->stok_dipesan);

                if ($jumlah > $tersedia) {
                    throw ValidationException::withMessages([
                        'jumlah' => [
                            "Stok tidak mencukupi untuk {$variant->sku}. Stok tersedia: {$tersedia}.",
                        ],
                    ]);
                }

                $stokSesudah = $stokSebelum - $jumlah;
                $jenisPergerakan = 'penyesuaian_keluar';
            }

            $variant->update(['stok' => $stokSesudah]);

            $movement = InventoryMovement::create([
                'id_inventory_movement' => (string) Str::uuid(),
                'id_variant' => $variant->id_variant,
                'id_user' => $idUser,
                'jenis_pergerakan' => $jenisPergerakan,
                'jumlah' => $jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'referensi' => $referensi,
                'catatan' => $alasan,
                'waktu_pergerakan' => now(),
            ]);

            return [
                'variant' => $variant->fresh(),
                'movement' => $movement,
            ];
        });
    }
}
