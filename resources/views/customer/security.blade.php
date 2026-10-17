@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        currentPassword: 'BatikPusakaSolo2023!',
        newPassword: 'MahardikaSurakarta2024#',
        confirmPassword: 'MahardikaSurakarta2024#',

        showCurrentPass: false,
        showNewPass: false,
        showConfirmPass: false,

        resetForm() {
            this.currentPassword = '';
            this.newPassword = '';
            this.confirmPassword = '';
        }
     }">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-6">
            <a href="/" class="hover:text-black transition flex items-center gap-1">
                <i class="fa-solid fa-house text-[11px]"></i> Beranda
            </a>
            <span>/</span>
            <a href="/profile" class="hover:text-black transition">Akun Saya</a>
            <span>/</span>
            <span class="text-black font-bold">Keamanan & Kata Sandi</span>
        </nav>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDEBAR: Navigasi Akun (4 Kolom) -->
            <div class="lg:col-span-4 space-y-4">
                
                <!-- Card Profile Header -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm text-center space-y-2">
                    <div class="relative w-16 h-16 bg-[#0F1A24] text-white font-bold text-xl rounded-2xl flex items-center justify-center mx-auto shadow-md">
                        <span>RW</span>
                        <span class="absolute -bottom-1 -right-1 bg-sky-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    </div>

                    <div class="pt-1">
                        <h2 class="text-lg font-bold text-[#0F1A24]">Raden Arya Wijaya</h2>
                        <span class="inline-block bg-[#E0F2FE] text-[#0369A1] text-[9px] font-bold px-3 py-1 rounded-full uppercase tracking-wider mt-1">
                            PELANGGAN KEHORMATAN
                        </span>
                    </div>

                    <div class="bg-slate-50 p-2.5 rounded-xl text-slate-500 text-xs font-medium space-y-0.5 mt-2">
                        <p class="flex items-center justify-center gap-1.5 text-slate-600">
                            <i class="fa-regular fa-envelope text-[11px]"></i> arya.wijaya@keraton-heritage.id
                        </p>
                        <p class="flex items-center justify-center gap-1.5 text-slate-500">
                            <i class="fa-solid fa-mobile-screen text-[11px]"></i> +62 812-3456-7890
                        </p>
                    </div>
                </div>

                <!-- Navigation Menu Sidebar -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden text-xs divide-y divide-slate-100">
                    
                    <a href="/profile" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-user text-slate-400 text-sm"></i>
                            <span class="font-semibold">Profil & Informasi Akun</span>
                        </div>
                    </a>

                    <a href="/orders" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-receipt text-slate-400 text-sm"></i>
                            <span class="font-semibold">Riwayat Pesanan</span>
                        </div>
                        <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold text-[10px]">2 Aktif</span>
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
                    </a>

                    <!-- Active Menu: Keamanan & Kata Sandi -->
                    <a href="/security" class="flex items-center justify-between p-4 bg-sky-50/50 text-[#0369A1] font-bold border-l-4 border-[#0369A1]">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-shield-halved text-[#0369A1] text-sm"></i>
                            <span>Keamanan & Kata Sandi</span>
                        </div>
                        <i class="fa-solid fa-angle-right text-[#0369A1]"></i>
                    </a>

                    <div class="p-4 flex items-center justify-between bg-slate-50/50">
                        <a href="/logout" class="text-rose-600 font-bold flex items-center gap-2 hover:underline">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar Akun</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDE: Form Pembaruan Kata Sandi (8 Kolom) -->
            <div class="lg:col-span-8 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-6">
                
                <!-- Form Header -->
                <div class="flex items-start gap-4 pb-2 border-b border-slate-100">
                    <div class="w-10 h-10 bg-slate-100 text-slate-700 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-rotate text-base"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-[#0F1A24]">Pembaruan Kata Sandi</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Disarankan memperbarui kata sandi secara berkala setiap 90 hari.</p>
                    </div>
                </div>

                <form @submit.prevent="" class="space-y-5">
                    
                    <!-- Kata Sandi Saat Ini -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700">Kata Sandi Saat Ini</label>
                            <a href="/forgot-password" class="text-xs font-semibold text-slate-500 hover:text-black hover:underline">Lupa kata sandi?</a>
                        </div>
                        <div class="relative">
                            <input :type="showCurrentPass ? 'text' : 'password'" 
                                   x-model="currentPassword"
                                   class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                            <button type="button" 
                                    @click="showCurrentPass = !showCurrentPass"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-regular" :class="showCurrentPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Kata Sandi Baru & Indikator Kekuatan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Baru</label>
                        <div class="relative">
                            <input :type="showNewPass ? 'text' : 'password'" 
                                   x-model="newPassword"
                                   class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                            <button type="button" 
                                    @click="showNewPass = !showNewPass"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-regular" :class="showNewPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>

                        <!-- Password Strength Bar Indicator -->
                        <div class="bg-slate-50 border border-slate-200/80 p-3 rounded-xl mt-2 space-y-1.5">
                            <div class="flex justify-between items-center text-[11px] font-bold">
                                <span class="text-slate-500">Tingkat Kekuatan:</span>
                                <span class="text-[#0369A1] flex items-center gap-1">
                                    <i class="fa-solid fa-circle text-[8px]"></i> Kuat (Sangat Aman)
                                </span>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5">
                                <div class="h-1.5 bg-slate-700 rounded-full"></div>
                                <div class="h-1.5 bg-slate-700 rounded-full"></div>
                                <div class="h-1.5 bg-slate-700 rounded-full"></div>
                                <div class="h-1.5 bg-slate-700 rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Konfirmasi Kata Sandi Baru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative">
                            <input :type="showConfirmPass ? 'text' : 'password'" 
                                   x-model="confirmPassword"
                                   class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                            <button type="button" 
                                    @click="showConfirmPass = !showConfirmPass"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-regular" :class="showConfirmPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Syarat Minimum Kata Sandi Box -->
                    <div class="bg-slate-50 border border-slate-200/80 p-4 rounded-2xl space-y-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 block">SYARAT MINIMUM KATA SANDI:</span>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600 font-medium">
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-circle-check text-sky-600 text-sm"></i>
                                <span>Minimal 8 karakter (Disarankan 12+)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-circle-check text-sky-600 text-sm"></i>
                                <span>Kombinasi huruf besar & kecil</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-circle-check text-sky-600 text-sm"></i>
                                <span>Mengandung minimal satu angka</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-regular fa-circle-check text-sky-600 text-sm"></i>
                                <span>Mengandung simbol khusus (@, #, $, %)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" 
                                class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0369A1] text-xs font-bold px-6 py-3.5 rounded-xl transition cursor-pointer shadow-sm">
                            SIMPAN KATA SANDI
                        </button>

                        <button @click="resetForm()" 
                                type="button" 
                                class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-6 py-3.5 rounded-xl transition cursor-pointer">
                            BATAL
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>
</div>
@endsection