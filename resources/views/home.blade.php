@extends('layouts.app')

@section('content')
    {{-- 1. Hero Slider --}}
    <x-hero />

    {{-- 2. Our E-Commerce Partners --}}
    <x-partners />

    {{-- 3. Koleksi Busana & Wastra Pilihan (4 Grid) --}}
    <x-categories />

    {{-- 4. Women's Wear Showcase --}}
    <section class="bg-white py-14 border-b border-slate-100 font-['Plus_Jakarta_Sans']">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
            <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-7 bg-[#0369A1] rounded-full"></div>
                    <div>
                        <span class="text-[10px] font-bold text-[#0369A1] tracking-wider uppercase block">KOLEKSI BUSANA WANITA</span>
                        <h2 class="text-2xl font-bold text-[#0F1A24] tracking-wide">WOMEN'S WEAR</h2>
                    </div>
                </div>
                <a href="/katalog?category=pakaian-wanita" class="text-xs font-bold text-slate-800 uppercase px-5 py-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                    VIEW ALL
                </a>
            </div>

            @if(isset($womensProducts) && $womensProducts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
                    @foreach($womensProducts as $product)
                        @php
                            $coverImg = $product->image_url ?: asset('images/placeholder.jpg');
                            $minPrice = $product->variants->where('status_varian', 'aktif')->min('harga');
                        @endphp
                        <div x-data="{
                                product: {{ Js::from($product) }},
                                p: {{ Js::from($product) }},
                                coverImg: '{{ $coverImg }}'
                             }"
                             class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between w-full max-w-full">
                            <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                                <a href="/detail/{{ $product->id_product }}" class="block w-full h-full">
                                    <img src="{{ $product->image_url ?? asset('images/placeholder.jpg') }}"
                                         alt="{{ $product->nama_produk }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                                         onerror="this.onerror=null; this.src='/images/placeholder.jpg';">
                                </a>

                                <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10 max-w-[70%] truncate pointer-events-none">
                                    {{ strtoupper($product->category?->nama_kategori ?? 'KOLEKSI') }}
                                </span>

                                <!-- Tombol Toggle Wishlist / Like -->
                                <button type="button"
                                        @click.stop.prevent="typeof toggleWishlist === 'function' ? toggleWishlist(product) : window.toggleWishlist(product)"
                                        class="absolute top-2 right-2 sm:top-3 sm:right-3 w-7 h-7 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10 cursor-pointer disabled:opacity-50"
                                        :class="product.is_liked ? 'text-red-500' : 'text-gray-400'"
                                        :disabled="product.isLoading || product.isLoadingWishlist"
                                        :title="product.is_liked ? 'Batalkan Suka' : 'Sukai Produk'">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="w-4 h-4 transition-colors duration-150"
                                         :class="product.is_liked ? 'text-red-500 fill-red-500' : 'text-gray-400 fill-none stroke-current'"
                                         viewBox="0 0 24 24"
                                         stroke-width="1.8"
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                    </svg>
                                </button>

                                <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4 pointer-events-none">
                                    <a href="/detail/{{ $product->id_product }}" class="pointer-events-auto bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                        Detail Produk
                                    </a>
                                </div>
                            </div>

                            <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                        {{ strtoupper($product->category?->nama_kategori ?? 'BATIK PANDANSARI') }} • BATIK PANDANSARI
                                    </span>
                                    <a href="/detail/{{ $product->id_product }}" class="block">
                                        <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                            {{ $product->nama_produk }}
                                        </h3>
                                    </a>
                                </div>

                                <div class="mt-2 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100">
                                    @if($minPrice)
                                        <span class="text-[8px] sm:text-[10px] text-slate-400 font-medium block leading-tight mb-0.5">Mulai dari</span>
                                        <span class="font-bold text-[#0F1A24] text-xs sm:text-base leading-tight block">
                                            Rp {{ number_format($minPrice, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[10px] sm:text-xs text-slate-400 italic block">Harga belum tersedia</span>
                                    @endif
                                </div>

                                <div class="mt-2 sm:mt-3 space-y-1.5">
                                    <a href="/detail/{{ $product->id_product }}"
                                       class="w-full bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200 text-[10px] sm:text-xs font-bold py-1.5 sm:py-2 rounded-xl flex items-center justify-center gap-1.5 transition">
                                        <span>Lihat Detail</span>
                                        <i class="fa-solid fa-arrow-right text-[9px]"></i>
                                    </a>
                                    <button @click="PandansariAPI.keKeranjangProduk(product.id_product).then(r => { if(r.login) window.location.href='/login'; else PandansariAPI.toast(r.ok ? 'Ditambahkan ke keranjang!' : (r.message ?? 'Gagal.'), r.ok); })"
                                            type="button"
                                            class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white text-[10px] sm:text-xs font-bold py-2 sm:py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition shadow-sm cursor-pointer">
                                        <i class="fa-solid fa-cart-plus text-xs"></i> + Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-slate-50 rounded-2xl py-10 text-center">
                    <p class="text-sm font-bold text-[#0F1A24]">Belum ada produk wanita tersedia</p>
                    <a href="/katalog?category=pakaian-wanita" class="inline-block mt-3 bg-[#0F1A24] text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-slate-800 transition">LIHAT KATALOG →</a>
                </div>
            @endif
        </div>
    </section>

    {{-- 5. Men's Wear Showcase --}}
    <section class="bg-white py-14 border-b border-slate-100 font-['Plus_Jakarta_Sans']">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
            <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-7 bg-[#0369A1] rounded-full"></div>
                    <div>
                        <span class="text-[10px] font-bold text-[#0369A1] tracking-wider uppercase block">KOLEKSI BUSANA PRIA</span>
                        <h2 class="text-2xl font-bold text-[#0F1A24] tracking-wide">MEN'S WEAR</h2>
                    </div>
                </div>
                <a href="/katalog?category=pakaian-pria" class="text-xs font-bold text-slate-800 uppercase px-5 py-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                    VIEW ALL
                </a>
            </div>

            @if(isset($mensProducts) && $mensProducts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
                    @foreach($mensProducts as $product)
                        @php
                            $coverImg = $product->image_url ?: asset('images/placeholder.jpg');
                            $minPrice = $product->variants->where('status_varian', 'aktif')->min('harga');
                        @endphp
                        <div x-data="{
                                product: {{ Js::from($product) }},
                                p: {{ Js::from($product) }},
                                coverImg: '{{ $coverImg }}'
                             }"
                             class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between w-full max-w-full">
                            <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
                                <a href="/detail/{{ $product->id_product }}" class="block w-full h-full">
                                    <img src="{{ $product->image_url ?? asset('images/placeholder.jpg') }}"
                                         alt="{{ $product->nama_produk }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
                                         onerror="this.onerror=null; this.src='/images/placeholder.jpg';">
                                </a>

                                <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10 max-w-[70%] truncate pointer-events-none">
                                    {{ strtoupper($product->category?->nama_kategori ?? 'KOLEKSI') }}
                                </span>

                                <!-- Tombol Toggle Wishlist / Like -->
                                <button type="button"
                                        @click.stop.prevent="typeof toggleWishlist === 'function' ? toggleWishlist(product) : window.toggleWishlist(product)"
                                        class="absolute top-2 right-2 sm:top-3 sm:right-3 w-7 h-7 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10 cursor-pointer disabled:opacity-50"
                                        :class="product.is_liked ? 'text-red-500' : 'text-gray-400'"
                                        :disabled="product.isLoading || product.isLoadingWishlist"
                                        :title="product.is_liked ? 'Batalkan Suka' : 'Sukai Produk'">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="w-4 h-4 transition-colors duration-150"
                                         :class="product.is_liked ? 'text-red-500 fill-red-500' : 'text-gray-400 fill-none stroke-current'"
                                         viewBox="0 0 24 24"
                                         stroke-width="1.8"
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                    </svg>
                                </button>

                                <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4 pointer-events-none">
                                    <a href="/detail/{{ $product->id_product }}" class="pointer-events-auto bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                                        Detail Produk
                                    </a>
                                </div>
                            </div>

                            <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                                        {{ strtoupper($product->category?->nama_kategori ?? 'BATIK PANDANSARI') }} • BATIK PANDANSARI
                                    </span>
                                    <a href="/detail/{{ $product->id_product }}" class="block">
                                        <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2">
                                            {{ $product->nama_produk }}
                                        </h3>
                                    </a>
                                </div>

                                <div class="mt-2 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100">
                                    @if($minPrice)
                                        <span class="text-[8px] sm:text-[10px] text-slate-400 font-medium block leading-tight mb-0.5">Mulai dari</span>
                                        <span class="font-bold text-[#0F1A24] text-xs sm:text-base leading-tight block">
                                            Rp {{ number_format($minPrice, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[10px] sm:text-xs text-slate-400 italic block">Harga belum tersedia</span>
                                    @endif
                                </div>

                                <div class="mt-2 sm:mt-3 space-y-1.5">
                                    <a href="/detail/{{ $product->id_product }}"
                                       class="w-full bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200 text-[10px] sm:text-xs font-bold py-1.5 sm:py-2 rounded-xl flex items-center justify-center gap-1.5 transition">
                                        <span>Lihat Detail</span>
                                        <i class="fa-solid fa-arrow-right text-[9px]"></i>
                                    </a>
                                    <button @click="PandansariAPI.keKeranjangProduk(product.id_product).then(r => { if(r.login) window.location.href='/login'; else PandansariAPI.toast(r.ok ? 'Ditambahkan ke keranjang!' : (r.message ?? 'Gagal.'), r.ok); })"
                                            type="button"
                                            class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white text-[10px] sm:text-xs font-bold py-2 sm:py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition shadow-sm cursor-pointer">
                                        <i class="fa-solid fa-cart-plus text-xs"></i> + Keranjang
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-slate-50 rounded-2xl py-10 text-center">
                    <p class="text-sm font-bold text-[#0F1A24]">Belum ada produk pria tersedia</p>
                    <a href="/katalog?category=pakaian-pria" class="inline-block mt-3 bg-[#0F1A24] text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-slate-800 transition">LIHAT KATALOG →</a>
                </div>
            @endif
        </div>
    </section>

    {{-- 6. Promo Spesial Sarimbit --}}
    <x-promo-banner />

    {{-- 7. 3 Kategori Utama Teknik (Tulis, Cap, Handprint) --}}
    <x-craft-categories />

    {{-- 8. Galeri Butik & Atelier --}}
    <x-gallery />

    {{-- 9. Banner Konsultasi --}}
    <x-consultation-banner />
@endsection

