@extends('emails.layouts.brand', ['judul' => 'Pesanan Dibatalkan - '.$order->nomor_pesanan])

@section('preheader', 'Pesanan '.$order->nomor_pesanan.' telah dibatalkan.')

@section('content')
<h1 style="margin:0 0 16px 0; font-size:20px; line-height:1.4; color:#142B4A;">
    Pesanan dibatalkan
</h1>

<p style="margin:0 0 12px 0; font-size:14px; line-height:1.75; color:#374151;">
    Halo <strong style="color:#142B4A;">{{ $namaPelanggan }}</strong>,
</p>
<p style="margin:0 0 18px 0; font-size:14px; line-height:1.75; color:#374151;">
    Pesananmu dibatalkan secara otomatis karena pembayaran tidak diselesaikan dalam batas waktu
    {{ $batasMenit }} menit. Stok produk telah kami kembalikan ke ketersediaan.
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
                    <td style="padding:2px 0; color:#5B7391;">Status pesanan</td>
                    <td align="right" style="padding:2px 0; font-weight:bold; color:#B91C1C;">Dibatalkan</td>
                </tr>
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Tanggal pesanan</td>
                    <td align="right" style="padding:2px 0;">{{ optional($tanggalPesan)->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0; color:#5B7391;">Total pembayaran</td>
                    <td align="right" style="padding:2px 0; font-weight:bold; color:#142B4A;">
                        Rp {{ number_format((float) $order->total_pembayaran, 2, ',', '.') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<h2 style="margin:0 0 10px 0; font-size:14px; color:#142B4A;">
    Produk pada pesanan
</h2>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
    <tr style="background-color:#E8EEF5;">
        <th align="left" style="padding:9px 10px; color:#142B4A; font-weight:bold; border:1px solid #D6E0EA;">Produk</th>
        <th align="right" style="padding:9px 10px; color:#142B4A; font-weight:bold; border:1px solid #D6E0EA;">Jumlah</th>
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
    </tr>
    @endforeach
</table>

<p style="margin:20px 0 0 0; font-size:13px; line-height:1.7; color:#374151;">
    Ingin memesan ulang? Silakan kunjungi kembali katalog kami dan lakukan checkout baru.
    Kami akan senang melayani Anda.
</p>

<p style="margin:22px 0 0 0; font-size:14px; line-height:1.75; color:#374151;">
    Salam hangat,<br>
    <strong style="color:#142B4A;">Tim Batik Pandansari</strong>
</p>
@endsection
