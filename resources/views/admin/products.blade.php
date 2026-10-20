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
    categories: [],
    selectedCategory: 'ALL',
    searchQuery: '',
    loading: true,
    notice: '', noticeOk: true,

    // === Varian (per produk terpilih) ===
    variants: [],
    variantProduct: null,
    loadingVar: false,

    // === Modal tambah (dipakai komponen x-admin.modal-add-product) ===
    showModalTambah: false,
    savingProduk: false,
    namaFoto: '',

    fmt(n) { return PandansariAPI.formatRp(n); },
    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    coverOf(p) { return (p.images ?? []).find(i => i.url)?.url ?? '{{ asset('images/katalog1.webp') }}'; },
    stokOf(p) {
        const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif');
        return v.reduce((s, x) => s + Number(x.stok ?? 0), 0);
    },
    hargaMinOf(p) {
        const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif' && x.harga != null);
        return v.length ? Math.min(...v.map(x => Number(x.harga))) : null;
    },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4000); },

    async loadProduk() {
        this.loading = true;
        const res = await PandansariAPI.admin.adminProducts({
            page: this.page, per_page: this.perPage,
            search: this.searchQuery.trim() || undefined,
            category: this.selectedCategory !== 'ALL' ? this.selectedCategory : undefined,
        });
        this.loading = false;
        if (res.ok) {
            this.products = res.data ?? [];
            this.meta = res.meta;
        } else this.pesan(false, res.message);
    },
    async loadKategori() {
        const res = await PandansariAPI.categories();
        if (res.ok) this.categories = res.data ?? [];
    },
    setKategori(id) { this.selectedCategory = id; this.page = 1; this.loadProduk(); },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadProduk(); },

    // === Aksi produk ===
    async editHarga(p) {
        const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif' && x.harga != null)[0];
        if (!v) { this.pesan(false, 'Produk ini belum memiliki varian aktif untuk diedit harganya.'); return; }
        const hargaBaru = prompt('Harga baru untuk ' + p.nama_produk + ':', Number(v.harga));
        if (hargaBaru === null) return;
        const n = Number(hargaBaru);
        if (isNaN(n) || n < 0) { this.pesan(false, 'Harga tidak valid.'); return; }
        const res = await PandansariAPI.admin.updateVariant(v.id_variant, { harga: n });
        if (res.ok) { this.pesan(true, 'Harga ' + p.nama_produk + ' diperbarui.'); this.loadProduk(); if (this.variantProduct?.id_product === p.id_product) this.loadVarian(p); }
        else this.pesan(false, res.message);
    },
    async nonaktifkan(p) {
        if (!confirm('Nonaktifkan (sembunyikan dari toko) produk ' + p.nama_produk + '?')) return;
        const res = await PandansariAPI.admin.deleteProduct(p.id_product);
        if (res.ok) { this.pesan(true, 'Produk dinonaktifkan.'); this.loadProduk(); }
        else this.pesan(false, res.message);
    },
    async tambahProduk(form) {
        // form: { nama_produk, id_category, deskripsi?, harga, stok, file? }
        this.savingProduk = true;
        const res = await PandansariAPI.admin.createProduct({
            id_category: form.id_category,
            nama_produk: form.nama_produk,
            deskripsi: form.deskripsi || undefined,
            status_produk: 'aktif',
        });
        if (!res.ok) { this.savingProduk = false; this.pesan(false, res.message); return; }
        const produk = res.data;
        // varian awal dari harga & stok form
        if (form.harga != null) {
            await PandansariAPI.admin.createVariant(produk.id_product, {
                sku: (form.sku || produk.slug || 'VAR').toUpperCase() + '-01',
                nama_varian: 'Standard',
                harga: Number(form.harga),
                stok: Number(form.stok ?? 0),
            });
        }
        // upload gambar opsional
        if (form.file) {
            const fd = new FormData();
            fd.append('gambar', form.file);
            fd.append('is_primary', '1');
            await PandansariAPI.admin.addProductImage(produk.id_product, fd);
        }
        this.savingProduk = false;
        this.showModalTambah = false;
        this.namaFoto = '';
        this.pesan(true, 'Produk ' + produk.nama_produk + ' berhasil ditambahkan.');
        this.loadProduk();
    },

    // === Varian ===
    async loadVarian(p) {
        this.variantProduct = p;
        this.loadingVar = true;
        const res = await PandansariAPI.productVariants(p.id_product);
        this.loadingVar = false;
        this.variants = res.ok ? (res.data ?? []) : [];
    },
    async stepper(v, delta) {
        const next = Math.max(0, Number(v.stok ?? 0) + delta);
        const res = await PandansariAPI.admin.updateVariant(v.id_variant, { stok: next });
        if (res.ok) { v.stok = next; this.pesan(true, 'Stok ' + (v.ukuran ?? v.sku) + ' → ' + next); }
        else this.pesan(false, res.message);
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await Promise.all([this.loadProduk(), this.loadKategori()]);
        this.loading = false;
    }
}" class="space-y-6">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka katalog admin</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER BANNER -->
    <div class="bg-[#0A192F] text-white p-6 sm:p-8 rounded-2xl relative overflow-hidden shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-4 max-w-2xl relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                Katalog Mahakarya & Kurasi Varian Wastra
            </h1>
            <p class="text-xs text-slate-300 leading-relaxed font-normal">
                GET /admin/products — cari, filter kategori, edit harga varian, dan nonaktifkan produk.
            </p>

            <!-- Search Bar -->
            <div class="flex items-center gap-2.5 pt-1">
                <div class="relative w-full max-w-md">
                    <i class="fa-solid fa-sliders absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                    <input type="text"
                           x-model.debounce.600ms="searchQuery"
                           @input.debounce.600ms="page = 1; loadProduk()"
                           placeholder="Saring nama / deskripsi..."
                           class="w-full text-xs font-semibold bg-[#AEE2F0] text-slate-900 placeholder-slate-600 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400 border-none">
                </div>
                <button type="button" @click="PandansariAPI.admin.productsExport()"
                        class="bg-[#AEE2F0] hover:bg-[#97d7e8] text-slate-900 p-2.5 px-3 rounded-xl transition cursor-pointer shrink-0 font-bold" title="Unduh CSV (export produk)">
                    <i class="fa-solid fa-download text-xs"></i>
                </button>
            </div>

            <!-- Badges Kategori (dari API) -->
            <div class="flex flex-wrap gap-2 pt-1 text-[10px] font-extrabold">
                <button @click="setKategori('ALL')"
                        :class="selectedCategory === 'ALL' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'"
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    SEMUA
                </button>
                <template x-for="c in categories" :key="c.id_category">
                    <button @click="setKategori(c.id_category)"
                            :class="selectedCategory === c.id_category ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'"
                            class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider"
                            x-text="c.nama_kategori">
                    </button>
                </template>
            </div>
        </div>

        <div class="absolute right-0 bottom-0 top-0 z-0 opacity-25 pointer-events-none md:opacity-40 flex items-end justify-end">
            <img src="{{ asset('images/wayang.png') }}" alt="Wayang Wastra" class="h-full w-auto object-contain mix-blend-luminosity filter brightness-150">
        </div>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. STATS SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-sky-100/60 border border-sky-200/80 p-5 rounded-2xl flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider block mb-1">ARSIP KURASI AKTIF</span>
                <div class="text-2xl font-extrabold text-slate-800"><span x-text="num(meta?.total ?? products.length)"></span> <span class="text-xs font-semibold text-slate-500">Produk</span></div>
            </div>
            <div class="w-10 h-10 bg-white text-sky-600 rounded-xl flex items-center justify-center text-base shadow-sm">
                <i class="fa-solid fa-box-archive"></i>
            </div>
        </div>

        <div class="bg-slate-900 text-white p-5 rounded-2xl flex items-center justify-between border border-slate-800">
            <div>
                <span class="text-[9px] font-bold text-rose-400 uppercase tracking-wider block mb-0.5">PERHATIAN MENDESAK</span>
                <div class="text-2xl font-extrabold text-white"><span x-text="products.filter(p => stokOf(p) <= 2).length"></span> SKU <span class="text-xs font-semibold text-rose-300">Menipis/Habis</span></div>
                <p class="text-[9px] text-slate-400 mt-0.5">Stok varian aktif ≤ 2</p>
            </div>
            <div class="w-10 h-10 bg-rose-500/20 text-rose-400 rounded-xl flex items-center justify-center text-base border border-rose-500/30">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="bg-sky-100/60 border border-sky-200/80 p-5 rounded-2xl flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider block mb-1">TOTAL STOK FISIK</span>
                <div class="text-2xl font-extrabold text-slate-800"><span x-text="num(products.reduce((s, p) => s + stokOf(p), 0))"></span> <span class="text-xs font-semibold text-slate-500">unit</span></div>
            </div>
            <div class="w-10 h-10 bg-white text-sky-600 rounded-xl flex items-center justify-center text-base shadow-sm">
                <i class="fa-solid fa-warehouse"></i>
            </div>
        </div>
    </div>

    <!-- 3. ACTION BAR & ARSIP MASTER -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <button type="button" @click="showModalTambah = true" class="bg-sky-400 hover:bg-sky-500 text-slate-900 font-extrabold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 self-start shadow-sm cursor-pointer">
                <i class="fa-solid fa-circle-plus"></i>
                <span>TAMBAH MAHAKARYA BARU</span>
            </button>

            <div class="flex items-center gap-3 self-end">
                <span class="text-xs text-slate-500 font-bold">Arsip Master</span>
                <span class="text-[10px] text-slate-400">Menampilkan <strong x-text="products.length"></strong> karya (hal. <span x-text="meta?.current_page ?? 1"></span>/<span x-text="meta?.last_page ?? 1"></span>)</span>
            </div>
        </div>

        <!-- TABEL ARSIP MASTER PRODUK -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-sky-50/60 text-slate-700 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4">WASTRA & CANTING</th>
                            <th class="py-3 px-4 text-center">SLUG</th>
                            <th class="py-3 px-4 text-center">STOK TOTAL</th>
                            <th class="py-3 px-4">HARGA MULAI</th>
                            <th class="py-3 px-4 text-center">VISIBILITAS TOKO</th>
                            <th class="py-3 px-4 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <template x-if="loading">
                            <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat produk…</td></tr>
                        </template>
                        <template x-if="!loading && !products.length">
                            <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold">Tidak ada produk untuk filter ini.</td></tr>
                        </template>
                        <template x-for="p in products" :key="p.id_product">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <img :src="coverOf(p)" class="w-12 h-12 object-cover rounded-xl border border-slate-200 shrink-0 bg-slate-100">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-2">
                                                <span class="bg-[#0A192F] text-white text-[9px] font-bold px-1.5 py-0.5 rounded" x-text="(p.category?.nama_kategori ?? 'TANPA').toUpperCase()"></span>
                                                <strong class="text-slate-800 text-sm" x-text="p.nama_produk"></strong>
                                            </div>
                                            <p class="text-[10px] text-slate-400 line-clamp-1 max-w-xs truncate" x-text="p.deskripsi ?? '—'"></p>
                                            <span class="text-[9px] font-bold block" :class="stokOf(p) > 0 ? 'text-sky-600' : 'text-rose-600'"
                                                  x-text="stokOf(p) > 0 ? ((p.variants ?? []).length + ' varian aktif') : 'TANPA STOK'"></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-[10px] text-slate-500 font-bold" x-text="p.slug"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-extrabold text-xs px-2.5 py-1 rounded-lg inline-block" :class="stokOf(p) <= 2 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-800'" x-text="stokOf(p) + ' unit'"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <strong class="text-slate-800 block" x-text="hargaMinOf(p) != null ? fmt(hargaMinOf(p)) : '—'"></strong>
                                    <span class="text-[10px] text-slate-400 block">Harga varian terendah</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="text-white text-[10px] font-bold px-3 py-1 rounded-full inline-block uppercase"
                                          :class="p.status_produk === 'aktif' ? 'bg-sky-700' : (p.status_produk === 'draft' ? 'bg-amber-500' : 'bg-slate-400')"
                                          x-text="p.status_produk"></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <button @click="editHarga(p)" class="bg-sky-100 hover:bg-sky-200 text-sky-800 text-[10px] font-bold px-2.5 py-1 rounded-lg w-24 transition cursor-pointer">Edit Harga</button>
                                        <button @click="loadVarian(p)" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold px-2.5 py-1 rounded-lg w-24 transition cursor-pointer">Lihat Varian</button>
                                        <button @click="nonaktifkan(p)" class="bg-rose-100 hover:bg-rose-200 text-rose-700 text-[10px] font-bold px-2.5 py-1 rounded-lg w-24 transition cursor-pointer">Nonaktifkan</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-end gap-1.5 p-4 border-t border-slate-100 font-bold text-xs">
                <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lt;</button>
                <span class="px-2 text-slate-500" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
                <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                        class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&gt;</button>
            </div>
        </div>
    </div>

    <!-- 4. PUSAT KENDALI VARIAN -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6">
        <div class="bg-[#0A192F] text-white p-4 rounded-xl flex items-center gap-3">
            <div class="w-8 h-8 bg-sky-500 text-slate-900 rounded-lg flex items-center justify-center text-sm font-bold shrink-0">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-bold">Pusat Kendali Varian & Saldo Stok</h3>
                <p class="text-[11px] text-slate-300">
                    <span x-show="variantProduct">Varian <span class="font-bold" x-text="variantProduct?.nama_produk"></span> — stok disimpan via PUT /variants/{id}.</span>
                    <span x-show="!variantProduct">Klik "Lihat Varian" pada tabel produk untuk memuat varian.</span>
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <template x-if="!variantProduct">
                <div class="py-8 text-center text-xs text-slate-400 font-semibold">Belum ada produk dipilih.</div>
            </template>
            <template x-if="variantProduct && loadingVar">
                <div class="py-8 text-center text-xs text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat varian…</div>
            </template>
            <template x-if="variantProduct && !loadingVar && !variants.length">
                <div class="py-8 text-center text-xs text-slate-400 font-semibold">Produk ini belum memiliki varian aktif.</div>
            </template>
            <table x-show="variantProduct && !loadingVar && variants.length" class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">SKU & VARIAN</th>
                        <th class="py-3 px-4 text-center">UKURAN</th>
                        <th class="py-3 px-4">WARNA</th>
                        <th class="py-3 px-4 text-center">STOK (STEPPER)</th>
                        <th class="py-3 px-4">HARGA</th>
                        <th class="py-3 px-4">INDIKATOR KONDISI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <template x-for="v in variants" :key="v.id_variant">
                        <tr>
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-800 block" x-text="v.nama_varian ?? v.sku"></strong>
                                <span class="text-[10px] text-slate-400 font-mono" x-text="v.sku"></span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 font-extrabold flex items-center justify-center mx-auto text-slate-800 text-[11px]" x-text="v.ukuran ?? '—'"></span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-medium" x-text="v.warna ?? '—'"></td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="stepper(v, -1)" :disabled="Number(v.stok ?? 0) <= 0" class="w-6 h-6 bg-slate-100 rounded text-slate-600 font-bold hover:bg-slate-200 cursor-pointer disabled:opacity-40">-</button>
                                    <span class="font-extrabold text-sm w-6 text-center" :class="Number(v.stok ?? 0) === 0 ? 'text-rose-600' : 'text-slate-800'" x-text="v.stok"></span>
                                    <button @click="stepper(v, 1)" class="w-6 h-6 bg-slate-100 rounded text-slate-600 font-bold hover:bg-slate-200 cursor-pointer">+</button>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-800" x-text="fmt(v.harga)"></td>
                            <td class="py-3.5 px-4">
                                <template x-if="Number(v.stok ?? 0) >= 3">
                                    <span class="bg-emerald-100 text-emerald-800 font-bold text-[10px] px-3 py-1 rounded-full inline-flex items-center gap-1">Aman (<span x-text="v.stok"></span>)</span>
                                </template>
                                <template x-if="Number(v.stok ?? 0) > 0 && Number(v.stok ?? 0) < 3">
                                    <span class="bg-amber-100 text-amber-800 font-bold text-[10px] px-3 py-1 rounded-full inline-flex items-center gap-1">Menipis (<span x-text="v.stok"></span>)</span>
                                </template>
                                <template x-if="Number(v.stok ?? 0) === 0">
                                    <span class="bg-rose-500 text-white font-bold text-[10px] px-3 py-1 rounded-full inline-flex items-center gap-1">Habis</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    </div>

    <!-- MODAL: terima submit dari komponen (panggil tambahProduk) -->
    <x-admin.modal-add-product />

</div>

@endsection
