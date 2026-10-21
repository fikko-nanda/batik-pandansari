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

    // === Tab 3: Banner Hero (GET/POST/PUT/DELETE /admin/cms/hero) ===
    heroes: [],
    loadingHero: true,
    savingHero: false,
    heroDragging: false,
    pendingDeletes: [],
    lastSavedAt: '',

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

    // === Banner Hero ===
    get heroesAktif() { return this.heroes.filter(h => !h._deleted); },

    gambarUrl(h) { return PandansariAPI.imgUrl(h.url_gambar ?? h.gambar ?? ''); },

    normalizeHero(h) {
        const aktif = h.status_aktif === true || h.status_aktif === 1 || h.status_aktif === '1';
        return {
            ...h,
            id_hero: h.id_slide_hero ?? h.id_hero ?? h.id ?? h.id_banner ?? '',
            judul: h.judul ?? '',
            subjudul: h.subjudul ?? '',
            label_badge: h.label_badge ?? '',
            teks_tombol: h.teks_tombol ?? '',
            tautan_tombol: h.tautan_tombol ?? '',
            urutan_tampilan: Number(h.urutan_tampilan ?? 0),
            status_aktif: aktif ? 1 : 0,
            url_gambar: h.url_gambar ?? h.gambar ?? '',
            _isNew: false,
            _dirty: false,
            _deleted: false,
            _file: null,
            _preview: PandansariAPI.imgUrl(h.url_gambar ?? h.gambar ?? ''),
            _dimensi: '',
        };
    },

    async loadHeroes() {
        this.loadingHero = true;
        const res = await PandansariAPI.admin.heroes();
        this.loadingHero = false;
        if (!res.ok) {
            this.heroes = [];
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
            return;
        }
        this.heroes.forEach(h => {
            if (h._preview && h._preview.startsWith('blob:')) URL.revokeObjectURL(h._preview);
        });
        const raw = res.data;
        const list = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : []);
        this.heroes = list.map(h => this.normalizeHero(h));
        this.pendingDeletes = [];
        if (!this.lastSavedAt) {
            const stamp = this.heroes.map(h => h.diperbarui_pada).filter(Boolean).sort().pop();
            if (stamp) this.lastSavedAt = this.formatTanggal(stamp);
        }
    },

    nextUrutan() {
        return this.heroesAktif.reduce((max, h) => Math.max(max, Number(h.urutan_tampilan) || 0), 0) + 1;
    },

    draftHero() {
        return {
            id_hero: 'baru-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
            judul: '',
            subjudul: '',
            label_badge: '',
            teks_tombol: '',
            tautan_tombol: '',
            urutan_tampilan: this.nextUrutan(),
            status_aktif: 1,
            url_gambar: '',
            _isNew: true,
            _dirty: true,
            _deleted: false,
            _file: null,
            _preview: '',
            _dimensi: '',
        };
    },

    validasiGambar(file) {
        const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) return 'Format gambar harus JPG, PNG, atau WEBP.';
        if (file.size > 2 * 1024 * 1024) return 'Ukuran gambar maksimal 2 MB.';
        return '';
    },

    pilihDropzone(event) {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (file) this.tambahDariFile(file);
    },

    onDropHero(event) {
        this.heroDragging = false;
        const file = event.dataTransfer?.files?.[0];
        if (file) this.tambahDariFile(file);
    },

    tambahDariFile(file) {
        const err = this.validasiGambar(file);
        if (err) { this.pesan(false, err); return; }
        const h = this.draftHero();
        h._file = file;
        h._preview = URL.createObjectURL(file);
        this.heroes.push(h);
        this.pesan(true, 'Banner baru ditambahkan. Lengkapi datanya lalu simpan.');
    },

    pilihGambar(h, event) {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (!file) return;
        const err = this.validasiGambar(file);
        if (err) { this.pesan(false, err); return; }
        if (h._preview && h._preview.startsWith('blob:')) URL.revokeObjectURL(h._preview);
        h._file = file;
        h._preview = URL.createObjectURL(file);
        h._dimensi = '';
        h._dirty = true;
    },

    hapusHero(h) {
        if (h._isNew) {
            if (h._preview && h._preview.startsWith('blob:')) URL.revokeObjectURL(h._preview);
            this.heroes = this.heroes.filter(x => x !== h);
            return;
        }
        if (!confirm('Hapus banner ' + (h.judul || 'tanpa judul') + '? Perubahan diterapkan saat Simpan Semua.')) return;
        h._deleted = true;
        if (!this.pendingDeletes.includes(h.id_hero)) this.pendingDeletes.push(h.id_hero);
    },

    adaPerubahanHero() {
        return this.pendingDeletes.length > 0 || this.heroes.some(h => h._dirty || h._isNew || h._deleted);
    },

    batalHero() {
        if (this.adaPerubahanHero() && !confirm('Batalkan semua perubahan banner hero?')) return;
        this.lastSavedAt = '';
        this.loadHeroes();
    },

    heroFormData(h) {
        const fd = new FormData();
        ['judul', 'subjudul', 'label_badge', 'teks_tombol', 'tautan_tombol'].forEach(k => {
            const v = String(h[k] ?? '').trim();
            if (v !== '') fd.append(k, v);
        });
        fd.append('urutan_tampilan', String(Number(h.urutan_tampilan) || 0));
        fd.append('status_aktif', h.status_aktif ? '1' : '0');
        if (h._file) fd.append('gambar', h._file);
        return fd;
    },

    _heroError(res) {
        const errs = Object.values(res.errors ?? {}).flat();
        return errs[0] ?? res.message;
    },

    async simpanSemuaHero() {
        const aktif = this.heroesAktif;
        const baru = aktif.filter(h => h._isNew);

        for (const h of aktif) {
            if (!String(h.judul ?? '').trim()) {
                this.pesan(false, 'HEADLINE wajib diisi pada setiap banner.');
                return;
            }
        }
        for (const h of baru) {
            if (!String(h.teks_tombol ?? '').trim() || !String(h.tautan_tombol ?? '').trim()) {
                this.pesan(false, 'LABEL TOMBOL dan LINK BUTTON wajib diisi pada banner baru.');
                return;
            }
            if (!h._file) {
                this.pesan(false, 'Pilih gambar untuk banner baru sebelum menyimpan.');
                return;
            }
        }

        this.savingHero = true;
        const gagal = [];

        for (const id of [...this.pendingDeletes]) {
            const res = await PandansariAPI.admin.deleteHero(id);
            if (res.ok) this.pendingDeletes = this.pendingDeletes.filter(x => x !== id);
            else gagal.push(this._heroError(res));
        }

        for (const h of baru) {
            const res = await PandansariAPI.admin.createHero(this.heroFormData(h));
            if (!res.ok) gagal.push(this._heroError(res));
        }

        for (const h of aktif.filter(x => !x._isNew && x._dirty)) {
            const res = await PandansariAPI.admin.updateHero(h.id_hero, this.heroFormData(h));
            if (!res.ok) gagal.push(this._heroError(res));
        }

        this.savingHero = false;

        if (gagal.length) {
            this.pesan(false, gagal[0]);
            this.loadHeroes();
            return;
        }

        this.lastSavedAt = '';
        await this.loadHeroes();
        this.lastSavedAt = this.formatTanggal(new Date());
        this.pesan(true, 'Semua perubahan banner hero berhasil disimpan.');
    },

    formatTanggal(value) {
        if (!value) return '';
        const d = value instanceof Date ? value : new Date(value);
        if (Number.isNaN(d.getTime())) return '';
        const tgl = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        const jam = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
        return `${tgl}, ${jam} WIB`;
    },

    namaFileHero(h) {
        if (h._file) return h._file.name;
        const p = String(h.url_gambar ?? h.gambar ?? '').split('?')[0];
        return p.split('/').filter(Boolean).pop() || 'Belum ada gambar';
    },

    ukuranFileHero(h) {
        if (!h._file) return '';
        const mb = h._file.size / (1024 * 1024);
        return (mb >= 0.1 ? mb.toFixed(1) : mb.toFixed(2)) + ' MB';
    },

    metaHero(h) {
        const parts = [this.namaFileHero(h)];
        if (h._dimensi) parts.push(h._dimensi);
        const ukuran = this.ukuranFileHero(h);
        if (ukuran) parts.push(ukuran);
        const tanggal = h._isNew ? '' : this.formatTanggal(h.diperbarui_pada ?? h.dibuat_pada);
        if (tanggal) parts.push('Diunggah ' + tanggal);
        return parts.join(' • ');
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; return; }
        const me = await PandansariAPI.me();
        if (me.ok && me.data?.peran !== 'admin') { this.notAdmin = true; return; }
        this.loadMe();
        this.loadKategori();
        this.loadHeroes();
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
            Profil akun admin (PUT /me), kelola kategori toko, dan banner hero beranda (GET/POST/PUT/DELETE /admin/cms/hero).
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
    <!-- TAB 3: KELOLA BANNER HERO                  -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'info'" x-transition class="space-y-6">

        <!-- Judul seksi -->
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Kelola Banner Hero</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Atur gambar dan teks slide yang tampil di beranda toko. Perubahan diterapkan setelah menekan Simpan Semua.
                </p>
            </div>
            <button type="button" @click="loadHeroes()" :disabled="loadingHero"
                    class="px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer disabled:opacity-60 w-fit">
                <i class="fa-solid fa-rotate" :class="loadingHero ? 'fa-spin' : ''"></i>
                <span>Muat ulang</span>
            </button>
        </div>

        <!-- Loading -->
        <div x-show="loadingHero" x-cloak class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center">
            <p class="text-xs text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat banner hero…</p>
        </div>

        <!-- Empty state -->
        <div x-show="!loadingHero && !heroesAktif.length" x-cloak
             class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center space-y-3">
            <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-300 text-2xl mx-auto">
                <i class="fa-regular fa-images"></i>
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Belum ada banner hero</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                Tambahkan banner pertama lewat area unggah di bawah. Banner akan tampil di beranda setelah disimpan dan berstatus aktif.
            </p>
        </div>

        <!-- Daftar kartu banner -->
        <div class="space-y-6">
            <template x-for="(h, index) in heroesAktif" :key="h.id_hero">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

                    <!-- Header kartu -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 bg-[#0A192F] text-sky-300 rounded-xl flex items-center justify-center text-sm shrink-0">
                                <i class="fa-regular fa-image"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] font-black tracking-[0.18em] text-slate-400 uppercase"
                                   x-text="index === 0 ? 'Banner Utama Dashboard' : 'Banner Tambahan'"></p>
                                <p class="text-sm font-extrabold text-slate-900 truncate" x-text="h.judul || 'Banner tanpa judul'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span x-show="h._dirty || h._isNew" class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[9px] font-extrabold uppercase tracking-wider">
                                Belum disimpan
                            </span>
                            <button type="button"
                                    @click="h.status_aktif = h.status_aktif ? 0 : 1; h._dirty = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer"
                                    :class="h.status_aktif ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="h.status_aktif ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span x-text="h.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pt-5">
                        <!-- Preview gambar 1920x600 -->
                        <div class="relative w-full aspect-[1920/600] rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                            <template x-if="h._preview">
                                <img :src="h._preview" :alt="h.judul || 'Banner hero'"
                                     class="w-full h-full object-cover"
                                     @load="h._dimensi = $event.target.naturalWidth + ' × ' + $event.target.naturalHeight + ' px'">
                            </template>
                            <template x-if="!h._preview">
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-2">
                                    <i class="fa-regular fa-image text-2xl"></i>
                                    <span class="text-[11px] font-bold">Belum ada gambar</span>
                                </div>
                            </template>
                            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg bg-black/55 text-white text-[9px] font-extrabold tracking-widest backdrop-blur"
                                  x-text="index === 0 ? 'BANNER 1' : 'BANNER ' + (index + 1)"></span>
                        </div>

                        <!-- Meta info + aksi gambar -->
                        <div class="mt-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <p class="text-[11px] text-slate-500 font-medium break-all" x-text="metaHero(h)"></p>
                            <div class="flex items-center gap-2 shrink-0">
                                <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                    <span>Ganti Banner</span>
                                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp"
                                           @change="pilihGambar(h, $event)">
                                </label>
                                <button type="button" @click="hapusHero(h)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer">
                                    <i class="fa-regular fa-trash-can"></i>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Form teks banner -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-medium">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Headline</label>
                            <input type="text" x-model="h.judul" @input="h._dirty = true"
                                   placeholder="Contoh: Koleksi Batik Pandansari — Edisi Terbatas"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">
                                Teks Pendukung <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span>
                            </label>
                            <textarea rows="2" x-model="h.subjudul" @input="h._dirty = true"
                                      placeholder="Deskripsi singkat yang tampil di bawah headline"
                                      class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">
                                Badge <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span>
                            </label>
                            <input type="text" x-model="h.label_badge" @input="h._dirty = true"
                                   placeholder="Contoh: EDISI TERBATAS"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Label Tombol</label>
                            <input type="text" x-model="h.teks_tombol" @input="h._dirty = true"
                                   placeholder="Isi label tombol"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Link Button</label>
                            <input type="text" x-model="h.tautan_tombol" @input="h._dirty = true"
                                   placeholder="/koleksi"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Dropzone tambah banner -->
        <div @dragover.prevent="heroDragging = true"
             @dragleave.prevent="heroDragging = false"
             @drop.prevent="onDropHero($event)"
             @click="$refs.dropHero.click()"
             class="bg-white rounded-2xl border-2 border-dashed p-10 text-center space-y-4 transition cursor-pointer"
             :class="heroDragging ? 'border-sky-400 bg-sky-50/60' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50/60'">
            <input type="file" x-ref="dropHero" class="hidden" accept="image/jpeg,image/png,image/webp"
                   @click.stop @change="pilihDropzone($event)">
            <div class="w-14 h-14 rounded-full bg-sky-50 text-sky-500 border border-sky-100 flex items-center justify-center text-xl mx-auto">
                <i class="fa-solid fa-cloud-arrow-up"></i>
            </div>
            <div class="space-y-1">
                <p class="text-sm font-extrabold text-slate-900">Tarik &amp; lepas file banner di sini</p>
                <p class="text-xs text-slate-500 font-medium">atau klik untuk menambah banner baru</p>
            </div>
            <span class="inline-block px-3 py-1.5 rounded-lg bg-slate-100 text-slate-500 text-[9px] font-extrabold tracking-widest uppercase">
                PNG, JPG, WEBP Maks 2MB (Rekomendasi 1920×600px)
            </span>
        </div>

        <!-- Catatan galeri -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                <i class="fa-regular fa-images"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-slate-900">Galeri Atelier</p>
                <p class="text-xs text-slate-500">Pengelolaan galeri belum tersedia di API. Tab ini khusus banner hero.</p>
            </div>
            <span class="sm:ml-auto inline-block px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-800 text-[9px] font-extrabold uppercase tracking-wider rounded-lg w-fit">
                Menunggu endpoint galeri
            </span>
        </div>

        <!-- Footer aksi sticky -->
        <div class="sticky bottom-0 z-20 -mx-6 sm:-mx-8 px-6 sm:px-8 py-4 bg-[#FAF8F5]/95 backdrop-blur border-t border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-[11px] text-slate-500 font-semibold"
                   x-text="lastSavedAt ? 'Perubahan terakhir disimpan pada ' + lastSavedAt : 'Belum ada perubahan tersimpan.'"></p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="batalHero()" :disabled="savingHero"
                            class="px-5 py-3 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-extrabold hover:bg-slate-50 transition cursor-pointer disabled:opacity-50">
                        Batal
                    </button>
                    <button type="button" @click="simpanSemuaHero()" :disabled="savingHero"
                            class="px-6 py-3 rounded-xl bg-sky-400 hover:bg-sky-500 text-white text-xs font-extrabold shadow-sm transition cursor-pointer disabled:opacity-60 flex items-center gap-2">
                        <i class="fa-solid" :class="savingHero ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="savingHero ? 'Menyimpan…' : 'SIMPAN SEMUA PERUBAHAN'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    </div>

</div>

@endsection
