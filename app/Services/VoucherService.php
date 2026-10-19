<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /**
     * Validate a voucher code against a subtotal for a given user and return
     * the voucher together with the discount amount.
     *
     * @return array{voucher: Voucher, diskon: float}
     *
     * @throws ValidationException
     */
    public function resolve(string $kodeVoucher, float $subtotal, string $idUser): array
    {
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
