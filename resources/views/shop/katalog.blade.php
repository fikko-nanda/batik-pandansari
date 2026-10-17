@extends('layouts.app')

@section('content')

    <!-- Container Utama Katalog dengan Alpine.js Data Binding Interaktif -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{ 
            selectedCategory: 'pria',
            searchQuery: '',
            sortBy: 'terpopuler',
            viewMode: 'grid',
            
            categories: {
                '': { label: 'Semua Koleksi', count: 114 },
                'pria': { label: 'Pakaian Pria', count: 18 },
                'wanita': { label: 'Pakaian Wanita', count: 16 },
                'anak': { label: 'Pakaian Anak-Anak', count: 12 },
                'tulis': { label: 'Batik Tulis', count: 14 },
                'cap': { label: 'Batik Cap', count: 32 },
                'handprint': { label: 'Bahan Handprint', count: 8 },
                'sarimbit': { label: 'Sarimbit', count: 8 },
                'kain': { label: 'Kain Tradisional', count: 10 }
            },
            
            selectCategory(key) {
                this.selectedCategory = key;
            },
            clearCategory() {
                this.selectedCategory = '';
            },
            clearSearch() {
                this.searchQuery = '';
            }
         }">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

            <!-- 1. Breadcrumb Navigation -->
            <nav class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-medium text-slate-500 mb-3 sm:mb-4 overflow-x-auto whitespace-nowrap">
                <a href="/" class="hover:text-slate-900 transition">Beranda</a>
                <span>/</span>
                <a href="/katalog" class="hover:text-slate-900 transition">Katalog Mahakarya Wastra</a>
                <span>/</span>
                <span class="text-slate-900 font-bold" x-text="categories[selectedCategory] ? categories[selectedCategory].label : 'Semua Koleksi'">Kemeja Busana Pria</span>
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
                       placeholder="Cari kata kunci motif, kain, sarimbit, warna di katalog..." 
                       class="w-full pl-11 pr-20 sm:pr-24 py-2.5 sm:py-3 text-xs sm:text-sm bg-white border border-slate-200 rounded-2xl shadow-sm focus:outline-none focus:ring-2 focus:ring-sky-500 font-['Plus_Jakarta_Sans'] placeholder:text-slate-400">
                <button type="button" 
                        class="absolute right-1.5 sm:right-2 top-1/2 -translate-y-1/2 bg-[#0F1A24] text-white text-[11px] sm:text-xs font-bold px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl hover:bg-slate-800 transition font-['Plus_Jakarta_Sans']">
                    Cari
                </button>
            </div>

            <!-- 4. Filter Category Pills (Rapi Flex-Wrap Tanpa Geser) -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-4 sm:mb-6 text-[11px] sm:text-xs font-semibold font-['Plus_Jakarta_Sans']">
                <template x-for="(cat, key) in categories" :key="key">
                    <button @click="selectCategory(key)" 
                            type="button"
                            class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl border transition whitespace-nowrap shadow-sm font-['Plus_Jakarta_Sans'] cursor-pointer"
                            :class="selectedCategory === key ? 'bg-[#BAE6FD] text-[#0F1A24] font-bold border-sky-300' : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'">
                        <span x-show="selectedCategory === key">✓ </span>
                        <span x-text="cat.label + ' (' + cat.count + ')'"></span>
                    </button>
                </template>
            </div>

            <!-- 5. Active Filter Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200/80 mb-6 shadow-sm font-['Plus_Jakarta_Sans']">
                
                <!-- Applied Filter Tags -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-500 font-medium text-[11px] sm:text-xs">
                        Menampilkan <span class="font-bold text-slate-900" x-text="categories[selectedCategory] ? categories[selectedCategory].count : 114"></span> Mahakarya Busana:
                    </span>

                    <!-- Tag Kategori Aktif -->
                    <template x-if="selectedCategory !== ''">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-md text-slate-800 font-bold transition-all duration-200 text-[11px] sm:text-xs">
                            <span x-text="categories[selectedCategory].label"></span>
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

                <!-- Item 1: Kemeja Pria Parang Seling Indigo -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/katalog1.webp') }}" alt="Kemeja Pria" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            PAKAIAN PRIA
                        </span>
                        
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>

                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                PAKAIAN PRIA • BATIK TULIS SOLO
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Kemeja Pria Parang Seling Indigo
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Kemeja pria motif pakem parang seling...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 1.350.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                M, L, XL
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item 2: Tunik Batik Pesisir Cheongsam -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/katalog2.webp') }}" alt="Tunik Cheongsam" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            PAKAIAN WANITA
                        </span>
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>
                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                PAKAIAN WANITA • CHEONGSAM MODERN
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Tunik Batik Pesisir Cheongsam
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Siluet tunik kerah shanghai dengan motif...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 1.280.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                S, M, L
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item 3: Dress Anak Bunga Sekar Navy -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/katalog1.webp') }}" alt="Dress Anak" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            PAKAIAN ANAK-ANAK
                        </span>
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>
                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                PAKAIAN ANAK-ANAK • DRESS KATUN
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Dress Anak Bunga Sekar Navy
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Gaun anak bahan katun primisima lembut...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 450.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                2-8 Thn
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item 4: Megamendung Indigo Silk -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/katalog2.webp') }}" alt="Kain Megamendung" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            HANDPRINT
                        </span>
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>
                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                CIREBON • MEGAMENDUNG
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Megamendung Indigo Silk
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Gradasi biru teduh indigo khas dengan...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 1.480.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                XL, LL
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item 5: Sekar Jagad Pesisir Royal -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/hero-bg.jpg') }}" alt="Sekar Jagad" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#EE4D2D] text-white text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            BATIK TULIS
                        </span>
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>
                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                PEKALONGAN • SEKAR JAGAD
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Sekar Jagad Pesisir Royal
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Kain seni corak mosaik nusantara klasik...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 1.150.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                Ukuran: L
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item 6: Blouse Batik Cap -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/katalog1.webp') }}" alt="Blouse Batik Cap" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            PAKAIAN WANITA
                        </span>
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>
                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                PAKAIAN WANITA • BATIK CAP
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Blouse Batik Cap
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Blouse batik cap modern wanita motif khas...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 1.190.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                S, M, L
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item 7: Kemeja Sutra Parang Kencana -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/katalog2.webp') }}" alt="Kemeja Sutra" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            PAKAIAN PRIA
                        </span>
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>
                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                PAKAIAN PRIA • SUTRA HALUS
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Kemeja Sutra Parang Kencana
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Kemeja sutra lembut dengan motif parang...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 1.450.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                M, L, XL
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Item 8: Kain Panjang Sogan Primissima -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/hero-bg.jpg') }}" alt="Kain Sogan" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10">
                            BATIK CAP
                        </span>
                        <button class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-regular fa-heart text-[10px] sm:text-xs"></i>
                        </button>
                        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
                            <a href="#" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                Detail Produk
                            </a>
                        </div>
                    </div>
                    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                KAIN TRADISIONAL • CAP TEMBAGA
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Kain Panjang Sogan Primissima
                            </h3>
                            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block">
                                Lembaran kain katun primisima siap jahit...
                            </p>
                        </div>
                        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="font-bold text-[#0F1A24] text-xs sm:text-base">Rp 850.000</span>
                            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto">
                                Pjg: 2.4 m
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 7. Pagination Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-200/80 mb-12 font-['Plus_Jakarta_Sans']">
                <span class="text-xs text-slate-500 text-center sm:text-left">
                    Halaman <span class="font-bold text-slate-900">1</span> dari <span class="font-bold text-slate-900">4</span> (Menampilkan <span x-text="categories[selectedCategory] ? categories[selectedCategory].count : 114"></span> mahakarya)
                </span>
                <div class="flex items-center gap-1.5 text-xs font-bold">
                    <a href="#" class="w-8 h-8 rounded-lg bg-[#BAE6FD] text-[#0F1A24] flex items-center justify-center">1</a>
                    <a href="#" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 flex items-center justify-center">2</a>
                    <a href="#" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 flex items-center justify-center">3</a>
                    <a href="#" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 flex items-center justify-center">4</a>
                    <span class="px-1 text-slate-400">...</span>
                    <a href="#" class="px-3 h-8 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 flex items-center justify-center">Berikutnya →</a>
                </div>
            </div>

            <!-- 8. Banner Konsultasi Custom WhatsApp -->
            <x-consultation-banner />

        </div>
    </div>

@endsection