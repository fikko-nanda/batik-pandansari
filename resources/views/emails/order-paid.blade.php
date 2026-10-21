@extends('emails.layouts.brand', ['judul' => 'Pembayaran Berhasil'])

@section('content')
<h1 style="margin:0 0 6px 0; font-size:20px; color:#142B4A;">Pembayaran Berhasil</h1>
<p style="margin:0 0 18px 0; font-size:14px; color:#374151; line-height:1.7;">
    Halo <strong>{{ $namaPelanggan }}</strong>, terima kasih telah berbelanja di Batik Pandansari.
    Pembayaran Anda sudah kami terima dan pesanan sedang kami siapkan.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:18px;">
    <tr>
        <td style="background-color:#142B4A; color:#FFFCF5; padding:12px 16px; border-radius:8px 8px 0 0; font-size:13px;">
            Nomor Pesanan<br>
            <span style="font-size:16px; font-weight:bold; color:#C7A96B; letter-spacing:0.5px;">{{ $order->nomor_pesanan }}</span>
        </td>
    </tr>
    <tr>
        <td style="border:1px solid #e7dfcd; border-top:none; border-radius:0 0 8px 8px; padding:16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#374151; line-height:1.8;">
                <tr>
                    <td style="padding:2px 0; color:#6b7280;">Tanggal pesanan</td>
                    <td style="padding:2px 0; text-align:right;">{{ optional($tanggalPesan)->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0; color:#6b7280;">Waktu pembayaran</td>
                    <td style="padding:2px 0; text-align:right;">{{ optional($tanggalBayar)->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
                @if (!empty($metodePembayaran))
                <tr>
                    <td style="padding:2px 0; color:#6b7280;">Metode pembayaran</td>
                    <td style="padding:2px 0; text-align:right;">{{ $metodePembayaran }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding:2px 0; color:#6b7280;">Status pembayaran</td>
                    <td style="padding:2px 0; text-align:right; text-transform:capitalize;">{{ $statusPembayaran }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<h2 style="margin:0 0 10px 0; font-size:14px; color:#142B4A; border-bottom:1px solid #C7A96B; padding-bottom:6px;">Rincian Pesanan</h2>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
    <tr style="background-color:#FFFCF5;">
        <th align="left" style="padding:8px; border:1px solid #e7dfcd; color:#142B4A;">Produk</th>
        <th align="right" style="padding:8px; border:1px solid #e7dfcd; color:#142B4A;">Jumlah</th>
        <th align="right" style="padding:8px; border:1px solid #e7dfcd; color:#142B4A;">Subtotal</th>
    </tr>
    @foreach ($order->items as $item)
    <tr>
        <td style="padding:8px; border:1px solid #e7dfcd; color:#374151;">
            {{ $item->nama_produk }}
            @php $varian = trim(implode(' / ', array_filter([$item->warna, $item->ukuran]))); @endphp
            @if ($varian !== '')
                <div style="font-size:11px; color:#6b7280;">{{ $varian }}</div>
            @endif
        </td>
        <td align="right" style="padding:8px; border:1px solid #e7dfcd; color:#374151;">{{ (int) $item->jumlah }}</td>
        <td align="right" style="padding:8px; border:1px solid #e7dfcd; color:#374151;">Rp {{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>
    </tr>
    @endforeach
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px; font-size:13px; color:#374151;">
    <tr>
        <td style="padding:4px 0; color:#6b7280;">Subtotal</td>
        <td align="right" style="padding:4px 0;">Rp {{ number_format((float) $order->subtotal, 2, ',', '.') }}</td>
    </tr>
    @if ((float) $order->diskon > 0)
    <tr>
        <td style="padding:4px 0; color:#6b7280;">
            Diskon {{ ($voucherUsage?->voucher?->kode_voucher) ? '('.$voucherUsage->voucher->kode_voucher.')' : '' }}
        </td>
        <td align="right" style="padding:4px 0;">- Rp {{ number_format((float) $order->diskon, 2, ',', '.') }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding:4px 0; color:#6b7280;">Biaya pengiriman</td>
        <td align="right" style="padding:4px 0;">Rp {{ number_format((float) $order->biaya_pengiriman, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td style="padding:10px 0 0 0; border-top:2px solid #142B4A; font-weight:bold; color:#142B4A;">Total pembayaran</td>
        <td align="right" style="padding:10px 0 0 0; border-top:2px solid #142B4A; font-weight:bold; font-size:15px; color:#142B4A;">Rp {{ number_format((float) $order->total_pembayaran, 2, ',', '.') }}</td>
    </tr>
</table>

<h2 style="margin:22px 0 10px 0; font-size:14px; color:#142B4A; border-bottom:1px solid #C7A96B; padding-bottom:6px;">Alamat Pengiriman</h2>
<div style="font-size:13px; color:#374151; line-height:1.7;">
    <strong>{{ $order->nama_penerima }}</strong><br>
    {{ $order->nomor_telepon_penerima }}<br>
    {{ $order->alamat_pengiriman }}
</div>

@if (!empty($order->catatan))
<p style="margin:16px 0 0 0; font-size:12px; color:#6b7280; line-height:1.6;">
    <strong style="color:#142B4A;">Catatan:</strong> {{ $order->catatan }}
</p>
@endif

<div style="margin-top:22px; padding:14px 16px; background-color:#FFFCF5; border-left:4px solid #C7A96B; border-radius:6px; font-size:13px; color:#374151; line-height:1.7;">
    Invoice pesanan Anda terlampir pada email ini dalam format PDF.
</div>

<p style="margin:22px 0 0 0; font-size:13px; color:#374151; line-height:1.7;">
    Salam hangat,<br>
    <strong style="color:#142B4A;">Batik Pandansari</strong>
</p>
@endsection
