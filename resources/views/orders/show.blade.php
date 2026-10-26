@extends('layouts.app')

@section('content')

    <!-- Detail Pesanan (customer) — data dari GET /api/orders/{id}, bayar via Midtrans Snap -->
    <div class="bg-[#FAF8F5] min-h-screen py-6 sm:py-10 font-['Plus_Jakarta_Sans']"
         x-data="{
            // ID pesanan dari URL /orders/{id} (diisi backend via route closure)
            orderId: '{{ $id ?? '' }}',

            // State data
            nomorPesanan: '',
            status: '',
            dibuatPada: '',
            totalBayar: 0,
            items: [],
            ongkir: 0,
            diskon: 0,
            ppn: 0,

            // State UI
            loading: true,
            needLogin: false,
            errorMsg: '',
            busyPay: false,
            fallbackImage: '{{ asset('images/katalog1.webp') }}',

            // Banner sukses pembayaran + hitung mundur pengalihan halaman
            isPaidSuccess: false,
            countdown: 8,
            _countdownTimer: null,

            get subtotal() {
                return this.items.reduce((s, it) => s + Number(it.price ?? 0), 0);
            },
            // Pajak Penjualan (PPN) 11% — utamakan nilai backend (order.ppn),
            // fallback hitung 11% dari subtotal.
            get ppnAmount() {
                return Number(this.ppn) > 0 ? Number(this.ppn) : Math.round(this.subtotal * 0.11);
            },
            // Total akhir yang ditampilkan: Subtotal − Diskon + PPN + Ongkir.
            get totalAkhir() {
                return Math.max(0, this.subtotal - this.diskon + this.ppnAmount + this.ongkir);
            },
            statusLabel(s) {
                const labels = {
                    'menunggu_pembayaran': 'Menunggu Pembayaran',
                    'dibayar': 'Dibayar', 'diproses': 'Diproses', 'dikirim': 'Sedang Dikirim',
                    'selesai': 'Selesai', 'dibatalkan': 'Dibatalkan'
                };
                return labels[s] ?? (s || '-');
            },
            statusBadgeClass(s) {
                if (s === 'menunggu_pembayaran') return 'bg-amber-100 text-amber-700';
                if (s === 'dibatalkan') return 'bg-rose-100 text-rose-600';
                if (s === 'selesai') return 'bg-slate-100 text-slate-600';
                return 'bg-[#BAE6FD] text-[#0369A1]';
            },
            canPay() { return this.status === 'menunggu_pembayaran'; },

            tanggalPesan() {
                try {
                    return new Date(this.dibuatPada).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                } catch { return ''; }
            },
            formatRupiah(n) { return PandansariAPI.formatRp(n); },

            async load() {
                this.loading = true;
                this.errorMsg = '';
                if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }

                // Detail pesanan berdasarkan ID dari URL: GET /api/orders/{id}
                const res = await PandansariAPI.fetch('/orders/' + this.orderId);
                this.loading = false;

                if (res.ok && res.data) {
                    const o = res.data;
                    this.nomorPesanan = o.nomor_pesanan ?? o.id_order ?? this.orderId;
                    this.status = o.status_pesanan ?? o.status ?? '';
                    this.dibuatPada = o.dibuat_pada ?? o.created_at ?? '';
                    this.totalBayar = Number(o.total_pembayaran ?? o.total ?? 0);
                    this.ongkir = Number(o.ongkir ?? o.biaya_pengiriman ?? 0);
                    this.diskon = Number(o.diskon_voucher ?? o.diskon ?? 0);
                    this.ppn = Number(o.ppn ?? o.ppn_amount ?? o.pajak_ppn ?? 0);
                    // Items backend bersifat FLAT (nama_produk, warna, ukuran, harga_satuan,
                    // jumlah, id_variant) — fallback ke bentuk nested bila ada
                    this.items = (o.items ?? []).map(it => {
                        const v = it.variant ?? {};
                        const p = v.product ?? {};
                        const harga = Number(v.harga ?? it.harga_satuan ?? it.harga ?? 0);
                        const ukuran = v.ukuran ?? it.ukuran;
                        const warna = v.warna ?? it.warna;
                        const jumlah = Number(it.jumlah ?? 1);
                        return {
                            name: p.nama_produk ?? it.nama_produk ?? 'Produk',
                            variant: [ukuran && ('Ukuran ' + ukuran), warna, jumlah > 1 && (jumlah + ' pcs')].filter(Boolean).join(' • '),
                            price: harga * jumlah,
                            image: PandansariAPI.coverUrl(p.images) || this.fallbackImage,
                        };
                    });
                } else if (res.status === 401) {
                    this.needLogin = true;
                } else if (res.status === 404) {
                    this.errorMsg = 'Pesanan tidak ditemukan. Pastikan tautan Anda benar atau pesanan sudah dihapus.';
                } else {
                    this.errorMsg = res.message;
                }
            },

            // Buka popup Midtrans Snap (QRIS/VA/e-wallet) untuk menyelesaikan pembayaran.
            // Dipakai setelah kembali dari konfirmasi WhatsApp di halaman checkout.
            async bayarSekarang() {
                if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
                this.busyPay = true;
                try {
                    const res = await PandansariAPI.payOrder(this.orderId);
                    if (!res.ok) { PandansariAPI.toast(res.message ?? 'Gagal menyiapkan pembayaran.', false); return; }
                    // Response: { snap_token, redirect_url, client_key } — bayarSnap
                    // otomatis fallback ke redirect_url bila popup Snap gagal dimuat.
                    await PandansariAPI.bayarSnap(res.data ?? {}, {
                        // Pembayaran sukses → tandai status dibayar, tampilkan banner, lalu
                        // hitung mundur 8 detik dan alihkan pengguna ke Halaman Utama.
                        onSuccess: () => {
                            this.status = 'dibayar';
                            this.isPaidSuccess = true;
                            this.mulaiCountdown();
                        },
                        onPending: () => this.load(),
                        onClose: () => this.load(),
                    });
                } catch (e) {
                    PandansariAPI.toast(e?.message ?? 'Pembayaran gagal dibuka, silakan coba lagi.', false);
                } finally {
                    this.busyPay = false;
                }
            },

            // Hitung mundur 8 detik → window.location.href = '/'
            mulaiCountdown() {
                this.countdown = 8;
                clearInterval(this._countdownTimer);
                this._countdownTimer = setInterval(() => {
                    this.countdown -= 1;
                    if (this.countdown <= 0) {
                        clearInterval(this._countdownTimer);
                        window.location.href = '/';
                    }
                }, 1000);
            },
         }"
         x-init="load()">

        <div class="max-w-3xl mx-auto px-4 sm:px-6">

            <!-- Banner Sukses Pembayaran + hitung mundur pengalihan halaman -->
            <div x-show="isPaidSuccess" x-cloak
                 class="mb-6 bg-emerald-50 border border-emerald-200/90 rounded-2xl p-5 sm:p-6 flex items-start gap-3 shadow-sm">
                <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-emerald-800">Pembayaran Berhasil!</h3>
                    <p class="text-xs text-emerald-800/90 leading-relaxed mt-1">
                        Terima kasih, pembayaran pesanan Anda telah kami terima dan sedang diproses.
                    </p>
                    <p class="text-[11px] text-emerald-700/90 mt-2 font-semibold">
                        <i class="fa-solid fa-house mr-1"></i>
                        Dialihkan ke Halaman Utama dalam
                        <span class="font-extrabold text-emerald-700" x-text="countdown"></span> detik…
                    </p>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
                <a href="/" class="hover:text-[#0F1A24] font-semibold transition">Beranda</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <a href="/orders" class="hover:text-[#0F1A24] font-semibold transition">Pesanan</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-[#0F1A24] font-bold">Detail Pesanan</span>
            </nav>
            <!-- ================= STATE LOADING ================= -->
            <template x-if="loading">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-10 text-center">
                    <i class="fa-solid fa-circle-notch fa-spin text-xl text-[#0369A1]"></i>
                    <p class="mt-3 text-sm text-slate-500 font-semibold">Memuat detail pesanan…</p>
                </div>
            </template>

            <!-- ================= STATE BELUM LOGIN ================= -->
            <template x-if="needLogin && !loading">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-10 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-[#BAE6FD] flex items-center justify-center">
                        <i class="fa-solid fa-lock text-[#0369A1]"></i>
                    </div>
                    <h1 class="mt-4 text-base font-extrabold text-[#0F1A24]">Masuk Diperlukan</h1>
                    <p class="mt-2 text-sm text-slate-500">Silakan masuk untuk melihat detail pesanan Anda.</p>
                    <a href="/login"
                       class="mt-5 inline-flex items-center justify-center gap-2 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold py-3 px-6 rounded-xl transition shadow-sm">
                        <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
                    </a>
                </div>
            </template>

            <!-- ================= STATE ERROR (404 / server) ================= -->
            <template x-if="errorMsg && !loading">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-10 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-rose-100 flex items-center justify-center">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                    </div>
                    <h1 class="mt-4 text-base font-extrabold text-[#0F1A24]">Terjadi Kendala</h1>
                    <p class="mt-2 text-sm text-slate-500" x-text="errorMsg"></p>
                    <a href="/orders"
                       class="mt-5 inline-flex items-center justify-center gap-2 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold py-3 px-6 rounded-xl transition shadow-sm">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Pesanan
                    </a>
                </div>
            </template>

            <!-- ================= STATE DATA ================= -->
            <template x-if="!loading && !needLogin && !errorMsg">
                <div class="space-y-4">

                    <!-- Ringkasan Pesanan -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nomor Pesanan</p>
                                <p class="mt-1 text-lg font-extrabold text-[#0F1A24]" x-text="nomorPesanan"></p>
                                <p class="mt-1 text-xs text-slate-500" x-text="tanggalPesan()"></p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1.5 rounded-full text-xs font-bold" x-text="statusLabel(status)"
                                      :class="statusBadgeClass(status)"></span>
                                <button @click="load()" title="Muat ulang status terbaru"
                                        class="w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-[#0F1A24] transition flex items-center justify-center cursor-pointer">
                                    <i class="fa-solid fa-rotate-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Item Pesanan -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sm:p-6">
                        <h2 class="text-sm font-extrabold text-[#0F1A24] mb-4">
                            <i class="fa-solid fa-box-open text-[#0369A1] mr-1"></i> Rincian Produk
                        </h2>

                        <template x-if="items.length === 0">
                            <p class="text-sm text-slate-500">Tidak ada item pada pesanan ini.</p>
                        </template>

                        <ul class="space-y-4" x-show="items.length > 0">
                            <template x-for="(it, i) in items" :key="i">
                                <li class="flex gap-4 items-center">
                                    <img :src="it.image" :alt="it.name" class="w-16 h-16 rounded-xl object-cover border border-slate-100 bg-[#FAF8F5]" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-[#0F1A24] truncate" x-text="it.name"></p>
                                        <p class="text-xs text-slate-500 mt-0.5" x-text="it.variant || '—'"></p>
                                    </div>
                                    <p class="text-sm font-bold text-[#0F1A24] shrink-0" x-text="formatRupiah(it.price)"></p>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Ringkasan Pembayaran -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 sm:p-6">
                        <h2 class="text-sm font-extrabold text-[#0F1A24] mb-4">
                            <i class="fa-solid fa-receipt text-[#0369A1] mr-1"></i> Ringkasan Pembayaran
                        </h2>

                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Subtotal Produk</dt>
                                <dd class="font-bold text-[#0F1A24]" x-text="formatRupiah(subtotal)"></dd>
                            </div>
                            <div class="flex justify-between text-slate-600 font-medium">
                                <dt>Pajak PPN (11%)</dt>
                                <dd>+ <span x-text="formatRupiah(ppnAmount)"></span></dd>
                            </div>
                            <div class="flex justify-between" x-show="ongkir > 0">
                                <dt class="text-slate-500">Ongkos Kirim</dt>
                                <dd class="font-bold text-[#0F1A24]" x-text="formatRupiah(ongkir)"></dd>
                            </div>
                            <div class="flex justify-between" x-show="diskon > 0">
                                <dt class="text-slate-500">Diskon Voucher</dt>
                                <dd class="font-bold text-emerald-600" x-text="'- ' + formatRupiah(diskon)"></dd>
                            </div>
                            <div class="flex justify-between border-t border-dashed border-slate-200 pt-3 mt-2">
                                <dt class="font-extrabold text-[#0F1A24]">Total Pembayaran</dt>
                                <dd class="font-extrabold text-[#0369A1] text-base" x-text="formatRupiah(totalAkhir)"></dd>
                            </div>
                        </dl>

                        <!-- Tombol Midtrans Snap — muncul saat pesanan menunggu pembayaran -->
                        <template x-if="canPay() && items.length > 0">
                            <div class="mt-5">
                                <button @click="bayarSekarang()" :disabled="busyPay"
                                        class="w-full inline-flex items-center justify-center gap-2 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold py-3.5 px-4 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                    <i class="fa-solid fa-credit-card" :class="busyPay && 'fa-beat-fade'"></i>
                                    <span x-text="busyPay ? 'MEMBUKA PEMBAYARAN…' : 'BAYAR SEKARANG (QRIS / VA / E-WALLET)'"></span>
                                </button>
                                <p class="mt-3 text-[11px] text-slate-500 leading-relaxed text-center">
                                    <i class="fa-brands fa-whatsapp text-emerald-500"></i>
                                    Setelah konfirmasi WhatsApp dari halaman checkout, kembali ke halaman ini lalu tekan tombol di
                                    atas untuk menyelesaikan pembayaran digital via <span class="font-bold text-[#0369A1]">Midtrans Snap</span>
                                    (QRIS, Virtual Account, atau e-wallet).
                                </p>
                            </div>
                        </template>

                        <!-- Info bila tidak lagi menunggu pembayaran -->
                        <template x-if="!canPay()">
                            <p class="mt-4 text-xs text-slate-500 bg-[#FAF8F5] rounded-xl px-4 py-3">
                                <i class="fa-solid fa-circle-info text-[#0369A1] mr-1"></i>
                                Pembayaran digital hanya tersedia saat status pesanan
                                <span class="font-bold">Menunggu Pembayaran</span>.
                            </p>
                        </template>

                        <!-- Alert Catatan Biaya Pengiriman -->
                        <div class="mt-5 bg-amber-50/80 border border-amber-200/90 rounded-2xl p-4 sm:p-5 flex items-start gap-3">
                            <i class="fa-solid fa-truck-ramp-box text-amber-600 text-base mt-0.5"></i>
                            <div>
                                <h3 class="text-xs font-extrabold text-amber-800">Catatan Biaya Pengiriman</h3>
                                <p class="text-[11px] text-amber-800/90 leading-relaxed mt-1">
                                    Total pembayaran digital di atas belum termasuk ongkos kirim. Seluruh biaya pengiriman
                                    sepenuhnya ditanggung oleh pembeli dan dikonfirmasikan terpisah oleh Admin via WhatsApp.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

@endsection
