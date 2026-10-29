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

    // === Inventory summary ===
    lowStockCount: 0,
    valuation: null,
    loadingSummary: true,

    // === Popup atur stok per produk ===
    stockModalOpen: false,
    stockProduct: null,
    stockSelectedId: '',
    stockDraft: 0,
    savingStock: false,
    stockError: '',
    stockSuccess: '',

    fmt(n) { return PandansariAPI.formatRp(n); },
    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    // === Load inventory summary ===
    async loadInventorySummary() {
        this.loadingSummary = true;
        const [low, val] = await Promise.all([
            PandansariAPI.admin.inventoryLowStock(),
            PandansariAPI.admin.inventoryValuation(),
        ]);
        this.loadingSummary = false;
        if (low.ok) this.lowStockCount = (low.data ?? []).length;
        if (val.ok) this.valuation = val.data;
    },

    activeVariants(p) {
        return (p?.variants ?? []).filter(v => v.status_varian !== 'nonaktif');
    },
    totalStok(p) {
        return this.activeVariants(p).reduce((s, v) => s + Number(v.stok ?? 0), 0);
    },
    coverOf(p) {
        return PandansariAPI.coverUrl(p?.images) || '{{ asset('images/katalog1.webp') }}';
    },
    statusInfo(p) {
        const t = this.totalStok(p);
        if (p.status_produk !== 'aktif') return { label: 'Nonaktif', style: 'bg-slate-100 text-slate-600 border border-slate-200', row: '' };
        if (t === 0) return { label: 'Habis', style: 'bg-rose-100 text-rose-800 border border-rose-200 font-bold', row: 'bg-rose-50/40 hover:bg-rose-50/70' };
        if (t <= 3) return { label: 'Stok Menipis', style: 'bg-sky-200 text-sky-900 border border-sky-300 font-bold', row: 'bg-sky-50/30 hover:bg-sky-50/60' };
        return { label: 'Aman', style: 'bg-emerald-50 text-emerald-800 border border-emerald-200', row: 'hover:bg-slate-50/80' };
    },
    variantLabel(v) {
        const nama = String(v?.nama_varian ?? '').trim();
        if (nama) return nama;
        const kombinasi = [v?.ukuran, v?.warna]
            .map(x => String(x ?? '').trim())
            .filter(Boolean)
            .join('-');
        if (kombinasi) return kombinasi;
        return String(v?.sku ?? '').trim() || 'Varian';
    },
    stockLevelInfo(n) {
        const stok = Number(n ?? 0);
        if (stok <= 0) return { label: 'Habis', style: 'bg-rose-100 text-rose-700 border border-rose-200' };
        if (stok <= 3) return { label: 'Menipis', style: 'bg-amber-100 text-amber-800 border border-amber-200' };
        return { label: 'Aman', style: 'bg-emerald-50 text-emerald-700 border border-emerald-200' };
    },
    selectedStockVariant() {
        if (!this.stockProduct) return null;
        return this.activeVariants(this.stockProduct).find(v => String(v.id_variant) === String(this.stockSelectedId)) ?? null;
    },
    stockDraftValue() {
        if (this.stockDraft === '' || this.stockDraft === null || this.stockDraft === undefined) return NaN;
        const n = Number(this.stockDraft);
        return Number.isInteger(n) ? n : NaN;
    },
    stockDraftValid() {
        const n = this.stockDraftValue();
        return Number.isInteger(n) && n >= 0;
    },
    stockDraftDiff() {
        const v = this.selectedStockVariant();
        if (!v || !this.stockDraftValid()) return 0;
        return this.stockDraftValue() - Number(v.stok ?? 0);
    },
    stockDraftPreviewTotal() {
        const p = this.stockProduct;
        const selected = this.selectedStockVariant();
        if (!p) return 0;
        const next = selected && this.stockDraftValid() ? this.stockDraftValue() : Number(selected?.stok ?? 0);
        return this.activeVariants(p).reduce((sum, v) => {
            const stok = selected && String(v.id_variant) === String(selected.id_variant) ? next : Number(v.stok ?? 0);
            return sum + stok;
        }, 0);
    },
    canSaveStock() {
        const v = this.selectedStockVariant();
        return !!v && this.stockDraftValid() && this.stockDraftValue() !== Number(v.stok ?? 0) && !this.savingStock;
    },
    clearStockFeedback() {
        this.stockError = '';
        this.stockSuccess = '';
    },
    openStockModal(p) {
        const variants = this.activeVariants(p);
        if (!variants.length) {
            this.pesan(false, 'Produk ini belum memiliki varian aktif.');
            return;
        }
        this.stockProduct = p;
        this.stockSelectedId = variants[0].id_variant;
        this.stockDraft = Number(variants[0].stok ?? 0);
        this.savingStock = false;
        this.clearStockFeedback();
        this.stockModalOpen = true;
    },
    closeStockModal() {
        if (this.savingStock) return;
        this.stockModalOpen = false;
        this.clearStockFeedback();
    },
    selectStockVariant(v) {
        if (this.savingStock) return;
        this.stockSelectedId = v.id_variant;
        this.stockDraft = Number(v.stok ?? 0);
        this.clearStockFeedback();
    },
    async saveStockVariant() {
        if (this.savingStock) return;
        const v = this.selectedStockVariant();
        if (!v) return;
        if (!this.stockDraftValid()) {
            this.stockError = 'Stok baru harus berupa angka bulat minimal 0.';
            return;
        }
        const next = this.stockDraftValue();
        if (next === Number(v.stok ?? 0)) {
            this.stockError = 'Stok belum berubah.';
            return;
        }
        this.savingStock = true;
        this.clearStockFeedback();
        const res = await PandansariAPI.admin.updateVariant(v.id_variant, { stok: next });
        this.savingStock = false;
        if (!res.ok) {
            const errs = Object.values(res.errors ?? {}).flat();
            this.stockError = errs[0] ?? this._mediaError(res);
            return;
        }
        const saved = Number(res.data?.stok ?? next);
        v.stok = Number.isInteger(saved) && saved >= 0 ? saved : next;
        this.stockDraft = Number(v.stok);
        this.stockSuccess = 'Stok ' + this.variantLabel(v) + ' tersimpan: ' + this.num(v.stok) + ' unit.';
        this.pesan(true, this.stockSuccess);
        await this.loadInventorySummary();
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
        } else this.pesan(false, res.message);
    },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadProduk(); },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (!me.ok || me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await Promise.all([this.loadProduk(), this.loadInventorySummary()]);
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
                Klik Atur Stok untuk membuka popup per produk; saldo varian disimpan via PUT /variants/{id} dan status toko via PUT /products/{id}.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <button type="button" @click="PandansariAPI.admin.inventoryExport()"
                    class="px-4 py-2.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-file-export text-emerald-600"></i>
                <span>Export Inventaris</span>
            </button>
            <button type="button" @click="PandansariAPI.admin.productsExport()"
                    class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-500"></i>
                <span>Unduh CSV Produk</span>
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5">

        <div class="bg-[#AEE2F0]/50 border border-sky-200/80 rounded-2xl p-4 sm:p-6 relative overflow-hidden flex flex-col justify-between shadow-sm">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest">TOTAL PRODUK TERCATAT</span>
                <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900"><span x-text="meta ? num(meta.total) : '…'"></span> SKU</div>
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

        <div class="bg-[#AEE2F0]/50 border border-sky-200/80 rounded-2xl p-4 sm:p-6 relative overflow-hidden flex flex-col justify-between shadow-sm">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest">TOTAL UNIT DI HALAMAN INI</span>
                <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900"><span x-text="num(products.reduce((s, p) => s + totalStok(p), 0))"></span> <span class="text-lg sm:text-xl font-bold text-slate-700">unit</span></div>
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

        <!-- Stok Kritis (Low Stock) -->
        <div class="bg-rose-50 border border-rose-200/80 rounded-2xl p-4 sm:p-6 relative overflow-hidden flex flex-col justify-between shadow-sm">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-extrabold text-rose-700 uppercase tracking-widest">STOK KRITIS (≤3)</span>
                <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-rose-600" x-text="loadingSummary ? '…' : num(lowStockCount)"></div>
            </div>

            <div class="relative z-10 mt-6 flex items-center gap-2 text-xs font-bold text-rose-700">
                <i class="fa-solid fa-triangle-exclamation text-rose-500 text-sm"></i>
                <span x-text="lowStockCount + ' varian butuh restock segera'"></span>
            </div>

            <div class="absolute right-4 top-4 w-9 h-9 bg-rose-200/80 rounded-xl flex items-center justify-center text-rose-600 text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
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
                        <th class="py-3.5 px-4">RINGKASAN STOK VARIAN</th>
                        <th class="py-3.5 px-4">STATUS & REKOMENDASI</th>
                        <th class="py-3.5 px-4 text-center">STATUS TOKO</th>
                        <th class="py-3.5 px-4 text-center">AKSI</th>
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

                            <!-- Ringkasan stok per varian (read-only) -->
                            <td class="py-4 px-4">
                                <template x-if="!activeVariants(item).length">
                                    <span class="text-[10px] text-slate-400 font-semibold">Belum ada varian aktif</span>
                                </template>
                                <div x-show="activeVariants(item).length" class="flex flex-wrap items-center gap-1.5">
                                    <template x-for="v in activeVariants(item)" :key="v.id_variant">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-1"
                                              :class="Number(v.stok ?? 0) <= 0 ? 'bg-rose-50 border-rose-200 text-rose-700' : (Number(v.stok ?? 0) <= 3 ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-slate-100/80 border-slate-200 text-slate-700')">
                                            <span class="text-[10px] font-bold" x-text="variantLabel(v)"></span>
                                            <span class="text-[11px] font-black" x-text="num(v.stok)"></span>
                                        </span>
                                    </template>
                                    <span class="bg-amber-100/80 text-amber-900 border border-amber-200 text-[11px] font-extrabold px-3 py-1.5 rounded-xl shrink-0"
                                          x-text="totalStok(item) + ' unit'"></span>
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

                            <!-- Aksi: atur stok lewat popup & kelola varian -->
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="openStockModal(item)" :disabled="!activeVariants(item).length"
                                            class="h-8 px-3 rounded-xl bg-sky-100 text-sky-800 hover:bg-sky-200 transition inline-flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                            :title="activeVariants(item).length ? 'Atur stok varian produk ini' : 'Belum ada varian aktif'">
                                        <i class="fa-solid fa-boxes-stacked text-xs"></i>
                                        <span class="text-[10px] font-extrabold">Atur Stok</span>
                                    </button>
                                    <a :href="'/admin/products?varian=' + item.id_product" class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 hover:bg-amber-200 transition flex items-center justify-center cursor-pointer" title="Kelola Varian">
                                        <i class="fa-solid fa-boxes-packing text-xs"></i>
                                    </a>
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
                <span>Stok diatur lewat popup per produk; setiap varian langsung tersimpan ke database.</span>
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

    <x-admin.modal-stock />

</div>

@endsection
