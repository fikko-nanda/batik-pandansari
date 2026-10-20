@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        user: PandansariAPI.getUser(),
        needLogin: false,
        form: { nama_pengguna: '', email: '', nomor_telepon: '' },
        busy: false,
        saving: false,
        msg: '', msgOk: true,

        initials() {
            const n = this.user?.nama_pengguna ?? '';
            return n.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';
        },
        formatTanggal(s) {
            if (!s) return '—';
            try { return new Date(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); } catch { return s; }
        },
        async load() {
            if (!PandansariAPI.getToken()) { this.needLogin = true; return; }
            this.busy = true;
            await PandansariAPI.me();               // segarkan profil dari server + simpan ke localStorage
            this.user = PandansariAPI.getUser();
            this.busy = false;
            if (this.user) {
                this.form.nama_pengguna = this.user.nama_pengguna ?? '';
                this.form.email = this.user.email ?? '';
                this.form.nomor_telepon = this.user.nomor_telepon ?? '';
            }
        },
        resetForm() {
            this.form.nama_pengguna = this.user?.nama_pengguna ?? '';
            this.form.email = this.user?.email ?? '';
            this.form.nomor_telepon = this.user?.nomor_telepon ?? '';
            this.msg = '';
        },
        async simpan() {
            if (!this.form.nama_pengguna.trim() || !this.form.email.trim()) {
                this.msgOk = false; this.msg = 'Nama dan email wajib diisi.'; return;
            }
            this.saving = true; this.msg = '';
            const res = await PandansariAPI.updateMe({
                nama_pengguna: this.form.nama_pengguna.trim(),
                email: this.form.email.trim(),
                nomor_telepon: this.form.nomor_telepon.trim() || undefined,
            });
            this.saving = false;
            if (res.ok) {
                this.user = res.data;
                PandansariAPI.setUser(res.data);
                this.msgOk = true; this.msg = 'Profil berhasil diperbarui!';
            } else {
                this.msgOk = false;
                const errs = Object.values(res.errors ?? {}).flat();
                this.msg = errs[0] ?? res.message ?? 'Gagal memperbarui profil.';
            }
        },
        async logout() {
            await PandansariAPI.logout();
            PandansariAPI.clearSession();
            window.location.href = '/';
        },
        init() { this.load(); }
     }">

    <!-- Gate: belum login -->
    <div x-show="needLogin" x-cloak class="max-w-md mx-auto mt-16 bg-white rounded-2xl border border-slate-200 p-10 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login untuk melihat profil Anda</p>
        <a href="/login" class="inline-block bg-[#0F1A24] text-white text-xs font-bold px-6 py-3 rounded-xl hover:bg-slate-800 transition">LOGIN SEKARANG</a>
    </div>

    <div x-show="!needLogin" class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-6">
            <a href="/" class="hover:text-black transition">Beranda</a>
            <span>/</span>
            <span class="text-black font-bold">Profil & Biodata Diri</span>
        </nav>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDEBAR: Navigasi Akun (4 Kolom) -->
            <div class="lg:col-span-4 space-y-4">

                <!-- Card Profile Header (dari data login) -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm text-center space-y-2">
                    <div class="w-16 h-16 bg-[#0F1A24] text-white font-bold text-xl rounded-2xl flex items-center justify-center mx-auto shadow-md">
                        <span x-text="initials()">RW</span>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-[#0F1A24]" x-text="user?.nama_pengguna ?? '...'"></h2>
                        <p class="text-xs text-slate-400 font-medium" x-text="user?.email ?? ''"></p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5" x-text="user?.nomor_telepon ?? '—'"></p>
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
                    </a>

                    <a href="/address" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-slate-400 text-sm"></i>
                            <span class="font-semibold">Buku Alamat Pengiriman</span>
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
                        <i class="fa-solid fa-shield-halved text-slate-300 text-xs"></i>
                    </a>

                    <div class="p-4 flex items-center justify-between bg-slate-50/50">
                        <button @click="logout()" type="button" class="text-rose-600 font-bold flex items-center gap-2 hover:underline cursor-pointer">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar Akun</span>
                        </button>
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
                    <p class="text-xs text-slate-400 mt-1" x-show="user?.dibuat_pada">
                        Anggota sejak <span class="font-semibold" x-text="formatTanggal(user?.dibuat_pada)"></span>
                        <span class="ml-2 bg-sky-100 text-[#0369A1] px-2 py-0.5 rounded font-bold uppercase text-[9px]" x-text="user?.peran ?? ''"></span>
                    </p>
                </div>

                <form @submit.prevent="simpan()" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                        <!-- Nama Lengkap Sesuai KTP -->
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                NAMA LENGKAP SESUAI KTP <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text"
                                       x-model="form.nama_pengguna"
                                       :disabled="saving"
                                       class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-60">
                                <i class="fa-regular fa-id-card absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                        <!-- Alamat Surel (Email) -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                    ALAMAT SUREL (EMAIL) <span class="text-red-500">*</span>
                                </label>
                            </div>
                            <div class="relative">
                                <input type="email"
                                       x-model="form.email"
                                       :disabled="saving"
                                       class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-60">
                                <i class="fa-regular fa-envelope absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                        <!-- Nomor WhatsApp / Seluler -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                    NOMOR WHATSAPP / SELULER
                                </label>
                            </div>
                            <div class="relative">
                                <input type="text"
                                       x-model="form.nomor_telepon"
                                       :disabled="saving"
                                       class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-60">
                                <i class="fa-solid fa-mobile-screen absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            </div>
                        </div>

                    </div>

                    <p class="text-[11px] text-slate-400 bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                        <i class="fa-solid fa-circle-info text-sky-500 mr-1"></i>
                        Saat ini backend menyimpan <span class="font-semibold">nama, email, dan nomor telepon</span> saja.
                        Tanggal lahir & jenis kelamin akan menyusul bila kolomnya tersedia di API.
                    </p>

                    <!-- Pesan hasil simpan -->
                    <div x-show="msg" x-cloak
                         :class="msgOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                         class="text-xs font-bold border rounded-xl px-4 py-3" x-text="msg"></div>

                    <!-- Footer Action Buttons & Link Ubah Sandi -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-100">
                        <a href="/security" class="text-xs font-bold text-slate-700 hover:text-black flex items-center gap-1.5 underline underline-offset-4">
                            <i class="fa-solid fa-key text-xs"></i>
                            <span>UBAH KATA SANDI AKUN</span>
                        </a>

                        <div class="flex items-center gap-2 justify-end">
                            <button @click="resetForm()"
                                    type="button"
                                    :disabled="saving"
                                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-6 py-3 rounded-xl transition cursor-pointer disabled:opacity-60">
                                BATAL
                            </button>
                            <button type="submit"
                                    :disabled="saving"
                                    class="bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-6 py-3 rounded-xl transition cursor-pointer shadow-sm disabled:opacity-60">
                                <span x-show="!saving">SIMPAN PERUBAHAN</span>
                                <span x-show="saving"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i>MENYIMPAN...</span>
                            </button>
                        </div>
                    </div>

                </form>

            </div>

        </div>

    </div>
</div>
@endsection
