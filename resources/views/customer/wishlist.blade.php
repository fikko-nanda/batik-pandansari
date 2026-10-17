@extends('layouts.app')

@section('content')

    <!-- Container Utama Wishlist / Produk Favorit -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{ 
            selectedCategory: 'pria',
            
            categories: {
                '': { label: 'Semua Koleksi', count: 6 },
                'pria': { label: 'Pakaian Pria', count: 3 },
                'wanita': { label: 'Pakaian Wanita', count: 2 },
                'anak': { label: 'Pakaian Anak', count: 1 }
            },
            
            selectCategory(key) {
                this.selectedCategory = key;
            }
         }">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

            <!-- 1. Breadcrumb Navigation -->
            <nav class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-medium text-slate-500 mb-3 sm:mb-4 overflow-x-auto whitespace-nowrap">
                <a href="/" class="hover:text-slate-900 transition">Beranda</a>
                <span>/</span>
                <a href="/katalog" class="hover:text-slate-900 transition">Katalog</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">Produk yang Disukai (Favorit)</span>
            </nav>

            <!-- 2. Page Header & Action Bar -->
            <div class="mb-6">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-slate-200/60 text-[#0369A1] text-[9px] sm:text-[10px] font-bold uppercase tracking-wider mb-2">
                    ✦ KOLEKSI PRIBADI
                </div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-3xl font-bold text-[#0F1A24] tracking-tight">
                            Mahakarya yang Anda Sukai
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mt-1 leading-relaxed">
                            Daftar busana dan kain batik pilihan yang telah Anda beri tanda hati (favorit). Simpan untuk nanti atau pindahkan langsung ke keranjang belanja Anda.
                        </p>
                    </div>

                    <!-- Right Quick Actions -->
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
                        <!-- Indikator Jumlah Tersimpan -->
                        <div class="inline-flex items-center gap-1.5 px-3 py-2 bg-sky-50 text-[#0369A1] border border-sky-200 rounded-xl text-xs font-bold">
                            <i class="fa-solid fa-heart text-sky-500"></i>
                            <span>6 Mahakarya Tersimpan</span>
                        </div>

                        <!-- Tombol Hapus Semua -->
                        <button type="button" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                            <i class="fa-regular fa-trash-can text-xs"></i>
                            <span>Hapus Semua</span>
                        </button>

                        <!-- Tombol Pindahkan Semua ke Keranjang -->
                        <button type="button" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#0F1A24] hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-sm">
                            <i class="fa-solid fa-bag-shopping text-xs"></i>
                            <span>Pindahkan Semua ke Keranjang</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Filter Category Pills -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-6 text-[11px] sm:text-xs font-semibold font-['Plus_Jakarta_Sans']">
                <template x-for="(cat, key) in categories" :key="key">
                    <button @click="selectCategory(key)" 
                            type="button"
                            class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl border transition whitespace-nowrap shadow-sm font-['Plus_Jakarta_Sans'] cursor-pointer"
                            :class="selectedCategory === key ? 'bg-[#BAE6FD] text-[#0F1A24] font-bold border-sky-300' : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'">
                        <span x-text="cat.label + ' (' + cat.count + ')'"></span>
                    </button>
                </template>
            </div>

            <!-- 4. Grid Wishlist Cards (3 Kolom Desktop / 2 Kolom Smartphone) -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-12">

                <!-- Item 1: Kemeja Pria Parang Seling Indigo -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-52 sm:h-80 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/m1.png') }}" alt="Kemeja Pria" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        
                        <!-- Badge Ukuran Pilihan -->
                        <span class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md text-[#0F1A24] text-[9px] sm:text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-slate-200 z-10">
                            Ukuran: L
                        </span>
                        
                        <!-- Icon Heart Merah Terisi -->
                        <button class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-rose-500 shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-solid fa-heart text-xs"></i>
                        </button>
                    </div>

                    <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-1 line-clamp-1">
                                PAKAIAN PRIA • BATIK TULIS SOLO
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Kemeja Pria Parang Seling Indigo
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-2 mt-1">
                                Katun primisima, full furing katun hero adem, motif simetris rapi.
                            </p>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-[#0F1A24] text-xs sm:text-lg">Rp 1.350.000</span>
                                <span class="text-[10px] sm:text-xs text-slate-400 line-through">Rp 1.550.000</span>
                            </div>

                            <!-- Tombol Aksi Keranjang & Trash -->
                            <div class="flex items-center gap-2">
                                <button type="button" class="flex-1 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-cart-plus text-xs"></i>
                                    <span>+ Ke Keranjang</span>
                                </button>
                                <button type="button" class="w-9 h-9 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center transition shrink-0">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item 2: Tunik Batik Pesisir -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-52 sm:h-80 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/w1.png') }}" alt="Tunik Cheongsam" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        
                        <span class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md text-[#0F1A24] text-[9px] sm:text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-slate-200 z-10">
                            Ukuran: M
                        </span>
                        
                        <button class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-rose-500 shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-solid fa-heart text-xs"></i>
                        </button>
                    </div>

                    <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-1 line-clamp-1">
                                PAKAIAN WANITA • CHEONGSAM MODERN
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Tunik Batik Pesisir Cheongsam
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-2 mt-1">
                                Kerah shanghai klasik, kancing shanghai handmade, sutra halus.
                            </p>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-[#0F1A24] text-xs sm:text-lg">Rp 1.280.000</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="flex-1 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-cart-plus text-xs"></i>
                                    <span>+ Ke Keranjang</span>
                                </button>
                                <button type="button" class="w-9 h-9 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center transition shrink-0">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item 3: Dress Anak Bunga Sekar Navy -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-52 sm:h-80 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/cat-kids.jpg') }}" alt="Dress Anak" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        
                        <span class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md text-[#0F1A24] text-[9px] sm:text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-slate-200 z-10">
                            Ukuran: 4-5 Thn
                        </span>
                        
                        <button class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-rose-500 shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-solid fa-heart text-xs"></i>
                        </button>
                    </div>

                    <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-1 line-clamp-1">
                                PAKAIAN ANAK-ANAK • DRESS KATUN
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Dress Anak Bunga Sekar Navy
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-2 mt-1">
                                Katun primisima lembut, nyaman untuk anak usia 2-6 tahun.
                            </p>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-[#0F1A24] text-xs sm:text-lg">Rp 450.000</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="flex-1 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-cart-plus text-xs"></i>
                                    <span>+ Ke Keranjang</span>
                                </button>
                                <button type="button" class="w-9 h-9 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center transition shrink-0">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item 4: Sarimbit Parang Kencana -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-52 sm:h-80 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/sarimbit-1.jpg') }}" alt="Sarimbit" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        
                        <span class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md text-[#0F1A24] text-[9px] sm:text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-slate-200 z-10">
                            Pria L / Wanita M
                        </span>
                        
                        <button class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-rose-500 shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-solid fa-heart text-xs"></i>
                        </button>
                    </div>

                    <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-1 line-clamp-1">
                                SARIMBIT • BATIK TULIS SOGAN
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Sarimbit Parang Kencana &...
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-2 mt-1">
                                Stelan serasi kemeja pria & tunik wanita dengan motif pakem senada.
                            </p>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-[#0F1A24] text-xs sm:text-lg">Rp 2.450.000</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="flex-1 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-cart-plus text-xs"></i>
                                    <span>+ Ke Keranjang</span>
                                </button>
                                <button type="button" class="w-9 h-9 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center transition shrink-0">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item 5: Blouse Batik Cap Indigo -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-52 sm:h-80 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/w2.png') }}" alt="Blouse Batik Cap" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        
                        <span class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md text-[#0F1A24] text-[9px] sm:text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-slate-200 z-10">
                            Ukuran: S
                        </span>
                        
                        <button class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-rose-500 shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-solid fa-heart text-xs"></i>
                        </button>
                    </div>

                    <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-1 line-clamp-1">
                                PAKAIAN WANITA • BATIK CAP
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Blouse Batik Cap Indigo...
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-2 mt-1">
                                Motif kontemporer pewarna alami indigo teduh, lengan karet wudhu friendly.
                            </p>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-[#0F1A24] text-xs sm:text-lg">Rp 1.190.000</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="flex-1 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-cart-plus text-xs"></i>
                                    <span>+ Ke Keranjang</span>
                                </button>
                                <button type="button" class="w-9 h-9 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center transition shrink-0">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item 6: Kemeja Pria Parang Klasik -->
                <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                    <div class="relative h-52 sm:h-80 bg-slate-100 overflow-hidden">
                        <img src="{{ asset('images/m2.png') }}" alt="Kemeja Pria Sutra" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                        
                        <span class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md text-[#0F1A24] text-[9px] sm:text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-slate-200 z-10">
                            Ukuran: XL
                        </span>
                        
                        <button class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-rose-500 shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10">
                            <i class="fa-solid fa-heart text-xs"></i>
                        </button>
                    </div>

                    <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-1 line-clamp-1">
                                PAKAIAN PRIA • SUTRA TULIS
                            </span>
                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                Kemeja Pria Parang Klasik...
                            </h3>
                            <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-2 mt-1">
                                Jahitan tailor halus berfuring katun premium dari sentra Sragen.
                            </p>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-bold text-[#0F1A24] text-xs sm:text-lg">Rp 1.480.000</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="flex-1 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-cart-plus text-xs"></i>
                                    <span>+ Ke Keranjang</span>
                                </button>
                                <button type="button" class="w-9 h-9 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center transition shrink-0">
                                    <i class="fa-regular fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

@endsection