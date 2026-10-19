@extends('layouts.app')

@section('content')

    <!-- Container Utama Katalog dengan Alpine.js Data Binding Interaktif (data dari GET /api) -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{
            selectedCategory: '',
            searchQuery: '',
            sortBy: 'terpopuler',
            viewMode: 'grid',

            // --- State dari API ---
            categories: [],       // GET /api/categories → [{ id_category, nama_kategori }]
            products: [],         // GET /api/products → data
            loading: true,
            errorMsg: '',
            page: 1,
            perPage: 12,
            total: 0,
            lastPage: 1,
            wishlisted: {},
            fallbackImage: '{{ asset('images/katalog1.webp') }}',

            get activeCategoryLabel() {
                const c = this.categories.find(c => c.id_category === this.selectedCategory);
                return c ? c.nama_kategori : 'Semua Koleksi';
            },

            // --- Loader ---
            async loadProducts(page = 1) {
                this.loading = true;
                this.errorMsg = '';
                const res = await PandansariAPI.products({
                    search: this.searchQuery,
                    category: this.selectedCategory,
                    page: page,
                    per_page: this.perPage,
                });
                this.loading = false;
                if (res.ok) {
                    this.products = res.data ?? [];
                    this.total = res.meta?.total ?? this.products.length;
                    this.lastPage = res.meta?.last_page ?? 1;
                    this.page = res.meta?.current_page ?? page;
                } else {
                    this.products = [];
                    this.errorMsg = res.message;
                }
            },
            async loadCategories() {
                const res = await PandansariAPI.categories();
                if (res.ok) this.categories = res.data ?? [];
            },

            // --- Helper kartu produk ---
            coverOf(p) {
                const img = (p.images ?? []).find(i => i.url);
                return img?.url ?? this.fallbackImage;
            },
            priceOf(p) {
                const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif' && x.harga != null);
                if (!v.length) return 'Lihat Detail';
                return PandansariAPI.formatRp(Math.min(...v.map(x => Number(x.harga))));
            },
            sizesOf(p) {
                const v = (p.variants ?? []).map(x => x.ukuran).filter(Boolean);
                return v.length ? [...new Set(v)].join(', ') : '';
            },
            async toggleWishlist(p) {
                if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
                if (this.wishlisted[p.id_product]) return;
                const res = await PandansariAPI.addWishlist(p.id_product);
                if (res.ok || res.status === 422) this.wishlisted[p.id_product] = true; // 422 = sudah ada
                else this.errorMsg = res.message;
            },

            // --- Aksi filter ---
            selectCategory(key) {
                this.selectedCategory = key;
                this.loadProducts(1);
            },
            clearCategory() {
                this.selectCategory('');
            },
            clearSearch() {
                this.searchQuery = '';
                this.loadProducts(1);
            },
            gotoPage(n) {
                if (n < 1 || n > this.lastPage || n === this.page) return;
                this.loadProducts(n);
                document.querySelector('input[placeholder*=\"Cari kata kunci\"]')?.blur();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            async init() {
                await Promise.all([this.loadCategories(), this.loadProducts(1)]);
            }
         }">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

            <!-- 1. Breadcrumb Navigation -->
            <nav class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-medium text-slate-500 mb-3 sm:mb-4 overflow-x-auto whitespace-nowrap">
                <a href="/" class="hover:text-slate-900 transition">Beranda</a>
                <span>/</span>
                <a href="/katalog" class="hover:text-slate-900 transition">Katalog Mahakarya Wastra</a>
                <span>/</span>
                <span class="text-slate-900 font-bold" x-text="activeCategoryLabel">Semua Koleksi</span>
            </nav>

            <!-- 2. Page Header Title & Subtitle -->
            <div class="mb-4 sm:mb-6">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-slate-200/60 text-[#0369A1] text-[9px] sm:text-[10px] font-bold uppercase tracking-wider mb-2">
                    ✦ KARYA BATIK PANDANSARI MAHARDIKA
                </div>
                <h1 class="text-xl sm:text-3xl font-bold text-[#0F1A24] tracking-tight">
                    Katalog Batik Pandansari
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 max-w-3xl mt-1 leading-relaxed">
                    Eksplorasi kurasi busana batik autentik Batik Pandansari karya pengrajin maestro dari episentrum budaya: Surakarta, Yogyakarta, Pekalongan, dan Cirebon. Menyajikan batik tulis, cap tembaga, hingga busana siap pakai berfuring premium.
                </p>
            </div>

            <!-- 3. Promo Banner Diskon 25% -->
            <div class="relative bg-[#0F1A24] rounded-2xl p-5 sm:p-8 text-white mb-6 sm:mb-8 overflow-hidden shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-sky-900/60 border border-sky-400/30 text-[#BAE6FD] text-[9px] sm:text-[10px] font-bold uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-tag text-[9px]"></i> BANNER PROMO INDONESIA
                    </div>
                    <h2 class="text-lg sm:text-2xl font-bold text-white tracking-tight">
                        Diskon Spesial Hingga 25%
                    </h2>
                    <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                        Nikmati penawaran eksklusif untuk koleksi sarimbit keluarga, kemeja pria, dan Wastra Tradisional Lembaran. Gunakan kode kupon istimewa di keranjang belanja Anda: <span class="font-mono text-sky-300 font-bold underline underline-offset-2">PANDANSARI25</span>
                    </p>
                </div>
                <a href="#" class="relative z-10 w-full sm:w-auto text-center shrink-0 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold px-5 py-3 rounded-xl transition shadow">
                    KLAIM VOUCHER & BELANJA →
                </a>
            </div>

            <!-- Pencarian Kata Kunci Motif / Produk -->
            <div class="relative w-full mb-4">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.500ms="if (page === 1) { loadProducts(1) } else { page = 1; loadProducts(1) }"
                       placeholder="Cari kata kunci motif, kain, sarimbit, warna di katalog..."
                       class="w-full pl-11 pr-20 sm:pr-24 py-2.5 sm:py-3 text-xs sm:text-sm bg-white border border-slate-200 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500 font-['Plus_Jakarta_Sans'] placeholder:text-slate-400">
                <button type="button"
                        @click="loadProducts(1)"
                        class="absolute right-1.5 sm:right-2 top-1/2 -translate-y-1/2 bg-[#0F1A24] text-white text-[11px] sm:text-xs font-bold px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl hover:bg-slate-800 transition font-['Plus_Jakarta_Sans']">
                    Cari
                </button>
            </div>

            <!-- 4. Filter Category Pills (Rapi Flex-Wrap Tanpa Geser) -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-4 sm:mb-6 text-[11px] sm:text-xs font-semibold font-['Plus_Jakarta_Sans']">
                <!-- Pill: Semua (tanpa filter kategori) -->
                <button @click="clearCategory()"
                        type="button"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl border transition whitespace-nowrap shadow-sm font-['Plus_Jakarta_Sans'] cursor-pointer"
                        :class="selectedCategory === '' ? 'bg-[#BAE6FD] text-[#0F1A24] font-bold border-sky-300' : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'">
                    <span x-show="selectedCategory === ''">✓ </span> Semua Koleksi
                </button>
                <!-- Pill dari API: GET /api/categories -->
                <template x-for="cat in categories" :key="cat.id_category">
                    <button @click="selectCategory(cat.id_category)"
                            type="button"
                            class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl border transition whitespace-nowrap shadow-sm font-['Plus_Jakarta_Sans'] cursor-pointer"
                            :class="selectedCategory === cat.id_category ? 'bg-[#BAE6FD] text-[#0F1A24] font-bold border-sky-300' : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'">
                        <span x-show="selectedCategory === cat.id_category">✓ </span>
                        <span x-text="cat.nama_kategori"></span>
                    </button>
                </template>
            </div>

            <!-- 5. Active Filter Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200/80 mb-6 shadow-sm font-['Plus_Jakarta_Sans']">
                
                <!-- Applied Filter Tags -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-500 font-medium text-[11px] sm:text-xs">
                        Menampilkan <span class="font-bold text-slate-900" x-text="total"></span> Mahakarya Busana:
                    </span>

                    <!-- Tag Kategori Aktif -->
                    <template x-if="selectedCategory !== ''">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-md text-slate-800 font-bold transition-all duration-200 text-[11px] sm:text-xs">
                            <span x-text="activeCategoryLabel"></span>
                            <button @click="clearCategory()" 
                                    type="button" 
                                    class="hover:text-red-500 font-bold ml-0.5 text-slate-500 hover:bg-slate-200 rounded px-1 transition">
                                ×
                            </button>
                        </span>
                    </template>

                    <!-- Tag Pencarian Kata Kunci -->
                    <template x-if="searchQuery !== ''">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-sky-50 border border-sky-200 text-[#0369A1] rounded-md font-bold transition-all duration-200 text-[11px] sm:text-xs">
                            Cari: "<span x-text="searchQuery"></span>"
                            <button @click="clearSearch()" 
                                    type="button" 
                                    class="hover:text-red-500 font-bold ml-0.5 text-sky-500 hover:bg-sky-100 rounded px-1 transition">
                                ×
                            </button>
                        </span>
                    </template>
                </div>

                <!-- Sorting Dropdown & View Mode Switcher -->
                <div class="flex items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-medium whitespace-nowrap">Urutkan:</span>
                        <select x-model="sortBy" 
                                class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 font-bold text-slate-800 focus:outline-none cursor-pointer">
                            <option value="terpopuler">Katalog Terpopuler</option>
                            <option value="harga-rendah">Harga: Rendah ke Tinggi</option>
                            <option value="harga-tinggi">Harga: Tinggi ke Rendah</option>
                            <option value="terbaru">Terbaru</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50 shrink-0">
                        <button @click="viewMode = 'grid'" 
                                type="button" 
                                class="p-1.5 transition"
                                :class="viewMode === 'grid' ? 'bg-white text-slate-900 border-r border-slate-200 shadow-sm' : 'text-slate-400 hover:text-slate-900'">
                            <i class="fa-solid fa-table-cells-large text-xs"></i>
                        </button>
                        <button @click="viewMode = 'list'" 
                                type="button" 
                                class="p-1.5 transition"
                                :class="viewMode === 'list' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-900'">
                            <i class="fa-solid fa-list text-xs"></i>
                        </button>
                    </div>
                </div>

            </div>

            <!-- 6. Grid Produk (2 Kolom di HP / Smartphone, 4 Kolom di Desktop) -->
            <div class="grid gap-3 sm:gap-6 mb-10"
                 :class="viewMode === 'grid' ? 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4' : 'grid-cols-1'">

            <!-- State API: Skeleton / Error / Kosong -->
            <template x-if="loading">
                <div class="col-span-full h-56 flex items-center justify-center text-slate-400 text-xs font-semibold font-['Plus_Jakarta_Sans']">
                    <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Memuat katalog dari server…
                </div>
            </template>

            <template x-if="!loading && errorMsg">
                <div class="col-span-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span x-text="errorMsg"></span>
                </div>
            </template>

            <template x-if="!loading && !errorMsg && products.length === 0">
                <div class="col-span-full text-center py-16 text-slate-500 text-xs font-medium font-['Plus_Jakarta_Sans']">
                    <i class="fa-regular fa-face-frown text-2xl block mb-3 text-slate-300"></i>
                    Tidak ada produk yang cocok dengan pencarian atau filter yang dipilih.
                </div>
            </template>

            <!-- Kartu Produk (data dari GET /api/products) -->
            <template x-for="p in products" :key="p.id_product">
                @include('components.shop.product-item')
            </template>
            </div>

            <!-- 7. Pagination Bar (dari meta GET /api/products) -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200/80 mb-12 font-['Plus_Jakarta_Sans']">
                <span class="text-xs text-slate-500 text-center sm:text-left">
                    Halaman <span class="font-bold text-slate-900" x-text="page"></span> dari <span class="font-bold text-slate-900" x-text="lastPage"></span> (Menampilkan <span x-text="total"></span> mahakarya)
                </span>
                <div class="flex items-center gap-1.5 text-xs font-bold">
                    <button type="button" @click="gotoPage(page - 1)"
                            class="px-3 h-8 rounded-lg flex items-center justify-center transition"
                            :class="page > 1 ? 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 cursor-pointer' : 'bg-slate-50 text-slate-300 cursor-not-allowed'">← Sebelumnya</button>

                    <template x-for="n in Math.min(lastPage, Math.max(5, page + 2))" :key="n">
                        <button type="button" @click="gotoPage(n)"
                                class="w-8 h-8 rounded-lg flex items-center justify-center cursor-pointer transition"
                                :class="n === page ? 'bg-[#BAE6FD] text-[#0F1A24]' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                                x-show="n > Math.max(0, page - 3) && n <= Math.min(lastPage, page + 2)"
                                x-text="n"></button>
                    </template>

                    <button type="button" @click="gotoPage(page + 1)"
                            class="px-3 h-8 rounded-lg flex items-center justify-center transition"
                            :class="page < lastPage ? 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 cursor-pointer' : 'bg-slate-50 text-slate-300 cursor-not-allowed'">Berikutnya →</button>
                </div>
            </div>

            <!-- 8. Banner Konsultasi Custom WhatsApp -->
            <x-consultation-banner />

        </div>
    </div>

@endsection
