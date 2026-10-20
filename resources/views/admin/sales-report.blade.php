@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Ringkasan (GET /admin/orders/summary & /admin/payments/summary) ===
    orderSummary: null,
    paySummary: null,

    // === Buku kas (GET /admin/payments, termutakhir) ===
    payments: [],
    meta: null,
    page: 1,
    perPage: 8,
    loading: true,
    notice: '', noticeOk: true,

    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    fmt(n) { return PandansariAPI.formatRp(n); },
    waktu(s) {
        if (!s) return '—';
        try { return new Date(s).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch { return s; }
    },
    statusStyle(s) {
        return s === 'dibayar' ? 'bg-emerald-100 text-emerald-800'
            : (s === 'menunggu' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600');
    },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    async loadSummaries() {
        const [o, p] = await Promise.all([
            PandansariAPI.admin.ordersSummary(),
            PandansariAPI.admin.paymentsSummary(),
        ]);
        if (o.ok) this.orderSummary = o.data;
        else this.pesan(false, o.message);
        if (p.ok) this.paySummary = p.data;
    },
    async loadKas() {
        this.loading = true;
        const res = await PandansariAPI.admin.payments({ page: this.page, per_page: this.perPage });
        this.loading = false;
        if (res.ok) {
            this.payments = res.data ?? [];
            this.meta = res.meta;
            this.page = res.meta?.current_page ?? this.page;
        } else this.pesan(false, res.message);
    },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadKas(); },

    // === Ekspor CSV client-side ===
    eksporCsv() {
        const Q = '\x22';
        const head = ['nomor_pesanan', 'pelanggan', 'provider', 'metode', 'jumlah', 'status', 'dibuat_pada', 'paid_at'];
        const rows = this.payments.map(p => [
            p.order?.nomor_pesanan, p.order?.user?.nama_pengguna, p.provider,
            p.metode_pembayaran, p.jumlah_pembayaran, p.status_pembayaran, p.dibuat_pada, p.paid_at,
        ]);
        const csv = [head, ...rows].map(r => r.map(c => Q + String(c ?? '').split(Q).join(Q + Q) + Q).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'buku-kas.csv';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2000);
        this.pesan(true, 'CSV buku kas (halaman aktif) terunduh.');
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await this.loadSummaries();
        await this.loadKas();
        this.loading = false;
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka laporan penjualan</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Konsolidasi Pembukuan & Tata Kelola Atelier
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Ringkasan pesanan & pembayaran (summary API) + buku kas termutakhir dari /admin/payments.
            </p>
        </div>

        <button type="button" @click="eksporCsv()"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer border border-slate-200/80">
            <i class="fa-solid fa-file-csv text-emerald-600 text-sm"></i>
            <span>Ekspor CSV Buku Kas</span>
        </button>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. RINGKASAN ANGKA -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">

        <!-- Total nilai pesanan -->
        <div class="bg-[#0A192F] text-white rounded-2xl p-5 relative overflow-hidden shadow-md flex flex-col justify-between">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">NILAI PESANAN (SEMUA STATUS)</span>
                <div class="text-2xl font-black text-white tracking-tight pt-1" x-text="orderSummary ? fmt(orderSummary.total_nilai) : '…'"></div>
                <p class="text-[10px] text-slate-400 font-bold pt-1" x-text="orderSummary ? (num(orderSummary.total_pesanan) + ' pesanan • rata-rata ' + fmt(orderSummary.rata_rata_nilai)) : '…'"></p>
            </div>
            <div class="absolute right-[-10px] bottom-[-20px] z-0 opacity-20 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="" class="h-32 w-auto object-contain filter brightness-150">
            </div>
        </div>

        <!-- Nilai sudah dibayar -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
            <div class="space-y-1">
                <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest block">KAS MASUK (SUDAH DIBAYAR)</span>
                <div class="text-2xl font-black text-emerald-800 tracking-tight pt-1" x-text="paySummary ? fmt(paySummary.nilai_dibayar) : '…'"></div>
                <p class="text-[10px] text-emerald-700 font-bold pt-1" x-text="paySummary ? ((paySummary.per_status?.dibayar ?? 0) + ' dari ' + paySummary.total_pembayaran + ' transaksi') : '…'"></p>
            </div>
            <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-700 text-base mt-3"><i class="fa-solid fa-sack-dollar"></i></div>
        </div>

        <!-- Total nilai transaksi -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 flex flex-col justify-between shadow-sm">
            <div class="space-y-1">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">NILAI TRANSAKSI PEMBAYARAN</span>
                <div class="text-2xl font-black text-slate-900 tracking-tight pt-1" x-text="paySummary ? fmt(paySummary.total_nilai) : '…'"></div>
                <p class="text-[10px] text-slate-500 font-bold pt-1" x-text="paySummary ? ('menunggu: ' + (paySummary.per_status?.menunggu ?? 0) + ' • ditolak/kadaluarsa: ' + ((paySummary.per_status?.ditolak ?? 0) + (paySummary.per_status?.kedaluwarsa ?? 0) + (paySummary.per_status?.dibatalkan ?? 0))) : '…'"></p>
            </div>
            <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 text-base mt-3"><i class="fa-solid fa-receipt"></i></div>
        </div>

        <!-- Kanal provider -->
        <div class="bg-[#1EABF1] text-white rounded-2xl p-5 relative overflow-hidden shadow-md flex flex-col justify-between">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-black text-white/80 uppercase tracking-widest block">KANAL PEMBAYARAN</span>
                <div class="text-sm font-black text-white pt-1 leading-snug">
                    <template x-for="(v, k) in (paySummary?.per_provider ?? {})" :key="k">
                        <span class="block" x-text="k.toUpperCase() + ': ' + v + ' transaksi'"></span>
                    </template>
                    <span x-show="!paySummary" class="opacity-60">…</span>
                </div>
            </div>
            <div class="absolute right-[-10px] bottom-[-20px] z-0 opacity-25 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="" class="h-32 w-auto object-contain filter brightness-200">
            </div>
        </div>

    </div>

    <!-- 3. DISTRIBUSI STATUS PESANAN -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3">
        <h2 class="text-sm font-extrabold text-slate-900">Distribusi status pesanan</h2>
        <div class="flex flex-wrap gap-2">
            <template x-for="(v, k) in (orderSummary?.per_status ?? {})" :key="k">
                <div class="bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5 flex items-center gap-2 text-xs">
                    <span class="w-2 h-2 rounded-full" :class="k === 'dibatalkan' ? 'bg-rose-400' : (k === 'selesai' ? 'bg-emerald-500' : 'bg-sky-500')"></span>
                    <span class="font-bold text-slate-600" x-text="k.replaceAll('_', ' ')"></span>
                    <span class="font-black text-slate-900" x-text="num(v)"></span>
                </div>
            </template>
            <span x-show="!orderSummary" class="text-xs text-slate-400 font-semibold">Memuat…</span>
        </div>
    </div>

    <!-- 4. BUKU KAS (PEMBAYARAN TERMUTAKHIR) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-book text-sky-700 text-base"></i>
                <h2 class="text-base font-extrabold text-slate-900">Buku Kas Pembayaran (Real-Time)</h2>
            </div>
            <span class="text-[11px] font-bold text-slate-400">
                GET /admin/payments — halaman <span x-text="meta?.current_page ?? 1"></span>/<span x-text="meta?.last_page ?? 1"></span> • total <span x-text="num(meta?.total ?? 0)"></span>
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4">TANGGAL & PESANAN</th>
                        <th class="py-3.5 px-4">PELANGGAN</th>
                        <th class="py-3.5 px-4">KANAL</th>
                        <th class="py-3.5 px-4 text-right">DEBET (BRUTO)</th>
                        <th class="py-3.5 px-4 text-center">STATUS</th>
                        <th class="py-3.5 px-4 text-right">DIBAYAR PADA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-if="loading">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat buku kas…</td></tr>
                    </template>
                    <template x-if="!loading && !payments.length">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold">Belum ada transaksi pembayaran.</td></tr>
                    </template>

                    <template x-for="p in payments" :key="p.id_payment">
                        <tr class="hover:bg-slate-50/80 transition">

                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-extrabold block" x-text="p.order?.nomor_pesanan ?? p.id_order"></strong>
                                <span class="text-[10px] text-slate-400 block" x-text="waktu(p.dibuat_pada)"></span>
                                <span class="bg-sky-800 text-white text-[9px] font-extrabold px-2 py-0.5 rounded inline-block mt-1" x-text="'ID: ' + (p.payment_reference ?? '—').slice(0, 14)"></span>
                            </td>

                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-black block" x-text="p.order?.user?.nama_pengguna ?? '—'"></strong>
                                <span class="text-[10px] text-slate-500 block" x-text="p.order?.user?.email ?? ''"></span>
                            </td>

                            <td class="py-4 px-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                                    <strong class="text-slate-800 font-extrabold" x-text="p.provider"></strong>
                                </div>
                                <span class="text-[10px] text-slate-500 block pl-3.5" x-text="p.metode_pembayaran ?? 'belum dipilih'"></span>
                            </td>

                            <td class="py-4 px-4 text-right">
                                <strong class="text-slate-900 font-black text-sm block" x-text="fmt(p.jumlah_pembayaran)"></strong>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="text-[9px] font-extrabold px-2.5 py-1 rounded-md uppercase inline-block" :class="statusStyle(p.status_pembayaran)" x-text="p.status_pembayaran"></span>
                            </td>

                            <td class="py-4 px-4 text-right">
                                <span class="text-[10px] text-emerald-700 font-bold" x-text="p.paid_at ? waktu(p.paid_at) : '—'"></span>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer + pagination -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-semibold text-slate-500">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <span>Status diperbarui otomatis oleh webhook Midtrans</span>
            </div>

            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold">
                <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lt;</button>
                <span class="px-2 text-slate-700" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
                <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                        class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&gt;</button>
            </div>
        </div>

    </div>

    <!-- 5. CATATAN -->
    <div class="p-3 bg-slate-100 rounded-xl border border-slate-200/80 flex items-start gap-2 text-[11px] font-bold text-slate-700">
        <i class="fa-solid fa-circle-info text-slate-500 mt-0.5"></i>
        <span>Nilai MDR/kurir belum tersedia di API — kolom kas keluar (fee gateway) akan ditambahkan saat endpoint laporan tersedia.</span>
    </div>

    </div>

</div>

@endsection
