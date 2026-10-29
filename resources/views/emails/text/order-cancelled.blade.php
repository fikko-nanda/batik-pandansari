Halo {{ $namaPelanggan }},

Pesananmu dibatalkan secara otomatis karena pembayaran tidak diselesaikan dalam batas waktu {{ $batasMenit }} menit. Stok produk telah kami kembalikan ke ketersediaan.

NOMOR PESANAN: {{ $order->nomor_pesanan }}
STATUS PESANAN: Dibatalkan
TANGGAL PESANAN: {{ optional($tanggalPesan)->format('d-m-Y H:i') ?? '-' }}
TOTAL PEMBAYARAN: Rp {{ number_format((float) $order->total_pembayaran, 2, ',', '.') }}

PRODUK PADA PESANAN
@foreach ($order->items as $item)
- {{ $item->nama_produk }}@php $varian = trim(implode(' / ', array_filter([$item->warna, $item->ukuran]))); @endphp{{ $varian !== '' ? ' ('.$varian.')' : '' }} x {{ (int) $item->jumlah }}
@endforeach

Ingin memesan ulang? Silakan kunjungi kembali katalog kami dan lakukan checkout baru. Kami akan senang melayani Anda.

Salam hangat,
Tim Batik Pandansari
