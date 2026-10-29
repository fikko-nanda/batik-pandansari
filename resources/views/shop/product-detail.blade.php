@extends('layouts.app')

@section('content')

    <!-- Container Utama Detail Produk dengan Alpine.js (data dari GET /api/products/{id}) -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{
            // Data dari API
            productId: '{{ $id ?? '' }}',
            product: null,
            variants: [],
            images: [],
            sizeChart: null,
            loading: true,
            errorMsg: '',

            // State UI
            activeImage: '',
            fallbackImage: '{{ asset('images/katalog1.webp') }}',
            selectedWarna: '',
            selectedUkuran: '',
            qty: 1,
            cartBusy: false,
            notice: '',       // pesan sukses/eror aksi (keranjang/wishlist)
            noticeOk: false,
            rekomendasi: [],  // produk serupa dari kategori sama (GET /products?category=...)

            // Varian terpilih = kombinasi warna + ukuran
            get selectedVariant() {
                return this.variantFor(this.selectedWarna, this.selectedUkuran);
            },

            get activeImageItem() {
                return this.images.find(i => i.src === this.activeImage) ?? null;
            },

            get activeImageIsChart() {
                return !!this.activeImageItem?.isChart;
            },

            // Daftar warna unik dari varian aktif
            get listWarna() {
                return [...new Set(this.variants.map(v => v.warna).filter(Boolean))];
            },

            // Ukuran unik untuk warna terpilih, diurutkan logis
            get listUkuran() {
                const URUT = ['ALL SIZE', 'FREE SIZE', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', 'XXXL', '3XL', '4XL', '5XL'];
                const src = this.selectedWarna
                    ? this.variants.filter(v => (v.warna ?? '') === this.selectedWarna)
                    : this.variants;
                const uniq = [...new Set(src.map(v => v.ukuran).filter(Boolean))];
                return uniq.sort((a, b) => {
                    const ia = URUT.indexOf(String(a).toUpperCase());
                    const ib = URUT.indexOf(String(b).toUpperCase());
                    if (ia === -1 && ib === -1) return String(a).localeCompare(String(b));
                    if (ia === -1) return 1;
                    if (ib === -1) return -1;
                    return ia - ib;
                });
            },

            // Cari varian dari kombinasi warna + ukuran
            variantFor(warna, ukuran) {
                return this.variants.find(v => (v.warna ?? '') === (warna ?? '') && (v.ukuran ?? '') === (ukuran ?? '')) ?? null;
            },

            pilihWarna(w) {
                this.selectedWarna = w;
                this.selectedUkuran = this.listUkuran[0] ?? '';
                this.qty = 1;
            },

            pilihUkuran(u) {
                this.selectedUkuran = u;
                this.qty = 1;
            },

            isSizeChartImage(img) {
                return String(img?.type ?? '').toLowerCase() === 'size_chart';
            },

            async load() {
                this.loading = true;
                const [p, v, imgRes] = await Promise.all([
                    PandansariAPI.product(this.productId),
                    PandansariAPI.productVariants(this.productId),
                    PandansariAPI.productImages(this.productId),
                ]);
                if (!p.ok) { this.loading = false; this.errorMsg = p.message; return; }
                this.product = p.data;
                this.loading = false;

                // Gabungkan gambar dari detail produk + endpoint gambar publik, lalu deduplikasi per path.
                const rawImgs = [
                    ...(Array.isArray(this.product.images) ? this.product.images : []),
                    ...(imgRes.ok && Array.isArray(imgRes.data) ? imgRes.data : []),
                ].filter(i => i && (i.path_gambar || i.url));
                const seen = new Set();
                const imgs = rawImgs.filter(img => {
                    const key = String(img.path_gambar ?? img.url ?? img.id_image ?? '');
                    if (!key || seen.has(key)) return false;
                    seen.add(key);
                    return true;
                });

                // Size chart bisa datang dari relasi size_chart atau item gambar bertipe size_chart.
                const rawChart = Array.isArray(this.product.size_chart) ? (this.product.size_chart[0] ?? null) : this.product.size_chart;
                const chartFromProduct = rawChart && (typeof rawChart === 'string' || rawChart.path_gambar || rawChart.url) ? rawChart : null;
                const chartFromImages = rawImgs.find(i => this.isSizeChartImage(i)) ?? null;
                const chart = chartFromProduct ?? chartFromImages;

                const chartId = chart && typeof chart === 'object' ? chart.id_image : null;
                const chartPath = typeof chart === 'string' ? chart : (chart?.path_gambar ?? chart?.url ?? null);
                const isChart = (img) => this.isSizeChartImage(img)
                    || (chartId && String(img.id_image) === String(chartId))
                    || (chartPath && (img.path_gambar === chartPath || img.url === chartPath));

                // Galeri: foto produk dulu, size chart selalu menjadi slide terakhir.
                this.images = imgs
                    .filter(img => !isChart(img))
                    .map(img => ({ src: PandansariAPI.imgUrl(img.path_gambar ?? img.url), fallback: this.fallbackImage, label: img.alt_text ?? 'Foto Produk' }));

                const chartSrc = typeof chart === 'string'
                    ? PandansariAPI.imgUrl(chart)
                    : PandansariAPI.imgUrl(chart?.path_gambar ?? chart?.url ?? '');
                this.sizeChart = chartSrc
                    ? { src: chartSrc, fallback: this.fallbackImage, label: chart?.alt_text ?? 'Panduan Ukuran' }
                    : null;
                if (this.sizeChart) {
                    this.images.push({ ...this.sizeChart, isChart: true });
                }
                this.activeImage = this.images[0]?.src ?? this.fallbackImage;

                // Varian aktif hanya; default = warna pertama + ukuran pertama yang tersedia (prioritaskan yang memiliki stok)
                this.variants = (v.data ?? []).filter(x => x.status_varian !== 'nonaktif');
                const inStockVariant = this.variants.find(x => x.stok == null || Number(x.stok) > 0) ?? this.variants[0];
                if (inStockVariant) {
                    this.selectedWarna = inStockVariant.warna || (this.listWarna[0] ?? '');
                    this.selectedUkuran = inStockVariant.ukuran || (this.listUkuran[0] ?? '');
                } else {
                    this.selectedWarna = this.listWarna[0] ?? '';
                    this.selectedUkuran = this.listUkuran[0] ?? '';
                }
            },

            changeImage(img) { this.activeImage = img.src; },

            // Produk serupa: kategori sama, kecuali produk ini sendiri (max 4)
            async loadRekomendasi() {
                const idKategori = this.product?.category?.id_category;
                const res = await PandansariAPI.products({ category: idKategori, per_page: 20 });
                const list = (res.ok ? (res.data ?? []) : []).filter(p => String(p.id_product) !== String(this.productId));
                this.rekomendasi = list.slice(0, 4);
            },
            coverRek(p) { return PandansariAPI.coverUrl(p.images) || this.fallbackImage; },
            hargaRek(p) {
                const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif' && x.harga != null);
                return v.length ? PandansariAPI.formatRp(Math.min(...v.map(x => Number(x.harga)))) : '-';
            },
            kategoriRek(p) { return p.category?.nama_kategori ?? this.categoryName; },

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
                const currentStock = this.selectedVariant.stok != null ? Number(this.selectedVariant.stok) : 99;
                if (currentStock <= 0) {
                    this.tampilkanNotice('Stok produk telah habis.', false);
                    return;
                }
                const qtyNum = Number(this.qty);
                if (qtyNum < 1) { this.tampilkanNotice('Jumlah minimal 1.', false); return; }
                if (qtyNum > currentStock) {
                    this.tampilkanNotice('Stok tidak mencukupi. Stok tersedia: ' + currentStock + '.', false);
                    return;
                }
                this.cartBusy = true;
                const res = await PandansariAPI.addCartItem(this.selectedVariant.id_variant, qtyNum);
                this.cartBusy = false;
                if (res.ok) {
                    if (typeof PandansariAPI?.jadwalLencana === 'function') PandansariAPI.jadwalLencana(100);
                    if (typeof PandansariAPI?.muatLencana === 'function') {
                        PandansariAPI.muatLencana().catch(() => {});
                    }
                    if (buyNow) window.location.href = '/cart';
                    else this.tampilkanNotice('Produk berhasil ditambahkan ke keranjang.', true);
                } else {
                    this.tampilkanNotice(res.message || 'Gagal menambahkan ke keranjang.', false); // mis. 422 stok / varian nonaktif
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

            async init() {
                await this.load();
                this.loadRekomendasi();
            }
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
                             :alt="activeImageItem?.label ?? 'Foto Produk'" 
                             class="w-full h-full block transition-all duration-300"
                             :class="activeImageIsChart ? 'object-contain p-4 bg-white' : 'object-cover'"
                             onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800&auto=format&fit=crop'">
                    </div>

                    <!-- 2. THUMBNAIL (ASPECT-SQUARE, TANPA OVERLAY TEKS — KLIK UNTUK GANTI GAMBAR UTAMA) -->
                    <div class="flex flex-row flex-wrap gap-2.5 sm:gap-3 w-full">
                        <template x-for="(img, idx) in images" :key="idx">
                            <button @click="changeImage(img)" 
                                    type="button" 
                                    class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-xl overflow-hidden border-2 transition shadow-sm cursor-pointer shrink-0 group"
                                    :class="activeImage === img.src ? 'border-sky-500 ring-2 ring-sky-200' : 'border-slate-200 hover:border-slate-400'"
                                    :aria-label="img.label">
                                <img :src="img.src" 
                                     :alt="img.label" 
                                     class="w-full h-full block group-hover:scale-105 transition-transform duration-300"
                                     :class="img.isChart ? 'object-contain p-1.5 bg-white' : 'object-cover'"
                                     :onerror="`this.src='${img.fallback || fallbackImage}'; if(activeImage === img.src) { const fb = img.fallback || fallbackImage; activeImage = fb; img.src = fb; }`">
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

                    <!-- Varian Selector — 2 tahap: Warna → Ukuran (dari GET /api/products/{id}/variants) -->
                    <div class="space-y-4">

                        <!-- Tahap 1: Pilih Warna -->
                        <div class="space-y-2" x-show="listWarna.length">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-[#0F1A24]">Pilih Warna:</span>
                                <span class="text-slate-400 font-medium" x-text="selectedWarna || '—'"></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="w in listWarna" :key="'w-' + w">
                                    <button @click="pilihWarna(w)"
                                            type="button"
                                            class="px-4 h-10 sm:h-11 rounded-xl text-xs sm:text-sm font-bold border transition shadow-sm cursor-pointer text-center"
                                            :class="selectedWarna === w ? 'bg-[#0F1A24] text-white border-[#0F1A24]' : 'bg-white text-slate-800 border-slate-200 hover:border-slate-400'">
                                        <span x-text="w"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Tahap 2: Pilih Ukuran (terfilter sesuai warna terpilih) -->
                        <div class="space-y-2" x-show="listUkuran.length">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-[#0F1A24]">Pilih Ukuran:</span>
                                <span class="text-slate-400 font-medium" x-show="selectedVariant" x-text="'Tersedia ' + (selectedVariant?.stok ?? 0) + ' buah'"></span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="u in listUkuran" :key="'u-' + u">
                                    <button @click="pilihUkuran(u)"
                                            type="button"
                                            :disabled="(variantFor(selectedWarna, u)?.stok ?? 0) <= 0"
                                            class="px-3 h-10 sm:w-auto sm:h-11 rounded-xl text-xs sm:text-sm font-bold border transition shadow-sm cursor-pointer text-center disabled:opacity-40 disabled:cursor-not-allowed"
                                            :class="selectedUkuran === u ? 'bg-[#0F1A24] text-white border-[#0F1A24]' : 'bg-white text-slate-800 border-slate-200 hover:border-slate-400'">
                                        <span x-text="u"></span>
                                        <span class="block text-[9px] font-medium" :class="selectedUkuran === u ? 'text-slate-300' : 'text-slate-400'"
                                              x-text="(variantFor(selectedWarna, u)?.stok ?? 0) > 0 ? PandansariAPI.formatRp(variantFor(selectedWarna, u).harga) : 'HABIS'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <p class="text-xs text-slate-400 italic" x-show="!loading && !variants.length">Varian tidak tersedia.</p>
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

                    <!-- Quantity Selector (kondisi boundary: qty > stock) -->
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-bold text-[#0F1A24]">Kuantitas:</span>
                        <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm">
                            <button @click="if(qty > 1) qty--" type="button" :disabled="qty <= 1" class="w-9 h-9 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">-</button>
                            <span class="w-10 text-center text-xs font-bold text-[#0F1A24]" x-text="qty"></span>
                            <button @click="if (selectedVariant && (selectedVariant.stok == null || Number(qty) < Number(selectedVariant.stok))) qty++" type="button"
                                    :disabled="!selectedVariant || (selectedVariant.stok != null && Number(qty) >= Number(selectedVariant.stok))"
                                    class="w-9 h-9 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold transition cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">+</button>
                        </div>
                        <span class="text-xs text-slate-400" x-show="selectedVariant" x-text="'Tersedia ' + (selectedVariant?.stok ?? 0) + ' buah'"></span>
                    </div>

                    <!-- CTA Buttons (bisa diorder saat stok === 1 & qty === 1) -->
                    <div class="space-y-2 pt-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button type="button" :disabled="cartBusy || !selectedVariant || (selectedVariant.stok != null && Number(selectedVariant.stok) <= 0)" @click="tambahKeKeranjang(false)"
                                    class="bg-white hover:bg-slate-50 text-[#0F1A24] border border-slate-300 text-xs font-bold py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                <i class="fa-solid" :class="cartBusy ? 'fa-circle-notch fa-spin' : 'fa-cart-plus'" style="font-size:12px"></i>
                                <span x-text="cartBusy ? 'MEMPROSES…' : '+ KERANJANG'"></span>
                            </button>
                            <button type="button" :disabled="cartBusy || !selectedVariant || (selectedVariant.stok != null && Number(selectedVariant.stok) <= 0)" @click="tambahKeKeranjang(true)"
                                    class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
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
                        <a href="https://wa.me/6283820380977?text=Halo%20Batik%20Pandansari,%20saya%20ingin%20konsultasi%20ukuran%20Kemeja%20Pria%20Parang%20Seling%20Indigo" target="_blank" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2">
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

                <div x-show="sizeChart" class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs sm:text-sm font-bold text-[#0F1A24] uppercase tracking-wider mb-3">Panduan Ukuran</h3>
                    <a :href="sizeChart?.src ?? '#'" target="_blank" rel="noopener noreferrer"
                       class="block max-w-2xl mx-auto bg-slate-50 border border-slate-200 rounded-xl overflow-hidden"
                       :title="'Buka ' + (sizeChart?.label ?? 'Panduan Ukuran')">
                        <img :src="sizeChart?.src ?? ''" :alt="sizeChart?.label ?? 'Panduan Ukuran'"
                             class="w-full max-h-[640px] object-contain">
                    </a>
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
                    <template x-for="p in rekomendasi" :key="p.id_product">
                        <a :href="'/detail/' + p.id_product"
                           class="group bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 pb-4 flex flex-col justify-between cursor-pointer">
                            <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                                <img :src="coverRek(p)" :alt="p.nama_produk ?? 'Produk'"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            <div class="min-h-[52px]">
                                <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5" x-text="kategoriRek(p)"></span>
                                <h3 class="font-bold text-[#0F1A24] text-xs leading-snug line-clamp-2" x-text="p.nama_produk ?? 'Produk'"></h3>
                            </div>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1" x-text="hargaRek(p)"></span>
                            <span class="mt-3 block text-center bg-slate-100 group-hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</span>
                        </a>
                    </template>
                    <p class="col-span-2 lg:col-span-4 text-center text-xs text-slate-400 italic py-6"
                       x-show="!loading && !rekomendasi.length">Belum ada rekomendasi untuk produk ini.</p>
                </div>
            </div>

        </div>
    </div>

@endsection