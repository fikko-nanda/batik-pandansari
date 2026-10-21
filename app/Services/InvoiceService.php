<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\VoucherUsage;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Membuat invoice pesanan dalam format PDF memakai dompdf/dompdf.
 *
 * Sumber data 100% dari database (orders, order_items, payments,
 * voucher_usages) — bukan dikirim dari frontend — sehingga angka pada invoice
 * selalu konsisten dengan total pembayaran yang tersimpan.
 */
class InvoiceService
{
    /**
     * Membangun binary PDF invoice untuk sebuah pesanan.
     */
    public function build(Order $order): string
    {
        $order->loadMissing(['items', 'user', 'payments', 'voucherUsages.voucher']);

        $payment = $order->payments
            ->sortByDesc('dibuat_pada')
            ->first();

        $voucherUsage = $order->voucherUsages
            ->whereIn('status_penggunaan', ['menunggu', 'digunakan'])
            ->first();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', sys_get_temp_dir());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderHtml($order, $payment, $voucherUsage), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Nama file invoice yang aman dipakai sebagai lampiran.
     */
    public function filename(Order $order): string
    {
        return 'invoice-'.$order->nomor_pesanan.'.pdf';
    }

    private function renderHtml(Order $order, ?Payment $payment, ?VoucherUsage $voucherUsage): string
    {
        $itemRows = '';

        foreach ($order->items as $item) {
            $varian = trim(implode(' / ', array_filter([$item->warna, $item->ukuran])));

            $itemRows .= '<tr>'
                .'<td>'.$this->esc($item->nama_produk)
                .($varian !== '' ? '<div class="muted">'.$this->esc($varian).'</div>' : '')
                .'</td>'
                .'<td class="muted">'.$this->esc($item->sku).'</td>'
                .'<td class="num">'.(int) $item->jumlah.'</td>'
                .'<td class="num">'.$this->rupiah($item->harga_satuan).'</td>'
                .'<td class="num">'.$this->rupiah($item->subtotal).'</td>'
                .'</tr>';
        }

        $diskonRow = '';
        $diskon = (float) $order->diskon;

        if ($diskon > 0) {
            $label = 'Diskon';
            if ($voucherUsage && $voucherUsage->voucher) {
                $label .= ' ('.$voucherUsage->voucher->kode_voucher.')';
            }

            $diskonRow = '<tr><td class="label">'.$this->esc($label).'</td>'
                .'<td class="num">- '.$this->rupiah($diskon).'</td></tr>';
        }

        $statusBayar = $payment?->status_pembayaran ?? 'menunggu';
        $metode = $payment?->metode_pembayaran;
        $dibayarPada = $payment?->paid_at;

        $metodeRow = $metode
            ? '<tr><td class="label">Metode pembayaran</td><td>'.$this->esc($metode).'</td></tr>'
            : '';

        $waktuBayarRow = $dibayarPada
            ? '<tr><td class="label">Waktu pembayaran</td><td>'.$this->esc($dibayarPada->format('d-m-Y H:i')).'</td></tr>'
            : '';

        $header = <<<'HTML'
<div class="header">
    <div class="brand">Batik Pandansari</div>
    <div class="brand-sub">Invoice Pesanan</div>
</div>
HTML;

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Invoice {$this->esc($order->nomor_pesanan)}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #142B4A; margin: 0; }
    .header { background: #142B4A; color: #FFFCF5; padding: 14px 16px; }
    .brand { font-size: 18px; font-weight: bold; letter-spacing: 0.5px; }
    .brand-sub { font-size: 11px; color: #C7A96B; margin-top: 2px; }
    .meta { width: 100%; border-collapse: collapse; margin: 14px 0; }
    .meta td { vertical-align: top; padding: 0 8px 0 0; }
    .meta .title { color: #C7A96B; font-weight: bold; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; }
    h2 { font-size: 11px; margin: 14px 0 6px 0; border-bottom: 1px solid #C7A96B; padding-bottom: 3px; }
    table.items { width: 100%; border-collapse: collapse; }
    table.items th, table.items td { border: 1px solid #d8cfba; padding: 5px 6px; text-align: left; }
    table.items th { background: #FFFCF5; color: #142B4A; }
    td.num, th.num { text-align: right; }
    td.label { font-weight: bold; }
    .muted { color: #6b7280; font-size: 9px; }
    table.total { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.total td { padding: 4px 6px; }
    table.total tr.grand td { border-top: 2px solid #142B4A; font-size: 12px; font-weight: bold; color: #142B4A; }
    .footer { margin-top: 20px; font-size: 9px; color: #6b7280; border-top: 1px solid #C7A96B; padding-top: 6px; }
</style>
</head>
<body>
{$header}
<table class="meta">
<tr>
    <td width="50%">
        <div class="title">Ditagihkan kepada</div>
        <div>{$this->esc($order->nama_penerima)}</div>
        <div>{$this->esc($order->nomor_telepon_penerima)}</div>
        <div>{$this->esc($order->alamat_pengiriman)}</div>
    </td>
    <td width="50%">
        <div class="title">Nomor Pesanan</div>
        <div>{$this->esc($order->nomor_pesanan)}</div>
        <div class="title" style="margin-top:6px;">Tanggal Pesanan</div>
        <div>{$this->esc($order->dibuat_pada?->format('d-m-Y H:i'))}</div>
        <div class="title" style="margin-top:6px;">Status Pembayaran</div>
        <div>{$this->esc($statusBayar)}</div>
    </td>
</tr>
</table>

<h2>Rincian Produk</h2>
<table class="items">
<thead>
<tr>
<th>Produk</th>
<th>SKU</th>
<th class="num">Jumlah</th>
<th class="num">Harga Satuan</th>
<th class="num">Subtotal</th>
</tr>
</thead>
<tbody>{$itemRows}</tbody>
</table>

<table class="total">
<tbody>
<tr><td class="label">Subtotal</td><td class="num">{$this->rupiah($order->subtotal)}</td></tr>
{$diskonRow}
<tr><td class="label">Biaya pengiriman</td><td class="num">{$this->rupiah($order->biaya_pengiriman)}</td></tr>
<tr class="grand"><td>Total pembayaran</td><td class="num">{$this->rupiah($order->total_pembayaran)}</td></tr>
{$metodeRow}
{$waktuBayarRow}
</tbody>
</table>

<div class="footer">
Invoice ini dihasilkan otomatis oleh sistem Batik Pandansari pada {$this->esc(now()->format('d-m-Y H:i'))}.
Dokumen ini sah tanpa tanda tangan basah.
</div>
</body>
</html>
HTML;
    }

    private function rupiah($nilai): string
    {
        return 'Rp '.number_format((float) $nilai, 2, ',', '.');
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
