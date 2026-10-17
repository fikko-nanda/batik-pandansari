@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        activeTab: 'semua',
        searchQuery: '',

        orders: [
            {
                id: 'BP-20241007-8851',
                date: '7 Okt 2024',
                status: 'ongoing',
                statusLabel: 'Sedang Dikirim',
                courier: 'JNE Regular',
                resi: 'JNE82011048201',
                est: 'Estimasi tiba: 9 – 11 Okt 2024',
                items: [
                    {
                        name: 'Kemeja Pria Parang Seling Indigo',
                        variant: 'Ukuran L • 1 pcs',
                        price: 1350000,
                        image: '{{ asset('images/m1.png') }}',
                        fallback: 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=200'
                    },
                    {
                        name: 'Kain Batik Kawung Gold Prada',
                        variant: 'Ukuran 2.5m x 1.15m • 1 pcs',
                        price: 1372000,
                        image: '{{ asset('images/tulis.jpg') }}',
                        fallback: 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=200'
                    }
                ],
                total: 2722000
            },
            {
                id: 'BP-20240915-4420',
                date: '15 Sep 2024',
                status: 'completed',
                statusLabel: 'Selesai',
                items: [
                    {
                        name: 'Kemeja Batik Parang Kencana Silk',
                        variant: 'Ukuran L • 1 pcs',
                        price: 850000,
                        image: '{{ asset('images/m2.png') }}',
                        fallback: 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?q=80&w=200'
                    }
                ],
                total: 850000
            },
            {
                id: 'BP-20240822-11D4',
                date: '22 Agu 2024',
                status: 'completed',
                statusLabel: 'Selesai',
                items: [
                    {
                        name: 'Blouse Batik Cap Pakaian Wanita',
                        variant: 'Ukuran M • 1 pcs',
                        price: 975000,
                        image: '{{ asset('images/cat-kids.jpg') }}',
                        fallback: 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?q=80&w=200'
                    }
                ],
                total: 975000
            }
        ],

        formatRupiah(number) {
            return 'Rp ' + number.toLocaleString('id-ID');
        }
     }">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-6">
            <a href="/" class="hover:text-black transition">Beranda</a>
            <span>/</span>
            <a href="/account" class="hover:text-black transition">Akun Saya</a>
            <span>/</span>
            <span class="text-black font-bold">Riwayat Pesanan</span>
        </nav>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDEBAR: Navigasi Akun (4 Kolom) -->
            <div class="lg:col-span-4 space-y-4">
                
                <!-- Card Profile Header -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm text-center space-y-2">
                    <div class="w-16 h-16 bg-[#0F1A24] text-white font-bold text-xl rounded-2xl flex items-center justify-center mx-auto shadow-md">
                        <span>RW</span>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-[#0F1A24]">Raden Arya Wijaya</h2>
                        <p class="text-xs text-slate-400 font-medium">arya.wijaya@keraton-heritage.id</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">+62 812-3456-7890</p>
                    </div>
                </div>

                <!-- Navigation Menu Sidebar -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden text-xs divide-y divide-slate-100">
                    <a href="/profile" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-user text-slate-400 text-sm"></i>
                            <span class="font-semibold">Profil & Biodata Diri</span>
                        </div>
                        <i class="fa-solid fa-angle-right text-slate-300"></i>
                    </a>

                    <!-- Active Menu: Riwayat Pesanan -->
                    <a href="/orders" class="flex items-center justify-between p-4 bg-sky-50/50 text-[#0369A1] font-bold border-l-4 border-[#0369A1]">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-receipt text-[#0369A1] text-sm"></i>
                            <span>Riwayat Pesanan</span>
                        </div>
                        <span class="bg-sky-100 text-[#0369A1] px-2 py-0.5 rounded font-bold text-[10px]">1 Aktif</span>
                    </a>

                    <a href="/address" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-slate-400 text-sm"></i>
                            <span class="font-semibold">Buku Alamat Pengiriman</span>
                        </div>
                        <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold text-[10px]">2 Alamat</span>
                    </a>

                    <a href="/wishlist" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-heart text-slate-400 text-sm"></i>
                            <span class="font-semibold">Koleksi Favorit (Wishlist)</span>
                        </div>
                        <i class="fa-solid fa-arrow-up-right-from-square text-slate-300 text-[10px]"></i>
                    </a>

                    <a href="/security" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-lock text-slate-400 text-sm"></i>
                            <span class="font-semibold">Keamanan & Kata Sandi</span>
                        </div>
                        <i class="fa-solid fa-angle-right text-slate-300"></i>
                    </a>

                    <div class="p-4 flex items-center justify-between bg-slate-50/50">
                        <a href="/logout" class="text-rose-600 font-bold flex items-center gap-2 hover:underline">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar Akun</span>
                        </a>
                        <span class="text-[9px] font-bold text-rose-500 uppercase">SIGN OUT</span>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDE: Content Utama Riwayat Pesanan (8 Kolom) -->
            <div class="lg:col-span-8 space-y-5">

                <!-- Title Header -->
                <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Riwayat Pesanan</h1>

                <!-- Tabs Filter Status -->
                <div class="flex flex-wrap items-center gap-2">
                    <button @click="activeTab = 'semua'"
                            type="button" 
                            class="px-4 py-2 rounded-full text-xs font-bold transition"
                            :class="activeTab === 'semua' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Semua <span class="ml-1 text-[10px] opacity-80">(3)</span>
                    </button>

                    <button @click="activeTab = 'berlangsung'"
                            type="button" 
                            class="px-4 py-2 rounded-full text-xs font-bold transition"
                            :class="activeTab === 'berlangsung' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Berlangsung <span class="ml-1 text-[10px] opacity-80">(1)</span>
                    </button>

                    <button @click="activeTab = 'selesai'"
                            type="button" 
                            class="px-4 py-2 rounded-full text-xs font-bold transition"
                            :class="activeTab === 'selesai' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Selesai <span class="ml-1 text-[10px] opacity-80">(2)</span>
                    </button>

                    <button @click="activeTab = 'dibatalkan'"
                            type="button" 
                            class="px-4 py-2 rounded-full text-xs font-bold transition"
                            :class="activeTab === 'dibatalkan' ? 'bg-[#0F1A24] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Dibatalkan <span class="ml-1 text-[10px] opacity-80">(0)</span>
                    </button>
                </div>

                <!-- Input Pencarian & Dropdown Waktu -->
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative w-full flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               x-model="searchQuery"
                               placeholder="Cari nomor pesanan atau nama produk..." 
                               class="w-full text-xs font-medium border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm">
                    </div>

                    <select class="w-full sm:w-auto text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 shadow-sm cursor-pointer">
                        <option>30 Hari Terakhir</option>
                        <option>3 Bulan Terakhir</option>
                        <option>Tahun 2024</option>
                    </select>
                </div>

                <!-- Daftar Card Pesanan -->
                <div class="space-y-4">
                    <template x-for="order in orders" :key="order.id">
                        <div x-show="(activeTab === 'semua') || (activeTab === 'berlangsung' && order.status === 'ongoing') || (activeTab === 'selesai' && order.status === 'completed')"
                             class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6">
                            
                            <!-- Header Card: Tanggal, ID, & Badge Status -->
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24]">
                                    <span x-text="order.date"></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-500 font-medium" x-text="`#${order.id}`"></span>
                                </div>

                                <span class="text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider"
                                      :class="order.status === 'ongoing' ? 'bg-[#BAE6FD] text-[#0369A1]' : 'bg-slate-100 text-slate-600'"
                                      x-text="order.statusLabel">
                                </span>
                            </div>

                            <!-- Detail Ekspedisi khusus untuk status ongoing -->
                            <div x-show="order.status === 'ongoing'" class="flex flex-wrap items-center justify-between text-xs text-slate-500 bg-slate-50 p-2.5 rounded-xl">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[#0F1A24]" x-text="order.courier"></span>
                                    <span>•</span>
                                    <span>Resi: <strong class="text-slate-800" x-text="order.resi"></strong></span>
                                </div>
                                <span class="text-[10px] text-slate-400" x-text="order.est"></span>
                            </div>

                            <!-- List Item Produk dalam Pesanan -->
                            <div class="space-y-3 divide-y divide-slate-100">
                                <template x-for="item in order.items" :key="item.name">
                                    <div class="flex items-center justify-between gap-4 pt-2 first:pt-0">
                                        <div class="flex items-center gap-3">
                                            <img :src="item.image" 
                                                 :alt="item.name" 
                                                 class="w-14 h-16 object-cover rounded-xl border border-slate-100 shrink-0"
                                                 :onerror="`this.src='${item.fallback}'`">
                                            <div>
                                                <h4 class="text-xs font-bold text-[#0F1A24]" x-text="item.name"></h4>
                                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="item.variant"></p>
                                            </div>
                                        </div>

                                        <span class="text-xs font-bold text-[#0F1A24] shrink-0" x-text="formatRupiah(item.price)"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Footer Card: Total Belanja & Action Buttons -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                <div class="text-xs">
                                    <span class="text-slate-500">Total Pesanan: </span>
                                    <span class="font-bold text-[#0F1A24] text-sm" x-text="formatRupiah(order.total)"></span>
                                </div>

                                <div class="flex items-center gap-2 justify-end">
                                    <!-- Tombol untuk Status Ongoing -->
                                    <template x-if="order.status === 'ongoing'">
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-4 py-2 rounded-xl transition">
                                                RINCIAN PESANAN
                                            </button>
                                            <button type="button" class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0369A1] text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1.5">
                                                <i class="fa-solid fa-location-crosshairs text-xs"></i>
                                                <span>LACAK PAKET</span>
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Tombol untuk Status Completed -->
                                    <template x-if="order.status === 'completed'">
                                        <div class="flex items-center gap-2">
                                            <button type="button" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-4 py-2 rounded-xl transition">
                                                RINCIAN
                                            </button>
                                            <button type="button" class="bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1.5">
                                                <i class="fa-solid fa-bag-shopping text-xs"></i>
                                                <span>BELI LAGI</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- Pagination Bottom -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 text-xs text-slate-500">
                    <span>Menampilkan <strong>3</strong> dari <strong>4</strong> transaksi pesanan</span>

                    <div class="flex items-center gap-1">
                        <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-400 flex items-center justify-center cursor-not-allowed">
                            <i class="fa-solid fa-angle-left text-xs"></i>
                        </button>
                        <button type="button" class="w-8 h-8 rounded-lg bg-[#BAE6FD] text-[#0369A1] font-bold flex items-center justify-center">1</button>
                        <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 font-medium flex items-center justify-center">2</button>
                        <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 flex items-center justify-center">
                            <i class="fa-solid fa-angle-right text-xs"></i>
                        </button>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection