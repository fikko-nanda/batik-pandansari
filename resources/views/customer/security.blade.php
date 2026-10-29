@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen pt-6 sm:pt-8 pb-12 sm:pb-16 font-['Plus_Jakarta_Sans']"
     x-data="{
        user: PandansariAPI.getUser(),
        needLogin: false,

        currentPassword: '',
        newPassword: '',
        confirmPassword: '',

        showCurrentPass: false,
        showNewPass: false,
        showConfirmPass: false,

        saving: false,
        msg: '', msgOk: true,

        // Kekuatan sederhana: panjang + huruf besar/kecil + angka + simbol
        strength() {
            let skor = 0;
            const p = this.newPassword;
            if (p.length >= 8) skor++;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) skor++;
            if (/\d/.test(p)) skor++;
            if (/[^A-Za-z0-9]/.test(p)) skor++;
            return skor; // 0..4
        },
        strengthLabel() {
            return ['', 'Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'][this.strength()] ?? '—';
        },
        strengthColor(i) {
            const s = this.strength();
            if (s >= 4) return 'bg-emerald-500';
            if (s === 3) return 'bg-sky-500';
            if (s === 2) return 'bg-amber-500';
            return 'bg-slate-300';
        },
        reqOk(i) {
            const p = this.newPassword;
            if (i === 0) return p.length >= 8;
            if (i === 1) return /[A-Z]/.test(p) && /[a-z]/.test(p);
            if (i === 2) return /\d/.test(p);
            return /[^A-Za-z0-9]/.test(p);
        },

        resetForm() {
            this.currentPassword = '';
            this.newPassword = '';
            this.confirmPassword = '';
            this.msg = '';
        },
        async simpan() {
            this.msg = '';
            if (!this.currentPassword || !this.newPassword || !this.confirmPassword) {
                this.msgOk = false; this.msg = 'Semua kolom kata sandi wajib diisi.'; return;
            }
            if (this.newPassword.length < 8) {
                this.msgOk = false; this.msg = 'Kata sandi baru minimal 8 karakter.'; return;
            }
            if (this.newPassword !== this.confirmPassword) {
                this.msgOk = false; this.msg = 'Konfirmasi kata sandi tidak cocok.'; return;
            }
            this.saving = true;
            const res = await PandansariAPI.updatePassword(this.currentPassword, this.newPassword, this.confirmPassword);
            this.saving = false;
            if (res.ok) {
                this.msgOk = true; this.msg = 'Kata sandi berhasil diperbarui!';
                this.resetForm();
            } else {
                this.msgOk = false;
                const errs = Object.values(res.errors ?? {}).flat();
                this.msg = errs[0] ?? res.message ?? 'Gagal memperbarui kata sandi.';
            }
        },
        async logout() {
            await PandansariAPI.logout();
            PandansariAPI.clearSession();
            window.location.href = '/';
        },
        init() {
            if (!PandansariAPI.getToken()) { this.needLogin = true; return; }
            PandansariAPI.me().then(() => { this.user = PandansariAPI.getUser(); });
        }
     }">

    <!-- Gate: belum login -->
    <div x-show="needLogin" x-cloak class="max-w-md mx-auto mt-16 bg-white rounded-2xl border border-slate-200 p-10 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login untuk mengubah kata sandi</p>
        <a href="/login" class="inline-block bg-[#0F1A24] text-white text-xs font-bold px-6 py-3 rounded-xl hover:bg-slate-800 transition">LOGIN SEKARANG</a>
    </div>

    <div x-show="!needLogin" class="max-w-[1280px] mx-auto px-4 sm:px-6">

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
                        <span x-text="(user?.nama_pengguna ?? '').split(' ').filter(Boolean).slice(0,2).map(w => w[0]).join('').toUpperCase() || '?'">RW</span>
                        <span class="absolute -bottom-1 -right-1 bg-sky-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">
                            <i class="fa-solid fa-check"></i>
                        </span>
                    </div>

                    <div class="pt-1">
                        <h2 class="text-lg font-bold text-[#0F1A24]" x-text="user?.nama_pengguna ?? '...'"></h2>
                        <span class="inline-block bg-[#E0F2FE] text-[#0369A1] text-[9px] font-bold px-3 py-1 rounded-full uppercase tracking-wider mt-1"
                              x-text="user?.peran ?? 'pelanggan'"></span>
                    </div>

                    <div class="bg-slate-50 p-2.5 rounded-xl text-slate-500 text-xs font-medium space-y-0.5 mt-2">
                        <p class="flex items-center justify-center gap-1.5 text-slate-600">
                            <i class="fa-regular fa-envelope text-[11px]"></i> <span x-text="user?.email ?? '—'"></span>
                        </p>
                        <p class="flex items-center justify-center gap-1.5 text-slate-500">
                            <i class="fa-solid fa-mobile-screen text-[11px]"></i> <span x-text="user?.nomor_telepon ?? '—'"></span>
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
                        <button @click="logout()" type="button" class="text-rose-600 font-bold flex items-center gap-2 hover:underline cursor-pointer">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar Akun</span>
                        </button>
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

                <form @submit.prevent="simpan()" class="space-y-5">

                    <!-- Kata Sandi Saat Ini -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Saat Ini</label>
                        <div class="relative">
                            <input :type="showCurrentPass ? 'text' : 'password'"
                                   x-model="currentPassword"
                                   :disabled="saving"
                                   autocomplete="current-password"
                                   class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10 disabled:opacity-60">
                            <button type="button"
                                    @click="showCurrentPass = !showCurrentPass"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer">
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
                                   :disabled="saving"
                                   autocomplete="new-password"
                                   class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10 disabled:opacity-60">
                            <button type="button"
                                    @click="showNewPass = !showNewPass"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                <i class="fa-regular" :class="showNewPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>

                        <!-- Password Strength Bar Indicator (dinamis) -->
                        <div class="bg-slate-50 border border-slate-200/80 p-3 rounded-xl mt-2 space-y-1.5">
                            <div class="flex justify-between items-center text-[11px] font-bold">
                                <span class="text-slate-500">Tingkat Kekuatan:</span>
                                <span class="text-[#0369A1] flex items-center gap-1">
                                    <i class="fa-solid fa-circle text-[8px]"></i> <span x-text="newPassword ? strengthLabel() : '—'"></span>
                                </span>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5">
                                <template x-for="i in 4" :key="i">
                                    <div class="h-1.5 rounded-full transition" :class="newPassword && strength() >= i ? strengthColor(i) : 'bg-slate-200'"></div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Konfirmasi Kata Sandi Baru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative">
                            <input :type="showConfirmPass ? 'text' : 'password'"
                                   x-model="confirmPassword"
                                   :disabled="saving"
                                   autocomplete="new-password"
                                   class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10 disabled:opacity-60">
                            <button type="button"
                                    @click="showConfirmPass = !showConfirmPass"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                                <i class="fa-regular" :class="showConfirmPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Syarat Minimum Kata Sandi Box (ceklis hidup) -->
                    <div class="bg-slate-50 border border-slate-200/80 p-4 rounded-2xl space-y-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 block">SYARAT MINIMUM KATA SANDI:</span>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-medium">
                            <div class="flex items-center gap-2" :class="reqOk(0) ? 'text-emerald-600' : 'text-slate-600'">
                                <i class="fa-regular text-sm" :class="reqOk(0) ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-300'"></i>
                                <span>Minimal 8 karakter</span>
                            </div>
                            <div class="flex items-center gap-2" :class="reqOk(1) ? 'text-emerald-600' : 'text-slate-600'">
                                <i class="fa-regular text-sm" :class="reqOk(1) ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-300'"></i>
                                <span>Kombinasi huruf besar & kecil</span>
                            </div>
                            <div class="flex items-center gap-2" :class="reqOk(2) ? 'text-emerald-600' : 'text-slate-600'">
                                <i class="fa-regular text-sm" :class="reqOk(2) ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-300'"></i>
                                <span>Mengandung minimal satu angka</span>
                            </div>
                            <div class="flex items-center gap-2" :class="reqOk(3) ? 'text-emerald-600' : 'text-slate-600'">
                                <i class="fa-regular text-sm" :class="reqOk(3) ? 'fa-circle-check text-emerald-600' : 'fa-circle text-slate-300'"></i>
                                <span>Mengandung simbol khusus (@, #, $, %)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pesan hasil simpan -->
                    <div x-show="msg" x-cloak
                         :class="msgOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                         class="text-xs font-bold border rounded-xl px-4 py-3" x-text="msg"></div>

                    <!-- Footer Buttons -->
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                :disabled="saving"
                                class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0369A1] text-xs font-bold px-6 py-3.5 rounded-xl transition cursor-pointer shadow-sm disabled:opacity-60">
                            <span x-show="!saving">SIMPAN KATA SANDI</span>
                            <span x-show="saving"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i>MENYIMPAN...</span>
                        </button>

                        <button @click="resetForm()"
                                type="button"
                                :disabled="saving"
                                class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-6 py-3.5 rounded-xl transition cursor-pointer disabled:opacity-60">
                            BATAL
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>
</div>
@endsection
