@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Data API ===
    orders: [],
    meta: null,
    page: 1,
    perPage: 8,
    searchQuery: '',
    selectedTab: 'ALL',        // '' | menunggu_pembayaran | dibayar | diproses | dikirim | selesai | dibatalkan
    summary: null,
    loading: true,
    notice: '', noticeOk: true,

    // === Detail & aksi ===
    detail: null,
    detailBusy: false,
    newStatus: '',
    statusNote: '',
    fulfillStatus: 'dikemas',
    statusBusy: false,

    STATUS_LIST: ['menunggu_pembayaran','dibayar','diproses','dikirim','selesai','dibatalkan'],

    fmt(n) { return PandansariAPI.formatRp(n); },
    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    waktu(s) {
        if (!s) return '';
        try { return new Date(s).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch { return s; }
    },
    statusClass(s) {
        return {
            menunggu_pembayaran: 'bg-amber-100 text-amber-800',
            dibayar: 'bg-emerald-100 text-emerald-800',
            diproses: 'bg-sky-100 text-sky-800',
            dikirim: 'bg-indigo-100 text-indigo-800',
            selesai: 'bg-emerald-100 text-emerald-800',
            dibatalkan: 'bg-rose-100 text-rose-800',
        }[s] ?? 'bg-slate-100 text-slate-700';
    },
    countStatus(s) {
        if (s === 'ALL') return this.summary?.total_pesanan ?? 0;
        return this.summary?.per_status?.[s] ?? 0;
    },
    itemPertama(o) {
        const it = (o.items ?? [])[0];
        if (!it) return null;
        return it.nama_produk ?? it.variant?.product?.nama_produk ?? 'Produk';
    },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    // === Muat data ===
    async loadOrders() {
        this.loading = true;
        const res = await PandansariAPI.admin.orders({
            page: this.page, per_page: this.perPage,
            search: this.searchQuery.trim() || undefined,
            status: this.selectedTab !== 'ALL' ? this.selectedTab : undefined,
        });
        this.loading = false;
        if (res.ok) {
            this.orders = res.data ?? [];
            this.meta = res.meta;
            this.page = res.meta?.current_page ?? this.page;
        } else this.pesan(false, res.message);
    },
    async loadSummary() {
        const res = await PandansariAPI.admin.ordersSummary();
        if (res.ok) this.summary = res.data;
    },
    setTab(t) { this.selectedTab = t; this.page = 1; this.loadOrders(); },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadOrders(); },
    resetFilter() { this.searchQuery = ''; this.selectedTab = 'ALL'; this.page = 1; this.loadOrders(); },

    // === Detail pesanan ===
    async bukaDetail(o) {
        this.detailBusy = true;
        this.detail = null;
        this.newStatus = o.status_pesanan;
        this.statusNote = '';
        const res = await PandansariAPI.admin.order(o.id_order);
        this.detailBusy = false;
        if (res.ok) {
            this.detail = res.data;
            this.newStatus = res.data.status_pesanan ?? this.newStatus;
        } else this.pesan(false, res.message);
    },
    tutupDetail() { this.detail = null; },

    // === Ubah status pesanan ===
    async simpanStatus() {
        if (!this.detail) return;
        if (!this.newStatus) { this.pesan(false, 'Pilih status baru terlebih dahulu.'); return; }
        if (this.newStatus === this.detail.status_pesanan && !this.statusNote) {
            this.pesan(false, 'Status sama dengan saat ini — tidak ada perubahan.'); return;
        }
        this.statusBusy = true;
        const res = await PandansariAPI.admin.updateOrderStatus(this.detail.id_order, this.newStatus, this.statusNote.trim() || null);
        this.statusBusy = false;
        if (res.ok) {
            this.pesan(true, 'Status pesanan diperbarui menjadi ' + this.newStatus.replaceAll('_', ' ') + '.');
            this.bukaDetail(this.detail);
            this.loadOrders();
            this.loadSummary();
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },

    // === Tambah fulfillment (packing) ===
    async tambahFulfillment() {
        if (!this.detail) return;
        this.statusBusy = true;
        const res = await PandansariAPI.admin.addFulfillment(this.detail.id_order, this.fulfillStatus);
        if (res.ok) {
            this.pesan(true, 'Fulfillment (' + this.fulfillStatus + ') tercatat untuk pesanan ini.');
            // muat daftar fulfillment milik pesanan ini
            const f = await PandansariAPI.admin.orderFulfillments({ per_page: 50 });
            if (f.ok) {
                this.detail.fulfillments = (f.data ?? []).filter(x => x.id_order === this.detail.id_order);
            } else {
                this.detail.fulfillments = this.detail.fulfillments ?? [];
            }
            this.loadOrders();
            this.loadSummary();
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
        this.statusBusy = false;
    },

    // === Ekspor CSV (client-side, dari halaman aktif) ===
    eksporCsv() {
        const Q = '\x22';
        const head = ['nomor_pesanan','tanggal','penerima','kota','total','ongkir','status'];
        const rows = this.orders.map(o => [
            o.nomor_pesanan, o.dibuat_pada, o.nama_penerima, o.kode_tujuan_pengiriman,
            o.total_pembayaran, o.biaya_pengiriman, o.status_pesanan,
        ]);
        const csv = [head, ...rows].map(r => r.map(c => Q + String(c ?? '').split(Q).join(Q + Q) + Q).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'pesanan.csv';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2000);
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await Promise.all([this.loadOrders(), this.loadSummary()]);
        this.loading = false;
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka manajemen pesanan</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER TITLE & EXPORT BUTTON -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Pesanan Masuk & Logistik Transaksi
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                GET /admin/orders — cari, filter status, ubah status pesanan, dan catat fulfillment packing.
            </p>
        </div>

        <button type="button" @click="eksporCsv()" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer">
            <i class="fa-solid fa-file-export text-xs"></i>
            <span>Ekspor CSV (halaman ini)</span>
        </button>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. ANTREAN PACKING & PICK-UP SUMMARY CARDS (dari summary API) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">ANTREAN PACKING (DIPROSES)</span>
                <div class="text-2xl font-black text-slate-900"><span x-text="summary ? num(summary.per_status?.diproses ?? 0) : '…'"></span> <span class="text-base font-bold text-slate-700">Pesanan</span></div>
                <p class="text-[10px] font-bold text-sky-700">Siap diproses admin</p>
            </div>
            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 text-base">
                <i class="fa-solid fa-box"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">MENUNGGU PICK-UP (DIKIRIM)</span>
                <div class="text-2xl font-black text-slate-900"><span x-text="summary ? num(summary.per_status?.dikirim ?? 0) : '…'"></span> <span class="text-base font-bold text-slate-700">Paket</span></div>
                <p class="text-[10px] font-bold text-slate-500">Sedang dalam perjalanan</p>
            </div>
            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 text-base">
                <i class="fa-solid fa-truck-ramp-box"></i>
            </div>
        </div>

    </div>

    <!-- 3. STATUS TABS (filter server-side) -->
    <div class="bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-sm inline-flex flex-wrap items-center gap-1 text-xs font-bold">
        <button @click="setTab('ALL')"
                :class="selectedTab === 'ALL' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
            <span>Semua</span>
            <span class="px-1.5 py-0.5 text-[10px] rounded-md font-black" :class="selectedTab === 'ALL' ? 'bg-sky-500/30 text-sky-300' : 'bg-slate-100 text-slate-600'" x-text="summary ? num(summary.total_pesanan) : '…'"></span>
        </button>

        <template x-for="s in ['menunggu_pembayaran','dibayar','diproses','dikirim','selesai','dibatalkan']" :key="s">
            <button @click="setTab(s)"
                    :class="selectedTab === s ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
                <span x-text="s.replaceAll('_', ' ')"></span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-md font-black"
                      :class="selectedTab === s ? 'bg-sky-500/30 text-sky-300' : 'bg-slate-100 text-slate-600'"
                      x-text="summary ? num(summary.per_status?.[s] ?? 0) : '…'"></span>
            </button>
        </template>
    </div>

    <!-- 4. SEARCH & RESET -->
    <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
        <div class="relative w-full sm:w-72">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text"
                   x-model.debounce.600ms="searchQuery"
                   @input.debounce.600ms="page = 1; loadOrders()"
                   placeholder="Cari nomor pesanan, nama penerima, kota..."
                   class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <button type="button" @click="resetFilter()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold flex items-center gap-1.5 transition self-start md:self-auto cursor-pointer">
            <i class="fa-solid fa-rotate-left text-xs"></i>
            <span>Reset Filter</span>
        </button>
    </div>

    <!-- 5. ORDERS TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#AEE2F0]/40 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4">ID & WAKTU</th>
                        <th class="py-3.5 px-4">PENERIMA</th>
                        <th class="py-3.5 px-4">KATALOG & KAIN</th>
                        <th class="py-3.5 px-4">TOTAL NOMINAL</th>
                        <th class="py-3.5 px-4">STATUS PESANAN</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    <template x-if="loading">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat pesanan…</td></tr>
                    </template>
                    <template x-if="!loading && !orders.length">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold">Tidak ada pesanan untuk filter ini.</td></tr>
                    </template>

                    <template x-for="item in orders" :key="item.id_order">
                        <tr class="hover:bg-slate-50/80 transition">

                            <!-- ID & Waktu -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-extrabold block text-xs" x-text="item.nomor_pesanan"></strong>
                                <span class="text-[10px] text-slate-400 block" x-text="waktu(item.dibuat_pada)"></span>
                            </td>

                            <!-- Penerima -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-bold block text-xs" x-text="item.nama_penerima ?? '—'"></strong>
                                <span class="text-[10px] text-slate-500 block font-normal" x-text="item.kode_tujuan_pengiriman ?? '—'"></span>
                                <span class="text-[10px] text-slate-400 block font-mono" x-text="item.nomor_telepon_penerima ?? ''"></span>
                            </td>

                            <!-- Katalog & Kain -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 text-xs font-extrabold block" x-text="itemPertama(item) ?? '—'"></strong>
                                <span class="text-[10px] text-slate-500 block" x-text="(item.items?.length ?? 0) + ' item dalam keranjang'"></span>
                            </td>

                            <!-- Total Nominal -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 text-sm font-black block" x-text="fmt(item.total_pembayaran)"></strong>
                                <span class="text-[10px] text-slate-500 font-bold block mt-0.5" x-text="'Ongkir ' + fmt(item.biaya_pengiriman)"></span>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4">
                                <span class="text-[9px] font-extrabold px-2.5 py-1 rounded-md uppercase inline-block"
                                      :class="statusClass(item.status_pesanan)"
                                      x-text="(item.status_pesanan ?? '').replaceAll('_', ' ')"></span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-4 px-4 text-center">
                                <button type="button" @click="bukaDetail(item)"
                                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-[#0A192F] hover:text-white text-slate-700 transition inline-flex items-center justify-center cursor-pointer" title="Detail & Ubah Status">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Info & Pagination -->
        <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-medium text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fa-regular fa-folder-open text-amber-700"></i>
                <span>Menampilkan halaman <strong x-text="meta?.current_page ?? 1"></strong> — total <strong x-text="num(meta?.total ?? 0)"></strong> pesanan</span>
            </div>

            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold text-xs">
                <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lt; Sebelumnya</button>
                <span class="px-2 text-slate-700" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
                <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">Selanjutnya &gt;</button>
            </div>
        </div>
    </div>

    <!-- 6. MIDTRANS STATUS BANNER -->
    <div class="p-3 bg-slate-100 rounded-xl border border-slate-200/80 flex items-center gap-2 text-[11px] font-extrabold text-slate-700">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>MIDTRANS PRODUCTION ONLINE</span>
    </div>

    </div>

    <!-- ===== MODAL DETAIL PESANAN ===== -->
    <div x-show="detail || detailBusy" x-cloak
         class="fixed inset-0 z-[9999] bg-slate-950/80 backdrop-blur-md flex items-start justify-center p-4 sm:p-6 overflow-y-auto"
         @keydown.escape.window="tutupDetail()">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl border border-slate-100 my-6" @click.outside="tutupDetail()">

            <div x-show="detailBusy" class="p-10 text-center text-xs text-slate-400 font-semibold">
                <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat detail pesanan…
            </div>

            <template x-if="detail">
                <div>
                    <!-- Header -->
                    <div class="bg-[#0A192F] text-white p-5 px-6 rounded-t-2xl flex items-center justify-between">
                        <div>
                            <h3 class="font-extrabold text-base" x-text="detail.nomor_pesanan"></h3>
                            <p class="text-[11px] text-slate-300" x-text="waktu(detail.dibuat_pada) + ' • ' + (detail.nama_penerima ?? '')"></p>
                        </div>
                        <button type="button" @click="tutupDetail()" class="w-8 h-8 rounded-xl bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center justify-center cursor-pointer">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 text-xs max-h-[70vh] overflow-y-auto">

                        <!-- Info ringkas -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-slate-50 rounded-xl p-3">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">SUBTOTAL</span>
                                <strong class="text-slate-900" x-text="fmt(detail.subtotal)"></strong>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">ONGKIR</span>
                                <strong class="text-slate-900" x-text="fmt(detail.biaya_pengiriman)"></strong>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">DISKON</span>
                                <strong class="text-slate-900" x-text="'- ' + fmt(detail.diskon)"></strong>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">STATUS</span>
                                <span class="font-extrabold uppercase px-2 py-0.5 rounded text-[10px]"
                                      :class="statusClass(detail.status_pesanan)"
                                      x-text="(detail.status_pesanan ?? '').replaceAll('_', ' ')"></span>
                            </div>
                        </div>

                        <!-- Alamat & Pembayaran -->
                        <div class="border border-slate-200 rounded-xl p-4 space-y-1">
                            <span class="block text-[9px] font-bold uppercase text-slate-400">ALAMAT PENGIRIMAN</span>
                            <strong class="text-slate-900 block" x-text="detail.nama_penerima ?? '—'"></strong>
                            <p class="text-slate-600" x-text="detail.alamat_pengiriman ?? ''"></p>
                            <p class="text-slate-500 font-mono" x-text="detail.nomor_telepon_penerima ?? ''"></p>
                            <p class="text-slate-500" x-text="detail.user?.email ?? ''"></p>
                            <p class="text-slate-500 text-[10px]" x-text="detail.catatan ? ('Catatan: ' + detail.catatan) : ''"></p>
                        </div>

                        <!-- Pembayaran (relasi dari detail) -->
                        <div x-show="(detail.payments ?? []).length" class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 text-[9px] font-bold uppercase text-slate-500">
                                        <th class="py-2.5 px-3">PROVIDER</th>
                                        <th class="py-2.5 px-3">METODE</th>
                                        <th class="py-2.5 px-3 text-right">JUMLAH</th>
                                        <th class="py-2.5 px-3 text-center">STATUS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="pmt in (detail.payments ?? [])" :key="pmt.id_payment">
                                        <tr>
                                            <td class="py-2.5 px-3">
                                                <strong class="text-slate-900" x-text="pmt.provider"></strong>
                                                <span class="block text-[10px] text-slate-400 font-mono" x-text="pmt.payment_reference ?? ''"></span>
                                            </td>
                                            <td class="py-2.5 px-3" x-text="pmt.metode_pembayaran ?? '—'"></td>
                                            <td class="py-2.5 px-3 text-right font-bold" x-text="fmt(pmt.jumlah_pembayaran)"></td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded"
                                                      :class="pmt.status_pembayaran === 'dibayar' ? 'bg-emerald-100 text-emerald-800' : (pmt.status_pembayaran === 'menunggu' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600')"
                                                      x-text="pmt.status_pembayaran"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Item -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 text-[9px] font-bold uppercase text-slate-500">
                                        <th class="py-2.5 px-3">PRODUK / VARIAN</th>
                                        <th class="py-2.5 px-3 text-center">QTY</th>
                                        <th class="py-2.5 px-3 text-right">SUBTOTAL</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="it in (detail.items ?? [])" :key="it.id_order_item ?? it.id">
                                        <tr>
                                            <td class="py-2.5 px-3">
                                                <strong class="text-slate-900 block" x-text="it.nama_produk ?? it.variant?.product?.nama_produk ?? 'Produk'"></strong>
                                                <span class="text-[10px] text-slate-400" x-text="[it.sku, it.warna, it.ukuran].filter(Boolean).join(' • ')"></span>
                                            </td>
                                            <td class="py-2.5 px-3 text-center font-bold" x-text="it.jumlah"></td>
                                            <td class="py-2.5 px-3 text-right font-bold" x-text="fmt(it.subtotal ?? (Number(it.jumlah) * Number(it.harga_satuan ?? 0)))"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Riwayat status -->
                        <div x-show="(detail.status_histories ?? []).length" class="border border-slate-200 rounded-xl p-4 space-y-3">
                            <span class="block text-[9px] font-bold uppercase text-slate-400">RIWAYAT STATUS</span>
                            <template x-for="(h, i) in (detail.status_histories ?? [])" :key="i">
                                <div class="flex items-start gap-3">
                                    <span class="w-2 h-2 rounded-full bg-sky-500 mt-1.5 shrink-0"></span>
                                    <div>
                                        <strong class="text-slate-900 block" x-text="(h.status_baru ?? '').replaceAll('_', ' ') + (h.status_sebelumnya ? ' (dari ' + h.status_sebelumnya.replaceAll('_', ' ') + ')' : '')"></strong>
                                        <span class="text-[10px] text-slate-400" x-text="waktu(h.waktu_perubahan ?? h.dibuat_pada) + (h.catatan ? ' — ' + h.catatan : '') + (h.changed_by?.nama_pengguna ? ' • oleh ' + h.changed_by.nama_pengguna : '')"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Aksi: ubah status -->
                        <div class="border border-slate-200 rounded-xl p-4 space-y-3">
                            <span class="block text-[9px] font-bold uppercase text-slate-400">UBAH STATUS PESANAN (PUT /admin/orders/{id}/status)</span>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <select x-model="newStatus" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                                    <template x-for="s in STATUS_LIST" :key="s">
                                        <option :value="s" x-text="s.replaceAll('_', ' ')"></option>
                                    </template>
                                </select>
                                <input type="text" x-model="statusNote" placeholder="Catatan (opsional)…"
                                       class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
                                <button type="button" @click="simpanStatus()" :disabled="statusBusy"
                                        class="bg-[#0A192F] text-white font-bold px-5 py-2.5 rounded-xl hover:bg-slate-800 transition cursor-pointer disabled:opacity-60">
                                    <span x-show="!statusBusy">SIMPAN STATUS</span>
                                    <span x-show="statusBusy"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                                </button>
                            </div>

                            <div class="pt-3 border-t border-slate-100 space-y-2">
                                <span class="block text-[9px] font-bold uppercase text-slate-400">CATAT FULFILLMENT (PACKING) — POST /admin/orders/{id}/fulfillments</span>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <select x-model="fulfillStatus" class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                                        <option value="dikemas">dikemas (sedang dipacking)</option>
                                        <option value="selesai">selesai (siap/kirim selesai)</option>
                                    </select>
                                    <button type="button" @click="tambahFulfillment()" :disabled="statusBusy"
                                            class="bg-sky-100 hover:bg-sky-200 text-sky-800 font-bold px-5 py-2.5 rounded-xl transition cursor-pointer disabled:opacity-60">
                                        <span x-show="!statusBusy">CATAT</span>
                                        <span x-show="statusBusy"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                                    </button>
                                </div>
                                <div x-show="(detail.fulfillments ?? []).length" class="pt-2 space-y-1">
                                    <template x-for="(f, i) in (detail.fulfillments ?? [])" :key="i">
                                        <p class="text-[10px] text-slate-500">
                                            <i class="fa-solid fa-box text-sky-500 mr-1"></i>
                                            <span class="font-bold uppercase" x-text="f.status_fulfillment"></span>
                                            <span x-text="waktu(f.dibuat_pada ?? f.created_at)"></span>
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

@endsection
