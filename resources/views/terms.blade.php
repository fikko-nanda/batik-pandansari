@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-[#F8F9FA] font-['Plus_Jakarta_Sans']">
    <div class="max-w-[720px] mx-auto px-4 sm:px-6 py-10">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Ketentuan Layanan</h1>
            <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200 px-3 py-1 rounded-md">
                Diperbarui 20 Oktober 2026
            </span>
        </div>

        <!-- Intro -->
        <p class="text-sm text-slate-600 leading-relaxed mb-8">
            Ketentuan Layanan ini mengatur penggunaan platform <strong class="text-slate-900">Batik Pandansari</strong>
            (situs web dan aplikasi, secara kolektif disebut "Layanan"). Dengan mengakses atau menggunakan Layanan ini,
            Anda setuju terikat oleh ketentuan berikut.
        </p>

        <!-- Isi -->
        <div class="space-y-8 text-sm text-slate-600 leading-relaxed">

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">1. Akun Pengguna</h2>
                <p>Anda wajib mendaftarkan akun untuk berbelanja. Anda bertanggung jawab atas keamanan kata sandi dan
                    semua aktivitas di bawah akun Anda. Segala kerusakan akibat penggunaan tidak sah akun Anda menjadi
                    tanggung jawab Anda.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">2. Produk &amp; Stok</h2>
                <p>Setiap produk ditampilkan sesuai data paling akurat kami. Ketersediaan stok dan harga dapat berubah
                    sewaktu-waktu tanpa pemberitahuan. Kami berhak membatalkan pesanan yang tidak dapat dipenuhi atas
                    sebab stok habis, dengan notifikasi kepada Anda.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">3. Pesanan &amp; Pembayaran</h2>
                <p>Pesanan Anda tercatat setelah melewati halaman checkout. Pembayaran diproses melalui Midtrans;
                    konfirmasi pembayaran otomatis memperbarui status pesanan. Anda mendapatkan <em>nomor pesanan</em>
                    untuk lacak pesanan di tautan riwayat pesanan akun Anda.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">4. Ongkos Kirim &amp; Konfirmasi Manual</h2>
                <p>Ongkos kirim <strong>tidak dihitung otomatis</strong> dan <strong>belum termasuk</strong> dalam total
                    pembayaran di halaman checkout. Setelah pesanan dibuat, tim kami akan mengonfirmasi ongkos kirim
                    berdasarkan alamat dan berat paket melalui <strong>WhatsApp</strong>, beserta total pembayaran dan
                    rekening yang digunakan. Pesanan diproses setelah konfirmasi dan pembayaran diterima.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">5. Pengembalian &amp; Garansi</h2>
                <p>Barang yang dikirimkan cacat atau salah kirim dapat ditolak (Return to Sender) pada saat
                    penerimaan. Pengembalian dana diproses paling lambat 7 Hari Kerja setelah kami menerima kondisi
                    barang yang dilaporkan.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">6. Hak Merek &amp; Konten</h2>
                <p>Semua materi (logo, teks, gambar, desain) adalah milik eksklusif Batik Pandansari atau pihak
                    yang berlisensi kepada kami. Dilarang menyalin, mendistribusikan, atau memodifikasi konten tanpa
                    izin tertulis.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">7. Pembatasan Tanggung Jawab</h2>
                <p>Kami berusaha menampilkan produk sesuai dengan kondisi sebenarnya, namun gambar pada layar dapat
                    berbeda dengan warna sebenarnya. Batik Pandansari tidak bertanggung jawab atas kerusakan tidak langsung
                    akibat penggunaan layanan ini.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">8. Perubahan Ketentuan</h2>
                <p>Kami dapat memperbarui halaman Ketentuan Layanan ini. Versi terbaru selalu mengikat dan ditandai
                    dengan tanggal pembaruan terbaru di bagian atas.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">9. Kontak</h2>
                <p>Pertanyaan terkait Ketentuan Layanan dapat dikirim ke <a href="https://wa.me/6282210516993"
                    target="_blank" rel="noopener noreferrer" class="text-[#0369A1] hover:underline">WhatsApp CS
                    Batik Pandansari</a>.</p>
            </div>

        </div>

        <!-- Back to Home -->
        <div class="mt-12 pt-6 border-t border-slate-200 text-center">
            <a href="/" class="inline-flex items-center justify-center gap-2 text-xs font-semibold text-[#0369A1] hover:text-[#0F1A24] hover:underline transition">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

@endsection
