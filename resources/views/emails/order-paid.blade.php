@extends('emails.layouts.brand', ['judul' => 'Pembayaran Berhasil - '.$order->nomor_pesanan])

@section('preheader', 'Pembayaran pesanan '.$order->nomor_pesanan.' sudah kami terima.')

@section('content')
<h1 style="margin:0 0 16px 0; font-size:20px; line-height:1.4; color:#142B4A;">
    Pembayaran berhasil
</h1>

<p style="margin:0 0 12px 0; font-size:14px; line-height:1.75; color:#374151;">
    Halo <strong style="color:#142B4A;">{{ $namaPelanggan }}</strong>,
</p>
<p style="margin:0 0 18px 0; font-size:14px; line-height:1.75; color:#374151;">
    Pembayaran pesananmu di Batik Pandansari sudah kami terima dan pesanan sedang kami siapkan.
    Berikut ringkasan pesananmu.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px 0; background-color:#EEF3F9; border:1px solid #C3D3E4; border-radius:8px;">
    <tr>
        <td style="padding:16px 18px; font-size:13px; line-height:1.9; color:#374151;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Nomor pesanan</td>
                    <td align="right" style="padding:2px 0; font-weight:bold; color:#142B4A;">{{ $order->nomor_pesanan }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Status pembayaran</td>
                    <td align="right" style="padding:2px 0; font-weight:bold; color:#047857;">Lunas</td>
                </tr>
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Tanggal pesanan</td>
                    <td align="right" style="padding:2px 0;">{{ optional($tanggalPesan)->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Waktu pembayaran</td>
                    <td align="right" style="padding:2px 0;">{{ optional($tanggalBayar)->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
                @if (!empty($metodePembayaran))
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Metode pembayaran</td>
                    <td align="right" style="padding:2px 0; text-transform:capitalize;">{{ $metodePembayaran }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

<h2 style="margin:0 0 10px 0; font-size:14px; color:#142B4A;">
    Rincian pesanan
</h2>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
    <tr style="background-color:#E8EEF5;">
        <th align="left" style="padding:9px 10px; color:#142B4A; font-weight:bold; border:1px solid #D6E0EA;">Produk</th>
        <th align="right" style="padding:9px 10px; color:#142B4A; font-weight:bold; border:1px solid #D6E0EA;">Jumlah</th>
        <th align="right" style="padding:9px 10px; color:#142B4A; font-weight:bold; border:1px solid #D6E0EA;">Subtotal</th>
    </tr>
    @foreach ($order->items as $item)
    <tr>
        <td style="padding:9px 10px; border:1px solid #D6E0EA; color:#374151;">
            {{ $item->nama_produk }}
            @php $varian = trim(implode(' / ', array_filter([$item->warna, $item->ukuran]))); @endphp
            @if ($varian !== '')
                <div style="font-size:11px; color:#5B7391; margin-top:2px;">{{ $varian }}</div>
            @endif
        </td>
        <td align="right" style="padding:9px 10px; border:1px solid #D6E0EA; color:#374151;">{{ (int) $item->jumlah }}</td>
        <td align="right" style="padding:9px 10px; border:1px solid #D6E0EA; color:#374151;">Rp {{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>
    </tr>
    @endforeach
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
    <tr>
        <td style="padding:14px 16px; border:1px solid #D6E0EA; border-radius:8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#374151; line-height:1.9;">
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Subtotal</td>
                    <td align="right" style="padding:2px 0;">Rp {{ number_format((float) $order->subtotal, 2, ',', '.') }}</td>
                </tr>
                @if ((float) $order->diskon > 0)
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">
                        Diskon {{ ($voucherUsage?->voucher?->kode_voucher) ? '('.$voucherUsage->voucher->kode_voucher.')' : '' }}
                    </td>
                    <td align="right" style="padding:2px 0;">- Rp {{ number_format((float) $order->diskon, 2, ',', '.') }}</td>
                </tr>
                @endif
                @if ((float) $order->pph > 0)
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">PPh 11%</td>
                    <td align="right" style="padding:2px 0;">Rp {{ number_format((float) $order->pph, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding:10px 0 0 0; border-top:1px solid #E5E7EB; font-weight:bold; color:#142B4A;">Total pembayaran</td>
                    <td align="right" style="padding:10px 0 0 0; border-top:1px solid #E5E7EB; font-weight:bold; font-size:15px; color:#142B4A;">
                        Rp {{ number_format((float) $order->total_pembayaran, 2, ',', '.') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<h2 style="margin:22px 0 10px 0; font-size:14px; color:#142B4A;">
    Alamat pengiriman
</h2>
<p style="margin:0; font-size:13px; line-height:1.8; color:#374151;">
    <strong style="color:#142B4A;">{{ $order->nama_penerima }}</strong><br>
    {{ $order->nomor_telepon_penerima }}<br>
    {{ $order->alamat_pengiriman }}
</p>

@if (!empty($order->catatan))
<p style="margin:14px 0 0 0; font-size:12px; color:#5B7391; line-height:1.7;">
    <strong style="color:#142B4A;">Catatan:</strong> {{ $order->catatan }}
</p>
@endif

<p style="margin:20px 0 0 0; font-size:13px; line-height:1.7; color:#374151;">
    Invoice PDF untuk pesanan ini terlampir pada email. Simpan invoice tersebut sebagai
    bukti pembelian.
</p>

<p style="margin:22px 0 0 0; font-size:14px; line-height:1.75; color:#374151;">
    Salam hangat,<br>
    <strong style="color:#142B4A;">Tim Batik Pandansari</strong>
</p>
@endsection
