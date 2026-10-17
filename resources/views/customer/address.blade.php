@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        showAddModal: false,
        addresses: [
            {
                id: 1,
                name: 'Raden Arya Wijaya',
                tag: 'Pribadi',
                isPrimary: true,
                isHome: true,
                isBilling: true,
                phone: '+62 812-3456-7890',
                address: 'Jl. Teuku Umar No. 18, RT.04/RW.02, Kel. Menteng, Kec. Menteng, Kota Jakarta Pusat, DKI Jakarta 10310',
                notes: 'Titip di pos satpam depan, jangan dibanting (kain sutra premium).',
                updatedAt: '12 Oktober 2024'
            }
        ]
     }">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-6">
            <a href="/" class="hover:text-black transition">Beranda</a>
            <span>/</span>
            <a href="/account" class="hover:text-black transition">Akun Saya</a>
            <span>/</span>
            <span class="text-black font-bold">Buku Alamat Pengiriman</span>
        </nav>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDEBAR: Informasi Profil & Navigasi Akun (4 Kolom) -->
            <div class="lg:col-span-4 space-y-4">
                
                <!-- Card Profile Header -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm text-center space-y-3">
                    <!-- Avatar Inisial RW -->
                    <div class="relative w-16 h-16 bg-[#1E293B] text-white font-bold text-xl rounded-2xl flex items-center justify-center mx-auto shadow-md">
                        <span>RW</span>
                    </div>

                    <!-- User Information -->
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

                    <a href="/orders" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-receipt text-slate-400 text-sm"></i>
                            <span class="font-semibold">Riwayat Pesanan</span>
                        </div>
                        <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold text-[10px]">2 Aktif</span>
                    </a>

                    <!-- Active Menu: Buku Alamat -->
                    <a href="/address" class="flex items-center justify-between p-4 bg-sky-50/50 text-[#0369A1] font-bold border-l-4 border-[#0369A1]">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-[#0369A1] text-sm"></i>
                            <span>Buku Alamat Pengiriman</span>
                        </div>
                      
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

            <!-- RIGHT SIDE: Content Utama Buku Alamat Pengiriman (8 Kolom) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Header Section & Tombol Tambah Alamat -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-[10px] font-bold tracking-wider uppercase text-slate-400 block mb-1">DESTINASI PENGHANTARAN</span>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Buku Alamat Pengiriman</h1>
                        <p class="text-xs text-slate-500 mt-1">Kelola alamat tujuan pengiriman pesanan wastra Anda dengan presisi kurasi.</p>
                    </div>

                    <button @click="showAddModal = true"
                            type="button" 
                            class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] font-bold text-xs px-4 py-3 rounded-xl transition flex items-center justify-center gap-2 shrink-0 shadow-sm cursor-pointer">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>TAMBAH ALAMAT BARU</span>
                    </button>
                </div>

                <!-- Lista Alamat Pengiriman -->
                <div class="space-y-4">
                    <template x-for="item in addresses" :key="item.id">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4 relative">
                            
                            <!-- Badges Header Alamat & Action Ubah -->
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                                    <span x-show="item.isPrimary" class="bg-[#BAE6FD] text-[#0369A1] px-2.5 py-1 rounded-md uppercase flex items-center gap-1">
                                        ★ ALAMAT UTAMA
                                    </span>
                                    <span x-show="item.isHome" class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md uppercase flex items-center gap-1">
                                        <i class="fa-solid fa-house text-[9px]"></i> RUMAH
                                    </span>
                                    <span x-show="item.isBilling" class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md uppercase flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Alamat Penagihan Aktif
                                    </span>
                                </div>

                                <button type="button" class="text-xs font-bold text-slate-600 hover:text-black transition flex items-center gap-1">
                                    <i class="fa-regular fa-pen-to-square"></i> Ubah Alamat
                                </button>
                            </div>

                            <!-- Detail Rincian Alamat -->
                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-[#0F1A24]" x-text="item.name"></h3>
                                    <span class="text-[10px] text-slate-400 font-semibold" x-text="`(${item.tag})`"></span>
                                </div>

                                <p class="text-slate-600 font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-phone text-slate-400 text-[11px]"></i>
                                    <span x-text="item.phone"></span>
                                </p>

                                <p class="text-slate-600 leading-relaxed pt-1" x-text="item.address"></p>
                            </div>

                            <!-- Catatan Pengiriman khusus (Notes Box) -->
                            <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-3 flex items-start gap-2.5 text-xs text-slate-600">
                                <i class="fa-regular fa-note-sticky text-slate-400 text-sm mt-0.5"></i>
                                <div>
                                    <span class="font-bold text-[10px] uppercase text-slate-400 block tracking-wider">CATATAN PENGIRIMAN:</span>
                                    <p class="text-slate-700 font-medium" x-text="item.notes"></p>
                                </div>
                            </div>

                            <!-- Checkbox Alamat Penagihan & Footer Log -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-slate-100 text-[11px]">
                                <label class="flex items-center gap-2 font-semibold text-slate-700 cursor-pointer">
                                    <input type="checkbox" checked class="rounded text-black focus:ring-black w-4 h-4">
                                    <span>Gunakan sebagai alamat penagihan utama</span>
                                </label>
                                <span class="text-slate-400 text-[10px]" x-text="`Terakhir diperbarui: ${item.updatedAt}`"></span>
                            </div>

                        </div>
                    </template>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection