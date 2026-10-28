<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /**
     * @param  array<int, array{id_product: string, harga: float|int, jumlah: int}>  $eligibleLines
     * @return array{
     *     voucher: Voucher,
     *     diskon: float,
     *     id_product_peruntukan: string|null,
     *     nama_produk_peruntukan: string|null,
     *     subtotal_peruntukan: float|null
     * }
     *
     * @throws ValidationException
     */
    public function resolve(
        string $kodeVoucher,
        float $subtotal,
        string $idUser,
        array $eligibleLines = []
    ): array {
        $voucher = Voucher::where('kode_voucher', $kodeVoucher)->first();

        if (! $voucher) {
            $this->invalid('Kode voucher tidak ditemukan.');
        }

        if ($voucher->status_voucher !== 'aktif') {
            $this->invalid('Voucher tidak aktif.');
        }

        $now = Carbon::now();

        if ($voucher->tanggal_mulai > $now) {
            $this->invalid('Voucher belum berlaku.');
        }

        if ($voucher->tanggal_selesai < $now) {
            $this->invalid('Voucher sudah kedaluwarsa.');
        }

        if ($voucher->id_product_peruntukan === null) {
            if ($subtotal < (float) $voucher->minimal_pembelian) {
                $minimal = number_format((float) $voucher->minimal_pembelian, 0, ',', '.');

                $this->invalid("Minimal pembelian untuk voucher ini adalah Rp{$minimal}.");
            }

            if (! $this->passesQuota($voucher, $idUser)) {
                $this->invalid('Kuota voucher sudah habis atau sudah Anda gunakan.');
            }

            return [
                'voucher' => $voucher,
                'diskon' => $this->calculateDiscount($voucher, $subtotal),
                'id_product_peruntukan' => null,
                'nama_produk_peruntukan' => null,
                'subtotal_peruntukan' => null,
            ];
        }

        $voucher->loadMissing('productPeruntukan');
        $product = $voucher->productPeruntukan;
        $namaProduk = $product?->nama_produk;

        if (! $product || $product->status_produk !== 'aktif') {
            $this->invalid(
                $namaProduk !== null
                    ? "Voucher hanya berlaku untuk produk {$namaProduk}."
                    : 'Produk peruntukan voucher tidak ditemukan.'
            );
        }

        $subtotalPeruntukan = 0.0;

        foreach ($eligibleLines as $line) {
            if (($line['id_product'] ?? null) !== $voucher->id_product_peruntukan) {
                continue;
            }

            $harga = (float) ($line['harga'] ?? 0);
            $jumlah = (int) ($line['jumlah'] ?? 0);

            if ($harga <= 0 || $jumlah <= 0) {
                continue;
            }

            $subtotalPeruntukan += $harga * $jumlah;
        }

        $subtotalPeruntukan = round($subtotalPeruntukan, 2);

        if ($subtotalPeruntukan <= 0) {
            $this->invalid("Voucher hanya berlaku untuk produk {$namaProduk}.");
        }

        if ($subtotalPeruntukan < (float) $voucher->minimal_pembelian) {
            $minimal = number_format((float) $voucher->minimal_pembelian, 0, ',', '.');

            $this->invalid(
                "Minimal belanja untuk produk {$namaProduk} adalah Rp {$minimal}."
            );
        }

        if (! $this->passesQuota($voucher, $idUser)) {
            $this->invalid('Kuota voucher sudah habis atau sudah Anda gunakan.');
        }

        return [
            'voucher' => $voucher,
            'diskon' => $this->calculateDiscount($voucher, $subtotalPeruntukan),
            'id_product_peruntukan' => $voucher->id_product_peruntukan,
            'nama_produk_peruntukan' => $namaProduk,
            'subtotal_peruntukan' => $subtotalPeruntukan,
        ];
    }

    public function passesQuota(Voucher $voucher, string $idUser): bool
    {
        $statuses = ['menunggu', 'digunakan'];

        if ($voucher->kuota_total !== null) {
            $totalUsed = VoucherUsage::where('id_voucher', $voucher->id_voucher)
                ->whereIn('status_penggunaan', $statuses)
                ->count();

            if ($totalUsed >= $voucher->kuota_total) {
                return false;
            }
        }

        $userUsed = VoucherUsage::where('id_voucher', $voucher->id_voucher)
            ->where('id_user', $idUser)
            ->whereIn('status_penggunaan', $statuses)
            ->count();

        return $userUsed < $voucher->kuota_per_pengguna;
    }

    public function calculateDiscount(Voucher $voucher, float $subtotal): float
    {
        if ($voucher->tipe_diskon === 'persentase') {
            $diskon = $subtotal * ((float) $voucher->nilai_diskon / 100);

            if ($voucher->maksimal_diskon !== null) {
                $diskon = min($diskon, (float) $voucher->maksimal_diskon);
            }

            return round(min($diskon, $subtotal), 2);
        }

        return round(min((float) $voucher->nilai_diskon, $subtotal), 2);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages([
            'kode_voucher' => [$message],
        ]);
    }
}
