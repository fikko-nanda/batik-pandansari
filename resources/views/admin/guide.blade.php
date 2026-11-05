@extends('layouts.admin')

@section('content')
<div class="space-y-6 text-slate-800">
    <div class="rounded-2xl bg-[#0A192F] p-6 text-white shadow-sm sm:p-8">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-sky-300">Pusat bantuan website</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Panduan Website Pandansari</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-300">Panduan ini menjelaskan cara menggunakan website dari sisi pelanggan dan admin, mulai dari memilih produk sampai mengelola pesanan dan laporan.</p>
    </div>

    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm sm:p-6">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-life-ring mt-1 text-amber-600"></i>
            <div class="text-sm leading-relaxed text-amber-950">
                <h2 class="font-extrabold">Butuh bantuan?</h2>
                <p class="mt-1">Jika terjadi masalah pada website, pembayaran, pesanan, atau stok, hubungi teknisi di <a class="font-extrabold underline" href="mailto:teknisiadminpandansari@gmail.com">teknisiadminpandansari@gmail.com</a>.</p>
                <p class="mt-1 text-xs text-amber-800">Sertakan nama, nomor pesanan bila ada, waktu kejadian, dan tangkapan layar agar masalah lebih cepat diperiksa.</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-store text-sky-600"></i> Cara pelanggan menggunakan website</h2>
        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4"><span class="font-black text-sky-700">1. Pilih produk</span><p class="mt-2 text-sm leading-relaxed text-slate-600">Buka katalog, pilih produk, varian, dan jumlah yang diinginkan.</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><span class="font-black text-sky-700">2. Masukkan keranjang</span><p class="mt-2 text-sm leading-relaxed text-slate-600">Periksa varian, jumlah, alamat, voucher, dan ongkos kirim.</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><span class="font-black text-sky-700">3. Buat pesanan</span><p class="mt-2 text-sm leading-relaxed text-slate-600">Setelah checkout, stok ditahan sementara dan pesanan menunggu pembayaran.</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><span class="font-black text-sky-700">4. Bayar melalui Midtrans</span><p class="mt-2 text-sm leading-relaxed text-slate-600">Pembayaran harus diselesaikan dalam 15 menit agar pesanan tidak dibatalkan.</p></div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-credit-card text-emerald-600"></i> Status pembayaran pelanggan</h2>
            <dl class="mt-4 space-y-3 text-sm leading-relaxed">
                <div><dt class="font-extrabold text-amber-700">Menunggu pembayaran</dt><dd class="text-slate-600">Pesanan sudah dibuat, tetapi pembayaran belum berhasil.</dd></div>
                <div><dt class="font-extrabold text-emerald-700">Dibayar</dt><dd class="text-slate-600">Pembayaran berhasil dikonfirmasi dan pesanan dapat diproses.</dd></div>
                <div><dt class="font-extrabold text-slate-700">Kedaluwarsa</dt><dd class="text-slate-600">Batas 15 menit terlewati, pesanan dibatalkan dan stok dikembalikan.</dd></div>
                <div><dt class="font-extrabold text-rose-700">Ditolak atau dibatalkan</dt><dd class="text-slate-600">Pembayaran tidak berhasil atau transaksi dibatalkan.</dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-user-gear text-indigo-600"></i> Jika pelanggan mengalami masalah</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-sm leading-relaxed text-slate-600">
                <li>Pastikan koneksi internet stabil dan gunakan tombol coba lagi.</li>
                <li>Jangan membuat banyak pesanan baru sebelum memeriksa riwayat pesanan.</li>
                <li>Jika uang terpotong tetapi status belum berubah, simpan bukti pembayaran.</li>
                <li>Hubungi teknisi melalui email dengan nomor pesanan dan bukti pembayaran.</li>
            </ul>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-user-tie text-sky-600"></i> Cara admin menggunakan panel</h2>
        <div class="mt-4 divide-y divide-slate-100 text-sm">
            <p class="py-3"><strong>Dashboard & Ringkasan:</strong> melihat jumlah pesanan, pesanan belum dibayar, dan pesanan yang siap diproses.</p>
            <p class="py-3"><strong>Manajemen Produk:</strong> menambah, mengubah, mengaktifkan, menonaktifkan, atau menyimpan produk sebagai draf.</p>
            <p class="py-3"><strong>Kurasi Stok & Varian:</strong> mengatur stok tiap varian. Peringatan muncul saat stok tinggal 10 atau 3.</p>
            <p class="py-3"><strong>Pesanan Masuk:</strong> membuka detail pelanggan, memeriksa status pembayaran, dan melanjutkan pesanan yang sudah dibayar.</p>
            <p class="py-3"><strong>Gerbang Midtrans:</strong> memeriksa rincian status pembayaran. Semua pembayaran website menggunakan Midtrans.</p>
            <p class="py-3"><strong>Laporan Penjualan:</strong> melihat uang yang berhasil diterima, biaya admin Midtrans, dana bersih, dan mengekspor laporan berdasarkan tanggal.</p>
            <p class="py-3"><strong>Pengaturan Toko dan Buat Promo:</strong> mengelola informasi toko, kategori, banner, voucher, dan promosi.</p>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-clock text-amber-500"></i> Alur pesanan 15 menit</h2>
            <ol class="mt-4 space-y-4 text-sm text-slate-600">
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-black text-sky-700">1</span><span>Pelanggan membuat pesanan dan stok ditahan sementara.</span></li>
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-black text-sky-700">2</span><span>Pelanggan membayar melalui Midtrans dalam 15 menit.</span></li>
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700">3</span><span>Jika berhasil, admin memproses pesanan.</span></li>
                <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-100 text-xs font-black text-rose-700">4</span><span>Jika tidak dibayar, pesanan dibatalkan dan stok kembali.</span></li>
            </ol>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-file-export text-emerald-600"></i> Ekspor laporan</h2>
            <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm leading-relaxed text-slate-600">
                <li>Buka menu yang memiliki tombol Ekspor CSV.</li>
                <li>Pilih tanggal mulai dan tanggal akhir pada jendela yang muncul.</li>
                <li>Klik Ekspor. Data yang diunduh hanya berada dalam rentang tanggal tersebut.</li>
                <li>Jika ingin semua data, kosongkan kedua tanggal.</li>
            </ol>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-circle-question text-violet-600"></i> Arti angka di laporan</h2>
        <div class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
            <p class="rounded-xl bg-slate-50 p-4"><strong>Pesanan:</strong> order yang dibuat pelanggan.</p>
            <p class="rounded-xl bg-slate-50 p-4"><strong>Catatan pembayaran:</strong> percobaan pembayaran, sehingga jumlahnya bisa berbeda dari pesanan.</p>
            <p class="rounded-xl bg-slate-50 p-4"><strong>Uang diterima:</strong> hanya pembayaran dengan status berhasil.</p>
            <p class="rounded-xl bg-slate-50 p-4"><strong>Dana bersih:</strong> uang diterima setelah dikurangi perkiraan biaya admin Midtrans.</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-table-cells-large text-sky-600"></i> Penjelasan setiap kartu ringkasan</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead><tr class="border-b border-slate-200 text-[10px] uppercase tracking-wider text-slate-500"><th class="px-3 py-3">Kartu</th><th class="px-3 py-3">Artinya</th><th class="px-3 py-3">Untuk apa?</th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    <tr><td class="px-3 py-3 font-extrabold text-slate-900">Nilai semua pesanan</td><td class="px-3 py-3">Total nilai semua order yang dibuat.</td><td class="px-3 py-3">Melihat potensi nilai penjualan, bukan uang yang sudah masuk.</td></tr>
                    <tr><td class="px-3 py-3 font-extrabold text-slate-900">Belum dibayar</td><td class="px-3 py-3">Jumlah order yang masih menunggu pembayaran.</td><td class="px-3 py-3">Mengetahui order yang masih menahan stok.</td></tr>
                    <tr><td class="px-3 py-3 font-extrabold text-slate-900">Siap diproses</td><td class="px-3 py-3">Order dengan pembayaran berhasil atau sedang diproses.</td><td class="px-3 py-3">Menentukan pesanan yang harus dikerjakan admin.</td></tr>
                    <tr><td class="px-3 py-3 font-extrabold text-slate-900">Pembayaran berhasil</td><td class="px-3 py-3">Uang dari pembayaran yang dikonfirmasi Midtrans.</td><td class="px-3 py-3">Mengetahui uang yang benar-benar sudah diterima.</td></tr>
                    <tr><td class="px-3 py-3 font-extrabold text-slate-900">Nilai catatan pembayaran</td><td class="px-3 py-3">Total semua catatan pembayaran, termasuk yang belum berhasil.</td><td class="px-3 py-3">Mencocokkan aktivitas pembayaran, bukan menghitung pemasukan.</td></tr>
                    <tr><td class="px-3 py-3 font-extrabold text-slate-900">Biaya Midtrans</td><td class="px-3 py-3">Perkiraan biaya admin dari pembayaran berhasil.</td><td class="px-3 py-3">Mengetahui biaya yang mengurangi penerimaan.</td></tr>
                    <tr><td class="px-3 py-3 font-extrabold text-slate-900">Dana bersih</td><td class="px-3 py-3">Pembayaran berhasil dikurangi biaya Midtrans.</td><td class="px-3 py-3">Perkiraan uang yang tersisa setelah biaya gateway.</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-shirt text-indigo-600"></i> Cara menambah produk</h2>
        <ol class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <li class="rounded-xl bg-indigo-50 p-4 text-sm leading-relaxed text-indigo-950"><strong>1. Buka Manajemen Produk</strong><p class="mt-2">Klik tombol tambah produk dan mulai dari identitas produk.</p></li>
            <li class="rounded-xl bg-indigo-50 p-4 text-sm leading-relaxed text-indigo-950"><strong>2. Isi identitas</strong><p class="mt-2">Masukkan nama, kategori, deskripsi, bahan, asal, dan foto produk.</p></li>
            <li class="rounded-xl bg-indigo-50 p-4 text-sm leading-relaxed text-indigo-950"><strong>3. Buat varian</strong><p class="mt-2">Pilih ukuran atau warna, lalu isi SKU, harga, dan stok tiap varian.</p></li>
            <li class="rounded-xl bg-indigo-50 p-4 text-sm leading-relaxed text-indigo-950"><strong>4. Simpan dan aktifkan</strong><p class="mt-2">Produk baru tersimpan sebagai draf. Pastikan foto dan varian lengkap sebelum mengaktifkannya.</p></li>
        </ol>
        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-950">
            <strong>Catatan penting:</strong> SKU harus berbeda untuk setiap varian. Stok boleh diisi 0 jika belum tersedia. Produk draf atau nonaktif tidak tampil untuk pelanggan.
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-boxes-stacked text-emerald-600"></i> Cara mengatur stok</h2>
            <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm leading-relaxed text-slate-600">
                <li>Buka menu <strong>Kurasi Stok & Varian</strong>.</li>
                <li>Gunakan filter untuk melihat stok aktif, nonaktif, draf, atau habis.</li>
                <li>Klik Atur Stok pada produk, pilih varian, lalu masukkan jumlah baru.</li>
                <li>Klik simpan. Stok 0 berarti varian tidak tersedia, bukan data rusak.</li>
            </ol>
            <p class="mt-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">Peringatan stok 10 berarti perlu mulai disiapkan. Peringatan stok 3 berarti stok sangat sedikit dan perlu segera diperiksa.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-receipt text-amber-600"></i> Cara memproses pesanan</h2>
            <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm leading-relaxed text-slate-600">
                <li>Buka <strong>Pesanan Masuk</strong> dan buka detail pesanan.</li>
                <li>Pastikan status pembayaran sudah <strong>dibayar</strong>.</li>
                <li>Siapkan barang sesuai varian dan jumlah pada detail pesanan.</li>
                <li>Ubah status pesanan sesuai proses pengiriman yang dilakukan.</li>
                <li>Jangan memproses order yang masih menunggu pembayaran atau sudah kedaluwarsa.</li>
            </ol>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-chart-line text-emerald-600"></i> Cara membaca dan mengekspor laporan</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-600"><strong class="text-slate-900">Laporan Penjualan</strong><p class="mt-2">Dipakai untuk melihat pemasukan, biaya Midtrans, dana bersih, dan perbandingan pesanan dengan catatan pembayaran.</p></div>
            <div class="rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-600"><strong class="text-slate-900">Ekspor CSV</strong><p class="mt-2">Klik tombol CSV, pilih tanggal mulai dan tanggal akhir, kemudian unduh file laporan.</p></div>
            <div class="rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-600"><strong class="text-slate-900">Tanggal kosong</strong><p class="mt-2">Jika tanggal tidak diisi, seluruh data yang tersedia akan diekspor.</p></div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-extrabold text-slate-900"><i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Masalah umum dan solusinya</h2>
        <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600">
            <p><strong class="text-slate-900">Pembayaran belum berubah:</strong> tunggu konfirmasi Midtrans, lalu periksa kembali detail pesanan. Jangan mengubah status menjadi dibayar tanpa bukti.</p>
            <p><strong class="text-slate-900">Pesanan hilang setelah 15 menit:</strong> itu berarti pesanan kedaluwarsa dan stok sudah dikembalikan.</p>
            <p><strong class="text-slate-900">Stok tidak bisa disimpan:</strong> pastikan jumlah berupa angka bulat 0 atau lebih dan varian sudah dipilih.</p>
            <p><strong class="text-slate-900">CSV tidak terunduh:</strong> pilih tanggal yang valid, pastikan sesi admin masih aktif, lalu coba kembali.</p>
            <p><strong class="text-slate-900">Ada angka yang terasa tidak cocok:</strong> bedakan jumlah pesanan, jumlah catatan pembayaran, pembayaran berhasil, dan nilai uang masuk.</p>
        </div>
    </section>
</div>
@endsection
