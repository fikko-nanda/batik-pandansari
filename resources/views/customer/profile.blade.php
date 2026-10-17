@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        fullName: 'Raden Arya Wijaya',
        email: 'arya.wijaya@keraton-heritage.id',
        phone: '+62 812-3456-7890',
        birthDate: '1988-11-24',
        gender: 'Laki-laki',

        resetForm() {
            this.fullName = 'Raden Arya Wijaya';
            this.email = 'arya.wijaya@keraton-heritage.id';
            this.phone = '+62 812-3456-7890';
            this.birthDate = '1988-11-24';
            this.gender = 'Laki-laki';
        }
     }">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-6">
            <a href="/" class="hover:text-black transition">Beranda</a>
            <span>/</span>
            <a href="/account" class="hover:text-black transition">Akun Saya</a>
            <span>/</span>
            <span class="text-black font-bold">Profil & Biodata Diri</span>
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
                    
                    <!-- Active Menu: Profil & Biodata Diri -->
                    <a href="/profile" class="flex items-center justify-between p-4 bg-sky-50/50 text-[#0369A1] font-bold border-l-4 border-[#0369A1]">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-user text-[#0369A1] text-sm"></i>
                            <span>Profil & Biodata Diri</span>
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
                        <i class="fa-solid fa-arrow-up-right-from-square text-slate-300 text-[10px]"></i>
                    </a>

                    <a href="/security" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-lock text-slate-400 text-sm"></i>
                            <span class="font-semibold">Keamanan & Kata Sandi</span>
                        </div>
                        <i class="fa-solid fa-shield-halved text-slate-300 text-xs"></i>
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

            <!-- RIGHT SIDE: Form Profil & Informasi Akun (8 Kolom) -->
            <div class="lg:col-span-8 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-6">
                
                <!-- Form Header -->
                <div>
                    <span class="text-[10px] font-bold tracking-wider uppercase text-slate-400 block mb-1">DATA PRIBADI ANGGOTA</span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Profil & Informasi Akun</h1>
                </div>

                <form @submit.prevent="" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        
                        <!-- Nama Lengkap Sesuai KTP -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                NAMA LENGKAP SESUAI KTP <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       x-model="fullName"
                                       class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <i class="fa-regular fa-id-card absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                        <!-- Alamat Surel (Email) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                    ALAMAT SUREL (EMAIL) <span class="text-red-500">*</span>
                                </label>
                                <span class="inline-flex items-center gap-1 text-[9px] bg-sky-100 text-[#0369A1] px-2 py-0.5 rounded-md font-bold">
                                    <i class="fa-solid fa-circle-check text-[9px]"></i> Terverifikasi
                                </span>
                            </div>
                            <div class="relative">
                                <input type="email" 
                                       x-model="email"
                                       class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <i class="fa-regular fa-envelope absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                        <!-- Nomor WhatsApp / Seluler -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                    NOMOR WHATSAPP / SELULER <span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div class="relative">
                                <input type="text" 
                                       x-model="phone"
                                       class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <i class="fa-solid fa-mobile-screen absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                        <!-- Tanggal Lahir -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                TANGGAL LAHIR (UNTUK HADIAH WASTRA TAHUNAN)
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       value="11 / 24 / 1988"
                                       class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <i class="fa-regular fa-calendar absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="pt-2">
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 mb-2">
                            JENIS KELAMIN
                        </label>
                        <div class="flex flex-wrap items-center gap-6 text-xs font-bold text-slate-700">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="gender" value="Laki-laki" x-model="gender" class="text-black focus:ring-black w-4 h-4">
                                <span>Laki-laki</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="gender" value="Perempuan" x-model="gender" class="text-black focus:ring-black w-4 h-4">
                                <span>Perempuan</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="gender" value="Memilih Tidak Menyebutkan" x-model="gender" class="text-black focus:ring-black w-4 h-4">
                                <span>Memilih Tidak Menyebutkan</span>
                            </label>
                        </div>
                    </div>

                    <!-- Footer Action Buttons & Link Ubah Sandi -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-100">
                        <a href="/security" class="text-xs font-bold text-slate-700 hover:text-black flex items-center gap-1.5 underline underline-offset-4">
                            <i class="fa-solid fa-key text-xs"></i>
                            <span>UBAH KATA SANDI AKUN</span>
                        </a>

                        <div class="flex items-center gap-2 justify-end">
                            <button @click="resetForm()" 
                                    type="button" 
                                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-6 py-3 rounded-xl transition cursor-pointer">
                                BATAL
                            </button>
                            <button type="submit" 
                                    class="bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-6 py-3 rounded-xl transition cursor-pointer shadow-sm">
                                SIMPAN PERUBAHAN
                            </button>
                        </div>
                    </div>

                </form>

            </div>

        </div>

    </div>
</div>
@endsection