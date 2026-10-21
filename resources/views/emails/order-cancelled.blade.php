@extends('emails.layouts.brand', ['judul' => 'Pesanan Dibatalkan'])

@section('content')
<h1 style="margin:0 0 6px 0; font-size:20px; color:#142B4A;">Pesanan Dibatalkan</h1>
<p style="margin:0 0 18px 0; font-size:14px; color:#374151; line-height:1.7;">
    Halo <strong>{{ $namaPelanggan }}</strong>, pesanan Anda dibatalkan secara otomatis karena
    pembayaran tidak diselesaikan dalam batas waktu <strong>{{ $batasMenit }} menit</strong>.
    Stok produk telah kami kembalikan ke ketersediaan.
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
                    <td style="padding:2px 0; color:#6b7280;">Status pesanan</td>
                    <td style="padding:2px 0; text-align:right;">Dibatalkan</td>
                </tr>
                <tr>
                    <td style="padding:2px 0; color:#6b7280;">Total pembayaran</td>
                    <td style="padding:2px 0; text-align:right; font-weight:bold; color:#142B4A;">
                        Rp {{ number_format((float) $order->total_pembayaran, 2, ',', '.') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<h2 style="margin:0 0 10px 0; font-size:14px; color:#142B4A; border-bottom:1px solid #C7A96B; padding-bottom:6px;">Produk pada Pesanan</h2>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
    <tr style="background-color:#FFFCF5;">
        <th align="left" style="padding:8px; border:1px solid #e7dfcd; color:#142B4A;">Produk</th>
        <th align="right" style="padding:8px; border:1px solid #e7dfcd; color:#142B4A;">Jumlah</th>
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
    </tr>
    @endforeach
</table>

<div style="margin-top:22px; padding:14px 16px; background-color:#FFFCF5; border-left:4px solid #C7A96B; border-radius:6px; font-size:13px; color:#374151; line-height:1.7;">
    Ingin memesan ulang? Silakan kunjungi kembali katalog kami dan lakukan checkout baru.
    Kami akan senang melayani Anda.
</div>

<p style="margin:22px 0 0 0; font-size:13px; color:#374151; line-height:1.7;">
    Salam hangat,<br>
    <strong style="color:#142B4A;">Batik Pandansari</strong>
</p>
@endsection
