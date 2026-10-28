@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-[#F8F9FA] font-['Plus_Jakarta_Sans']">
    <div class="max-w-[720px] mx-auto px-4 sm:px-6 py-10">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Kebijakan Privasi</h1>
            <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200 px-3 py-1 rounded-md">
                Diperbarui 20 Oktober 2026
            </span>
        </div>

        <!-- Intro -->
        <p class="text-sm text-slate-600 leading-relaxed mb-8">
            <strong class="text-slate-900">Batik Pandansari</strong> menghargai privasi Anda. Kebijakan Privasi ini
            menjelaskan data apa saja yang kami kumpulkan, cara kami memakainya, dan hak-hak Anda terkait data pribadi
            yang kami proses melalui Layanan kami.
        </p>

        <!-- Isi -->
        <div class="space-y-8 text-sm text-slate-600 leading-relaxed">

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">1. Data yang Kami Kumpulkan</h2>
                <p>Kami mengumpulkan data yang Anda berikan langsung (mis. nama, email, alamat, nomor telepon, kata sandi)
                    saat mendaftar akun, melakukan checkout, atau menghubungi kami. Kami juga menerima data teknis otomatis
                    (alamat IP, jenis peramban, jalur klik) untuk keperluan keamanan dan analitik.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">2. Cookies &amp; Pelacak</h2>
                <p>Layanan kami menggunakan cookie esensial untuk mengelola sesi login (token Sanctum) dan keadaan keranjang
                    / wishlist. Cookie analitik (hanya jika diaktifkan) membantu kami memahami penggunaan fitur agar
                    kami dapat meningkatkan pengalaman.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">3. Cara Penggunaan Data</h2>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    <li>Menyelesaikan dan mengirimkan pesanan Anda (termasuk verifikasi pembayaran Midtrans);</li>
                    <li>Mengirim notifikasi terkait pesanan, status ekspedisi, dan voucher yang relevan;</li>
                    <li>Mengamankan akun dan mencegah penyalahgunaan;</li>
                    <li>Meningkatkan fitur berdasarkan pola penggunaan.</li>
                </ul>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">4. Penyimpanan &amp; Keamanan</h2>
                <p>Data pribadi disimpan pada server kami yang dilindungi dengan enkripsi (TLS/HTTPS). Token akses disimpan
                    di <code class="px-1 py-0.5 bg-slate-200 rounded text-[11px]">localStorage</code> di sisi klien dan tidak
                    akan pernah membaca kata sandi Anda. Kami tidak pernah membagikan data kepada pihak ketiga kecuali untuk
                    keperluan ekspedisi (kurir) dan pemrosesan pembayaran (Midtrans) atas dasar kontrak kerja yang ketat.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">5. Data Keluarga &amp; Anak</h2>
                <p>Layanan ini tidak diarahkan untuk anak di bawah 13 tahun. Kami tidak sengaja mengumpulkan data pribadi
                    anak yang belum mencapai batas usia penuh yang ditetapkan oleh hukum yang berlaku.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">6. Hak Anda</h2>
                <p>Anda berhak memperoleh, mengoreksi, atau menghapus data pribadi Anda, serta mencabut konsent yang pernah
                    diberikan. Hubungi kami melalui WhatsApp CS untuk permintaan akses atau penghapusan data.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">7. Perubahan Kebijakan</h2>
                <p>Kapan saja kami memperbarui Kebijakan Privasi ini, kami akan memperbarui tanggal
                    "Diperbarui" di bagian atas. Penggunaan berkeljalan Layanan kami setelah perubahan berarti
                    Anda menerima versi terbaru.</p>
            </div>

            <div>
                <h2 class="text-base font-bold text-[#0F1A24] mb-2">8. Kontak</h2>
                <p>Untuk pertanyaan terkait Kebijakan Privasi ini:
                    <a href="https://wa.me/6283820380977" target="_blank" rel="noopener noreferrer"
                       class="text-[#0369A1] hover:underline">WhatsApp CS Batik Pandansari</a>.</p>
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
