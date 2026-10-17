@extends('layouts.app')

@section('content')

    <!-- Container Utama Keranjang Belanja Interaktif dengan Alpine.js -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{
            selectAll: true,
            couponApplied: true,
            couponCode: 'PANDANSARHERITAGE',
            
            items: [
                {
                    id: 1,
                    selected: true,
                    title: 'Kemeja Pria Parang Seling Indigo',
                    category: 'PAKAIAN PRIA • BATIK TULIS SOLO',
                    image: '{{ asset('images/m1.png') }}',
                    price: 1350000,
                    originalPrice: 1550000,
                    qty: 1,
                    spec: 'Ukuran: L • Furing Katun Hero Adem • Asal: Sragen • Jahitan Simetris'
                },
                {
                    id: 2,
                    selected: true,
                    title: 'Tunik Batik Pesisir Cheongsam',
                    category: 'PAKAIAN WANITA • CHEONGSAM MODERN',
                    image: '{{ asset('images/w1.png') }}',
                    price: 1280000,
                    originalPrice: null,
                    qty: 1,
                    spec: 'Ukuran: M • Kerah Shanghai Klasik • Kancing Handmade • Sutra Halus'
                },
                {
                    id: 3,
                    selected: true,
                    title: 'Dress Anak Bunga Sekar Navy',
                    category: 'PAKAIAN ANAK-ANAK • DRESS KATUN',
                    image: '{{ asset('images/cat-kids.jpg') }}',
                    price: 450000,
                    originalPrice: null,
                    qty: 1,
                    spec: 'Ukuran: 4-5 Thn • Katun Primisima Lembut • Pewarna Ramah Anak'
                }
            ],

            toggleSelectAll() {
                this.items.forEach(item => item.selected = this.selectAll);
            },

            get selectedCount() {
                return this.items.filter(i => i.selected).length;
            },

            get subtotal() {
                return this.items
                    .filter(i => i.selected)
                    .reduce((sum, item) => sum + (item.price * item.qty), 0);
            },

            get discountAmount() {
                return this.couponApplied ? Math.round(this.subtotal * 0.10) : 0;
            },

            get totalTagihan() {
                return Math.max(0, this.subtotal - this.discountAmount);
            },

            formatRupiah(number) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
            }
         }">
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

                    <!-- Bulk Action Header Bar -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer text-xs sm:text-sm font-bold text-[#0F1A24]">
                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 border-slate-300 cursor-pointer">
                            <span>Pilih Semua (<span x-text="selectedCount"></span> Item terpilih)</span>
                        </label>

                        <button type="button" @click="items = []" class="text-xs font-semibold text-slate-500 hover:text-rose-600 transition flex items-center gap-1">
                            <i class="fa-regular fa-trash-can text-xs"></i>
                            <span class="hidden sm:inline">Hapus Terpilih</span>
                        </button>
                    </div>

                    <!-- Cart Item List -->
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm transition hover:border-slate-300">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <!-- Checkbox -->
                                <input type="checkbox" x-model="item.selected" class="w-4 h-4 rounded text-sky-600 focus:ring-sky-500 border-slate-300 cursor-pointer mt-1">

                                <!-- Product Image -->
                                <div class="w-20 h-24 sm:w-28 sm:h-32 bg-slate-100 rounded-xl overflow-hidden shrink-0 border border-slate-100">
                                    <img :src="item.image" :alt="item.title" class="w-full h-full object-cover">
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5" x-text="item.category"></span>
                                            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base leading-snug truncate" x-text="item.title"></h3>
                                            <p class="text-[10px] sm:text-xs text-slate-500 mt-1 leading-relaxed line-clamp-2" x-text="item.spec"></p>
                                        </div>

                                        <!-- Delete Single Item -->
                                        <button @click="items.splice(index, 1)" type="button" class="text-slate-400 hover:text-rose-500 p-1 transition shrink-0">
                                            <i class="fa-regular fa-trash-can text-xs sm:text-sm"></i>
                                        </button>
                                    </div>

                                    <!-- Price & Quantity Control -->
                                    <div class="mt-3 sm:mt-4 pt-2.5 sm:pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                                        <!-- Prices -->
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-[#0F1A24] text-sm sm:text-lg" x-text="formatRupiah(item.price)"></span>
                                            <template x-if="item.originalPrice">
                                                <span class="text-[10px] sm:text-xs text-slate-400 line-through" x-text="formatRupiah(item.originalPrice)"></span>
                                            </template>
                                        </div>

                                        <!-- Qty Selector -->
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] sm:text-xs text-slate-500 font-medium hidden sm:inline">Kuantitas:</span>
                                            <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-slate-50">
                                                <button @click="if(item.qty > 1) item.qty--" type="button" class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition text-xs font-bold">-</button>
                                                <span class="w-8 sm:w-10 text-center text-xs font-bold text-[#0F1A24]" x-text="item.qty"></span>
                                                <button @click="item.qty++" type="button" class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition text-xs font-bold">+</button>
                                            </div>
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
                                <input type="text" x-model="couponCode" placeholder="Masukkan kode voucher..." class="w-full pl-9 pr-3 py-2.5 text-xs font-mono uppercase font-bold bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <button @click="couponApplied = true" type="button" class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold px-4 sm:px-6 py-2.5 rounded-xl transition shrink-0">
                                GUNAKAN
                            </button>
                        </div>

                        <!-- Active Coupon Alert -->
                        <template x-if="couponApplied">
                            <div class="flex items-center justify-between p-3 bg-sky-50/80 border border-sky-200 rounded-xl text-xs">
                                <div class="flex items-center gap-2 text-[#0369A1]">
                                    <i class="fa-solid fa-circle-check text-sky-600"></i>
                                    <div>
                                        <span class="font-bold font-mono">PANDANSARIHERITAGE (-10%)</span>
                                        <span class="bg-sky-200 text-[#0369A1] font-bold px-1.5 py-0.5 rounded text-[10px] ml-1">Hemat <span x-text="formatRupiah(discountAmount)"></span></span>
                                        <p class="text-[10px] text-slate-500 mt-0.5">Diskon privilege koleksi wastra mahakarya telah diaplikasikan.</p>
                                    </div>
                                </div>
                                <button @click="couponApplied = false" type="button" class="text-slate-400 hover:text-slate-700 p-1">
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

                            <template x-if="couponApplied">
                                <div class="flex justify-between text-rose-600">
                                    <span>Diskon (10%)</span>
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
                                <span class="text-xs text-slate-500 font-medium uppercase tracking-wider block">TOTAL TAGIHAN</span>
                                <span class="text-[10px] text-slate-400">Termasuk PPN 11%</span>
                            </div>
                            <span class="font-bold text-[#0F1A24] text-xl sm:text-2xl" x-text="formatRupiah(totalTagihan)"></span>
                        </div>

                        <!-- Checkout Button -->
                        <a href="/checkout" class="w-full inline-flex items-center justify-center gap-2 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold py-3.5 px-4 rounded-xl transition shadow-sm">
                            <span>LANJUT KE PENGIRIMAN</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>

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
                            <a href="https://wa.me/6281234567890?text=Halo%20Batik%20Pandansari,%20saya%20butuh%20panduan%20ukuran%20baju" target="_blank" class="inline-block mt-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-[10px] font-bold px-3 py-1.5 rounded-lg transition w-full text-center">
                                Konsultasi WhatsApp Atelier
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

@endsection