@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen pt-6 sm:pt-8 pb-12 sm:pb-16 font-['Plus_Jakarta_Sans']"
     x-data="customerProfile">

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
                        <span x-text="initials()">{{ strtoupper(substr(auth()->user()?->nama_pengguna ?? auth()->user()?->name ?? 'U', 0, 2)) }}</span>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-[#0F1A24]" x-text="user?.nama_pengguna ?? user?.name ?? '{{ auth()->user()?->nama_pengguna ?? auth()->user()?->name ?? '...' }}'">{{ auth()->user()?->nama_pengguna ?? auth()->user()?->name ?? '...' }}</h2>
                        <p class="text-xs text-slate-400 font-medium" x-text="user?.email ?? '{{ auth()->user()?->email ?? '' }}'">{{ auth()->user()?->email ?? '' }}</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5" x-text="user?.nomor_telepon ?? '{{ auth()->user()?->nomor_telepon ?? '—' }}'">{{ auth()->user()?->nomor_telepon ?? '—' }}</p>
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

                <!-- Card Tautkan Akun Google (POST /me/google) -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-2.5">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24]">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>Akun Google</span>
                        <span x-show="user?.google_id" class="ml-auto text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full uppercase">Tertaut</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed" x-text="user?.google_id ? 'Akun Google Anda sudah tertaut. Anda bisa masuk satu klik via Google.' : 'Tautkan akun Google agar bisa masuk satu klik di lain waktu.'"></p>
                    <template x-if="!user?.google_id">
                        <button type="button"
                                @click="tautkanGoogle()"
                                :disabled="googleBusy"
                                class="w-full bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-[11px] font-bold py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <span x-show="!googleBusy">TAUTKAN AKUN GOOGLE</span>
                            <span x-show="googleBusy" class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin"></i> MENGHUBUNGKAN…</span>
                        </button>
                    </template>
                    <p x-show="googleMsg" :class="googleOk ? 'text-emerald-600' : 'text-rose-600'" class="text-[11px] font-semibold" x-text="googleMsg"></p>
                </div>

            </div>

            <!-- RIGHT SIDE: Form Profil & Informasi Akun serta Riwayat Voucher (8 Kolom) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Card Profil & Informasi Akun -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-6">

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
                                           value="{{ auth()->user()?->nama_pengguna ?? auth()->user()?->name ?? '' }}"
                                           :disabled="saving"
                                           placeholder="Nama Lengkap"
                                           class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-60">
                                    <i class="fa-regular fa-id-card absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                </div>
                            </div>

                            <!-- Alamat Surel (Email) -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                        ALAMAT SUREL (EMAIL) <span class="text-[10px] text-slate-400 font-normal lowercase">(terkunci)</span>
                                    </label>
                                </div>
                                <div class="relative">
                                    <input type="email"
                                           x-model="form.email"
                                           value="{{ auth()->user()?->email ?? '' }}"
                                           readonly
                                           disabled
                                           :disabled="true"
                                           tabindex="-1"
                                           class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 text-slate-500 cursor-not-allowed select-none bg-gray-100 focus:outline-none pointer-events-none">
                                    <i class="fa-solid fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
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
                                    <input type="tel"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           maxlength="13"
                                           x-model="form.nomor_telepon"
                                           :disabled="saving"
                                           @input="form.nomor_telepon = $event.target.value.replace(/\D/g, ''); $event.target.value = form.nomor_telepon"
                                           @keydown="if(!['0','1','2','3','4','5','6','7','8','9','Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes($event.key) && !$event.ctrlKey && !$event.metaKey) $event.preventDefault()"
                                           class="w-full text-xs font-semibold border border-slate-200 rounded-xl px-3.5 py-3 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:opacity-60">
                                    <i class="fa-solid fa-mobile-screen absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                </div>
                            </div>

                        </div>

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

                <!-- Card Riwayat Pemakaian Voucher -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-[#0F1A24] flex items-center gap-2">
                            <i class="fa-solid fa-ticket text-sky-600"></i> Riwayat Pemakaian Voucher
                        </h2>
                        <span class="text-[10px] text-slate-400">
                            <span x-text="vkList.length"></span> penggunaan
                        </span>
                    </div>

                    <p x-show="vkMsg" x-cloak class="text-xs text-rose-600 font-semibold" x-text="vkMsg"></p>

                    <div x-show="!vkList.length && !vkMsg" class="text-center py-8">
                        <i class="fa-solid fa-ticket text-3xl text-slate-200 block mb-2"></i>
                        <p class="text-xs font-bold text-[#0F1A24]">Belum ada voucher terpakai</p>
                        <p class="text-[11px] text-slate-400 mt-1">Voucher yang dipakai saat checkout akan tampil di sini.</p>
                    </div>

                    <div class="space-y-2.5" x-show="vkList.length">
                        <template x-for="vk in vkList" :key="vk.id_voucher_usage ?? vk.id_order">
                            <div class="flex items-center justify-between gap-3 border border-slate-100 rounded-xl px-4 py-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-mono font-bold text-sky-800 bg-sky-50 border border-sky-100 rounded px-1.5 py-0.5" x-text="vk.voucher?.kode_voucher ?? 'VOUCHER'"></span>
                                        <span class="text-xs font-bold text-slate-700 truncate" x-text="vk.voucher?.nama_voucher ?? ''"></span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        Tanggal: <span x-text="vkTanggal(vk.digunakan_pada ?? vk.dibuat_pada)"></span>
                                    </p>
                                </div>
                                <div class="text-right shrink-0 space-y-1">
                                    <div class="text-xs font-bold text-[#0F1A24]" x-text="'- ' + PandansariAPI.formatRp(vk.jumlah_diskon)"></div>
                                    <span class="text-[9px] font-bold rounded-full px-2 py-0.5 inline-block" :class="vkWarna(vk.status_penggunaan)" x-text="vkLabel(vk.status_penggunaan)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
    /**
     * Komponen Alpine.js Halaman Profil & Informasi Akun
     * Mengelola data profil, sinkronisasi data user login,
     * penautan akun Google, dan riwayat voucher.
     */
    function customerProfile() {
        const authUserName = @json(auth()->user()?->nama_pengguna ?? auth()->user()?->name ?? '');
        const authUserEmail = @json(auth()->user()?->email ?? '');
        const authUserPhone = @json(auth()->user()?->nomor_telepon ?? '');
        const authUserObj = @json(auth()->user() ?? null);

        const initialUser = (typeof PandansariAPI !== 'undefined' && typeof PandansariAPI.getUser === 'function' ? PandansariAPI.getUser() : null) || authUserObj;

        return {
            user: initialUser,
            needLogin: false,
            form: {
                nama_pengguna: initialUser?.nama_pengguna || initialUser?.name || authUserName || '',
                email: initialUser?.email || authUserEmail || '',
                nomor_telepon: initialUser?.nomor_telepon || authUserPhone || ''
            },
            busy: false,
            saving: false,
            msg: '',
            msgOk: true,

            // === Tautkan Akun Google (POST /me/google) ===
            googleBusy: false,
            googleMsg: '',
            googleOk: false,

            initials() {
                const n = this.user?.nama_pengguna ?? this.user?.name ?? authUserName ?? '';
                return n.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';
            },

            formatTanggal(s) {
                if (!s) return '—';
                try {
                    return new Date(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                } catch {
                    return s;
                }
            },

            async load() {
                const hasToken = typeof PandansariAPI !== 'undefined' && typeof PandansariAPI.getToken === 'function' && PandansariAPI.getToken();
                if (!hasToken && !authUserObj) {
                    this.needLogin = true;
                    return;
                }

                this.busy = true;
                if (hasToken && typeof PandansariAPI.me === 'function') {
                    try {
                        await PandansariAPI.me();
                    } catch (e) {
                        console.warn('[Profile] Error fetching me:', e);
                    }
                }

                this.user = (typeof PandansariAPI !== 'undefined' && typeof PandansariAPI.getUser === 'function' ? PandansariAPI.getUser() : null) || authUserObj;
                this.busy = false;

                if (this.user) {
                    this.form.nama_pengguna = this.user.nama_pengguna ?? this.user.name ?? authUserName ?? this.form.nama_pengguna;
                    this.form.email = this.user.email ?? authUserEmail ?? this.form.email;
                    this.form.nomor_telepon = this.user.nomor_telepon ?? authUserPhone ?? this.form.nomor_telepon;
                }
            },

            resetForm() {
                this.form.nama_pengguna = this.user?.nama_pengguna ?? this.user?.name ?? authUserName ?? '';
                this.form.email = this.user?.email ?? authUserEmail ?? '';
                this.form.nomor_telepon = this.user?.nomor_telepon ?? authUserPhone ?? '';
                this.msg = '';
            },

            async simpan() {
                if (!this.form.nama_pengguna.trim()) {
                    this.msgOk = false;
                    this.msg = 'Nama lengkap wajib diisi.';
                    return;
                }

                this.saving = true;
                this.msg = '';

                const payload = {
                    nama_pengguna: this.form.nama_pengguna.trim(),
                    nomor_telepon: this.form.nomor_telepon ? this.form.nomor_telepon.trim() : undefined,
                };
                if (this.form.email && this.form.email.trim()) {
                    payload.email = this.form.email.trim();
                }

                try {
                    const res = await PandansariAPI.updateMe(payload);
                    if (res && res.ok) {
                        this.user = res.data;
                        PandansariAPI.setUser(res.data);
                        this.msgOk = true;
                        this.msg = 'Profil berhasil diperbarui!';
                    } else {
                        this.msgOk = false;
                        const errs = Object.values(res?.errors ?? {}).flat();
                        this.msg = errs[0] ?? res?.message ?? 'Gagal memperbarui profil.';
                    }
                } catch (e) {
                    console.error('[Profile] Error simpan:', e);
                    this.msgOk = false;
                    this.msg = 'Terjadi kesalahan saat memperbarui profil.';
                } finally {
                    this.saving = false;
                }
            },

            async logout() {
                try {
                    if (typeof PandansariAPI !== 'undefined') {
                        if (typeof PandansariAPI.logout === 'function') await PandansariAPI.logout();
                        if (typeof PandansariAPI.clearSession === 'function') PandansariAPI.clearSession();
                    }
                } catch (e) {
                    console.warn('[Profile] Logout error:', e);
                }
                window.location.href = '/';
            },

            async tautkanGoogle() {
                this.googleBusy = true;
                this.googleMsg = '';
                try {
                    const googleToken = await PandansariAPI.mintaGoogleToken();
                    const res = await PandansariAPI.linkGoogle(googleToken);
                    this.googleBusy = false;
                    if (res && res.ok) {
                        this.googleOk = true;
                        this.googleMsg = 'Akun Google berhasil ditautkan!';
                        if (res.data) {
                            this.user = res.data;
                            PandansariAPI.setUser(res.data);
                        } else {
                            await PandansariAPI.me();
                            this.user = PandansariAPI.getUser();
                        }
                    } else {
                        this.googleOk = false;
                        this.googleMsg = res?.message || 'Gagal menautkan akun Google.';
                    }
                } catch (e) {
                    this.googleBusy = false;
                    this.googleOk = false;
                    const pesan = {
                        GOOGLE_CLIENT_ID_BELUM_DISET: 'Login Google belum dikonfigurasi (VITE_GOOGLE_CLIENT_ID kosong).',
                        GOOGLE_SDK_TIDAK_SIAP: 'Layanan Google gagal dimuat. Periksa koneksi internet Anda, lalu coba lagi.',
                        GOOGLE_POPUP_DITUTUP: 'Popup Google ditutup sebelum akun dipilih. Silakan coba lagi.',
                        GOOGLE_POPUP_TIDAK_TAMPIL: 'Popup Google diblokir browser. Izinkan popup untuk situs ini, lalu coba lagi.',
                        GOOGLE_TOKEN_KOSONG: 'Google tidak mengembalikan token akses. Silakan coba lagi.',
                    }[e?.message];
                    this.googleMsg = pesan || 'Gagal memulai Google. Silakan coba lagi.';
                }
            },

            vkList: [],
            vkMsg: '',
            async muatRiwayatVoucher() {
                try {
                    if (typeof PandansariAPI !== 'undefined' && typeof PandansariAPI.voucherUsagesMe === 'function') {
                        const r = await PandansariAPI.voucherUsagesMe();
                        this.vkList = r && r.ok ? (r.data ?? []) : [];
                        if (r && !r.ok) this.vkMsg = r.message || '';
                    }
                } catch (e) {
                    console.warn('[Profile] Error muatRiwayatVoucher:', e);
                }
            },

            vkTanggal(s) {
                try {
                    return s ? new Date(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '—';
                } catch {
                    return '—';
                }
            },

            vkWarna(st) {
                const s = String(st ?? '').toLowerCase();
                if (['berhasil', 'digunakan', 'dibayar'].includes(s)) return 'bg-emerald-100 text-emerald-700';
                if (['dibatalkan', 'gagal', 'expired', 'kedaluwarsa'].includes(s)) return 'bg-rose-100 text-rose-700';
                return 'bg-amber-100 text-amber-700';
            },

            vkLabel(st) {
                return String(st ?? '').toLowerCase().toUpperCase() || 'MENUNGGU';
            },

            init() {
                this.load();
                this.muatRiwayatVoucher();
            }
        };
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('customerProfile', () => customerProfile());
    });

    if (window.Alpine) {
        Alpine.data('customerProfile', () => customerProfile());
    }
</script>
@endsection
