@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen pt-6 sm:pt-8 pb-12 sm:pb-16 font-['Plus_Jakarta_Sans']"
     x-data="{
        activeTab: 'semua',
        searchQuery: '',
        page: 1, perPage: 10, lastPage: 1, total: 0,
        orders: [],
        loading: true,
        needLogin: false,
        errorMsg: '',
        busyPay: null,
        user: null,
        addressesCount: 0,
        fallbackImage: '{{ asset('images/katalog1.webp') }}',

        statusLabels: {
            'menunggu_pembayaran': 'Menunggu Pembayaran',
            'menunggu_verifikasi': 'Menunggu Verifikasi',
            'dibayar': 'Dibayar', 'diproses': 'Diproses', 'dikirim': 'Sedang Dikirim',
            'selesai': 'Selesai', 'dibatalkan': 'Dibatalkan'
        },
        statusOf(o) { return o.status_tampil ?? o.status_pesanan; },
        statusGroup(s) {
            if (s === 'selesai') return 'selesai';
            if (s === 'dibatalkan') return 'dibatalkan';
            return 'berlangsung';
        },
        statusBadgeClass(s) {
            if (s === 'menunggu_pembayaran' || s === 'menunggu_verifikasi') return 'bg-amber-100 text-amber-700';
            if (s === 'dibatalkan') return 'bg-rose-100 text-rose-600';
            if (s === 'selesai') return 'bg-slate-100 text-slate-600';
            return 'bg-[#BAE6FD] text-[#0369A1]';
        },

        get counts() {
            return {
                semua: this.total || this.orders.length,
                berlangsung: this.orders.filter(o => this.statusGroup(this.statusOf(o)) === 'berlangsung').length,
                selesai: this.orders.filter(o => this.statusGroup(this.statusOf(o)) === 'selesai').length,
                dibatalkan: this.orders.filter(o => this.statusGroup(this.statusOf(o)) === 'dibatalkan').length,
            };
        },
        get filteredOrders() {
            const q = this.searchQuery.trim().toLowerCase();
            return this.orders.filter(o => {
                const inGroup = this.activeTab === 'semua' || this.statusGroup(this.statusOf(o)) === this.activeTab;
                const inSearch = !q ||
                    (o.nomor_pesanan ?? '').toLowerCase().includes(q) ||
                    (o.items ?? []).some(it => (it.name ?? '').toLowerCase().includes(q));
                return inGroup && inSearch;
            });
        },

        dateOf(o) {
            try {
                return new Date(o.dibuat_pada).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch { return ''; }
        },
        totalOf(o) { return Number(o.total_pembayaran ?? o.total ?? 0); },
        initialOf(u) {
            const n = u?.nama_pengguna ?? '';
            return n.split(' ').map(x => x[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || 'BP';
        },
        formatRupiah(number) { return PandansariAPI.formatRp(number); },

        async load() {
            this.loading = true;
            const res = await PandansariAPI.orders({ page: this.page, per_page: this.perPage });
            this.loading = false;
            if (res.ok) {
                this.orders = (res.data ?? []).map(o => ({
                    ...o,
                    // Items pesanan dari backend bersifat FLAT (nama_produk, warna, ukuran,
                    // harga_satuan, jumlah, id_variant) — fallback ke bentuk nested bila ada
                    items: (o.items ?? []).map(it => {
                        const v = it.variant ?? {};
                        const p = v.product ?? it.product ?? {};
                        const harga = Number(v.harga ?? it.harga_satuan ?? it.harga ?? 0);
                        const ukuran = v.ukuran ?? it.ukuran;
                        const warna = v.warna ?? it.warna;
                        const jumlah = Number(it.jumlah ?? 1);
                        return {
                            name: p.nama_produk ?? it.nama_produk ?? 'Produk',
                            variant: [ukuran && ('Ukuran ' + ukuran), warna, jumlah > 1 && (jumlah + ' pcs')].filter(Boolean).join(' • '),
                            price: harga * jumlah,
                            image: PandansariAPI.getProductImage(it) || PandansariAPI.getProductImage(v) || PandansariAPI.coverUrl(p.images) || this.fallbackImage,
                            id_variant: v.id_variant ?? it.id_variant,
                            raw: it,
                        };
                    }),
                }));
                this.total = res.meta?.total ?? this.orders.length;
                this.lastPage = res.meta?.last_page ?? 1;
                this.cekBanyakPembayaran();
                this.refreshItemImages();
            } else if (res.status === 401) {
                this.needLogin = true;
            } else {
                this.errorMsg = res.message;
            }
        },

        refreshItemImages() {
            this.orders.forEach(o => {
                (o.items ?? []).forEach(it => {
                    const resolved = PandansariAPI.getProductImage(it.raw ?? it);
                    if (resolved) it.image = resolved;
                });
            });
            this.orders = [...this.orders];
        },
        /**
         * Pesanan berstatus 'menunggu_pembayaran' bisa saja sudah lunas di Midtrans
         * tapi webhook backend belum memperbarui status. Cek transaksi terakhir agar
         * label di riwayat tidak menyesatkan (tampil 'Menunggu Verifikasi').
         */
        async cekBanyakPembayaran() {
            const pending = this.orders.filter(o => (o.status_pesanan ?? '') === 'menunggu_pembayaran');
            if (!pending.length) return;
            await Promise.all(pending.map(async (o) => {
                try {
                    const id = o.id_order ?? o.id ?? o.nomor_pesanan;
                    const r = await PandansariAPI.lastPayment(id);
                    if (!r.ok || !r.data) return;
                    const p = Array.isArray(r.data) ? r.data[0] : r.data;
                    const st = String(
                        p?.status_transaksi ?? p?.transaction_status ?? p?.status_pembayaran ?? p?.status ?? ''
                    ).toLowerCase();
                    if (['settlement', 'capture', 'paid', 'lunas', 'dibayar', 'success', 'berhasil'].includes(st)) {
                        o.status_tampil = 'menunggu_verifikasi';
                    }
                } catch (e) { /* transaksi belum ada — abaikan */ }
            }));
        },

        gotoPage(n) {
            if (n < 1 || n > this.lastPage || n === this.page) return;
            this.page = n;
            this.load();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // Konfirmasi pembayaran manual via WhatsApp (ongkir & rekening dari admin).
        bayar(order) {
            if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
            this.busyPay = order.nomor_pesanan;

            const items = (order.items ?? []).map((it, index) => {
                const varian = it.variant ? ' (' + it.variant + ')' : '';
                const idBarang = it.id_variant ?? '-';
                return `${index + 1}. ${it.name}${varian} — ${this.formatRupiah(it.price)} [ID: ${idBarang}]`;
            });

            const lines = [
                'Halo Batik Pandansari, saya ingin konfirmasi pembayaran pesanan saya.',
                '',
                'Nomor Pesanan: ' + (order.nomor_pesanan ?? '-'),
                'Nama: ' + (order.nama_penerima ?? this.user?.nama_pengguna ?? '-'),
                'No. HP: ' + (order.nomor_telepon_penerima ?? this.user?.nomor_telepon ?? '-'),
                'Alamat: ' + (order.alamat_pengiriman ?? '-'),
                '',
                'Item:',
                ...(items.length ? items : ['-']),
                '',
                'Total (belum termasuk ongkir): ' + this.formatRupiah(this.totalOf(order)),
                'Status: Menunggu Pembayaran',
                '',
                'Mohon info ongkir dan rekening pembayarannya. Terima kasih.',
            ];

            this.busyPay = null;
            const url = 'https://wa.me/6283820380977?text=' + encodeURIComponent(lines.join('\n'));
            const win = window.open(url, '_blank', 'noopener');
            if (!win) window.location.href = url;
        },
        // Beli Lagi: masukkan item pesanan ke keranjang
        async beliLagi(order) {
            if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
            const ok = [];
            for (const it of order.items) {
                if (it.id_variant) {
                    const res = await PandansariAPI.addCartItem(it.id_variant, 1);
                    if (res.ok) ok.push(true);
                }
            }
            window.location.href = ok.length ? '/cart' : '/katalog';
        },

        async loadIdentity() {
            this.user = PandansariAPI.getUser();
            if (PandansariAPI.getToken()) {
                await PandansariAPI.me();
                this.user = PandansariAPI.getUser();
                const a = await PandansariAPI.addresses();
                this.addressesCount = (a.data ?? []).length;
            }
        },

        async keluar() {
            await PandansariAPI.logout();
            PandansariAPI.clearSession();
            window.location.href = '/';
        },

        async init() {
            if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
            try {
                if (typeof PandansariAPI?.loadCatalogImages === 'function') {
                    const p = PandansariAPI.loadCatalogImages();
                    if (p && typeof p.then === 'function') {
                        p.then(() => { this.refreshItemImages(); }).catch(() => {});
                    }
                }
            } catch (_) {}
            await Promise.all([this.load(), this.loadIdentity()]);
            this.refreshItemImages();
            // Segarkan daftar saat pengguna kembali dari halaman pembayaran
            // (tombol back / tab kembali aktif) agar status tidak tertinggal.
            window.addEventListener('pageshow', (e) => { if (e.persisted) this.load(); });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') this.load();
            });
        }
     }">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-6">
            <a href="/" class="hover:text-black transition">Beranda</a>
            <span>/</span>
            <a href="/account" class="hover:text-black transition">Akun Saya</a>
            <span>/</span>
            <span class="text-black font-bold">Riwayat Pesanan</span>
        </nav>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDEBAR: Navigasi Akun (4 Kolom) -->
            <div class="lg:col-span-4 space-y-4">
                
                <!-- Card Profile Header (identitas dari API) -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm text-center space-y-2">
                    <div class="w-16 h-16 bg-[#0F1A24] text-white font-bold text-xl rounded-2xl flex items-center justify-center mx-auto shadow-md">
                        <span x-text="initialOf(user) || 'BP'"></span>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-[#0F1A24]" x-text="user?.nama_pengguna ?? 'Pengguna'"></h2>
                        <p class="text-xs text-slate-400 font-medium" x-text="user?.email ?? ''"></p>
                    </div>
                </div>

                <!-- Navigation Menu Sidebar -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden text-xs divide-y divide-slate-100">
                    <a href="/profile" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-user text-slate-400 text-sm"></i>
                            <span class="font-semibold">Profil & Biodata Diri</span>
                        </div>
                        <i class="fa-solid fa-angle-right text-slate-300"></i>
                    </a>

                    <!-- Active Menu: Riwayat Pesanan -->
                    <a href="/orders" class="flex items-center justify-between p-4 bg-sky-50/50 text-[#0369A1] font-bold border-l-4 border-[#0369A1]">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-receipt text-[#0369A1] text-sm"></i>
                            <span>Riwayat Pesanan</span>
                        </div>
                        <span class="bg-sky-100 text-[#0369A1] px-2 py-0.5 rounded font-bold text-[10px]" x-text="counts.berlangsung + ' Aktif'"></span>
                    </a>

                    <a href="/address" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-slate-400 text-sm"></i>
                            <span class="font-semibold">Buku Alamat Pengiriman</span>
                        </div>
                        <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold text-[10px]" x-text="addressesCount + ' Alamat'"></span>
                    </a>

                    <a href="/wishlist" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-heart text-slate-400 text-sm"></i>
                            <span class="font-semibold">Koleksi Favorit (Wishlist)</span>
                        </div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-slate-300 text-[10px]"></i>
                    </a>

                    <a href="/security" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-lock text-slate-400 text-sm"></i>
                            <span class="font-semibold">Keamanan & Kata Sandi</span>
                        </div>
                        <i class="fa-solid fa-angle-right text-slate-300"></i>
                    </a>

                    <div class="p-4 flex items-center justify-between bg-slate-50/50">
                        <a href="#" @click.prevent="keluar()" class="text-rose-600 font-bold flex items-center gap-2 hover:underline">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar Akun</span>
                        </a>
                        <span class="text-[9px] font-bold text-rose-500 uppercase">SIGN OUT</span>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDE: Content Utama Riwayat Pesanan (8 Kolom) -->
            <div class="lg:col-span-8 space-y-5">

                <!-- Title Header -->
                <div class="flex items-center justify-between gap-4">
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Riwayat Pesanan</h1>
                </div>

                <!-- Banner Status -->
                <template x-if="needLogin">
                    <div class="bg-sky-50 border border-sky-200 text-[#0369A1] text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-3">
                        <span><i class="fa-solid fa-lock mr-1.5"></i> Masuk untuk melihat riwayat pesanan Anda.</span>
                        <a href="/login" class="bg-[#0F1A24] text-white text-xs font-bold px-4 py-2 rounded-xl">MASUK →</a>
                    </div>
                </template>
                <template x-if="errorMsg">
                    <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span x-text="errorMsg"></span>
                    </div>
                </template>

                <!-- Tabs Filter Status (grup status dari API) -->
                <div class="flex flex-wrap items-center gap-2" x-show="!needLogin">
                    <button @click="activeTab = 'semua'"
                            type="button"
                            class="px-4 py-2 rounded-full text-xs font-bold transition cursor-pointer"
                            :class="activeTab === 'semua' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Semua <span class="ml-1 text-[10px] opacity-80" x-text="'(' + counts.semua + ')'"></span>
                    </button>

                    <button @click="activeTab = 'berlangsung'"
                            type="button"
                            class="px-4 py-2 rounded-full text-xs font-bold transition cursor-pointer"
                            :class="activeTab === 'berlangsung' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Berlangsung <span class="ml-1 text-[10px] opacity-80" x-text="'(' + counts.berlangsung + ')'"></span>
                    </button>

                    <button @click="activeTab = 'selesai'"
                            type="button"
                            class="px-4 py-2 rounded-full text-xs font-bold transition cursor-pointer"
                            :class="activeTab === 'selesai' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Selesai <span class="ml-1 text-[10px] opacity-80" x-text="'(' + counts.selesai + ')'"></span>
                    </button>

                    <button @click="activeTab = 'dibatalkan'"
                            type="button"
                            class="px-4 py-2 rounded-full text-xs font-bold transition cursor-pointer"
                            :class="activeTab === 'dibatalkan' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Dibatalkan <span class="ml-1 text-[10px] opacity-80" x-text="'(' + counts.dibatalkan + ')'"></span>
                    </button>
                </div>

                <!-- Input Pencarian & Dropdown Waktu -->
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative w-full flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               x-model.debounce.300ms="searchQuery"
                               placeholder="Cari nomor pesanan atau nama produk..." 
                               class="w-full text-xs font-medium border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                    </div>

                    <select class="w-full sm:w-auto text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm cursor-pointer">
                        <option>30 Hari Terakhir</option>
                        <option>3 Bulan Terakhir</option>
                        <option>Tahun 2024</option>
                    </select>
                </div>

                <!-- Daftar Card Pesanan (dari GET /api/orders) -->
                <div class="space-y-4" x-show="!needLogin">
                    <!-- Loading -->
                    <template x-if="loading">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                            <i class="fa-solid fa-circle-notch fa-spin text-xl text-sky-500"></i>
                            <p class="text-xs text-slate-500 mt-3 font-semibold">Memuat riwayat pesanan…</p>
                        </div>
                    </template>

                    <!-- Kosong -->
                    <template x-if="!loading && !filteredOrders.length">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                            <i class="fa-regular fa-receipt text-3xl text-slate-300 block mb-3"></i>
                            <p class="text-sm font-bold text-[#0F1A24]">Belum ada pesanan</p>
                            <p class="text-xs text-slate-500 mt-1">Pesanan Anda dari toko akan tampil di sini.</p>
                            <a href="/katalog" class="inline-block mt-4 bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">MULAI BELANJA →</a>
                        </div>
                    </template>

                    <template x-for="order in filteredOrders" :key="order.id_order">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6">

                            <!-- Header Card: Tanggal, Nomor, & Badge Status -->
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24]">
                                    <span x-text="dateOf(order)"></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-500 font-medium" x-text="`#${order.nomor_pesanan}`"></span>
                                </div>

                                <span class="text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider"
                                      :class="statusBadgeClass(statusOf(order))"
                                      x-text="statusLabels[statusOf(order)] ?? statusOf(order)">
                                </span>
                            </div>

                            <!-- List Item Produk dalam Pesanan -->
                            <div class="space-y-3 divide-y divide-slate-100">
                                <template x-for="(item, idx) in order.items" :key="idx">
                                    <div class="flex items-center justify-between gap-4 pt-2 first:pt-0">
                                        <div class="flex items-center gap-3">
                                            <img :src="item.image"
                                                 :alt="item.name"
                                                 class="w-14 h-16 object-cover rounded-xl border border-slate-100 shrink-0 bg-slate-100"
                                                 :onerror="`this.src='${fallbackImage}'`">
                                            <div>
                                                <h4 class="text-xs font-bold text-[#0F1A24]" x-text="item.name"></h4>
                                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="item.variant"></p>
                                            </div>
                                        </div>

                                        <span class="text-xs font-bold text-[#0F1A24] shrink-0" x-text="formatRupiah(item.price)"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Footer Card: Total Belanja & Action Buttons -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                <div class="text-xs">
                                    <span class="text-slate-500">Total Pesanan: </span>
                                    <span class="font-bold text-[#0F1A24] text-sm" x-text="formatRupiah(totalOf(order))"></span>
                                </div>

                                <div class="flex items-center gap-2 justify-end">
                                    <!-- Tombol Bayar via Midtrans Snap (ke detail pesanan) -->
                                    <template x-if="order.status_pesanan === 'menunggu_pembayaran' && !order.status_tampil">
                                        <a :href="'/orders/' + (order.id_order || order.id || order.nomor_pesanan)"
                                           class="bg-[#0369A1] hover:bg-[#0284C7] text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-sm shadow-sky-500/20">
                                            <i class="fa-solid fa-credit-card text-xs"></i>
                                            <span>BAYAR SEKARANG</span>
                                        </a>
                                    </template>

                                    <!-- Konfirmasi pembayaran manual via WhatsApp -->
                                    <template x-if="order.status_pesanan === 'menunggu_pembayaran' && !order.status_tampil">
                                        <button type="button" @click="bayar(order)"
                                                class="bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer shadow-sm shadow-emerald-500/20">
                                            <i class="fa-brands fa-whatsapp text-sm"></i>
                                            <span>KONFIRMASI WA</span>
                                        </button>
                                    </template>

                                    <!-- Beli Lagi -->
                                    <template x-if="order.status_pesanan !== 'dibatalkan'">
                                        <button type="button" @click="beliLagi(order)"
                                                class="bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-bag-shopping text-xs"></i>
                                            <span>BELI LAGI</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- Pagination Bottom (meta dari API) -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 text-xs text-slate-500" x-show="!needLogin && lastPage > 1">
                    <span>Halaman <strong x-text="page"></strong> dari <strong x-text="lastPage"></strong> (total <strong x-text="total"></strong> pesanan)</span>

                    <div class="flex items-center gap-1">
                        <button type="button" @click="gotoPage(page - 1)"
                                class="w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center justify-center cursor-pointer"
                                :class="page > 1 ? 'bg-white' : 'bg-white text-slate-300 cursor-not-allowed'">
                            <i class="fa-solid fa-angle-left text-xs"></i>
                        </button>
                        <span class="w-8 h-8 rounded-lg bg-[#BAE6FD] text-[#0369A1] font-bold flex items-center justify-center" x-text="page"></span>
                        <button type="button" @click="gotoPage(page + 1)"
                                class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 flex items-center justify-center cursor-pointer"
                                :class="page < lastPage ? '' : 'text-slate-300 cursor-not-allowed'">
                            <i class="fa-solid fa-angle-right text-xs"></i>
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection