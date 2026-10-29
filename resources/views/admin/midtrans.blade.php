@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Data API ===
    payments: [],
    meta: null,
    page: 1,
    perPage: 10,
    selectedStatus: 'ALL',
    selectedProvider: 'ALL',
    exportStartDate: '',
    exportEndDate: '',
    summary: null,
    loading: true,
    notice: '', noticeOk: true,

    STATUS_LIST: ['menunggu', 'dibayar', 'ditolak', 'kedaluwarsa', 'dibatalkan'],
    PROVIDER_LIST: ['midtrans', 'sandbox'],

    fmt(n) { return PandansariAPI.formatRp(n); },
    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    waktu(s) {
        if (!s) return '—';
        try { return new Date(s).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch { return s; }
    },
    statusStyle(s) {
        return {
            dibayar: 'bg-emerald-100 text-emerald-800',
            menunggu: 'bg-amber-100 text-amber-800',
            ditolak: 'bg-rose-100 text-rose-800',
            kedaluwarsa: 'bg-slate-100 text-slate-600',
            dibatalkan: 'bg-slate-200 text-slate-700',
        }[s] ?? 'bg-slate-100 text-slate-700';
    },
    providerStyle(p) {
        return p === 'midtrans' ? 'bg-sky-100 text-sky-800' : 'bg-violet-100 text-violet-800';
    },
    expired(p) {
        return p.expires_at && new Date(p.expires_at) < new Date() && p.status_pembayaran === 'menunggu';
    },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    async loadPayments() {
        this.loading = true;
        const res = await PandansariAPI.admin.payments({
            page: this.page, per_page: this.perPage,
            status: this.selectedStatus !== 'ALL' ? this.selectedStatus : undefined,
            provider: this.selectedProvider !== 'ALL' ? this.selectedProvider : undefined,
        });
        this.loading = false;
        if (res.ok) {
            this.payments = res.data ?? [];
            this.meta = res.meta;
            this.page = res.meta?.current_page ?? this.page;
        } else this.pesan(false, res.message);
    },
    async loadSummary() {
        const res = await PandansariAPI.admin.paymentsSummary();
        if (res.ok) this.summary = res.data;
    },
    setStatus(s) { this.selectedStatus = s; this.page = 1; this.loadPayments(); },
    setProvider(p) { this.selectedProvider = p; this.page = 1; this.loadPayments(); },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadPayments(); },
    resetFilter() { this.selectedStatus = 'ALL'; this.selectedProvider = 'ALL'; this.page = 1; this.loadPayments(); },

    async eksporCsv() {
        if (this.exportStartDate && this.exportEndDate && this.exportEndDate < this.exportStartDate) {
            this.pesan(false, 'Tanggal akhir tidak boleh sebelum tanggal mulai.');
            return;
        }
        const res = await PandansariAPI.admin.paymentsExport({
            status: this.selectedStatus !== 'ALL' ? this.selectedStatus : undefined,
            provider: this.selectedProvider !== 'ALL' ? this.selectedProvider : undefined,
            start_date: this.exportStartDate || undefined,
            end_date: this.exportEndDate || undefined,
        });
        this.pesan(res.ok, res.ok ? 'CSV seluruh pembayaran sesuai filter berhasil diunduh.' : res.message);
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (!me.ok || me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await Promise.all([this.loadPayments(), this.loadSummary()]);
        this.loading = false;
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka rekonsiliasi pembayaran</p>
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
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Rekonsiliasi Pembayaran Midtrans
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                GET /admin/payments — status transaksi diperbarui otomatis oleh webhook Midtrans.
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <label class="text-[10px] font-bold text-slate-500">Mulai
                <input type="date" x-model="exportStartDate" class="block rounded-lg border border-slate-300 px-2 py-2 text-xs">
            </label>
            <label class="text-[10px] font-bold text-slate-500">Akhir
                <input type="date" x-model="exportEndDate" class="block rounded-lg border border-slate-300 px-2 py-2 text-xs">
            </label>
            <button type="button" @click="eksporCsv()" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer">
                <i class="fa-solid fa-file-export text-xs"></i>
                <span>Ekspor semua hasil</span>
            </button>
        </div>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center justify-between">
            <div>
                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block">TOTAL TRANSAKSI</span>
                <div class="text-3xl font-black text-slate-900" x-text="summary ? num(summary.total_pembayaran) : '…'"></div>
                <span class="text-[10px] font-bold text-slate-500 block mt-0.5" x-text="summary ? ('menunggu: ' + (summary.per_status?.menunggu ?? 0) + ' • dibayar: ' + (summary.per_status?.dibayar ?? 0)) : '…'"></span>
            </div>
            <div class="w-10 h-10 bg-sky-100 rounded-xl flex items-center justify-center text-sky-700 text-lg"><i class="fa-solid fa-receipt"></i></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center justify-between">
            <div>
                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block">TOTAL NILAI</span>
                <div class="text-2xl font-black text-slate-900" x-text="summary ? fmt(summary.total_nilai) : '…'"></div>
                <span class="text-[10px] font-bold text-slate-500 block mt-0.5">Seluruh transaksi (termasuk belum dibayar)</span>
            </div>
            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-700 text-lg"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 shadow-sm p-5 flex items-center justify-between">
            <div>
                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-700 block">SUDAH DIBAYAR</span>
                <div class="text-2xl font-black text-emerald-800" x-text="summary ? fmt(summary.nilai_dibayar) : '…'"></div>
                <span class="text-[10px] font-bold text-emerald-700 block mt-0.5" x-text="summary ? ('provider: ' + Object.keys(summary.per_provider ?? {}).map(k => k + ' ' + summary.per_provider[k]).join(' • ')) : '…'"></span>
            </div>
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-700 text-lg"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>

    <!-- 3. FILTER STATUS + PROVIDER -->
    <div class="bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center gap-1 text-xs font-bold">
        <button @click="setStatus('ALL')"
                :class="selectedStatus === 'ALL' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                class="px-4 py-2 rounded-xl transition cursor-pointer">
            Semua status
        </button>
        <template x-for="s in STATUS_LIST" :key="s">
            <button @click="setStatus(s)"
                    :class="selectedStatus === s ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
                <span x-text="s"></span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-md font-black"
                      :class="selectedStatus === s ? 'bg-sky-500/30 text-sky-300' : 'bg-slate-100 text-slate-600'"
                      x-text="summary ? num(summary.per_status?.[s] ?? 0) : '…'"></span>
            </button>
        </template>

        <span class="mx-2 w-px h-6 bg-slate-200"></span>

        <button @click="setProvider('ALL')"
                :class="selectedProvider === 'ALL' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                class="px-3 py-2 rounded-xl transition cursor-pointer">Semua provider</button>
        <template x-for="p in PROVIDER_LIST" :key="p">
            <button @click="setProvider(p)"
                    :class="selectedProvider === p ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                    class="px-3 py-2 rounded-xl transition cursor-pointer flex items-center gap-1.5">
                <span x-text="p"></span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-md font-black"
                      :class="selectedProvider === p ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                      x-text="summary ? num(summary.per_provider?.[p] ?? 0) : '…'"></span>
            </button>
        </template>
    </div>

    <!-- 4. TABEL TRANSAKSI -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#AEE2F0]/40 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4">PESANAN</th>
                        <th class="py-3.5 px-4">PELANGGAN</th>
                        <th class="py-3.5 px-4">PROVIDER & METODE</th>
                        <th class="py-3.5 px-4 text-right">JUMLAH</th>
                        <th class="py-3.5 px-4">STATUS</th>
                        <th class="py-3.5 px-4">WAKTU</th>
                        <th class="py-3.5 px-4 text-center">TAUTAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-if="loading">
                        <tr><td colspan="7" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat pembayaran…</td></tr>
                    </template>
                    <template x-if="!loading && !payments.length">
                        <tr><td colspan="7" class="py-10 text-center text-slate-400 font-semibold">Tidak ada transaksi untuk filter ini.</td></tr>
                    </template>

                    <template x-for="p in payments" :key="p.id_payment">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-900 font-extrabold block" x-text="p.order?.nomor_pesanan ?? p.id_order"></strong>
                                <span class="text-[10px] text-slate-400 font-mono block" x-text="(p.payment_reference ?? '').slice(0, 16)"></span>
                            </td>
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-900 block" x-text="p.order?.user?.nama_pengguna ?? '—'"></strong>
                                <span class="text-[10px] text-slate-500 block" x-text="p.order?.user?.email ?? ''"></span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase inline-block" :class="providerStyle(p.provider)" x-text="p.provider"></span>
                                <span class="block text-[10px] text-slate-500 mt-1" x-text="p.metode_pembayaran ?? 'menunggu snap/VA dipilih'"></span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <strong class="text-slate-900 text-sm font-black block" x-text="fmt(p.jumlah_pembayaran)"></strong>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-[9px] font-extrabold px-2.5 py-1 rounded-md uppercase inline-block" :class="statusStyle(p.status_pembayaran)" x-text="p.status_pembayaran"></span>
                                <span x-show="expired(p)" class="block text-[9px] font-bold text-rose-500 mt-1">kedaluwarsa waktu</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-slate-600 block text-[11px]" x-text="waktu(p.dibuat_pada)"></span>
                                <span class="text-[10px] text-emerald-700 font-bold block" x-text="p.paid_at ? ('dibayar ' + waktu(p.paid_at)) : ''"></span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a :href="p.payment_url ?? '#'" target="_blank" rel="noopener"
                                   x-show="p.payment_url"
                                   class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-sky-100 hover:text-sky-700 text-slate-600 transition inline-flex items-center justify-center" title="Buka halaman bayar (Snap)">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer + pagination -->
        <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-medium text-slate-500">
            <div>
                Menampilkan halaman <strong x-text="meta?.current_page ?? 1"></strong> — total <strong x-text="num(meta?.total ?? 0)"></strong> transaksi
            </div>
            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold">
                <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lt; Sebelumnya</button>
                <span class="px-2 text-slate-700" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
                <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">Selanjutnya &gt;</button>
            </div>
        </div>
    </div>

    <!-- 5. INFO WEBHOOK -->
    <div class="p-3 bg-slate-100 rounded-xl border border-slate-200/80 flex items-start gap-2 text-[11px] font-bold text-slate-700">
        <i class="fa-solid fa-link text-slate-500 mt-0.5"></i>
        <span>Status transaksi berubah otomatis saat webhook <span class="font-mono">POST /payments/midtrans/notification</span> diterima — halaman ini tidak perlu sinkronisasi manual.</span>
    </div>

    </div>

</div>

@endsection
