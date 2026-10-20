@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,
    activeTab: 'profil',

    // === Tab 1: Profil Admin (GET /me, PUT /me, PUT /me/password) ===
    me: null,
    form: { nama_pengguna: '', email: '', nomor_telepon: '' },
    savingProfil: false,
    pwd: { saat_ini: '', baru: '', konfirmasi: '' },
    savingPwd: false,
    strength: 0,
    get pwdStrength() {
        const p = this.pwd.baru;
        let s = 0;
        if (p.length >= 8) s++;
        if (/[A-Z]/.test(p)) s++;
        if (/[0-9]/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        return s;
    },
    pwdLabel() {
        return ['', 'Lemah', 'Sedang', 'Kuat', 'Sangat kuat'][this.pwdStrength] ?? '';
    },

    // === Tab 2: Kategori (GET /categories + POST/PUT/DELETE /admin/categories) ===
    cats: [],
    loadingCat: true,
    catBusy: '',
    newCat: '',
    savingCat: false,
    editId: '',
    editName: '',

    notice: '', noticeOk: true,
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    async loadMe() {
        const res = await PandansariAPI.me();
        if (res.ok) {
            this.me = res.data;
            this.form = {
                nama_pengguna: res.data.nama_pengguna ?? '',
                email: res.data.email ?? '',
                nomor_telepon: res.data.nomor_telepon ?? '',
            };
        } else this.pesan(false, res.message);
    },

    async simpanProfil() {
        if (!this.form.nama_pengguna.trim() || !this.form.email.trim()) {
            this.pesan(false, 'Nama dan email wajib diisi.'); return;
        }
        this.savingProfil = true;
        const res = await PandansariAPI.updateMe({
            nama_pengguna: this.form.nama_pengguna.trim(),
            email: this.form.email.trim(),
            nomor_telepon: this.form.nomor_telepon.trim() || undefined,
        });
        this.savingProfil = false;
        if (res.ok) {
            this.me = res.data;
            localStorage.setItem('pandansari_user', JSON.stringify(res.data));
            this.pesan(true, 'Profil admin berhasil diperbarui.');
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },

    async gantiSandi() {
        const p = this.pwd;
        if (!p.saat_ini || !p.baru || !p.konfirmasi) { this.pesan(false, 'Semua kolom kata sandi wajib diisi.'); return; }
        if (p.baru.length < 8) { this.pesan(false, 'Kata sandi baru minimal 8 karakter.'); return; }
        if (p.baru !== p.konfirmasi) { this.pesan(false, 'Konfirmasi kata sandi tidak cocok.'); return; }
        this.savingPwd = true;
        const res = await PandansariAPI.updatePassword(p.saat_ini, p.baru, p.konfirmasi);
        this.savingPwd = false;
        if (res.ok) {
            this.pwd = { saat_ini: '', baru: '', konfirmasi: '' };
            this.pesan(true, 'Kata sandi berhasil diganti.');
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },

    async loadKategori() {
        this.loadingCat = true;
        const res = await PandansariAPI.categories();
        this.loadingCat = false;
        if (res.ok) this.cats = res.data ?? [];
        else this.pesan(false, res.message);
    },
    async tambahKategori() {
        const nama = this.newCat.trim();
        if (!nama) { this.pesan(false, 'Nama kategori wajib diisi.'); return; }
        this.savingCat = true;
        const res = await PandansariAPI.admin.createCategory({ nama_kategori: nama });
        this.savingCat = false;
        if (res.ok) {
            this.newCat = '';
            this.pesan(true, 'Kategori ' + nama + ' ditambahkan.');
            this.loadKategori();
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },
    mulaiEdit(c) { this.editId = c.id_category; this.editName = c.nama_kategori; },
    batalEdit() { this.editId = ''; this.editName = ''; },
    async simpanEdit(c) {
        const nama = this.editName.trim();
        if (!nama) { this.pesan(false, 'Nama kategori tidak boleh kosong.'); return; }
        this.catBusy = c.id_category;
        const res = await PandansariAPI.admin.updateCategory(c.id_category, { nama_kategori: nama });
        this.catBusy = '';
        if (res.ok) {
            c.nama_kategori = nama;
            this.batalEdit();
            this.pesan(true, 'Kategori diperbarui.');
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },
    async hapusKategori(c) {
        if (!confirm('Nonaktifkan kategori ' + c.nama_kategori + '?')) return;
        this.catBusy = c.id_category;
        const res = await PandansariAPI.admin.deleteCategory(c.id_category);
        this.catBusy = '';
        if (res.ok) {
            this.cats = this.cats.filter(x => x.id_category !== c.id_category);
            this.pesan(true, 'Kategori ' + c.nama_kategori + ' dinonaktifkan.');
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; return; }
        const me = await PandansariAPI.me();
        if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; return; }
        this.loadMe();
        this.loadKategori();
    }
}" class="space-y-6 text-slate-800 font-sans pb-8">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka pengaturan</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
            Pengaturan & Profil Atelier
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            Profil akun admin (PUT /me) dan kelola kategori toko (POST/PUT/DELETE /admin/categories).
        </p>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. TABS -->
    <div class="flex flex-wrap items-center gap-2 p-1.5 bg-slate-100/80 rounded-2xl border border-slate-200/80 w-fit text-xs font-bold">
        <button @click="activeTab = 'profil'"
                :class="activeTab === 'profil' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Profil & Keamanan Admin
        </button>
        <button @click="activeTab = 'kategori'"
                :class="activeTab === 'kategori' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Kelola Kategori
        </button>
        <button @click="activeTab = 'info'"
                :class="activeTab === 'info' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Banner & Galeri
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: PROFIL & KEAMANAN ADMIN             -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'profil'" x-transition class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Profil -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Profil Admin</h2>
                    <span class="text-[10px] text-slate-400 font-bold">GET /me • PUT /me</span>
                </div>
            </div>

            <form @submit.prevent="simpanProfil()" class="space-y-4 text-xs font-medium">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">NAMA PENGGUNA</label>
                    <input type="text" x-model="form.nama_pengguna" required
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">EMAIL</label>
                    <input type="email" x-model="form.email" required
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">NOMOR TELEPON</label>
                    <input type="text" x-model="form.nomor_telepon"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400"
                           placeholder="08xxxxxxxxxx">
                </div>
                <p class="text-[10px] text-slate-400">
                    Peran akun: <strong class="text-slate-600" x-text="me?.peran ?? '…'"></strong> • ID: <span class="font-mono" x-text="(me?.id_user ?? '').slice(0, 13) + '…'"></span>
                </p>
                <button type="submit" :disabled="savingProfil"
                        class="w-full bg-[#0A192F] hover:bg-slate-800 text-white font-extrabold text-xs px-5 py-3 rounded-xl transition cursor-pointer disabled:opacity-60 flex items-center justify-center gap-2">
                    <i class="fa-solid" :class="savingProfil ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="savingProfil ? 'Menyimpan…' : 'SIMPAN PROFIL'"></span>
                </button>
            </form>
        </div>

        <!-- Ganti sandi -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Ganti Kata Sandi</h2>
                    <span class="text-[10px] text-slate-400 font-bold">PUT /me/password</span>
                </div>
            </div>

            <form @submit.prevent="gantiSandi()" class="space-y-4 text-xs font-medium">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">KATA SANDI SAAT INI</label>
                    <input type="password" x-model="pwd.saat_ini" required autocomplete="current-password"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">KATA SANDI BARU (MIN. 8)</label>
                    <input type="password" x-model="pwd.baru" required autocomplete="new-password"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                    <div class="mt-2 flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300"
                                 :class="['bg-rose-400', 'bg-rose-400', 'bg-amber-400', 'bg-emerald-400', 'bg-emerald-500'][pwdStrength]"
                                 :style="'width:' + (pwdStrength * 25) + '%'"></div>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500" x-text="pwd.baru ? pwdLabel() : ''"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">KONFIRMASI KATA SANDI BARU</label>
                    <input type="password" x-model="pwd.konfirmasi" required autocomplete="new-password"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <button type="submit" :disabled="savingPwd"
                        class="w-full bg-sky-100 hover:bg-sky-200 text-sky-900 font-extrabold text-xs px-5 py-3 rounded-xl transition cursor-pointer disabled:opacity-60 flex items-center justify-center gap-2">
                    <i class="fa-solid" :class="savingPwd ? 'fa-circle-notch fa-spin' : 'fa-shield-halved'"></i>
                    <span x-text="savingPwd ? 'Memproses…' : 'GANTI KATA SANDI'"></span>
                </button>
            </form>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB 2: KELOLA KATEGORI                     -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'kategori'" x-transition class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Daftar Kategori</h2>
                        <span class="text-[10px] text-slate-400 font-bold">GET /categories • PUT & DELETE /admin/categories/{id}</span>
                    </div>
                </div>
                <button type="button" @click="loadKategori()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fa-solid fa-rotate text-xs"></i>
                    <span>Muat ulang</span>
                </button>
            </div>

            <!-- Tambah kategori baru -->
            <form @submit.prevent="tambahKategori()" class="flex flex-col sm:flex-row gap-2 text-xs">
                <input type="text" x-model="newCat" placeholder="Nama kategori baru (contoh: Batik Sogan)…"
                       class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold focus:outline-none focus:ring-2 focus:ring-sky-400">
                <button type="submit" :disabled="savingCat"
                        class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold rounded-xl transition shadow-sm cursor-pointer disabled:opacity-60 flex items-center justify-center gap-2">
                    <i class="fa-solid" :class="savingCat ? 'fa-circle-notch fa-spin' : 'fa-plus'"></i>
                    <span>Tambah Kategori</span>
                </button>
            </form>

            <!-- List -->
            <div class="divide-y divide-slate-100">
                <template x-if="loadingCat">
                    <p class="py-6 text-center text-xs text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat kategori…</p>
                </template>
                <template x-if="!loadingCat && !cats.length">
                    <p class="py-6 text-center text-xs text-slate-400 font-semibold">Belum ada kategori.</p>
                </template>

                <template x-for="c in cats" :key="c.id_category">
                    <div class="py-3 flex items-center justify-between gap-3 text-xs">
                        <div x-show="editId !== c.id_category" class="min-w-0">
                            <strong class="text-slate-900 font-extrabold block" x-text="c.nama_kategori"></strong>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="c.id_category"></span>
                        </div>
                        <input x-show="editId === c.id_category" x-model="editName" type="text"
                               class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold focus:outline-none focus:ring-2 focus:ring-sky-400">

                        <div class="flex items-center gap-1.5 shrink-0">
                            <template x-if="editId !== c.id_category">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="mulaiEdit(c)"
                                            class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">Edit</button>
                                    <button type="button" @click="hapusKategori(c)" :disabled="catBusy === c.id_category"
                                            class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-700 transition cursor-pointer disabled:opacity-50">Nonaktifkan</button>
                                </div>
                            </template>
                            <template x-if="editId === c.id_category">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="simpanEdit(c)" :disabled="catBusy === c.id_category"
                                            class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-800 transition cursor-pointer disabled:opacity-50">Simpan</button>
                                    <button type="button" @click="batalEdit()"
                                            class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition cursor-pointer">Batal</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <p class="text-[11px] text-slate-400 font-medium">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Kategori dipakai halaman katalog & produk. Saat ini API hanya mendukung nama kategori (tanpa gambar/banner).
        </p>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: BANNER & GALERI (PLACEHOLDER JUJUR) -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'info'" x-transition>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center space-y-4">
            <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-300 text-2xl mx-auto">
                <i class="fa-regular fa-images"></i>
            </div>
            <h2 class="text-base font-extrabold text-slate-900">Kelola Banner Hero & Galeri Atelier</h2>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                Endpoint API untuk banner hero, logo, dan galeri foto belum tersedia di backend
                (lihat <span class="font-mono text-[11px]">list-api.md</span>). Fitur ini akan diaktifkan
                otomatis begitu endpoint-nya menyediakan CRUD media.
            </p>
            <span class="inline-block px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-extrabold uppercase tracking-wider rounded-lg">
                Menunggu endpoint backend
            </span>
        </div>
    </div>

    </div>

</div>

@endsection
