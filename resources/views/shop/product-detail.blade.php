@extends('layouts.app')

@section('content')

    <!-- Container Utama Detail Produk dengan Alpine.js (data dari GET /api/products/{id}) -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{
            // Data dari API
            productId: @json($id ?? null),
            product: null,
            variants: [],
            images: [],
            loading: true,
            errorMsg: '',

            // State UI
            activeImage: '',
            fallbackImage: '{{ asset('images/katalog1.webp') }}',
            selectedVariantId: null,
            qty: 1,
            cartBusy: false,
            notice: '',       // pesan sukses/eror aksi (keranjang/wishlist)
            noticeOk: false,

            get selectedVariant() {
                return this.variants.find(v => v.id_variant === this.selectedVariantId) ?? null;
            },

            async load() {
                this.loading = true;
                const [p, v] = await Promise.all([
                    PandansariAPI.product(this.productId),
                    PandansariAPI.productVariants(this.productId),
                ]);
                if (!p.ok) { this.loading = false; this.errorMsg = p.message; return; }
                this.product = p.data;
                this.loading = false;

                // Galeri: gambar utama dulu, lalu sisanya
                const imgs = (this.product.images ?? []);
                this.images = imgs.map(i => ({ src: i.url, fallback: this.fallbackImage, label: i.alt_text ?? 'Foto Produk' }));
                this.activeImage = this.images[0]?.src ?? this.fallbackImage;

                // Varian aktif hanya; pilih default = varian pertama
                this.variants = (v.data ?? []).filter(x => x.status_varian !== 'nonaktif');
                if (this.variants.length) this.selectedVariantId = this.variants[0].id_variant;
            },

            changeImage(img) { this.activeImage = img.src; },

            get pageTitle()  { return this.product?.nama_produk ?? 'Memuat…'; },
            get categoryName() { return this.product?.category?.nama_kategori ?? ''; },

            async requireLogin() {
                if (PandansariAPI.getToken()) return true;
                window.location.href = '/login';
                return false;
            },

            async tambahKeKeranjang(buyNow = false) {
                if (!await this.requireLogin()) return;
                if (!this.selectedVariant) { this.tampilkanNotice('Pilih ukuran/varian terlebih dahulu.', false); return; }
                this.cartBusy = true;
                const res = await PandansariAPI.addCartItem(this.selectedVariant.id_variant, this.qty);
                this.cartBusy = false;
                if (res.ok) {
                    if (buyNow) window.location.href = '/cart';
                    else this.tampilkanNotice('Produk berhasil ditambahkan ke keranjang. ' + res.message, true);
                } else {
                    this.tampilkanNotice(res.message, false); // mis. 422 stok / varian nonaktif
                }
            },

            async simpanWishlist() {
                if (!await this.requireLogin()) return;
                const res = await PandansariAPI.addWishlist(this.productId);
                if (res.ok) this.tampilkanNotice('Produk disimpan ke wishlist.', true);
                else if (res.status === 422) this.tampilkanNotice('Produk sudah ada di wishlist.', true);
                else this.tampilkanNotice(res.message, false);
            },

            tampilkanNotice(msg, ok) {
                this.notice = msg;
                this.noticeOk = ok;
                setTimeout(() => { this.notice = ''; }, 4000);
            },

            async init() { await this.load(); }
         }">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

            <!-- 1. Breadcrumb Navigation & Share Button -->
            <div class="flex items-center justify-between gap-4 mb-4 sm:mb-6">
                <nav class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-medium text-slate-500 overflow-x-auto whitespace-nowrap">
                    <a href="/" class="hover:text-slate-900 transition">Beranda</a>
                    <span>/</span>
                    <a href="/katalog" class="hover:text-slate-900 transition">Katalog</a>
                    <template x-if="categoryName">
                        <a href="/katalog" class="hover:text-slate-900 transition" x-text="categoryName"></a>
                    </template>
                    <span>/</span>
                    <span class="text-slate-900 font-bold" x-text="pageTitle">Memuat produk…</span>
                </nav>

               
            </div>

            <!-- 2. Product Detail Section (Gallery + Info) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

                <!-- Left: Galeri Foto Produk (5 Kolom) -->
                <div class="lg:col-span-5 flex flex-col gap-3">
                    
                    <!-- 1. GAMBAR UTAMA (MEMANJANG KE BAWAH / PORTRAIT) -->
                    <div class="w-full h-[480px] sm:h-[580px] bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm relative shrink-0">
                        <img :src="activeImage" 
                             alt="Kemeja Pria Parang Seling Indigo" 
                             class="w-full h-full object-cover block transition-all duration-300"
                             onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800&auto=format&fit=crop'">
                    </div>

                    <!-- 2. THUMBNAIL 4 BIJI BERBARIS KE SAMPING (BISA DI-KLIK) -->
                    <div class="flex flex-row gap-2.5 sm:gap-3 w-full">
                        <template x-for="(img, idx) in images" :key="idx">
                            <button @click="changeImage(img)" 
                                    type="button" 
                                    class="flex-1 h-20 sm:h-24 bg-white rounded-xl overflow-hidden border-2 transition shadow-sm cursor-pointer relative shrink-0 group"
                                    :class="activeImage === img.src ? 'border-sky-500 ring-2 ring-sky-200' : 'border-slate-200 hover:border-slate-400'">
                                <img :src="img.src" 
                                     :alt="img.label" 
                                     class="w-full h-full object-cover block group-hover:scale-105 transition-transform duration-300"
                                     :onerror="`this.src='${img.fallback}'; if(activeImage === img.src) activeImage='${img.fallback}';`">
                                <span class="absolute bottom-0 inset-x-0 bg-slate-900/80 text-white text-[8px] sm:text-[9px] text-center py-0.5 font-medium truncate block" x-text="img.label"></span>
                            </button>
                        </template>
                    </div>

                </div>

                <!-- Right: Informasi & Form Pembelian (7 Kolom) -->
                <div class="lg:col-span-7 space-y-5">
                    
                    <!-- Title & Official Badge -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24] tracking-tight leading-snug" x-text="pageTitle">
                            Memuat produk…
                        </h1>
                    </div>

                    <!-- Banner Error / Notifikasi Aksi -->
                    <div x-show="errorMsg" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span x-text="errorMsg"></span>
                    </div>
                    <div x-show="notice" class="rounded-xl text-xs font-semibold px-4 py-3 flex items-center gap-2"
                         :class="noticeOk ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'">
                        <i :class="noticeOk ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                        <span x-text="notice"></span>
                    </div>

                    <!-- Price Box (harga varian terpilih dari API) -->
                    <div class="flex items-baseline gap-3">
                        <span class="text-2xl sm:text-3xl font-bold text-[#0F1A24]"
                              x-text="selectedVariant ? PandansariAPI.formatRp(selectedVariant.harga * qty) : '—'"></span>
                        <span class="text-xs sm:text-sm text-slate-400"
                              x-show="selectedVariant && qty > 1"
                              x-text="'@ ' + PandansariAPI.formatRp(selectedVariant?.harga)"></span>
                    </div>

                    <!-- Shipping Info -->
                    <div class="flex items-center gap-2 text-xs text-slate-600 bg-white p-3 rounded-xl border border-slate-200/80 shadow-sm">
                        <i class="fa-solid fa-truck-fast text-sky-600 text-sm"></i>
                        <div>
                            <span class="font-bold text-[#0F1A24]">Dikirim dari Sragen</span>
                        </div>
                    </div>

                    <!-- Varian Selector (dari GET /api/products/{id}/variants) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-[#0F1A24]">Pilih Ukuran / Varian:</span>
                            <span class="text-slate-400 font-medium" x-show="selectedVariant" x-text="'Warna: ' + (selectedVariant?.warna ?? '-')"></span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <template x-for="v in variants" :key="v.id_variant">
                                <button @click="selectedVariantId = v.id_variant; qty = 1"
                                        type="button"
                                        class="px-3 h-10 sm:w-auto sm:h-11 rounded-xl text-xs sm:text-sm font-bold border transition shadow-sm cursor-pointer text-center"
                                        :class="selectedVariantId === v.id_variant ? 'bg-[#0F1A24] text-white border-[#0F1A24]' : 'bg-white text-slate-800 border-slate-200 hover:border-slate-400'">
                                    <span x-text="v.ukuran ? v.ukuran : (v.nama_varian ?? 'Varian')"></span>
                                    <span class="block text-[9px] font-medium" :class="selectedVariantId === v.id_variant ? 'text-slate-300' : 'text-slate-400'" x-text="PandansariAPI.formatRp(v.harga)"></span>
                                </button>
                            </template>
                            <p class="text-xs text-slate-400 italic w-full" x-show="!loading && !variants.length">Varian tidak tersedia.</p>
                        </div>
                    </div>

                    <!-- Size Chart Table Box -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200/80 shadow-sm space-y-2" x-show="selectedVariant">
                        <div class="flex items-center justify-between text-xs font-bold text-[#0F1A24]">
                            <span>INFO VARIAN TERPILIH</span>
                            <span class="text-[10px] text-slate-400 font-normal">SKU: <span x-text="selectedVariant?.sku ?? '-'"></span></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-[11px]">
                            <div class="bg-slate-50 rounded-lg py-2">
                                <p class="text-slate-400 font-medium uppercase">Stok</p>
                                <p class="font-bold text-[#0F1A24] text-xs" x-text="selectedVariant?.stok ?? 0"></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg py-2">
                                <p class="text-slate-400 font-medium uppercase">Warna</p>
                                <p class="font-bold text-[#0F1A24] text-xs truncate" x-text="selectedVariant?.warna ?? '-'"></p>
                            </div>
                            <div class="bg-slate-50 rounded-lg py-2">
                                <p class="text-slate-400 font-medium uppercase">Ukuran</p>
                                <p class="font-bold text-[#0F1A24] text-xs" x-text="selectedVariant?.ukuran ?? '-'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-bold text-[#0F1A24]">Kuantitas:</span>
                        <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm">
                            <button @click="if(qty > 1) qty--" type="button" class="w-9 h-9 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold transition cursor-pointer">-</button>
                            <span class="w-10 text-center text-xs font-bold text-[#0F1A24]" x-text="qty"></span>
                            <button @click="if (selectedVariant && qty < selectedVariant.stok) qty++" type="button" class="w-9 h-9 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold transition cursor-pointer">+</button>
                        </div>
                        <span class="text-xs text-slate-400" x-show="selectedVariant" x-text="'Tersedia ' + (selectedVariant?.stok ?? 0) + ' buah'"></span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="space-y-2 pt-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button type="button" :disabled="cartBusy" @click="tambahKeKeranjang(false)"
                                    class="bg-white hover:bg-slate-50 text-[#0F1A24] border border-slate-300 text-xs font-bold py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-60">
                                <i class="fa-solid" :class="cartBusy ? 'fa-circle-notch fa-spin' : 'fa-cart-plus'" style="font-size:12px"></i>
                                <span x-text="cartBusy ? 'MEMPROSES…' : '+ KERANJANG'"></span>
                            </button>
                            <button type="button" :disabled="cartBusy" @click="tambahKeKeranjang(true)"
                                    class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-60">
                                <span>Beli Sekarang →</span>
                            </button>
                        </div>

                        <!-- Tombol Wishlist -->
                        <button type="button" @click="simpanWishlist()"
                                class="w-full bg-slate-50 hover:bg-rose-50 text-slate-600 hover:text-rose-500 border border-slate-200 text-xs font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2 cursor-pointer">
                            <i class="fa-regular fa-heart text-xs"></i>
                            <span>Simpan ke Wishlist</span>
                        </button>

                        <!-- WhatsApp Consultation Button -->
                        <a href="https://wa.me/6281234567890?text=Halo%20Batik%20Pandansari,%20saya%20ingin%20konsultasi%20ukuran%20Kemeja%20Pria%20Parang%20Seling%20Indigo" target="_blank" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                            <span>Konsultasi Ukuran & Fitting via WhatsApp</span>
                        </a>
                    </div>

                    <!-- Guarantees Footer -->
                    <div class="flex items-center justify-around text-[11px] text-slate-500 pt-3 border-t border-slate-200/80">
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-sky-600"></i> 100% Original</span>
                    </div>

                </div>

            </div>

            <!-- 3. Deskripsi Produk Detail Box -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm mb-12 space-y-4">
                <h2 class="text-base sm:text-lg font-bold text-[#0F1A24] tracking-tight uppercase border-b border-slate-100 pb-3">
                    DESKRIPSI PRODUK
                </h2>

                <div class="text-xs sm:text-sm text-slate-600 leading-relaxed space-y-3 font-['Plus_Jakarta_Sans']">
                    <p x-text="product?.deskripsi ?? 'Memuat deskripsi produk…'">
                        <strong class="text-[#0F1A24]"></strong>
                    </p>
                </div>
            </div>

            <!-- 4. Koleksi Rekomendasi Pilihan Serupa -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <span class="text-[9px] sm:text-[10px] font-bold text-[#0369A1] uppercase tracking-wider block">KURASI PILIHAN MAHARDIKA</span>
                        <h2 class="text-lg sm:text-2xl font-bold text-[#0F1A24]">Koleksi Rekomendasi Pilihan Serupa</h2>
                    </div>
                    <a href="/katalog" class="text-xs font-bold text-slate-600 hover:text-black transition hidden sm:inline">Lihat Seluruh Koleksi Pria & Sarimbit →</a>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <!-- Item 1 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/cat-kids.jpg') }}" alt="Tunik Pesisiran" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1598033129183-c4f50c736f10?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN WANITA • SARIMBIT</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Tunik Pesisiran Navy Sekar</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 1.280.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>

                    <!-- Item 2 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/m2.png') }}" alt="Kemeja Sutra" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN PRIA • SUTRA KATUN</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Kemeja Sutra Megamendung...</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 2.450.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>

                    <!-- Item 3 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/tulis.jpg') }}" alt="Kemeja Cap" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN PRIA • BATIK CAP HANDMADE</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Kemeja Cap Sawunggaling...</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 890.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>

                    <!-- Item 4 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/m1.png') }}" alt="Kemeja Tulis" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN PRIA • PRIMISSIMA</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Kemeja Tulis Gurdo Bimo...</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 1.420.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection