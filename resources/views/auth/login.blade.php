@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-[85vh] flex items-center justify-center py-12 px-4 sm:px-6 font-['Plus_Jakarta_Sans']">
    <!-- Pembungkus Utama: x-data="loginForm()" dipasang di sini agar mencakup seluruh Form, Alert, & Tombol Google -->
    <div x-data="loginForm()" class="max-w-md w-full space-y-6 bg-white p-8 sm:p-10 rounded-2xl border border-slate-200/80 shadow-sm">

        <!-- Header Section -->
        <div class="text-center space-y-2">
            <div class="w-12 h-12 bg-black text-white rounded-2xl flex items-center justify-center mx-auto shadow-sm">
                <i class="fa-regular fa-gem text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-[#0F1A24] tracking-tight">Masuk ke Akun Anda</h1>
            <p class="text-xs text-slate-500">Nikmati pengalaman berbelanja wastra batik autentik Indonesia</p>
        </div>

        <!-- Banner Alert Error -->
        <template x-if="errorMessage">
            <div role="alert" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span x-text="errorMessage"></span>
            </div>
        </template>

        <!-- Form Login Email + Password -->
        <form @submit.prevent="submit()" class="space-y-4">
            <!-- Email -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
                <div class="relative">
                    <input type="email"
                           x-model="email"
                           required
                           placeholder="contoh: arya.pandansari@gmail.com"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <i class="fa-regular fa-envelope absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700">Kata Sandi</label>
                    <a href="/forgot-password" class="text-xs font-semibold text-sky-600 hover:underline">Lupa Kata Sandi?</a>
                </div>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'"
                           x-model="kata_sandi"
                           required
                           placeholder="••••••••"
                           class="w-full text-xs font-medium border border-slate-200 rounded-xl px-4 py-3 bg-slate-50/60 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 pr-10">
                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-regular" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-600">
                    <input type="checkbox" class="rounded border-slate-300 text-black focus:ring-black w-4 h-4">
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold text-xs py-3.5 rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!loading">MASUK SEKARANG</span>
                <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MEMPROSES...</span>
            </button>
        </form>

        <!-- Divider -->
        <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-slate-100"></div>
            <span class="flex-shrink mx-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">atau</span>
            <div class="flex-grow border-t border-slate-100"></div>
        </div>

        <!-- Tombol Google Login (Terjangkau oleh scope x-data) -->
        <button type="button"
                @click="loginViaGoogle()"
                :disabled="loadingGoogle"
                class="w-full bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2.5 shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
            <svg x-show="!loadingGoogle" class="w-4 h-4" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span x-show="!loadingGoogle">Masuk Akun via Google</span>
            <span x-show="loadingGoogle" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MENGHUBUNGKAN…</span>
        </button>

        <!-- Fallback resmi Google: tampil bila One Tap/prompt tidak bisa ditampilkan -->
        <div x-show="googleFallback" x-cloak class="space-y-2">
            <p class="text-[11px] text-center text-slate-500 leading-relaxed"
               x-text="googleHint || 'Google meminta konfirmasi langsung dari Anda. Pilih akun lewat tombol di bawah.'"></p>
            <div x-ref="googleFallbackButton" class="flex justify-center min-h-[40px]"></div>
        </div>

        <!-- Footer Card -->
        <p class="text-center text-xs text-slate-500 pt-2">
            Belum memiliki akun ?
            <a href="/register" class="font-bold text-[#0369A1] hover:underline">Daftar Anggota Baru</a>
        </p>

    </div>
</div>

<script>
function loginForm() {
    return {
        email: '',
        kata_sandi: '',
        showPassword: false,
        loading: false,
        loadingGoogle: false,
        errorMessage: '',

        /* --- Status Google Identity Services (GIS) --- */
        googleSiap: false,       // google.accounts.id sudah diinisialisasi
        googleFallback: false,   // tampilkan tombol resmi Google bila prompt tidak bisa tampil
        googleHint: '',          // pesan pendamping pada area fallback
        googleTimeout: null,

        init() {
            // Muat script GSI & inisialisasi lebih awal (tidak memblokir render halaman).
            this.siapkanGoogle();
        },

        /* ---------- Google Identity Services (ID Token / JWT) ---------- */
        googleClientId() {
            // Prioritas: VITE_GOOGLE_CLIENT_ID via PandansariAPI; konstanta hanya cadangan.
            const dariEnv = window.PandansariAPI?.googleClientId?.() ?? '';
            return String(dariEnv || '1066530639857-ghnjm2t78aqqgcpar17ilbc3fjl91vus.apps.googleusercontent.com').trim();
        },

        /**
         * Inisialisasi GIS dengan google.accounts.id.initialize.
         * Callback menerima `response.credential` = ID Token (JWT) yang diverifikasi backend.
         */
        async siapkanGoogle() {
            try {
                if (!window.PandansariAPI || typeof PandansariAPI.siapkanGoogleScript !== 'function') return false;

                const gsi = await PandansariAPI.siapkanGoogleScript();
                const clientId = this.googleClientId();

                if (!gsi?.accounts?.id || !clientId) {
                    this.googleSiap = false;
                    return false;
                }

                gsi.accounts.id.initialize({
                    client_id: clientId,
                    callback: (response) => this.terimaGoogleCredential(response),
                    auto_select: false,
                    cancel_on_tap_outside: true,
                    ux_mode: 'popup',
                    use_fedcm_for_prompt: true,
                });

                this.googleSiap = true;
                return true;
            } catch (err) {
                console.warn('[LoginGoogle] GIS gagal disiapkan:', err?.message || err);
                this.googleSiap = false;
                return false;
            }
        },

        /* ---------- Login email + kata sandi (POST /api/login) ---------- */
        async submit() {
            this.loading = true;
            this.errorMessage = '';

            try {
                const res = await PandansariAPI.login(this.email, this.kata_sandi);

                if (res.ok) {
                    const token = res.data?.data?.token ?? res.data?.token ?? res.data?.access_token;
                    if (token) {
                        PandansariAPI.setToken(token);
                        await PandansariAPI.me();
                        await this.arahkanSetelahLogin();
                    } else {
                        this.errorMessage = 'Login berhasil, tetapi token tidak diterima dari server.';
                    }
                } else {
                    this.errorMessage = res.message || 'Alamat email atau kata sandi salah.';
                }
            } catch (err) {
                console.error('Login submit error:', err);
                this.errorMessage = 'Gagal terhubung ke server. Periksa koneksi Anda.';
            } finally {
                this.loading = false;
            }
        },

        /**
         * Arahkan ke panel sesuai peran: admin → /admin/dashboard, teknisi → /teknisi, lainnya → /.
         * Sebelum redirect, sinkronkan cookie panel HttpOnly agar route /admin & /teknisi lolos.
         */
        async arahkanSetelahLogin(user = null) {
            const api = window.PandansariAPI;
            const u = user ?? api?.getUser?.() ?? {};
            const peran = api?.peranUser?.(u) ?? 'pelanggan';

            if (peran === 'admin' || peran === 'teknisi') {
                if (typeof api?.sinkronPanel === 'function') {
                    const sesi = await api.sinkronPanel();
                    if (!sesi.ok) {
                        this.errorMessage = sesi.message || 'Gagal membuka sesi panel. Silakan login ulang.';
                        return;
                    }
                }
                window.location.href = api?.berandaPeran?.(u)
                    ?? (peran === 'admin' ? '/admin/dashboard' : '/teknisi');
                return;
            }

            // Customer: pastikan cookie panel lama (jika ada) tidak tertinggal.
            if (typeof api?.hapusPanel === 'function') await api.hapusPanel();
            window.location.href = api?.berandaPeran?.(u) ?? '/';
        },

        /* ---------- Login via Google (ID Token → POST /api/auth/google) ---------- */
        async loginViaGoogle() {
            this.errorMessage = '';
            this.googleFallback = false;
            this.googleHint = '';

            if (!this.googleSiap) {
                const siap = await this.siapkanGoogle();
                if (!siap) {
                    this.errorMessage = 'Google Client SDK belum termuat. Periksa koneksi internet Anda, muat ulang halaman, lalu coba lagi.';
                    return;
                }
            }

            this.loadingGoogle = true;

            // Jaring aman: jangan biarkan tombol menggantung bila prompt tak pernah dijawab.
            clearTimeout(this.googleTimeout);
            this.googleTimeout = setTimeout(() => {
                if (!this.loadingGoogle) return;
                this.lepasLoadingGoogle();
                this.tampilkanFallbackGoogle('Google tidak merespons. Silakan pilih akun lewat tombol di bawah.');
            }, 60000);

            try {
                // Prompt GIS memakai sesi Google di browser → mengembalikan ID Token via callback initialize.
                window.google.accounts.id.prompt((notifikasi) => {
                    const alasan = notifikasi.getDismissedReason?.();
                    // Credential sudah diterima lewat callback initialize — tidak perlu fallback.
                    if (alasan === 'credential_returned') return;

                    if (notifikasi.isNotDisplayed?.() === true) {
                        this.lepasLoadingGoogle();
                        this.tampilkanFallbackGoogle('Google One Tap tidak dapat ditampilkan di browser ini.');
                    } else if (notifikasi.isSkippedMoment?.() === true) {
                        this.lepasLoadingGoogle();
                        this.tampilkanFallbackGoogle('Google meminta konfirmasi langsung dari Anda.');
                    } else if (notifikasi.isDismissedMoment?.() === true) {
                        this.lepasLoadingGoogle();
                        this.tampilkanFallbackGoogle('Prompt Google ditutup sebelum akun dipilih.');
                    }
                });
            } catch (err) {
                this.lepasLoadingGoogle();
                console.warn('[LoginGoogle] gagal membuka prompt:', err?.message || err);
                this.tampilkanFallbackGoogle('Prompt Google gagal dibuka. Silakan pilih akun lewat tombol di bawah.');
            }
        },

        /** Callback initialize: `response.credential` berisi ID Token (JWT). */
        async terimaGoogleCredential(response) {
            const idToken = response?.credential;

            if (!idToken) {
                this.lepasLoadingGoogle();
                this.tampilkanFallbackGoogle('Google tidak mengirimkan ID Token. Silakan pilih akun lewat tombol di bawah.');
                return;
            }

            this.googleFallback = false;
            this.googleHint = '';
            await this.kirimGoogleIdToken(idToken);
        },

        /** Kirim ID Token (bukan access token) ke backend. */
        async kirimGoogleIdToken(idToken) {
            this.loadingGoogle = true;
            this.errorMessage = '';

            try {
                const res = await PandansariAPI.loginGoogle(idToken);

                if (res.ok) {
                    const token = res.data?.data?.token ?? res.data?.token ?? res.data?.access_token;
                    if (token) {
                        PandansariAPI.setToken(token);
                        // Pastikan peran user tersedia sebelum menentukan panel tujuan.
                        if (!PandansariAPI.getUser()?.peran) {
                            try { await PandansariAPI.me(); } catch { /* lanjut ke beranda customer */ }
                        }
                        await this.arahkanSetelahLogin();
                    } else {
                        this.errorMessage = 'Login Google berhasil, tetapi token otentikasi tidak diterima dari server.';
                    }
                } else {
                    this.errorMessage = res.message || 'Login via Google gagal. Silakan coba lagi.';
                }
            } catch (err) {
                console.error('Login Google error:', err);
                this.errorMessage = 'Gagal terhubung ke server. Periksa koneksi Anda.';
            } finally {
                this.lepasLoadingGoogle();
            }
        },

        /** Tampilkan area fallback + tombol resmi Google (butuh klik langsung pengguna). */
        tampilkanFallbackGoogle(pesan) {
            this.googleHint = pesan || '';
            this.googleFallback = true;
            this.$nextTick(() => this.renderTombolGoogle());
        },

        renderTombolGoogle() {
            const target = this.$refs.googleFallbackButton;
            if (!target || !window.google?.accounts?.id) return;
            if (target.dataset.rendered === '1') return;   // cukup dirender sekali

            window.google.accounts.id.renderButton(target, {
                type: 'standard',
                theme: 'outline',
                size: 'large',
                text: 'continue_with',
                shape: 'rectangular',
                logo_alignment: 'left',
                width: 320,
            });
            target.dataset.rendered = '1';
        },

        lepasLoadingGoogle() {
            clearTimeout(this.googleTimeout);
            this.googleTimeout = null;
            this.loadingGoogle = false;
        },
    };
}
</script>
@endsection