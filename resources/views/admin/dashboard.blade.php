@extends('layouts.admin')

@section('content')

<div x-data="{
        // === Gate admin ===
        needLogin: false,
        notAdmin: false,
        user: PandansariAPI.getUser(),

        // === Ringkasan (GET /admin/{orders,payments,users}/summary) ===
        orderSummary: null,
        paySummary: null,
        userSummary: null,

        // === Tabel transaksi terbaru (GET /admin/orders) ===
        orders: [],
        meta: null,
        page: 1,
        perPage: 5,
        filter: '',           // '' | 'menunggu_pembayaran' | 'dibayar' (tab)
        loading: true,

        fmt(n) { return PandansariAPI.formatRp(n); },
        num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
        waktu(s) {
            if (!s) return '';
            try { return new Date(s).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }); } catch { return s; }
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
        bayarClass(s) {
            return {
                menunggu: 'bg-amber-100 text-amber-800',
                dibayar: 'bg-emerald-100 text-emerald-800',
                ditolak: 'bg-rose-100 text-rose-800',
                kedaluwarsa: 'bg-slate-100 text-slate-600',
                dibatalkan: 'bg-rose-100 text-rose-800',
            }[s] ?? 'bg-slate-100 text-slate-700';
        },
        itemPertama(o) {
            const it = (o.items ?? [])[0];
            if (!it) return null;
            return it.nama_produk ?? it.variant?.product?.nama_produk ?? 'Produk';
        },

        async loadSummary() {
            const [o, p, u] = await Promise.all([
                PandansariAPI.admin.ordersSummary(),
                PandansariAPI.admin.paymentsSummary(),
                PandansariAPI.admin.usersSummary(),
            ]);
            if (o.ok) this.orderSummary = o.data;
            if (p.ok) this.paySummary = p.data;
            if (u.ok) this.userSummary = u.data;
        },
        async loadOrders() {
            const res = await PandansariAPI.admin.orders({ page: this.page, per_page: this.perPage, status: this.filter || undefined });
            if (res.ok) {
                this.orders = res.data ?? [];
                this.meta = res.meta;
                this.page = res.meta?.current_page ?? this.page;
            }
        },
        setFilter(f) { this.filter = f; this.page = 1; this.loadOrders(); },
        goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadOrders(); },

        async muatUlang() {
            await Promise.all([this.loadSummary(), this.loadOrders()]);
        },
        async init() {
            if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
            const me = await PandansariAPI.me();
            if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
            this.user = PandansariAPI.getUser();
            await this.muatUlang();
            this.loading = false;
        }
     }">

    <!-- Gate: belum login -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka dashboard</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl hover:bg-slate-800 transition">LOGIN</a>
    </div>

    <!-- Gate: bukan admin -->
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
        <a href="/" class="inline-block bg-slate-100 text-slate-700 text-xs font-bold px-6 py-3 rounded-xl hover:bg-slate-200 transition">KE BERANDA</a>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. WELCOME BANNER (DARK BLUE) -->
    <div class="bg-[#0A192F] text-white p-6 sm:p-8 rounded-2xl relative overflow-hidden shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2 max-w-2xl relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                Sugeng Rawuh, <span class="italic font-normal" x-text="user?.nama_pengguna ?? 'Admin'"></span>
            </h1>
            <p class="text-xs text-slate-300 leading-relaxed font-normal">
                Ikhtisar perputaran wastra, status gerbang bayar Midtrans, serta data langsung dari API backend.
            </p>
        </div>

        <button type="button" @click="muatUlang()" :disabled="loading"
                class="bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 font-bold text-xs px-4 py-2.5 rounded-xl border border-sky-400/30 transition flex items-center gap-2 shrink-0 self-start md:self-center cursor-pointer disabled:opacity-60">
            <i class="fa-solid fa-rotate text-xs" :class="loading ? 'fa-spin' : ''"></i>
            <span>SIN KRONISASI</span>
        </button>

        <!-- Background Circles -->
        <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none">
            <div class="w-64 h-64 border-4 border-dashed border-white rounded-full flex items-center justify-center">
                <div class="w-40 h-40 border-2 border-white rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- 2. STATS METRIC CARDS (3 COLUMN GRID) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        <!-- Metric 1: Total Nilai Pesanan -->
        <div class="bg-[#0A192F] text-white p-6 rounded-2xl border border-slate-800 shadow-sm relative space-y-3">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 bg-slate-800 text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <span class="text-[9px] bg-slate-800 text-slate-300 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">
                    <span x-text="orderSummary ? num(orderSummary.total_pesanan) + ' pesanan' : '—'"></span>
                </span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">TOTAL NILAI PESANAN</span>
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    <span class="text-lg font-bold text-slate-400">Rp</span>
                    <span x-text="orderSummary ? num(Math.round(orderSummary.total_nilai)) : '…'">0</span>
                </div>
            </div>
        </div>

        <!-- Metric 2: Pesanan per Status -->
        <div class="bg-[#0A192F] text-white p-6 rounded-2xl border border-slate-800 shadow-sm relative space-y-3">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 bg-slate-800 text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
                <span class="text-[10px] bg-sky-500/20 text-sky-300 px-2.5 py-1 rounded-full font-bold">
                    <span x-text="orderSummary ? 'Rata² ' + fmt(orderSummary.rata_rata_nilai) : '…'"></span>
                </span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">MENUNGGU PEMBAYARAN</span>
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight flex items-baseline gap-1.5">
                    <span x-text="orderSummary ? num(orderSummary.per_status?.menunggu_pembayaran ?? 0) : '…'"></span>
                    <span class="text-xs font-normal text-slate-400">Transaksi</span>
                </div>
            </div>
        </div>

        <!-- Metric 3: Pembayaran Dibayar & Pengguna -->
        <div class="bg-[#0A192F] text-white p-6 rounded-2xl border border-slate-800 shadow-sm relative space-y-3">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 bg-slate-800 text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <span class="text-[9px] bg-slate-800 text-slate-300 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">
                    <span x-text="userSummary ? num(userSummary.total_pengguna) + ' pengguna' : '…'"></span>
                </span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">NILAI PEMBAYARAN DIBAYAR</span>
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    <span class="text-lg font-bold text-slate-400">Rp</span>
                    <span x-text="paySummary ? num(Math.round(paySummary.nilai_dibayar)) : '…'">0</span>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. TABEL TRANSAKSI TERBARU & TERTUNDA -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6">

        <!-- Table Filter Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-[#0A192F] text-white rounded-xl flex items-center justify-center text-sm shrink-0">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#0F1A24]">Transaksi Terbaru</h3>
                    <p class="text-xs text-slate-500">GET /admin/orders — filter status server-side</p>
                </div>
            </div>

            <!-- Tabs Filters -->
            <div class="flex items-center gap-2 self-start md:self-auto text-xs">
                <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1">
                    <button @click="setFilter('')" type="button"
                            class="px-3 py-1.5 rounded-lg font-bold transition cursor-pointer"
                            :class="filter === '' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-black'">SEMUA</button>
                    <button @click="setFilter('dibayar')" type="button"
                            class="px-3 py-1.5 rounded-lg font-bold transition cursor-pointer"
                            :class="filter === 'dibayar' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-black'">LUNAS</button>
                    <button @click="setFilter('menunggu_pembayaran')" type="button"
                            class="px-3 py-1.5 rounded-lg font-bold transition cursor-pointer"
                            :class="filter === 'menunggu_pembayaran' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-black'">MENUNGGU</button>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/60 text-slate-700 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">NOMOR PESANAN</th>
                        <th class="py-3 px-4">KOLEKTOR WASTRA</th>
                        <th class="py-3 px-4">KARYA (ITEM PERTAMA)</th>
                        <th class="py-3 px-4">TOTAL BAYAR</th>
                        <th class="py-3 px-4">ONGKIR</th>
                        <th class="py-3 px-4 text-center">STATUS</th>
                        <th class="py-3 px-4 text-center">TINDAKAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">

                    <template x-if="loading">
                        <tr><td colspan="7" class="py-10 text-center text-slate-400 font-semibold">
                            <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat pesanan…
                        </td></tr>
                    </template>

                    <template x-if="!loading && !orders.length">
                        <tr><td colspan="7" class="py-10 text-center text-slate-400 font-semibold">Belum ada pesanan dengan filter ini.</td></tr>
                    </template>

                    <template x-for="o in orders" :key="o.id_order">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 font-bold text-sky-700">
                                <span x-text="o.nomor_pesanan"></span>
                                <span class="block text-[10px] font-normal text-slate-400" x-text="waktu(o.dibuat_pada)"></span>
                            </td>
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-800 block" x-text="o.nama_penerima ?? '—'"></strong>
                                <span class="text-[10px] text-slate-400" x-text="[o.nama_kecamatan, o.nama_kota].filter(Boolean).join(', ') || '—'"></span>
                            </td>
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-800 block truncate" x-text="itemPertama(o) ?? '—'"></strong>
                                <span class="text-[10px] text-slate-400" x-text="(o.items?.length ?? 0) + ' item'"></span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-800" x-text="fmt(o.total_pembayaran)"></td>
                            <td class="py-3.5 px-4 text-slate-600" x-text="fmt(o.biaya_pengiriman)"></td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-bold text-[10px] px-3 py-1 rounded-full inline-block uppercase"
                                      :class="statusClass(o.status_pesanan)" x-text="o.status_pesanan.replaceAll('_', ' ')"></span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a :href="'/admin/orders?detail=' + o.id_order"
                                   class="w-8 h-8 bg-slate-100 text-slate-700 hover:bg-[#0A192F] hover:text-white rounded-xl transition inline-flex items-center justify-center">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    </template>

                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
            <span>Halaman <strong x-text="meta?.current_page ?? 1"></strong> — total <strong x-text="num(meta?.total ?? 0)"></strong> pesanan</span>

            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold">
                <button type="button" @click="goto(page - 1)"
                        class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40"
                        :disabled="page <= 1">&lt;</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-[#AEE2F0] text-slate-900 flex items-center justify-center font-extrabold" x-text="meta?.current_page ?? 1"></button>
                <button type="button" @click="goto(page + 1)"
                        class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40"
                        :disabled="meta && page >= meta.last_page">&gt;</button>
            </div>
        </div>

    </div>

    </div>

@endsection
