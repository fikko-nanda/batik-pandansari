@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Data API ===
    movements: [],
    meta: null,
    page: 1,
    perPage: 15,
    searchQuery: '',
    selectedType: 'ALL',
    selectedVariant: '',
    dateFrom: '',
    dateTo: '',
    loading: true,
    notice: '', noticeOk: true,
    exporting: false,

    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    fmt(n) { return PandansariAPI.formatRp(n); },
    tanggal(s) {
        if (!s) return '—';
        try { return new Date(s).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch { return s; }
    },
    tipeBadge(t) {
        const map = {
            masuk: 'bg-emerald-100 text-emerald-800',
            keluar: 'bg-rose-100 text-rose-800',
            koreksi: 'bg-amber-100 text-amber-800',
            opname: 'bg-sky-100 text-sky-800',
            penjualan: 'bg-indigo-100 text-indigo-800',
            retur: 'bg-violet-100 text-violet-800',
        };
        return map[t] ?? 'bg-slate-100 text-slate-700';
    },
    tipeLabel(t) {
        const map = {
            masuk: 'Masuk',
            keluar: 'Keluar',
            koreksi: 'Koreksi',
            opname: 'Opname',
            penjualan: 'Penjualan',
            retur: 'Retur',
        };
        return map[t] ?? t;
    },
    tipeIcon(t) {
        const map = {
            masuk: 'fa-arrow-down',
            keluar: 'fa-arrow-up',
            koreksi: 'fa-pen-to-square',
            opname: 'fa-clipboard-list',
            penjualan: 'fa-cart-shopping',
            retur: 'fa-rotate-left',
        };
        return map[t] ?? 'fa-circle-question';
    },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    async loadMovements() {
        this.loading = true;
        const res = await PandansariAPI.admin.inventoryMovements({
            page: this.page, per_page: this.perPage,
            search: this.searchQuery.trim() || undefined,
            tipe: this.selectedType !== 'ALL' ? this.selectedType : undefined,
            id_variant: this.selectedVariant || undefined,
            dari: this.dateFrom || undefined,
            sampai: this.dateTo || undefined,
        });
        this.loading = false;
        if (res.ok) {
            this.movements = res.data ?? [];
            this.meta = res.meta;
            this.page = res.meta?.current_page ?? this.page;
        } else this.pesan(false, res.message);
    },
    setType(t) { this.selectedType = t; this.page = 1; this.loadMovements(); },
    setVariant(v) { this.selectedVariant = v; this.page = 1; this.loadMovements(); },
    setDateFrom(d) { this.dateFrom = d; this.page = 1; this.loadMovements(); },
    setDateTo(d) { this.dateTo = d; this.page = 1; this.loadMovements(); },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadMovements(); },
    resetFilter() { this.searchQuery = ''; this.selectedType = 'ALL'; this.selectedVariant = ''; this.dateFrom = ''; this.dateTo = ''; this.page = 1; this.loadMovements(); },

    async exportCsv() {
        this.exporting = true;
        const res = await PandansariAPI.admin.inventoryExport();
        this.exporting = false;
        if (!res.ok) this.pesan(false, res.message);
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await this.loadMovements();
        this.loading = false;
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka riwayat stok</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER TITLE & EXPORT -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-[10px] font-black text-amber-700 uppercase tracking-widest block mb-0.5">KURASI STOK & AUDIT</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Riwayat Pergerakan Stok
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                GET /admin/inventory/movements — filter tipe, varian, rentang tanggal. Export CSV tersedia.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button type="button" @click="exportCsv()" :disabled="exporting"
                    class="px-5 py-2.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <i class="fa-solid" :class="exporting ? 'fa-circle-notch fa-spin' : 'fa-file-export'"></i>
                <span x-text="exporting ? 'Mengekspor…' : 'Export CSV'"></span>
            </button>
            <a href="/admin/stock-opname" class="px-4 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-clipboard-list text-amber-600"></i>
                <span>Stok Opname</span>
            </a>
            <a href="/admin/stock" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#96d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-boxes-packing text-slate-800"></i>
                <span>Kurasi Stok</span>
            </a>
        </div>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. FILTER BAR -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 space-y-4">
        <!-- Row 1: Search + Type Filter -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1 min-w-[240px]">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text"
                       x-model.debounce.600ms="searchQuery"
                       @input.debounce.600ms="page = 1; loadMovements()"
                       placeholder="Cari SKU, nama produk, catatan..."
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <div class="flex items-center gap-1.5 flex-wrap">
                <button type="button" @click="setType('ALL')"
                        :class="selectedType === 'ALL' ? 'bg-[#0A192F] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        class="px-3 py-2 rounded-xl font-bold transition cursor-pointer text-xs">Semua</button>
                <template x-for="t in ['masuk','keluar','koreksi','opname','penjualan','retur']" :key="t">
                    <button type="button" @click="setType(t)"
                            :class="selectedType === t ? 'bg-[#0A192F] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3 py-2 rounded-xl font-bold transition cursor-pointer text-xs flex items-center gap-1.5">
                        <i :class="tipeIcon(t) + ' text-xs'"></i>
                        <span x-text="tipeLabel(t)"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Row 2: Date Range + Variant Filter -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider shrink-0">Dari:</span>
                <input type="date" x-model="dateFrom" @change="setDateFrom(dateFrom)"
                       class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider shrink-0">Sampai:</span>
                <input type="date" x-model="dateTo" @change="setDateTo(dateTo)"
                       class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>
            <div class="relative flex-1 min-w-[200px]">
                <i class="fa-solid fa-box absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <select x-model="selectedVariant" @change="setVariant(selectedVariant)"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-sky-400 appearance-none">
                    <option value="">Semua Varian</option>
                    <!-- Options populated from API if needed -->
                </select>
            </div>
        </div>

        <!-- Row 3: Reset + Summary -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
            <button type="button" @click="resetFilter()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition self-start sm:self-auto cursor-pointer">
                <i class="fa-solid fa-rotate-left text-xs"></i>
                <span>Reset Filter</span>
            </button>
            <div class="flex items-center gap-3 text-xs text-slate-500 font-bold self-end sm:self-auto">
                <i class="fa-solid fa-rotate text-sky-500"></i>
                <span>Menampilkan <strong class="text-slate-900" x-text="movements.length"></strong> pergerakan (hal. <span x-text="meta?.current_page ?? 1"></span>/<span x-text="meta?.last_page ?? 1"></span>) — total <strong x-text="num(meta?.total ?? 0)"></strong></span>
            </div>
        </div>
    </div>

    <!-- 3. MOVEMENTS TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4">TANGGAL & WAKTU</th>
                        <th class="py-3.5 px-4">TIPE</th>
                        <th class="py-3.5 px-4">VARIAN & PRODUK</th>
                        <th class="py-3.5 px-4 text-right">JUMLAH</th>
                        <th class="py-3.5 px-4 text-center">STOK SEBELUM</th>
                        <th class="py-3.5 px-4 text-center">STOK SESUDAH</th>
                        <th class="py-3.5 px-4">REFERENSI</th>
                        <th class="py-3.5 px-4">USER & CATATAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    <template x-if="loading">
                        <tr><td colspan="8" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat riwayat stok…</td></tr>
                    </template>
                    <template x-if="!loading && !movements.length">
                        <tr><td colspan="8" class="py-10 text-center text-slate-400 font-semibold">Tidak ada pergerakan stok untuk filter ini.</td></tr>
                    </template>

                    <template x-for="m in movements" :key="m.id_inventory_movement">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-extrabold block text-xs" x-text="tanggal(m.dibuat_pada)"></strong>
                                <span class="text-[10px] text-slate-400 block" x-text="tanggal(m.tanggal)"></span>
                            </td>

                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase inline-block flex items-center gap-1.5" :class="tipeBadge(m.tipe)">
                                    <i :class="tipeIcon(m.tipe) + ' text-[9px]'></i>
                                    <span x-text="tipeLabel(m.tipe)"></span>
                                </span>
                            </td>

                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-extrabold block text-xs" x-text="m.variant?.sku ?? ('#' + m.id_variant)"></strong>
                                <span class="text-[10px] text-slate-500 block font-medium" x-text="m.variant?.nama_varian ?? '—'"></span>
                                <span class="text-[9px] text-slate-400 block" x-text="m.variant?.product?.nama_produk ?? '—'"></span>
                            </td>

                            <td class="py-4 px-4 text-right font-bold" :class="Number(m.jumlah ?? 0) > 0 ? 'text-emerald-600' : (Number(m.jumlah ?? 0) < 0 ? 'text-rose-600' : 'text-slate-700')" x-text="Number(m.jumlah ?? 0) > 0 ? '+' + num(m.jumlah) : num(m.jumlah)"></td>

                            <td class="py-4 px-4 text-center font-bold text-slate-700" x-text="num(m.stok_sebelum ?? 0)"></td>

                            <td class="py-4 px-4 text-center font-bold" :class="Number(m.stok_sesudah ?? 0) === 0 ? 'text-rose-600' : 'text-slate-700'" x-text="num(m.stok_sesudah ?? 0)"></td>

                            <td class="py-4 px-4">
                                <template x-if="m.referensi_tipe">
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase inline-block bg-sky-50 text-sky-700 border border-sky-200" x-text="m.referensi_tipe.replace('_', ' ')"></span>
                                    <span class="block text-[10px] text-slate-400 font-mono mt-0.5" x-text="'#' + m.referensi_id"></span>
                                </template>
                                <template x-if="!m.referensi_tipe">
                                    <span class="text-[10px] text-slate-300">—</span>
                                </template>
                            </td>

                            <td class="py-4 px-4">
                                <strong class="text-slate-900 block text-xs" x-text="m.user?.nama_pengguna ?? 'Sistem'"></strong>
                                <template x-if="m.catatan">
                                    <span class="text-[10px] text-slate-500 block truncate max-w-xs" x-text="m.catatan"></span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer + pagination -->
        <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-medium text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-sky-500"></i>
                <span>Pergerakan stok tercatat otomatis dari: Opname, Penjualan, Retur, Koreksi manual.</span>
            </div>

            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold">
                <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">< Sebelumnya</button>
                <span class="px-2 text-slate-700" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
                <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">Selanjutnya ></button>
            </div>
        </div>
    </div>

    <!-- 4. LEGEND -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3 text-xs">
        <h4 class="font-extrabold text-slate-900">Keterangan Tipe Pergerakan</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2 text-[10px]">
            <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl p-2.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="font-bold text-emerald-800">Masuk</span>
                <span class="text-slate-600">Pembelian/Produksi</span>
            </div>
            <div class="flex items-center gap-2 bg-rose-50 border border-rose-200 rounded-xl p-2.5">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span class="font-bold text-rose-800">Keluar</span>
                <span class="text-slate-600">Penjualan/Pakai</span>
            </div>
            <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl p-2.5">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span class="font-bold text-amber-800">Koreksi</span>
                <span class="text-slate-600">Penyesuaian manual</span>
            </div>
            <div class="flex items-center gap-2 bg-sky-50 border border-sky-200 rounded-xl p-2.5">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <span class="font-bold text-sky-800">Opname</span>
                <span class="text-slate-600">Hasil stock opname</span>
            </div>
            <div class="flex items-center gap-2 bg-indigo-50 border border-indigo-200 rounded-xl p-2.5">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <span class="font-bold text-indigo-800">Penjualan</span>
                <span class="text-slate-600">Order terbayar</span>
            </div>
            <div class="flex items-center gap-2 bg-violet-50 border border-violet-200 rounded-xl p-2.5">
                <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                <span class="font-bold text-violet-800">Retur</span>
                <span class="text-slate-600">Pengembalian</span>
            </div>
        </div>
    </div>

    </div>

</div>

@endsection