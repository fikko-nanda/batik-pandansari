@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-[90vh] flex items-center justify-center py-12 px-4 sm:px-6 font-['Plus_Jakarta_Sans']"
     x-data="forgotForm()">

    <!-- Card Utama (responsif: penuh di HP, terikat 448px di desktop) -->
    <div class="max-w-md w-full space-y-6 bg-white p-8 sm:p-10 rounded-2xl border border-slate-200/80 shadow-sm">

        <!-- Header Section -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 bg-black text-white rounded-2xl flex items-center justify-center mx-auto shadow-sm">
                <i class="fa-solid fa-shield-halved text-xl"></i>
            </div>
            <span class="text-[10px] font-bold tracking-wider uppercase text-sky-700 bg-sky-50 px-3 py-1 rounded-full inline-block">PEMULIHAN AKUN AMAN</span>
            <h1 class="text-2xl font-bold text-[#0F1A24] tracking-tight">Lupa Kata Sandi</h1>
            <p class="text-xs text-slate-500">Verifikasi email via kode OTP, lalu buat kata sandi baru Anda</p>
        </div>

        <!-- Indikator Visual Langkah (Step 1 -> 2 -> 3) -->
        <div class="flex items-center justify-center gap-2 text-[10px] font-bold uppercase tracking-wider pt-1">
            <!-- Step 1 -->
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 rounded-full flex items-center justify-center transition shrink-0"
                      :class="step >= 1 ? 'bg-[#0F1A24] text-white shadow' : 'bg-slate-100 text-slate-400 ring-1 ring-slate-200'"
                      x-text="step >= 2 ? '✓' : '1'"></span>
                <span :class="step === 1 ? 'text-slate-900' : (step > 1 ? 'text-slate-500' : 'text-slate-400')" class="whitespace-nowrap">Email</span>
            </div>
            <span class="h-px w-6 sm:w-10 transition" :class="step >= 2 ? 'bg-[#0F1A24]' : 'bg-slate-200'"></span>
            <!-- Step 2 -->
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 rounded-full flex items-center justify-center transition shrink-0"
                      :class="step >= 2 ? 'bg-[#0F1A24] text-white shadow' : 'bg-slate-100 text-slate-400 ring-1 ring-slate-200'"
                      x-text="step >= 3 ? '✓' : '2'"></span>
                <span :class="step === 2 ? 'text-slate-900' : (step > 2 ? 'text-slate-500' : 'text-slate-400')" class="whitespace-nowrap">OTP</span>
            </div>
            <span class="h-px w-6 sm:w-10 transition" :class="step >= 3 ? 'bg-[#0F1A24]' : 'bg-slate-200'"></span>
            <!-- Step 3 -->
            <div class="flex items-center gap-1.5">
                <span class="w-6 h-6 rounded-full flex items-center justify-center transition shrink-0"
                      :class="step >= 3 ? 'bg-[#0F1A24] text-white shadow' : 'bg-slate-100 text-slate-400 ring-1 ring-slate-200'">3</span>
                <span :class="step === 3 ? 'text-slate-900' : 'text-slate-400'" class="whitespace-nowrap">Sandi Baru</span>
            </div>
        </div>

        <!-- Banner Error -->
        <template x-if="errorMessage">
            <div role="alert" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span x-text="errorMessage"></span>
            </div>
        </template>

        <!-- Banner Sukses -->
        <template x-if="successMessage">
            <div role="status" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-regular fa-circle-check mt-0.5"></i>
                <span x-text="successMessage"></span>
            </div>
        </template>

        <!-- ============================================================ -->
        <!-- STEP 1: Input Email (terhubung: POST /forgot-password) -->
        <!-- ============================================================ -->
        <form x-show="step === 1 && !berhasilReset" x-on:submit.prevent="mintaOTP()" class="space-y-4">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email Terdaftar <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="email"
                           x-model="email"
                           required
                           placeholder="nama@domain.com"
                           :class="pesanError('email') ? 'border-rose-300 bg-rose-50/40' : ''"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <i class="fa-regular fa-envelope absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>
                <p x-show="pesanError('email')" x-text="pesanError('email')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
            </div>

            <!-- Submit Step 1 -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!loading">KIRIM KODE OTP</span>
                <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MENGIRIM KODE...</span>
            </button>
        </form>

        <!-- ============================================================ -->
        <!-- STEP 2: Verifikasi OTP (terhubung: POST /forgot-password/verify-otp) -->
        <!-- ============================================================ -->
        <form x-show="step === 2 && !berhasilReset" x-cloak x-on:submit.prevent="verifikasiOTP()" class="space-y-5">

            <!-- Ringkasan tujuan OTP -->
            <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-center">
                <p class="text-xs text-slate-600 leading-relaxed">
                    Masukkan 6 digit kode OTP yang dikirim ke
                    <span class="font-bold text-[#0369A1] block truncate" x-text="email || 'email Anda'"></span>
                </p>
            </div>

            <!-- 6 Kotak OTP (auto-lanjut ketik, backspace mundur, dukungan paste) -->
            <div class="flex justify-center gap-2 sm:gap-2.5">
                <template x-for="i in 6" :key="i">
                    <input type="text"
                           inputmode="numeric"
                           autocomplete="one-time-code"
                           maxlength="1"
                           :value="otpOf(i - 1)"
                           x-on:input="setOtpDigit(i - 1, $event.target.value)"
                           x-on:keydown.backspace="otpBackspace(i - 1)"
                           x-on:paste="otpPaste($event)"
                           :class="pesanError('otp') ? 'border-rose-300 bg-rose-50/40' : ''"
                           class="w-10 h-12 sm:w-12 sm:h-14 text-center text-lg sm:text-xl font-bold text-[#0F1A24] border border-slate-200 rounded-xl bg-slate-50/60 focus:outline-none focus:ring-2 focus:ring-sky-500 transition">
                </template>
            </div>
            <p x-show="pesanError('otp')" x-text="pesanError('otp')" class="text-[10px] font-semibold text-rose-600 text-center"></p>

            <!-- Verifikasi OTP -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!loading">VERIFIKASI OTP</span>
                <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MEMVERIFIKASI...</span>
            </button>

            <!-- Kirim Ulang OTP (hitung mundur 60 detik) -->
            <div class="flex items-center justify-center gap-2 text-[11px] text-slate-500">
                <span x-show="sisikirim > 0">
                    Kirim ulang kode dalam <b class="text-slate-800" x-text="sisikirim">60</b> detik
                </span>
                <button type="button"
                        x-show="sisikirim === 0"
                        :disabled="loadingUlang"
                        x-on:click="kirimUlang()"
                        class="font-bold text-[#0369A1] hover:underline disabled:opacity-60 disabled:no-underline cursor-pointer">
                    <span x-show="!loadingUlang"><i class="fa-solid fa-rotate-right mr-1"></i>KIRIM ULANG OTP</span>
                    <span x-show="loadingUlang"><i class="fa-solid fa-circle-notch fa-spin"></i> MENGIRIM...</span>
                </button>
            </div>

            <!-- Kembali: salah email / pilihan lain -->
            <button type="button"
                    x-on:click="pilihLangkah(1)"
                    class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-3 rounded-xl transition cursor-pointer">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> GANTI EMAIL
            </button>
        </form>

        <!-- ============================================================ -->
        <!-- STEP 3: Password Baru (terhubung: POST /forgot-password/reset) -->
        <!-- ============================================================ -->
        <form x-show="step === 3 && !berhasilReset" x-cloak x-on:submit.prevent="simpanSandiBaru()" class="space-y-4">

            <!-- Password baru -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi Baru <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'"
                           x-model="kata_sandi"
                           required
                           placeholder="Minimal 8 karakter"
                           :class="pesanError('kata_sandi') ? 'border-rose-300 bg-rose-50/40' : ''"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <button type="button"
                            x-on:click="showPass = !showPass"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-regular" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <p x-show="pesanError('kata_sandi')" x-text="pesanError('kata_sandi')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
            </div>

            <!-- Konfirmasi password baru -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi Baru <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input :type="showConfirmPass ? 'text' : 'password'"
                           x-model="kata_sandi_confirmation"
                           required
                           placeholder="Ulangi kata sandi baru"
                           :class="pesanError('kata_sandi_confirmation') ? 'border-rose-300 bg-rose-50/40' : ''"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <button type="button"
                            x-on:click="showConfirmPass = !showConfirmPass"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-regular" :class="showConfirmPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <p x-show="pesanError('kata_sandi_confirmation')" x-text="pesanError('kata_sandi_confirmation')" class="text-[10px] font-semibold text-rose-600 mt-1"></p>
            </div>

            <!-- Submit Step 3 -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!loading">SIMPAN KATA SANDI BARU</span>
                <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MENYIMPAN...</span>
            </button>

            <!-- Kembali: OTP masih valid tapi user ragu -->
            <button type="button"
                    x-on:click="pilihLangkah(2)"
                    class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs py-3 rounded-xl transition cursor-pointer">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> KEMBALI KE VERIFIKASI OTP
            </button>
        </form>

        <!-- Sukses akhir: reset selesai -> otomatis /login setelah 2 detik -->
        <div x-show="berhasilReset" class="text-center pt-2">
            <a href="/login" class="text-xs font-bold text-[#0369A1] hover:underline"><i class="fa-solid fa-arrow-right-to-bracket mr-1"></i>Langsung ke halaman masuk</a>
        </div>

        <!-- Footer Card (link masuk) -->
        <p class="text-center text-xs text-slate-500 pt-2" x-show="!berhasilReset">
            Ternyata sudah ingat kata sandinya?
            <a href="/login" class="font-bold text-[#0369A1] hover:underline">Masuk Ke Akun</a>
        </p>

    </div>
</div>

<script>
/**
 * forgotForm() — seluruh state & logika alur "lupa password" 3 langkah
 * (Email -> Verifikasi OTP -> Kata Sandi Baru) dikeluarkan dari atribut HTML
 * (x-data) ke sini, mengikuti pola registerForm() & loginForm(). Alasannya:
 * objek dengan komentar `//` dan ekspresi panjang di dalam x-data="..." rawan
 * merusak parsing atribut HTML. Pemanggilan di markup cukup x-data="forgotForm()".
 * Script inline (sinkron) ini pasti termuat sebelum Alpine (async module).
 */
function forgotForm() {
    return {
        // ====== Alur 3 Langkah: 1 = Email, 2 = Verifikasi OTP, 3 = Password Baru ======
        step: 1,
        email: '',
        otpArr: ['','','','','',''],   // 6 kotak OTP (string gabungan via get otp)
        kata_sandi: '',
        kata_sandi_confirmation: '',
        showPass: false,
        showConfirmPass: false,
        loading: false,
        loadingUlang: false,
        errorMessage: '',
        successMessage: '',
        resetToken: null,           // token reset dari backend (langkah 2 -> langkah 3)
        fieldErrors: {},            // 422 validasi: { nama_field: ['pesan', ...] }
        berhasilReset: false,       // sukses langkah 3 (tunggu 2 detik -> /login)
        sisikirim: 0,               // hitung mundur tombol Kirim Ulang OTP
        _timerKirim: null,

        // Gabungan 6 digit -> string OTP
        get otp() { return this.otpArr.join(''); },
        otpOf(i) { return this.otpArr[i] ?? ''; },

        // Bantuan navigasi fokus antar kotak OTP: pakai traversal DOM asli
        semuaKotak() { return Array.from(this.$el?.querySelectorAll('input[maxlength]') ?? []); },
        fokusKotak(i) {
            if (i < 0 || i > 5) { this.semuaKotak().at(0)?.focus(); return; }
            this.semuaKotak()[i]?.focus();
        },

        // Ketik 1 digit -> auto-lanjut ke kotak berikutnya; backspace di kotak -> mundur
        setOtpDigit(i, val) {
            const digits = String(val ?? '').replace(/\D/g, '');
            if (!digits) { this.otpArr[i] = ''; return; }
            if (digits.length > 1) {
                const sisa = String(digits).slice(0, this.otpArr.length - i);
                for (let k = 0; k < sisa.length; k++) this.otpArr[i + k] = sisa[k];
                this.fokusKotak(i + sisa.length - 1);
            } else {
                this.otpArr[i] = digits.slice(-1);
                if (i < this.otpArr.length - 1) this.fokusKotak(i + 1);
            }
            if (this.otp.length === this.otpArr.length && !this.otpArr.includes('')) this.fokusKotak(5);
        },
        otpBackspace(i) {
            if (this.otpArr[i]) { this.otpArr[i] = ''; return; }
            if (i > 0) { this.fokusKotak(i - 1); this.otpArr[i - 1] = ''; }
        },
        otpPaste(e) {
            e.preventDefault();
            const digits = (e.clipboardData?.getData('text') ?? '').replace(/\D/g, '').slice(0, this.otpArr.length);
            if (!digits) return;
            for (let i = 0; i < digits.length; i++) this.otpArr[i] = digits[i];
            for (let i = digits.length; i < this.otpArr.length; i++) this.otpArr[i] = '';
            if (digits.length === this.otpArr.length) this.fokusKotak(this.otpArr.length - 1);
            else this.fokusKotak(digits.length);
        },

        pesanError(field) { return this.fieldErrors?.[field]?.[0] ?? ''; },
        bersihkanError() { this.errorMessage = ''; this.fieldErrors = {}; },

        mulaiHitungMundur(dari = 60) {
            this.sisikirim = dari;
            clearInterval(this._timerKirim);
            this._timerKirim = setInterval(() => {
                if (this.sisikirim <= 1) { clearInterval(this._timerKirim); this.sisikirim = 0; }
                else this.sisikirim--;
            }, 1000);
        },

        // Peta error 422 -> banner umum + pesan per field
        tempelErrorOtak(res) {
            if (res.errors && Object.keys(res.errors).length) {
                this.fieldErrors = res.errors;
                const pesanPertama = Object.values(res.errors)[0]?.[0];
                this.errorMessage = pesanPertama ?? res.message;
            } else {
                this.errorMessage = res.message ?? 'Terjadi kesalahan. Coba lagi.';
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /** Pesan bila endpoint belum tersedia di backend (404/405) */
        pesanEndpointBelumAda() {
            return {
                1: 'Fitur lupa password (kirim OTP) belum tersedia di server. Silakan hubungi WhatsApp CS untuk bantuan reset kata sandi.',
                2: 'Fitur verifikasi OTP belum tersedia di server.',
                3: 'Fitur ubah kata sandi belum tersedia di server.',
            }[this.step] ?? 'Fitur belum tersedia di server.';
        },

        // ====== Step 1: minta OTP ke email ======
        async mintaOTP() {
            this.bersihkanError();
            if (!this.email.trim()) { this.errorMessage = 'Masukkan alamat email Anda terlebih dahulu.'; return; }
            this.loading = true;
            const res = await PandansariAPI.forgotSendOtp(this.email.trim());
            this.loading = false;
            console.log('[Tes Lupa] step1 kirim OTP →', res.status, res.ok);
            if (res.ok) {
                this.successMessage = 'Kode OTP telah dikirim ke email Anda';
                this.otpArr = ['','','','','',''];
                this.mulaiHitungMundur(60);
                this.$nextTick(() => this.fokusKotak(0));
                this.pilihLangkah(2);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            if (res.status === 404 || res.status === 405) { this.errorMessage = this.pesanEndpointBelumAda(); return; }
            this.tempelErrorOtak(res);
        },

        // Navigasi langkah (reset banner)
        pilihLangkah(n) {
            this.step = n;
            this.bersihkanError();
            if (n !== 2) clearInterval(this._timerKirim);
            if (n === 1) this.sisikirim = 0;
        },

        // ====== Step 2: verifikasi OTP ======
        async verifikasiOTP() {
            this.bersihkanError();
            if (this.otp.length < 6) { this.errorMessage = 'Masukkan 6 digit kode OTP lengkap.'; return; }
            this.loading = true;
            const res = await PandansariAPI.forgotVerifyOtp(this.email.trim(), this.otp);
            this.loading = false;
            console.log('[Tes Lupa] step2 verifikasi OTP →', res.status, res.ok);
            if (res.ok) {
                // Simpan token reset dari backend (prioritas res.data.reset_token)
                this.resetToken = res.data?.reset_token ?? res.data?.token ?? null;
                console.log('[Tes Lupa] reset_token diterima →', this.resetToken ? 'ada' : 'TIDAK ADA');
                clearInterval(this._timerKirim);
                this.sisikirim = 0;
                this.successMessage = 'Kode OTP terverifikasi - silakan buat kata sandi baru.';
                this.pilihLangkah(3);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            if (res.status === 404 || res.status === 405) { this.errorMessage = this.pesanEndpointBelumAda(); return; }
            this.tempelErrorOtak(res);
        },

        // Kirim Ulang OTP (langkah 2, jeda 60 detik)
        async kirimUlang() {
            if (this.sisikirim > 0 || this.loadingUlang || this.loading) return;
            this.loadingUlang = true; this.bersihkanError();
            const res = await PandansariAPI.forgotResendOtp(this.email.trim());
            this.loadingUlang = false;
            console.log('[Tes Lupa] kirim ulang OTP →', res.status, res.ok);
            if (res.ok) {
                this.successMessage = 'Kode OTP terbaru telah dikirim ke email Anda (' + this.email.trim() + ').';
                this.mulaiHitungMundur(60);
            } else if (res.status === 429) {
                const sisa = Math.ceil(Number(res.retry_after ?? 60)) || 60;
                this.errorMessage = res.message ?? 'Mohon tunggu sebelum mengirim ulang kode OTP.';
                this.mulaiHitungMundur(sisa);
            } else {
                this.errorMessage = res.message ?? 'Gagal mengirim ulang kode OTP.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        // ====== Step 3: password baru ======
        async simpanSandiBaru() {
            this.bersihkanError();
            if (this.kata_sandi.length < 8) { this.errorMessage = 'Kata sandi minimal 8 karakter.'; return; }
            if (this.kata_sandi !== this.kata_sandi_confirmation) { this.errorMessage = 'Konfirmasi kata sandi tidak cocok.'; return; }
            this.loading = true;
            const payload = {
                email: this.email.trim(),
                // kontrak terbaru: reset_token + password + password_confirmation
                reset_token: this.resetToken ?? undefined,
                password: this.kata_sandi,
                password_confirmation: this.kata_sandi_confirmation,
                // alias kontrak lama (diabaikan validator bila tidak divalidasi):
                token: this.resetToken ?? undefined,
                otp: this.otp || undefined,
                kata_sandi: this.kata_sandi,
                kata_sandi_confirmation: this.kata_sandi_confirmation,
            };
            const res = await PandansariAPI.forgotResetPassword(payload);
            this.loading = false;
            console.log('[Tes Lupa] step3 reset password →', res.status, res.ok);
            if (res.ok) {
                this.berhasilReset = true;
                this.successMessage = 'Kata sandi berhasil diubah! Mengarahkan ke halaman masuk...';
                setTimeout(() => { window.location.href = '/login'; }, 2000);
                return;
            }
            if (res.status === 404 || res.status === 405) { this.errorMessage = this.pesanEndpointBelumAda(); return; }
            // OTP kedaluwarsa jika di antara verifikasi -> kembali langkah 2
            if (res.message && /otp|kode/i.test(res.message)) {
                this.errorMessage = res.message + ' Silakan verifikasi kode OTP kembali.';
                this.pilihLangkah(2);
                this.mulaiHitungMundur(60);
                return;
            }
            this.tempelErrorOtak(res);
        },
    };
}
</script>
@endsection
