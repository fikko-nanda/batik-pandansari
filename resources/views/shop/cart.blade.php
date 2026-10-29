@extends('layouts.app')

@section('content')

    <!-- Container Utama Keranjang Belanja Interaktif dengan Alpine.js (data dari GET /api/cart) -->
    <div class="bg-[#FAF8F5] min-h-screen pt-6 sm:pt-8 pb-12 sm:pb-16 font-['Plus_Jakarta_Sans']"
         x-data="cartComponent()">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

            <!-- 1. Header Navigation & Checkout Stepper -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-medium text-slate-500 overflow-x-auto whitespace-nowrap">
                    <a href="/" class="hover:text-slate-900 transition">Beranda</a>
                    <span>/</span>
                    <a href="/katalog" class="hover:text-slate-900 transition">Katalog</a>
                    <span>/</span>
                    <span class="text-slate-900 font-bold">Keranjang Belanja</span>
                </nav>

                <!-- Checkout Stepper Bar -->
                <div class="flex items-center gap-1.5 sm:gap-3 bg-white px-3 sm:px-4 py-1.5 rounded-full border border-slate-200/80 text-[11px] sm:text-xs shadow-sm self-start md:self-auto">
                    <div class="flex items-center gap-1.5 text-[#0369A1] font-bold">
                        <span class="w-5 h-5 rounded-full bg-[#BAE6FD] text-[#0F1A24] flex items-center justify-center text-[10px]">1</span>
                        <span>Keranjang</span>
                    </div>
                    <span class="text-slate-300">/</span>
                    <div class="flex items-center gap-1.5 text-slate-400 font-medium">
                        <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-[10px]">2</span>
                        <span>Pengiriman</span>
                    </div>
                    <span class="text-slate-300">/</span>
                    <div class="flex items-center gap-1.5 text-slate-400 font-medium">
                        <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center text-[10px]">3</span>
                        <span>Pembayaran</span>
                    </div>
                </div>
            </div>

            <!-- 2. Page Title Header -->
            <div class="mb-6">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-slate-200/60 text-[#0369A1] text-[9px] sm:text-[10px] font-bold uppercase tracking-wider mb-2">
                    ✦ KURASI MAHARDIKA AUTENTIK
                </div>
                <h1 class="text-xl sm:text-3xl font-bold text-[#0F1A24] tracking-tight">
                    Keranjang Belanja Mahakarya
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mt-1 leading-relaxed">
                    Periksa dan sesuaikan daftar busana batik pilihan Anda sebelum melanjutkan ke kalkulasi pengiriman.
                </p>
            </div>

            <!-- 3. Layout Grid Keranjang & Ringkasan Pesanan -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start mb-12">

                <!-- Left Column: Items List & Coupon Section (8 Cols) -->
                <div class="lg:col-span-8 space-y-4">

                    <!-- Banner Status (perlu login / error / notifikasi) -->
                    <template x-if="needLogin">
                        <div class="bg-sky-50 border border-sky-200 text-[#0369A1] text-xs font-semibold rounded-2xl px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <span><i class="fa-solid fa-lock mr-1.5"></i> Masuk untuk melihat keranjang belanja Anda.</span>
                            <a href="/login" class="bg-[#0F1A24] text-white text-xs font-bold px-4 py-2 rounded-xl">MASUK →</a>
                        </div>
                    </template>
                    <template x-if="errorMsg">
                        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span x-text="errorMsg"></span>
                            </div>
                            <button type="button" @click="load()" class="text-[11px] font-bold text-rose-800 underline hover:no-underline cursor-pointer">
                                Coba Lagi
                            </button>
                        </div>
                    </template>

                    <!-- Bulk Action Header Bar -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <span class="text-xs sm:text-sm font-bold text-[#0F1A24]">
                            <span x-text="items.length"></span> Item Produksi (<span x-text="selectedCount"></span> total kuantitas)
                        </span>

                        <button type="button" :disabled="busy || isLoading || !items.length" @click="kosongkan()"
                                class="text-xs font-semibold text-slate-500 hover:text-rose-600 transition flex items-center gap-1 disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed">
                            <i class="fa-regular fa-trash-can text-xs"></i>
                            <span class="hidden sm:inline">Kosongkan Keranjang</span>
                        </button>
                    </div>

                    <!-- Notifikasi aksi -->
                    <div x-show="msg" class="rounded-xl text-xs font-semibold px-4 py-3 flex items-center gap-2"
                         :class="msgOk ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'">
                        <i :class="msgOk ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                        <span x-text="msg"></span>
                    </div>

                    <!-- Cart Item List: Empty State -->
                    <template x-if="!needLogin && !errorMsg && !isLoading && !items.length">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                            <i class="fa-regular fa-face-frown text-3xl text-slate-300 block mb-3"></i>
                            <p class="text-sm font-bold text-[#0F1A24]">Keranjang Anda masih kosong</p>
                            <p class="text-xs text-slate-500 mt-1">Jelajahi koleksi batik pilihan dan mulai belanja.</p>
                            <a href="/katalog" class="inline-block mt-4 bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">BELANJA SEKARANG →</a>
                        </div>
                    </template>

                    <!-- Error State (Saat keranjang kosong karena error) -->
                    <template x-if="!needLogin && errorMsg && !isLoading && !items.length">
                        <div class="bg-white rounded-2xl border border-rose-200 shadow-sm py-12 px-6 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mb-3">
                                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                            </div>
                            <p class="text-sm font-bold text-[#0F1A24]">Gagal Memuat Keranjang</p>
                            <p class="text-xs text-slate-500 mt-1" x-text="errorMsg"></p>
                            <button type="button" @click="load()" class="inline-flex items-center gap-2 mt-4 bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition cursor-pointer">
                                <i class="fa-solid fa-rotate-right text-xs"></i>
                                <span>Coba Muat Ulang</span>
                            </button>
                        </div>
                    </template>

                    <!-- Loading State -->
                    <template x-if="isLoading && !needLogin">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                            <i class="fa-solid fa-circle-notch fa-spin text-xl text-sky-500"></i>
                            <p class="text-xs text-slate-500 mt-3 font-semibold">Memuat keranjang dari server…</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in items" :key="item.id_cart_item">
                        <div x-data="{ isExpanded: false }" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm transition hover:border-slate-300">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <!-- Product Image (klik menuju detail produk) -->
                                <a :href="'/detail/' + (item.variant?.product?.id_product ?? '')"
                                   class="w-20 h-24 sm:w-28 sm:h-32 bg-slate-100 rounded-xl overflow-hidden shrink-0 border border-slate-100 block group"
                                   title="Lihat detail produk">
                                    <img :src="imageOf(item)" :alt="productTitle(item)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" :onerror="`this.src='${fallbackImage}'`">
                                </a>

                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block truncate" x-text="productCat(item)"></span>
                                                <a :href="'/detail/' + (item.variant?.product?.id_product ?? '')" class="text-slate-400 hover:text-sky-600 transition text-[10px] inline-flex items-center shrink-0" title="Buka detail produk">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                                </a>
                                            </div>
                                            <!-- Interaktif Truncate 1 baris & Auto-Layout Teks Nama Produk -->
                                            <h3 @click="isExpanded = !isExpanded"
                                                :title="productTitle(item) + (isExpanded ? ' (Klik untuk menciutkan)' : ' (Klik untuk lihat nama lengkap)')"
                                                :class="isExpanded ? 'whitespace-normal break-words' : 'truncate line-clamp-1 block overflow-hidden text-ellipsis whitespace-nowrap'"
                                                class="font-bold text-[#0F1A24] text-xs sm:text-base leading-snug cursor-pointer hover:text-[#0369A1] transition select-none"
                                                x-text="productTitle(item)">
                                            </h3>
                                            <p class="text-[10px] sm:text-xs text-slate-500 mt-1 leading-relaxed line-clamp-2" x-text="specLine(item)"></p>
                                        </div>

                                        <!-- Delete Single Item -->
                                        <button @click="hapusItem(index)" :disabled="busy || isLoading" type="button" class="text-slate-400 hover:text-rose-500 p-1 transition shrink-0 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" title="Hapus dari keranjang">
                                            <i class="fa-regular fa-trash-can text-xs sm:text-sm"></i>
                                        </button>
                                    </div>

                                    <!-- Price & Quantity Control -->
                                    <div class="mt-3 sm:mt-4 pt-2.5 sm:pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                                        <!-- Prices -->
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[#0F1A24] text-sm sm:text-lg" x-text="formatRupiah(item.variant?.harga)"></span>
                                            <span class="text-[10px] text-slate-400" x-text="'× ' + (item.jumlah ?? 1)"></span>
                                            <span class="text-xs font-bold text-[#0369A1]" x-show="Number(item.jumlah ?? 1) > 1" x-text="formatRupiah(Number(item.variant?.harga ?? 0) * Number(item.jumlah ?? 1))"></span>
                                        </div>

                                        <!-- Qty Selector (dibatasi stok via API & boundary quantity > stock) -->
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] sm:text-xs text-slate-500 font-medium hidden sm:inline">Kuantitas:</span>
                                            <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                                                <button @click="ubahQty(item, -1)" :disabled="busy || isLoading || Number(item.jumlah || 1) <= 1" type="button" class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition text-xs font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">-</button>
                                                <span class="w-8 sm:w-10 text-center text-xs font-bold text-[#0F1A24]" x-text="item.jumlah ?? 1"></span>
                                                <button @click="ubahQty(item, +1)" :disabled="busy || isLoading || (item.variant?.stok != null && Number(item.jumlah || 1) >= Number(item.variant.stok))" type="button" class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition text-xs font-bold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">+</button>
                                            </div>
                                            <span class="text-[10px] text-slate-400" x-show="item.variant?.stok != null" x-text="'Stok ' + item.variant?.stok"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- 4. Kupon & Voucher Section -->
                    <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                        <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm flex items-center gap-2">
                            <i class="fa-solid fa-ticket text-sky-600"></i>
                            <span>Kupon & Voucher Hak Istimewa</span>
                        </h3>

                        <!-- Input Code Form -->
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-tag absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" x-model="couponCode" @keydown.enter="if (!isActionDisabled) applyVoucher()" :disabled="isActionDisabled" placeholder="Masukkan kode voucher..." class="w-full pl-9 pr-3 py-2.5 text-xs font-mono uppercase font-bold bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>
                            <button @click="applyVoucher()" type="button" :disabled="busyVoucher || isActionDisabled || !couponCode.trim()"
                                    class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold px-4 sm:px-6 py-2.5 rounded-xl transition shrink-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!busyVoucher">GUNAKAN</span>
                                <span x-show="busyVoucher"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                            </button>
                        </div>

                        <!-- Pesan hasil validasi voucher -->
                        <p x-show="voucherMsg" class="text-[11px] font-semibold flex items-center gap-1.5"
                           :class="voucherOk ? 'text-emerald-600' : 'text-rose-600'">
                            <i :class="voucherOk ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                            <span x-text="voucherMsg"></span>
                        </p>

                        <!-- Active Coupon Alert -->
                        <template x-if="voucher">
                            <div class="flex items-center justify-between p-3 bg-sky-50/80 border border-sky-200 rounded-xl text-xs">
                                <div class="flex items-center gap-2 text-[#0369A1]">
                                    <i class="fa-solid fa-circle-check text-sky-600"></i>
                                    <div>
                                        <span class="font-bold font-mono" x-text="voucher.nama_voucher ?? voucher.kode_voucher ?? 'VOUCHER'"></span>
                                        <span class="bg-sky-200 text-[#0369A1] font-bold px-1.5 py-0.5 rounded text-[10px] ml-1">Hemat <span x-text="formatRupiah(discountAmount)"></span></span>
                                        <p class="text-[10px] text-slate-500 mt-0.5">Diskon diaplikasikan saat checkout.</p>
                                    </div>
                                </div>
                                <button @click="hapusVoucher()" type="button" class="text-slate-400 hover:text-slate-700 p-1 cursor-pointer">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- Right Column: Ringkasan Pesanan (4 Cols) -->
                <div class="lg:col-span-4 space-y-4">

                    <!-- Order Summary Box -->
                    <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h2 class="font-bold text-[#0F1A24] text-base">Ringkasan Pesanan</h2>
                            <span class="text-xs bg-slate-100 text-slate-600d font-bold px-2.5 py-0.5 rounded-full" x-text="selectedCount + ' Item'"></span>
                        </div>

                        <!-- Details Rows -->
                        <div class="space-y-2.5 text-xs text-slate-600">
                            <div class="flex justify-between">
                                <span>Subtotal (<span x-text="selectedCount"></span> Item)</span>
                                <span class="font-bold text-slate-900" x-text="formatRupiah(subtotal)"></span>
                            </div>

                            <template x-if="voucher">
                                <div class="flex justify-between text-rose-600">
                                    <span class="truncate mr-2"><i class="fa-solid fa-ticket text-[10px] mr-1"></i><span x-text="voucher.nama_voucher ?? 'Voucher'"></span></span>
                                    <span class="font-bold" x-text="'- ' + formatRupiah(discountAmount)"></span>
                                </div>
                            </template>

                            <div class="flex justify-between">
                                <span>Estimasi Ongkir</span>
                                <span class="text-slate-400 font-medium italic">Dihitung saat checkout</span>
                            </div>
                        </div>

                        <!-- Divider & Total Tagihan -->
                        <div class="pt-3 border-t border-slate-100 flex items-baseline justify-between">
                            <div>
                                <span class="text-xs text-slate-500 font-medium uppercase tracking-wider block">ESTIMASI TAGIHAN</span>
                                <span class="text-[10px] text-slate-400">Sebelum biaya pengiriman</span>
                            </div>
                            <span class="font-bold text-[#0F1A24] text-xl sm:text-2xl" x-text="formatRupiah(totalTagihan)"></span>
                        </div>

                        <!-- Checkout Button -->
                        <button type="button"
                                @click="if (!isCheckoutDisabled) window.location.href = '/checkout'"
                                :disabled="isCheckoutDisabled"
                                :class="isCheckoutDisabled 
                                    ? 'bg-slate-200 text-slate-400 cursor-not-allowed opacity-60 shadow-none pointer-events-none' 
                                    : 'bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] cursor-pointer shadow-sm active:scale-[0.99]'"
                                class="w-full inline-flex items-center justify-center gap-2 text-xs font-bold py-3.5 px-4 rounded-xl transition duration-150">
                            <span x-show="isLoading" class="inline-flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                <span>MEMUAT KERANJANG...</span>
                            </span>
                            <span x-show="!isLoading" class="inline-flex items-center gap-2">
                                <span>LANJUT KE PENGIRIMAN</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </span>
                        </button>

                        <!-- Security Assurance -->
                        <div class="flex items-center justify-center gap-1.5 text-[10px] text-slate-500 pt-1">
                            <i class="fa-solid fa-shield-halved text-sky-600"></i>
                            <span>Terlindungi Midtrans & Garansi Kurasi 100%</span>
                        </div>
                    </div>

                    <!-- Consultation WhatsApp Box -->
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#0369A1] text-white flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-[#25D366] fa-comments text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-[#0F1A24] text-xs">Butuh Panduan Ukuran & Fitting?</h4>
                            <p class="text-[10px] text-slate-500 truncate">Konsultasikan postur</p>
                            <a href="https://wa.me/6283820380977?text=Halo%20Batik%20Pandansari,%20saya%20butuh%20panduan%20ukuran%20baju" target="_blank" class="inline-block mt-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[10px] font-bold px-3 py-1.5 rounded-lg transition w-full text-center">
                                Konsultasi WhatsApp Atelier
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

<script>
    /**
     * Komponen Alpine.js Keranjang Belanja (Cart)
     * Mengelola state item, kupon voucher, kalkulasi harga, dan integrasi PandansariAPI.
     */
    function cartComponent() {
        return {
            // === State Utama Keranjang ===
            items: [],
            isLoading: true,
            loadingCart: true,
            errorMsg: '',
            needLogin: false,
            busy: false,
            msg: '',
            msgOk: false,
            fallbackImage: '{{ asset('images/katalog1.webp') }}',

            // === State Voucher Promo ===
            couponCode: '',
            voucher: null,          // { nama_voucher, tipe_diskon, nilai_diskon, maksimal_diskon, ... }
            voucherMsg: '',
            voucherOk: false,
            busyVoucher: false,

            // === Getters & Komputasi ===
            get selectedCount() {
                return (this.items || []).reduce((s, i) => s + Number(i.jumlah ?? 1), 0);
            },

            get hasOverStock() {
                return (this.items || []).some(i => i.variant?.stok != null && Number(i.jumlah) > Number(i.variant.stok));
            },

            get subtotal() {
                return (this.items || []).reduce((s, i) => s + (Number(i.variant?.harga ?? 0) * Number(i.jumlah ?? 1)), 0);
            },

            get discountAmount() {
                if (!this.voucher) return 0;
                let d = this.voucher.tipe_diskon === 'persentase'
                    ? (this.subtotal * Number(this.voucher.nilai_diskon)) / 100
                    : Number(this.voucher.nilai_diskon);

                if (this.voucher.maksimal_diskon) {
                    d = Math.min(d, Number(this.voucher.maksimal_diskon));
                }

                return Math.round(Math.min(d, this.subtotal));
            },

            get totalTagihan() {
                return Math.max(0, this.subtotal - this.discountAmount);
            },

            get isCheckoutDisabled() {
                const count = (this.items || []).length;
                return this.isLoading || this.needLogin || !count || !!this.errorMsg || this.hasOverStock;
            },

            get isActionDisabled() {
                const count = (this.items || []).length;
                return this.isLoading || !count || !!this.errorMsg || this.needLogin;
            },

            // === Helper Render UI ===
            productTitle(i) {
                return i?.variant?.product?.nama_produk ?? 'Produk';
            },

            productCat(i) {
                return (i?.variant?.product?.category?.nama_kategori ?? 'Batik Pandansari').toUpperCase();
            },

            specLine(i) {
                const v = i?.variant;
                if (!v) return '';
                return [
                    v.nama_varian,
                    v.warna && ('Warna: ' + v.warna),
                    v.ukuran && ('Ukuran: ' + v.ukuran)
                ].filter(Boolean).join(' • ');
            },

            imageOf(i) {
                try {
                    if (typeof PandansariAPI?.getProductImage === 'function') {
                        const img = PandansariAPI.getProductImage(i);
                        if (img) return img;
                    }
                    if (typeof PandansariAPI?.coverUrl === 'function') {
                        const cover = PandansariAPI.coverUrl(i?.variant?.product?.images);
                        if (cover) return cover;
                    }
                } catch (e) {
                    console.warn('[Cart] Gagal memuat cover gambar produk:', e);
                }
                return this.fallbackImage;
            },

            formatRupiah(n) {
                try {
                    return typeof PandansariAPI?.formatRp === 'function'
                        ? PandansariAPI.formatRp(n)
                        : ('Rp ' + Number(n ?? 0).toLocaleString('id-ID'));
                } catch (e) {
                    return 'Rp ' + Number(n ?? 0).toLocaleString('id-ID');
                }
            },

            // === Loader API ===
            async load() {
                this.isLoading = true;
                this.loadingCart = true;
                this.needLogin = false;
                this.errorMsg = '';

                try {
                    if (!window.PandansariAPI || typeof PandansariAPI.cart !== 'function') {
                        throw new Error('PandansariAPI.cart tidak tersedia.');
                    }

                    const res = await PandansariAPI.cart();

                    if (res && res.ok) {
                        const cart = res.data ?? {};
                        this.items = Array.isArray(cart.items) ? cart.items : (Array.isArray(cart) ? cart : []);
                        if (!this.items.length) {
                            this.msg = '';
                        }
                    } else if (res && res.status === 401) {
                        this.needLogin = true;
                        this.items = [];
                    } else {
                        this.errorMsg = res?.message || 'Gagal memuat keranjang belanja. Silakan coba lagi.';
                    }

                    return res;
                } catch (err) {
                    console.error('[Cart] Error loading cart:', err);
                    this.errorMsg = 'Terjadi kesalahan saat memuat data keranjang belanja.';
                    return { ok: false, message: this.errorMsg };
                } finally {
                    this.isLoading = false;
                    this.loadingCart = false;
                }
            },

            // === Aksi Item ===
            async ubahQty(item, delta) {
                if (this.isLoading || this.busy || !item) return;

                const stock = item.variant?.stok != null ? Number(item.variant.stok) : 99;
                const current = Number(item.jumlah || 1);
                const next = current + delta;

                if (next < 1) return;
                if (delta > 0 && next > stock) {
                    this.tampil('Stok tidak mencukupi. Stok tersedia: ' + stock + '.', false);
                    return;
                }

                this.busy = true;
                try {
                    const res = await PandansariAPI.updateCartItem(item.id_cart_item, next);
                    if (res && res.ok) {
                        item.jumlah = next;
                    } else {
                        this.tampil(res?.message || 'Gagal memperbarui kuantitas.', false);
                    }
                } catch (e) {
                    console.error('[Cart] Error updateCartItem:', e);
                    this.tampil('Gagal memperbarui kuantitas.', false);
                } finally {
                    this.busy = false;
                }
            },

            async hapusItem(index) {
                if (this.isLoading || this.busy) return;

                const item = this.items[index];
                if (!item) return;

                this.busy = true;
                try {
                    const res = await PandansariAPI.removeCartItem(item.id_cart_item);
                    if (res && res.ok) {
                        this.items.splice(index, 1);
                        this.tampil('Item berhasil dihapus.', true);
                    } else {
                        this.tampil(res?.message || 'Gagal menghapus item.', false);
                    }
                } catch (e) {
                    console.error('[Cart] Error removeCartItem:', e);
                    this.tampil('Gagal menghapus item.', false);
                } finally {
                    this.busy = false;
                }
            },

            async kosongkan() {
                if (this.isLoading || this.busy || !this.items.length) return;

                this.busy = true;
                try {
                    const res = await PandansariAPI.clearCart();
                    if (res && res.ok) {
                        this.items = [];
                        this.voucher = null;
                        this.tampil('Keranjang berhasil dikosongkan.', true);
                    } else {
                        this.tampil(res?.message || 'Gagal mengosongkan keranjang.', false);
                    }
                } catch (e) {
                    console.error('[Cart] Error clearCart:', e);
                    this.tampil('Gagal mengosongkan keranjang.', false);
                } finally {
                    this.busy = false;
                }
            },

            // === Aksi Voucher Promo ===
            async applyVoucher() {
                if (this.isActionDisabled || this.busyVoucher || !this.couponCode.trim()) return;

                this.busyVoucher = true;
                try {
                    const res = await PandansariAPI.validateVoucher(this.couponCode.trim().toUpperCase(), this.subtotal);
                    if (res && res.ok) {
                        this.voucher = res.data;
                        this.voucherOk = true;
                        this.voucherMsg = res.message;
                    } else {
                        this.voucher = null;
                        this.voucherOk = false;
                        this.voucherMsg = res?.message || 'Kode voucher tidak valid.';
                    }
                } catch (e) {
                    console.error('[Cart] Error validateVoucher:', e);
                    this.voucher = null;
                    this.voucherOk = false;
                    this.voucherMsg = 'Gagal memvalidasi voucher.';
                } finally {
                    this.busyVoucher = false;
                }
            },

            hapusVoucher() {
                this.voucher = null;
                this.voucherMsg = '';
                this.voucherOk = false;
                this.couponCode = '';
            },

            tampil(msg, ok) {
                this.msg = msg;
                this.msgOk = ok;
                setTimeout(() => {
                    this.msg = '';
                }, 4000);
            },

            // === Inisialisasi Komponen ===
            async init() {
                try {
                    if (window.PandansariAPI && typeof window.PandansariAPI.loadCatalogImages === 'function') {
                        const result = window.PandansariAPI.loadCatalogImages();
                        if (result && typeof result.then === 'function') {
                            await result;
                            if (Array.isArray(this.items)) {
                                this.items = [...this.items];
                            }
                        }
                    }
                } catch (e) {
                    console.warn('[Cart] Error invoking loadCatalogImages:', e);
                }

                await this.load();
            }
        };
    }

    // Registrasi Alpine Component
    document.addEventListener('alpine:init', () => {
        Alpine.data('cartComponent', cartComponent);
    });

    if (window.Alpine) {
        Alpine.data('cartComponent', cartComponent);
    }
</script>
@endsection