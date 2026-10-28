@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-[90vh] flex items-center justify-center py-12 px-4 sm:px-6 font-['Plus_Jakarta_Sans']"
     x-data="registerForm()">

    <!-- Card Utama (responsif: penuh lebar di HP, terikat 512px di desktop) -->
    <div class="max-w-lg w-full space-y-6 bg-white p-8 sm:p-10 rounded-2xl border border-slate-200/80 shadow-sm">

        <!-- Header Section -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 bg-black text-white rounded-2xl flex items-center justify-center mx-auto shadow-sm">
                <i class="fa-regular fa-gem text-xl"></i>
            </div>
            <span class="text-[10px] font-bold tracking-wider uppercase text-sky-700 bg-sky-50 px-3 py-1 rounded-full inline-block">KEANGGOTAAN EKSLUSIF</span>
            <h1 class="text-2xl font-bold text-[#0F1A24] tracking-tight">Daftar Akun Pelanggan</h1>
        </div>

        <!-- Indikator Visual Langkah (Step 1 → Step 2) -->
        <div class="flex items-center justify-center gap-2.5 text-[10px] font-bold uppercase tracking-wider pt-1">
            <!-- Step 1 -->
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center transition shrink-0"
                      :class="step === 1 ? 'bg-[#0F1A24] text-white shadow' : 'bg-slate-100 text-slate-400 ring-1 ring-slate-200'">1</span>
                <span :class="step === 1 ? 'text-slate-900' : 'text-slate-400'" class="whitespace-nowrap">Data Diri</span>
            </div>
            <!-- Garis penghubung (terisi bila selesai step 1) -->
            <span class="h-px w-10 sm:w-16 transition" :class="step === 2 ? 'bg-[#0F1A24]' : 'bg-slate-200'"></span>
            <!-- Step 2 -->
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center transition shrink-0"
                      :class="step === 2 ? 'bg-[#0F1A24] text-white shadow' : 'bg-slate-100 text-slate-400 ring-1 ring-slate-200'">2</span>
                <span :class="step === 2 ? 'text-slate-900' : 'text-slate-400'" class="whitespace-nowrap">Kode OTP</span>
            </div>
        </div>

        <!-- Banner Error -->
        <template x-if="errorMessage">
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span x-text="errorMessage"></span>
            </div>
        </template>

        <!-- Banner Sukses (OTP terkirim / verifikasi sukses) -->
        <template x-if="successMessage">
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-regular fa-envelope-circle-check mt-0.5"></i>
                <span x-text="successMessage"></span>
            </div>
        </template>

        <!-- ============================================================ -->
        <!-- STEP 1: Form Data Diri (terhubung: POST /api/register) -->
        <!-- ============================================================ -->
        <form x-show="step === 1 && !akunAktifTanpaOtp" @submit.prevent="kirimStep1()" class="space-y-4" novalidate>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text"
                           x-model="form.nama_pengguna"
                           required
                           placeholder="Raden Arya Wijaya"
                           :class="pesanError('nama_pengguna') ? 'border-rose-300 bg-rose-50/40' : ''"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <i class="fa-regular fa-user absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>
                <p x-show="pesanError('nama_pengguna')" x-text="pesanError('nama_pengguna')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
            </div>

            <!-- Grid Email & Nomor WhatsApp -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="email"
                               x-model="form.email"
                               required
                               placeholder="nama@domain.com"
                               :class="pesanError('email') ? 'border-rose-300 bg-rose-50/40' : ''"
                               class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <i class="fa-regular fa-envelope absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    </div>
                    <p x-show="pesanError('email')" x-text="pesanError('email')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">No. WhatsApp</label>
                    <div class="relative">
                        <input type="tel"
                               inputmode="numeric"
                               pattern="[0-9]*"
                               maxlength="13"
                               x-model="form.nomor_telepon"
                               placeholder="08123456789"
                               :class="pesanError('nomor_telepon') ? 'border-rose-300 bg-rose-50/40' : ''"
                               @input="form.nomor_telepon = $event.target.value.replace(/\D/g, ''); $event.target.value = form.nomor_telepon"
                               @keydown="if(!['0','1','2','3','4','5','6','7','8','9','Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes($event.key) && !$event.ctrlKey && !$event.metaKey) $event.preventDefault()"
                               class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <i class="fa-solid fa-mobile-screen absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    </div>
                    <p x-show="pesanError('nomor_telepon')" x-text="pesanError('nomor_telepon')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           x-model="form.kata_sandi"
                           required
                           placeholder="Minimal 8 karakter"
                           :class="pesanError('kata_sandi') ? 'border-rose-300 bg-rose-50/40' : ''"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <button type="button"
                            @click="showPass = !showPass"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-regular" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <p x-show="pesanError('kata_sandi')" x-text="pesanError('kata_sandi')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input :type="showConfirmPass ? 'text' : 'password'"
                           x-model="form.kata_sandi_confirmation"
                           required
                           placeholder="Ulangi kata sandi"
                           :class="pesanError('kata_sandi_confirmation') ? 'border-rose-300 bg-rose-50/40' : ''"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <button type="button"
                            @click="showConfirmPass = !showConfirmPass"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-regular" :class="showConfirmPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <p x-show="pesanError('kata_sandi_confirmation')" x-text="pesanError('kata_sandi_confirmation')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
            </div>

            <!-- Checkbox Syarat & Ketentuan -->
            <div class="pt-1">
                <label class="flex items-start gap-2.5 cursor-pointer text-xs text-slate-600 leading-normal">
                    <input type="checkbox" x-model="setujuTerms" class="mt-0.5 rounded border-slate-300 text-black focus:ring-black w-4 h-4 shrink-0">
                    <span>Saya menyetujui <a href="/terms" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-800 hover:underline">Ketentuan Layanan</a> &amp; <a href="/privacy" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-800 hover:underline">Kebijakan Privasi</a> Batik Pandansari.</span>
                </label>
            </div>

            <!-- Submit Step 1: Daftar (Register) → OTP dikirim → lanjut verifikasi -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!loading">DAFTAR</span>
                <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MENDAFTARKAN…</span>
            </button>
        </form>

        <!-- Sukses fallback (alur lama): akun aktif tanpa OTP → tombol masuk -->
        <div x-show="akunAktifTanpaOtp">
            <a href="/login" class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm text-center block">
                MASUK KE AKUN →
            </a>
        </div>

        <!-- ============================================================ -->
        <!-- STEP 2: Verifikasi Kode OTP (terhubung: POST /api/otp/verify-registration) -->
        <!-- ============================================================ -->
        <form x-show="step === 2" x-cloak @submit.prevent="kirimStep2()" class="space-y-5">

            <!-- Ringkasan tujuan OTP -->
            <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-center">
                <p class="text-xs text-slate-600 leading-relaxed">
                    Masukkan <span class="font-bold text-slate-900">6 digit kode OTP</span> yang dikirim ke<br>
                    <span class="font-bold text-[#0369A1]" x-text="form.email || 'email Anda'"></span>
                    <i class="fa-regular fa-envelope-circle-check text-emerald-500 ml-1"></i>
                </p>
            </div>

            <!-- Input 6 digit kode OTP (x-model="otp", hanya angka) -->
            <div class="flex justify-center">
                <input type="text"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       maxlength="6"
                       x-ref="otpInput"
                       x-model="otp"
                       @input="otp = $event.target.value.replace(/\D/g, '').slice(0, 6); $event.target.value = otp"
                       :class="pesanError('otp') ? 'border-rose-300 bg-rose-50/40' : ''"
                       placeholder="••••••"
                       class="w-48 sm:w-56 h-14 text-center text-xl sm:text-2xl font-bold tracking-[0.5em] text-[#0F1A24] border border-slate-200 rounded-xl bg-slate-50/60 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
            </div>
            <p x-show="pesanError('otp')" x-text="pesanError('otp')" class="text-[10px] font-semibold text-rose-600 text-center"></p>

            <!-- Verifikasi OTP -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!loading">VERIFIKASI OTP &amp; BUAT AKUN →</span>
                <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MEMVERIFIKASI…</span>
            </button>

            <!-- Kirim Ulang OTP (hitung mundur 60 detik) -->
            <div class="flex items-center justify-center gap-2 text-[11px] text-slate-500">
                <span x-show="sisikirim > 0">
                    Kirim ulang kode dalam <b class="text-slate-800" x-text="sisikirim">60</b> detik
                </span>
                <button type="button"
                        x-show="sisikirim === 0"
                        :disabled="loadingUlang"
                        @click="kirimUlangOTP()"
                        class="font-bold text-[#0369A1] hover:underline disabled:opacity-60 disabled:no-underline cursor-pointer">
                    <span x-show="!loadingUlang"><i class="fa-solid fa-rotate-right mr-1"></i>KIRIM ULANG OTP</span>
                    <span x-show="loadingUlang"><i class="fa-solid fa-circle-notch fa-spin"></i> MENGIRIM</span>
                </button>
            </div>

            <!-- Kembali ke Form (salah memasukkan email / ingin ganti data) -->
            <button type="button"
                    @click="kembaliKeForm()"
                    class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-3 rounded-xl transition cursor-pointer">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> KEMBALI KE FORM SALAH EMAIL?
            </button>
        </form>

        <!-- Footer Card (Login Link) -->
        <p class="text-center text-xs text-slate-500 pt-2">
            Sudah memiliki akun?
            <a href="/login" class="font-bold text-[#0369A1] hover:underline">Masuk Ke Akun</a>
        </p>

    </div>
</div>

<script>
/**
 * registerForm() — seluruh state & logika form registrasi 2 langkah
 * dikeluarkan dari atribut HTML (x-data) ke sini agar tidak ada lagi
 * lagi-an tanda kutip panjang di atribut HTML /-commerce sintaks bocor.
 * Pemanggilan di markup cukup: x-data="registerForm()" + @click="namaMethod()".
 * Termuat sebelum Alpine: script ini inline (sinkron), Alpine hanya dimuat
 * via module (async) jadi eksekusi ini pasti lebih dulu.
 */
function registerForm() {
    return {
        // ====== Alur 2 Langkah: 1 = Form Data Diri, 2 = Verifikasi Kode OTP ======
        step: 1,
        form: { nama_pengguna: '', email: '', kata_sandi: '', kata_sandi_confirmation: '', nomor_telepon: '' },
        otp: '',                             // 6 digit kode OTP (satu input, digits saja)
        showPass: false,
        showConfirmPass: false,
        loading: false,
        loadingUlang: false,
        errorMessage: '',
        successMessage: '',
        fieldErrors: {},                 // 422 validasi: { nama_field: ['pesan', ...] }
        setujuTerms: false,
        akunAktifTanpaOtp: false,        // SUCCESS fallback register langsung (alur lama)
        sisikirim: 0,                    // jeda hitung mundur tombol Kirim Ulang OTP
        _timerKirim: null,

        /* ---------- Banner error ---------- */
        pesanError(field) { return this.fieldErrors?.[field]?.[0] ?? ''; },
        bersihkanError() {
            this.errorMessage = '';
            this.fieldErrors = {};
        },

        /* Jeda despaking tombol Kirim Ulang OTP selama 60 detik */
        mulaiHitungMundur(dari = 60) {
            this.sisikirim = dari;
            clearInterval(this._timerKirim);
            this._timerKirim = setInterval(() => {
                if (this.sisikirim <= 1) { clearInterval(this._timerKirim); this.sisikirim = 0; }
                else this.sisikirim--;
            }, 1000);
        },

        /* Peta error 422 → banner umum + pesan per field */
        tempelErrorRoll(res) {
            if (res.errors && Object.keys(res.errors).length) {
                this.fieldErrors = res.errors;
                const pesanPertama = Object.values(res.errors)[0]?.[0];
                this.errorMessage = pesanPertama ?? res.message;
            } else {
                this.errorMessage = res.message;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /* Validasi ringan client supaya request tidak sia-sia */
        validasiStep1() {
            this.bersihkanError();
            const f = this.form;
            if (!f.nama_pengguna.trim()) { this.errorMessage = 'Nama lengkap wajib diisi.'; return false; }
            if (!f.email.trim()) { this.errorMessage = 'Alamat email wajib diisi.'; return false; }
            if (f.kata_sandi.length < 8) { this.errorMessage = 'Kata sandi minimal 8 karakter.'; return false; }
            if (f.kata_sandi !== f.kata_sandi_confirmation) { this.errorMessage = 'Konfirmasi kata sandi tidak cocok.'; return false; }
            if (!this.setujuTerms) { this.errorMessage = 'Anda harus menyetujui Ketentuan Layanan & Kebijakan Privasi.'; return false; }
            return true;
        },

        /* ====== STEP 1: kirim data diri + request OTP ke email ======
         * Kirim this.form apa adanya (skema API backend: nama_pengguna, email,
         * kata_sandi, kata_sandi_confirmation, [nomor_telepon]) */
        async kirimStep1() {
            if (!this.validasiStep1()) { window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
            this.loading = true;
            this.bersihkanError();
            const res = await PandansariAPI.registerSendOtp(this.form);
            this.loading = false;
            console.log('[Tes Register] step1 kirim data →', res.status, res.ok);

            // Sukses (OK dan tidak dinyatakan gagal eksplisit) → langkah 2 (instruksi OTP)
            if (res.ok && res.data?.success !== false) {
                this.successMessage = 'Kode OTP telah dikirim ke email Anda';
                this.step = 2;
                this.pilihStep2();
                return;
            }

            // Fallback tangguhan: endpoint OTP belum ada di backend (404/405) →
            // jalankan alur register lama (langsung + auto-login) agar halaman tetap hidup.
            if (res.status === 404 || res.status === 405) {
                await this.fallbackRegister();
                return;
            }

            this.tempelErrorRoll(res);
        },

        /* Alur lama: POST /register → auto-login (dipakai bila backend belum punya OTP) */
        async fallbackRegister() {
            this.loading = true;
            const res = await PandansariAPI.register({
                ...this.form,
                nomor_telepon: this.form.nomor_telepon || undefined,
            });
            if (!res.ok) {
                this.loading = false;
                this.tempelErrorRoll(res);
                return;
            }
            // Respons register TIDAK memuat token → auto-login pakai data yang baru diisi
            const lg = await PandansariAPI.login(this.form.email.trim(), this.form.kata_sandi);
            this.loading = false;
            if (lg.ok) { window.location.href = '/'; return; }
            this.akunAktifTanpaOtp = true;
            this.successMessage = 'Akun Anda berhasil didaftarkan dan sudah aktif — silakan masuk menggunakan email dan kata sandi Anda.';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /* Mempersiapkan langkah 2: reset OTP + jeda kirim-ulang 60 detik (spesifikasi) */
        pilihStep2() {
            this.otp = '';
            this.mulaiHitungMundur(60);
            this.$nextTick(() => this.$refs.otpInput?.focus());
        },

        /* ====== STEP 2: verifikasi OTP ====== */
        async kirimStep2() {
            if (this.otp.length < 6) {
                this.bersihkanError();
                this.errorMessage = 'Masukkan 6 digit kode OTP lengkap.';
                return;
            }
            this.loading = true;
            this.bersihkanError();
            const res = await PandansariAPI.registerVerifyOtp(this.form.email.trim(), this.otp);
            this.loading = false;
            console.log('[Tes Register] step2 verifikasi OTP →', res.status, res.ok);

            if (res.ok) {
                // Simpan token Sanctum + data user, lalu auto-redirect ke beranda
                const token = res.data?.token ?? res.data?.data?.token ?? res.data?.access_token;
                const user = res.data?.user ?? res.data?.data?.user ?? null;
                if (token) {
                    PandansariAPI.setToken(token);       // simpan token otentikasi
                    if (user) PandansariAPI.setUser(user);   // simpan data user
                    else await PandansariAPI.me();        // fallback: ambil profil via /me
                    this.successMessage = 'Verifikasi berhasil! Mengarahkan ke beranda…';
                    window.location.href = '/';
                    return;
                }
                // Verifikasi sukses tapi token tidak dikirim → ke halaman login untuk masuk
                this.successMessage = 'Email berhasil diverifikasi! Akun aktif — mengarahkan ke halaman masuk…';
                setTimeout(() => { window.location.href = '/login'; }, 2000);
                return;
            }

            this.tempelErrorRoll(res);
        },

        /* Tombol "Kirim Ulang OTP" — hitung mundur 60 detik;
         * 429 → pakai retry_after dari server bila ada */
        async kirimUlangOTP() {
            if (this.sisikirim > 0 || this.loadingUlang || this.loading) return;
            this.loadingUlang = true;
            this.bersihkanError();
            const res = await PandansariAPI.registerResendOtp(this.form.email.trim());
            this.loadingUlang = false;
            console.log('[Tes Register] kirim ulang OTP →', res.status, res.ok);

            if (res.ok) {
                this.successMessage = 'Kode OTP terbaru telah dikirim ke email Anda (' + this.form.email.trim() + ').';
                this.mulaiHitungMundur(60);
            } else if (res.status === 429) {
                // Backend menolak: masih dalam masa menunggu — pakai sisa detik dari server
                const sisa = Math.ceil(Number(res.retry_after ?? 60)) || 60;
                this.errorMessage = res.message ?? 'Mohon tunggu sebelum mengirim ulang kode OTP.';
                this.mulaiHitungMundur(sisa);
            } else {
                this.errorMessage = res.message ?? 'Gagal mengirim ulang kode OTP.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        /* Tombol "Kembali ke Form" (salah memasukkan email / ingin ganti data) */
        kembaliKeForm() {
            this.step = 1;
            this.bersihkanError();
            this.successMessage = '';
            clearInterval(this._timerKirim);
            this.sisikirim = 0;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    };
}
</script>
@endsection
