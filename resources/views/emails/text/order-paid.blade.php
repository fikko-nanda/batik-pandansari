Halo {{ $namaPelanggan }},

Pembayaran pesananmu di Batik Pandansari sudah kami terima dan pesanan sedang kami siapkan.

NOMOR PESANAN: {{ $order->nomor_pesanan }}
STATUS PEMBAYARAN: Lunas
TANGGAL PESANAN: {{ optional($tanggalPesan)->format('d-m-Y H:i') ?? '-' }}
WAKTU PEMBAYARAN: {{ optional($tanggalBayar)->format('d-m-Y H:i') ?? '-' }}
@if (!empty($metodePembayaran))
METODE PEMBAYARAN: {{ $metodePembayaran }}
@endif

RINCIAN PESANAN
@foreach ($order->items as $item)
- {{ $item->nama_produk }}@php $varian = trim(implode(' / ', array_filter([$item->warna, $item->ukuran]))); @endphp{{ $varian !== '' ? ' ('.$varian.')' : '' }} x {{ (int) $item->jumlah }} = Rp {{ number_format((float) $item->subtotal, 2, ',', '.') }}
@endforeach

Subtotal: Rp {{ number_format((float) $order->subtotal, 2, ',', '.') }}
@if ((float) $order->diskon > 0)
Diskon{{ ($voucherUsage?->voucher?->kode_voucher) ? ' ('.$voucherUsage->voucher->kode_voucher.')' : '' }}: - Rp {{ number_format((float) $order->diskon, 2, ',', '.') }}
@endif
@if ((float) $order->pph > 0)
PPh 11%: Rp {{ number_format((float) $order->pph, 2, ',', '.') }}
@endif
Total pembayaran: Rp {{ number_format((float) $order->total_pembayaran, 2, ',', '.') }}

ALAMAT PENGIRIMAN
{{ $order->nama_penerima }}
{{ $order->nomor_telepon_penerima }}
{{ $order->alamat_pengiriman }}
@if (!empty($order->catatan))

Catatan: {{ $order->catatan }}
@endif

Invoice PDF untuk pesanan ini terlampir pada email. Simpan invoice tersebut sebagai bukti pembelian.

Salam hangat,
Tim Batik Pandansari
