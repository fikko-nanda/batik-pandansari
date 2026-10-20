@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Data API ===
    products: [],
    meta: null,
    page: 1,
    perPage: 10,
    searchQuery: '',
    loading: true,
    notice: '', noticeOk: true,
    savingKey: '',           // key produk yang sedang disimpan

    // === Edit stok sementara per varian: { id_variant: angka } ===
    draft: {},

    fmt(n) { return PandansariAPI.formatRp(n); },
    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    activeVariants(p) {
        return (p.variants ?? []).filter(v => v.status_varian !== 'nonaktif');
    },
    totalStok(p) {
        return this.activeVariants(p).reduce((s, v) => s + Number(v.stok ?? 0), 0);
    },
    coverOf(p) {
        return (p.images ?? []).find(i => i.url)?.url ?? '{{ asset('images/katalog1.webp') }}';
    },
    statusInfo(p) {
        const t = this.totalStok(p);
        if (p.status_produk !== 'aktif') return { label: 'Nonaktif', style: 'bg-slate-100 text-slate-600 border border-slate-200', row: '' };
        if (t === 0) return { label: 'Habis', style: 'bg-rose-100 text-rose-800 border border-rose-200 font-bold', row: 'bg-rose-50/40 hover:bg-rose-50/70' };
        if (t <= 3) return { label: 'Stok Menipis', style: 'bg-sky-200 text-sky-900 border border-sky-300 font-bold', row: 'bg-sky-50/30 hover:bg-sky-50/60' };
        return { label: 'Aman', style: 'bg-emerald-50 text-emerald-800 border border-emerald-200', row: 'hover:bg-slate-50/80' };
    },
    /** nilai draft varian (jika ada) atau nilai API */
    valOf(v) {
        return this.draft[v.id_variant] ?? Number(v.stok ?? 0);
    },
    setDraft(v, e) {
        const n = Math.max(0, parseInt(e.target.value || 0, 10));
        this.draft[v.id_variant] = n;
    },
    resetDraft(v) {
        delete this.draft[v.id_variant];
    },

    // === Simpan stok varian → PUT /variants/{id} ===
    async simpanStok(v) {
        const next = this.draft[v.id_variant];
        if (next === undefined || next === Number(v.stok ?? 0)) { this.pesan(false, 'Tidak ada perubahan stok untuk varian ini.'); return; }
        this.savingKey = v.id_variant;
        const res = await PandansariAPI.admin.updateVariant(v.id_variant, { stok: next });
        this.savingKey = '';
        if (res.ok) {
            v.stok = next;
            delete this.draft[v.id_variant];
            this.pesan(true, 'Stok ' + (v.sku ?? v.nama_varian) + ' → ' + next + ' tersimpan.');
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },
    async simpanSemuaProduk(p) {
        const ubahan = this.activeVariants(p).filter(v => this.draft[v.id_variant] !== undefined && this.draft[v.id_variant] !== Number(v.stok ?? 0));
        if (!ubahan.length) { this.pesan(false, 'Tidak ada perubahan stok pada produk ini.'); return; }
        this.savingKey = p.id_product;
        let okCount = 0;
        for (const v of ubahan) {
            const res = await PandansariAPI.admin.updateVariant(v.id_variant, { stok: this.draft[v.id_variant] });
            if (res.ok) { v.stok = this.draft[v.id_variant]; delete this.draft[v.id_variant]; okCount++; }
        }
        this.savingKey = '';
        this.pesan(okCount === ubahan.length, okCount + '/' + ubahan.length + ' varian stok tersimpan.');
    },

    // === Toggle status toko → PUT /products/{id} ===
    async toggleToko(p) {
        const next = p.status_produk === 'aktif' ? 'nonaktif' : 'aktif';
        const res = await PandansariAPI.admin.updateProduct(p.id_product, { status_produk: next });
        if (res.ok) {
            p.status_produk = next;
            this.pesan(true, p.nama_produk + ' sekarang ' + next + '.');
        } else this.pesan(false, res.message);
    },

    // === Muat data ===
    async loadProduk() {
        this.loading = true;
        const res = await PandansariAPI.admin.adminProducts({
            page: this.page, per_page: this.perPage,
            search: this.searchQuery.trim() || undefined,
        });
        this.loading = false;
        if (res.ok) {
            this.products = res.data ?? [];
            this.meta = res.meta;
            this.page = res.meta?.current_page ?? this.page;
            // siapkan draft kosong
            this.draft = {};
        } else this.pesan(false, res.message);
    },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadProduk(); },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await this.loadProduk();
        this.loading = false;
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka pusat stok</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER BANNER TITLE & ACTION BUTTONS -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Kurasi Stok & Varian Cepat
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Saldo stok per varian disimpan nyata via PUT /variants/{id}; status toko via PUT /products/{id}.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button type="button" @click="PandansariAPI.admin.productsExport()"
                    class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-500"></i>
                <span>Unduh CSV</span>
            </button>
            <a href="/admin/products" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#96d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2">
                <i class="fa-solid fa-bolt text-slate-800"></i>
                <span>Kelola Produk & Varian</span>
            </a>
        </div>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. SUMMARY CARDS (dari API) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <div class="bg-[#AEE2F0]/50 border border-sky-200/80 rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between shadow-sm">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest">TOTAL PRODUK TERCATAT</span>
                <div class="text-3xl sm:text-4xl font-black text-slate-900"><span x-text="meta ? num(meta.total) : '…'"></span> SKU</div>
            </div>

            <div class="relative z-10 mt-6 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i class="fa-regular fa-circle-check text-sky-700 text-sm"></i>
                <span>Termasuk draft & nonaktif (panel admin)</span>
            </div>

            <div class="absolute right-4 top-4 w-9 h-9 bg-sky-200/80 rounded-xl flex items-center justify-center text-sky-800 text-sm">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <div class="absolute right-2 -bottom-6 z-0 opacity-30 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang" class="h-36 w-auto object-contain">
            </div>
        </div>

        <div class="bg-[#AEE2F0]/50 border border-sky-200/80 rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between shadow-sm">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest">TOTAL UNIT DI HALAMAN INI</span>
                <div class="text-3xl sm:text-4xl font-black text-slate-900"><span x-text="num(products.reduce((s, p) => s + totalStok(p), 0))"></span> <span class="text-xl font-bold text-slate-700">unit</span></div>
            </div>

            <div class="relative z-10 mt-6 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i class="fa-solid fa-boxes-stacked text-sky-700 text-sm"></i>
                <span>Jumlah stok varian aktif pada halaman ini</span>
            </div>

            <div class="absolute right-4 top-4 w-9 h-9 bg-sky-200/80 rounded-xl flex items-center justify-center text-sky-800 text-sm">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="absolute right-2 -bottom-6 z-0 opacity-30 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang" class="h-36 w-auto object-contain">
            </div>
        </div>

    </div>

    <!-- 3. SEARCH BAR -->
    <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text"
                   x-model.debounce.600ms="searchQuery"
                   @input.debounce.600ms="page = 1; loadProduk()"
                   placeholder="Cari berdasarkan SKU, nama produk, atau deskripsi..."
                   class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500 font-bold self-end md:self-auto">
            <i class="fa-solid fa-rotate text-sky-500"></i>
            <span>Menampilkan <strong class="text-slate-900" x-text="products.length"></strong> produk (hal. <span x-text="meta?.current_page ?? 1"></span>/<span x-text="meta?.last_page ?? 1"></span>)</span>
        </div>
    </div>

    <!-- 4. TABLE STOK & VARIAN CEPAT -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/80 text-slate-700 text-[10px] font-extrabold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4 w-28">SLUG</th>
                        <th class="py-3.5 px-4">KARYA & VARIAN STOK</th>
                        <th class="py-3.5 px-4 text-center">PENYESUAIAN STOK VARIAN</th>
                        <th class="py-3.5 px-4">STATUS & REKOMENDASI</th>
                        <th class="py-3.5 px-4 text-center">STATUS TOKO</th>
                        <th class="py-3.5 px-4 text-center">AKSI CEPAT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <template x-if="loading">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat data stok…</td></tr>
                    </template>
                    <template x-if="!loading && !products.length">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold">Tidak ada produk.</td></tr>
                    </template>

                    <template x-for="item in products" :key="item.id_product + '-row'">
                        <tr :class="statusInfo(item).row" class="transition">

                            <!-- Kode SKU -->
                            <td class="py-4 px-4 font-mono font-black text-slate-900 text-xs" x-text="item.slug"></td>

                            <!-- Karya & Motif Wastra -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <img :src="coverOf(item)" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shrink-0 bg-slate-100" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                                    <div>
                                        <strong class="text-slate-900 text-sm font-extrabold block" x-text="item.nama_produk"></strong>
                                        <span class="text-[10px] text-slate-500 block font-medium" x-text="item.category?.nama_kategori ?? 'Tanpa kategori'"></span>
                                        <span class="text-[10px] text-slate-400 block" x-text="activeVariants(item).length + ' varian aktif'"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Penyesuaian stok per varian -->
                            <td class="py-4 px-4">
                                <template x-if="!activeVariants(item).length">
                                    <span class="text-[10px] text-slate-400 font-semibold">Belum ada varian aktif</span>
                                </template>
                                <div x-show="activeVariants(item).length" class="flex flex-wrap items-center gap-2">
                                    <template x-for="v in activeVariants(item)" :key="v.id_variant">
                                        <div class="bg-slate-100/80 px-1.5 py-1 rounded-xl border border-slate-200/60 flex items-center gap-1.5">
                                            <div class="text-center min-w-[26px]">
                                                <span class="text-[9px] font-bold text-slate-400 block leading-tight" x-text="(v.ukuran || v.sku || '?').toUpperCase()"></span>
                                                <input type="number" min="0"
                                                       :value="valOf(v)"
                                                       @change="setDraft(v, $event)"
                                                       class="w-9 h-7 text-center font-black text-xs bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-sky-400"
                                                       :class="valOf(v) === 0 ? 'text-rose-600' : 'text-slate-800'">
                                            </div>
                                        </div>
                                    </template>
                                    <span class="bg-amber-100/80 text-amber-900 border border-amber-200 text-[11px] font-extrabold px-3 py-1.5 rounded-xl shrink-0"
                                          x-text="activeVariants(item).reduce((s, v) => s + valOf(v), 0) + ' unit'"></span>
                                </div>
                            </td>

                            <!-- Status & Rekomendasi -->
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold inline-block" :class="statusInfo(item).style" x-text="statusInfo(item).label"></span>
                                    <p class="text-[10px] font-semibold text-slate-600"
                                       x-text="totalStok(item) === 0 ? 'Restock segera — unit habis' : (totalStok(item) <= 3 ? 'Sisa ' + totalStok(item) + ' unit — pantau terus' : 'Stok terjaga')"></p>
                                </div>
                            </td>

                            <!-- Status Toko Toggle Switch → PUT /products/{id} -->
                            <td class="py-4 px-4 text-center">
                                <button type="button" @click="toggleToko(item)" class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="item.status_produk === 'aktif' ? 'bg-sky-600' : 'bg-slate-300'" :title="item.status_produk === 'aktif' ? 'Aktif — klik untuk nonaktifkan' : 'Nonaktif — klik untuk aktifkan'">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="item.status_produk === 'aktif' ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                                <span class="block text-[9px] font-bold mt-1" :class="item.status_produk === 'aktif' ? 'text-sky-700' : 'text-slate-400'" x-text="item.status_produk"></span>
                            </td>

                            <!-- Aksi Cepat (Simpan varian & Reset draft) -->
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="simpanSemuaProduk(item)" :disabled="savingKey === item.id_product"
                                            class="w-8 h-8 rounded-xl bg-sky-100 text-sky-800 hover:bg-sky-200 transition flex items-center justify-center cursor-pointer disabled:opacity-50" title="Simpan semua perubahan stok produk ini">
                                        <i class="fa-solid fa-floppy-disk text-xs" :class="savingKey === item.id_product ? 'fa-spin' : ''"></i>
                                    </button>
                                    <button type="button" @click="draft = {}" class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition flex items-center justify-center cursor-pointer" title="Buang semua draft">
                                        <i class="fa-solid fa-rotate-left text-xs"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer Table Sync Info & Pagination -->
        <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-medium text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-rotate text-sky-500"></i>
                <span>Perubahan stok tersimpan per varian ke database (bukan lokal lagi).</span>
            </div>

            <div class="flex items-center gap-1 self-end sm:self-auto font-bold">
                <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lt;</button>
                <span class="px-2 text-slate-700" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
                <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                        class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&gt;</button>
            </div>
        </div>
    </div>

    </div>

</div>

@endsection
