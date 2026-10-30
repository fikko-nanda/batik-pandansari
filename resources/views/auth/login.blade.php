@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-[85vh] flex items-center justify-center py-12 px-4 sm:px-6 font-['Plus_Jakarta_Sans']">
    <!-- Pembungkus Utama: x-data="loginForm()" mencakup Form, Alert, Divider, & Opsi Login Google -->
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
        @if(session('error'))
            <div role="alert" class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="space-y-4">
            <!-- Form Login Utama -->
            <form method="POST" action="{{ route('login') }}" @submit.prevent="submit()" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alamat Email</label>
                    <input type="email"
                           name="email"
                           x-model="email"
                           required
                           placeholder="nama@email.com"
                           class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kata Sandi</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               name="password"
                               x-model="password"
                               @input="kata_sandi = password"
                               required
                               placeholder="••••••••"
                               class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition cursor-pointer">
                            <i class="fa-regular" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 cursor-pointer text-gray-600 font-medium">
                        <input type="checkbox" name="remember" x-model="remember" class="rounded text-blue-600 focus:ring-blue-500">
                        <span>Ingat Saya</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="text-blue-600 font-semibold hover:underline">Lupa Kata Sandi?</a>
                </div>

                <button type="submit"
                        :disabled="loading"
                        class="w-full py-3 bg-slate-900 text-white font-bold text-xs uppercase rounded-lg hover:bg-slate-800 transition shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!loading">MASUK KE AKUN</span>
                    <span x-show="loading" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MEMPROSES...</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-4 flex items-center justify-center">
                <div class="border-t border-gray-200 w-full"></div>
                <span class="bg-white px-3 text-xs text-gray-400 font-medium absolute">atau masuk dengan</span>
            </div>

            <!-- Opsi Login Google (Alternatif SSO) -->
            <a href="/auth/google"
               class="w-full flex items-center justify-center gap-2 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-semibold text-gray-700 shadow-sm cursor-pointer">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Masuk dengan Google</span>
            </a>

            <!-- Footer Tautan Register -->
            <p class="text-center text-xs text-gray-500 mt-4">
                Belum memiliki akun? <a href="{{ route('register') }}" class="text-blue-600 font-bold hover:underline">Daftar Anggota Baru</a>
            </p>
        </div>

    </div>
</div>

<script>
function loginForm() {
    return {
        email: '',
        password: '',
        kata_sandi: '',
        showPassword: false,
        remember: false,
        loading: false,
        loadingGoogle: false,
        errorMessage: '',

        /* --- Status Google Identity Services (GIS) --- */
        googleSiap: false,
        googleFallback: false,
        googleHint: '',
        googleTimeout: null,

        init() {
            this.siapkanGoogle();
        },

        /* ---------- Google Identity Services (ID Token / JWT) ---------- */
        googleClientId() {
            const dariEnv = window.PandansariAPI?.googleClientId?.() ?? '';
            return String(dariEnv || '1066530639857-ghnjm2t78aqqgcpar17ilbc3fjl91vus.apps.googleusercontent.com').trim();
        },

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
                const pwd = this.password || this.kata_sandi;
                const res = await PandansariAPI.login(this.email, pwd);

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

            if (typeof api?.hapusPanel === 'function') await api.hapusPanel();
            window.location.href = api?.berandaPeran?.(u) ?? '/';
        },

        loginViaGoogle() {
            this.loadingGoogle = true;
            window.location.href = '/auth/google';
        },

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

        async kirimGoogleIdToken(idToken) {
            this.loadingGoogle = true;
            this.errorMessage = '';

            try {
                const res = await PandansariAPI.loginGoogle(idToken);

                if (res.ok) {
                    const token = res.data?.data?.token ?? res.data?.token ?? res.data?.access_token;
                    if (token) {
                        PandansariAPI.setToken(token);
                        if (!PandansariAPI.getUser()?.peran) {
                            try { await PandansariAPI.me(); } catch { }
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

        tampilkanFallbackGoogle(pesan) {
            this.googleHint = pesan || '';
            this.googleFallback = true;
            this.$nextTick(() => this.renderTombolGoogle());
        },

        renderTombolGoogle() {
            const target = this.$refs.googleFallbackButton;
            if (!target || !window.google?.accounts?.id) return;
            if (target.dataset.rendered === '1') return;

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
