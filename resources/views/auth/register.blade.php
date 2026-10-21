@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-[90vh] flex items-center justify-center py-12 px-4 sm:px-6 font-['Plus_Jakarta_Sans']"
     x-data="{
         nama_pengguna: '',
         email: '',
         nomor_telepon: '',
         kata_sandi: '',
         kata_sandi_confirmation: '',
         showPass: false,
         showConfirmPass: false,
         loading: false,
         errorMessage: '',
         successMessage: '',
         async submit() {
             this.errorMessage = '';

             // Validasi ulang kata sandi di client (hindari request sia-sia)
             if (this.kata_sandi !== this.kata_sandi_confirmation) {
                 this.errorMessage = 'Konfirmasi kata sandi tidak cocok.';
                 return;
             }

             this.loading = true;

             const payload = {
                 nama_pengguna: this.nama_pengguna,
                 email: this.email,
                 kata_sandi: this.kata_sandi,
                 kata_sandi_confirmation: this.kata_sandi_confirmation,
             };
             // No. WhatsApp hanya dikirim jika diisi (opsional di backend)
             if (this.nomor_telepon) payload.nomor_telepon = this.nomor_telepon;

             const res = await PandansariAPI.register(payload);
             this.loading = false;

             if (res.ok) {
                 // Respons register TIDAK memuat token → auto-login pakai data yang baru saja diisi
                 const lg = await PandansariAPI.login(this.email.trim(), this.kata_sandi);
                 if (lg.ok) {
                     window.location.href = '/';
                     return;
                 }
                 // Kalau auto-login gagal (mis. email perlu verifikasi), suruh login manual
                 this.successMessage = 'Registrasi berhasil! Akun Anda sudah aktif — silakan masuk menggunakan email dan kata sandi Anda.';
                 window.scrollTo({ top: 0, behavior: 'smooth' });
             } else {
                 this.errorMessage = res.message;
             }
         }
     }">

    <div class="max-w-lg w-full space-y-6 bg-white p-8 sm:p-10 rounded-2xl border border-slate-200/80 shadow-sm">

        <!-- Header Section -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 bg-black text-white rounded-2xl flex items-center justify-center mx-auto shadow-sm">
                <i class="fa-regular fa-gem text-xl"></i>
            </div>
            <span class="text-[10px] font-bold tracking-wider uppercase text-sky-700 bg-sky-50 px-3 py-1 rounded-full inline-block">KEANGGOTAAN EKSLUSIF</span>
            <h1 class="text-2xl font-bold text-[#0F1A24] tracking-tight">Daftar Akun Pelanggan</h1>

        </div>

        <!-- Banner Error -->
        <template x-if="errorMessage">
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span x-text="errorMessage"></span>
            </div>
        </template>

        <!-- Banner Sukses (OTP terkirim) -->
        <template x-if="successMessage">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-regular fa-envelope-circle-check mt-0.5"></i>
                <span x-text="successMessage"></span>
            </div>
        </template>

        <!-- Form Register (terhubung: POST /api/register → verifikasi OTP via email) -->
        <div class="space-y-4" x-show="!successMessage">

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text"
                           x-model="nama_pengguna"
                           required
                           placeholder="Raden Arya Wijaya"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <i class="fa-regular fa-user absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- Grid Email & Nomor WhatsApp -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="email"
                               x-model="email"
                               required
                               placeholder="nama@domain.com"
                               class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <i class="fa-regular fa-envelope absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">No. WhatsApp</label>
                    <div class="relative">
                        <input type="text"
                               x-model="nomor_telepon"
                               placeholder="+62 812-3456-7890"
                               class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <i class="fa-solid fa-mobile-screen absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           x-model="kata_sandi"
                           required
                           placeholder="Minimal 8 karakter"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <button type="button"
                            @click="showPass = !showPass"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-regular" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input :type="showConfirmPass ? 'text' : 'password'"
                           x-model="kata_sandi_confirmation"
                           required
                           placeholder="Ulangi kata sandi"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <button type="button"
                            @click="showConfirmPass = !showConfirmPass"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-regular" :class="showConfirmPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <!-- Checkbox Syarat & Ketentuan -->
            <div class="pt-1">
                <label class="flex items-start gap-2.5 cursor-pointer text-xs text-slate-600 leading-normal">
                    <input type="checkbox" required x-ref="agree" class="mt-0.5 rounded border-slate-300 text-black focus:ring-black w-4 h-4 shrink-0">
                    <span>Saya menyetujui <a href="/terms" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-800 hover:underline">Ketentuan Layanan</a> &amp; <a href="/privacy" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-800 hover:underline">Kebijakan Privasi</a> Batik Pandansari.</span>
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div x-show="!successMessage">
            <button type="button"
                    :disabled="loading"
                    @click="if (!$refs.agree.checked) { errorMessage = 'Anda harus menyetujui Ketentuan Layanan & Kebijakan Privasi.'; return; } submit()"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm cursor-pointer mt-2 disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!loading">DAFTAR AKUN SEKARANG →</span>
                <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MEMPROSES…</span>
            </button>
        </div>

        <!-- CTA setelah sukses: cek email secara progresif -->
        <div x-show="successMessage">
            <a href="/login" class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm text-center block">
                MASUK KE AKUN →
            </a>
        </div>

        <!-- Footer Card (Login Link) -->
        <p class="text-center text-xs text-slate-500 pt-2">
            Sudah memiliki akun? 
            <a href="/login" class="font-bold text-[#0369A1] hover:underline">Masuk Ke Akun</a>
        </p>

    </div>
</div>
@endsection