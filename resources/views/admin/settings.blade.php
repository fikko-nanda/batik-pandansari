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

    // === Tab kategori CMS (GET/POST/PUT/DELETE /admin/cms/kategori) ===
    cmsKats: [],
    loadingKat: true,
    savingKat: false,
    pendingKatDeletes: [],
    lastSavedKatAt: '',

    // === Tab 4: Galeri, Alamat & Fasilitas (galeri + profil-usaha + fasilitas) ===
    galeriId: '',
    galeriJudul: '',
    galeriFotos: [],
    galeriNew: [],
    loadingGaleri: true,
    savingGaleri: false,
    pendingGaleriDeletes: [],
    lastSavedGaleriAt: '',
    galeriDragging: false,

    pu: {
        nama_usaha: '',
        deskripsi_singkat: '',
        deskripsi_lengkap: '',
        alamat: '',
        link_google_maps: '',
        email_resmi: '',
        nomor_whatsapp: '',
        nomor_telepon: '',
    },
    puLogoUrl: '',
    puLogoPending: null,
    puLogoPreview: '',
    puSudahAda: false,
    puDirty: false,
    savingPu: false,
    jams: [],

    fasList: [],
    loadingFas: true,
    fasNew: '',
    fasEditId: '',
    fasEditNama: '',
    pendingFasDeletes: [],

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

    _mediaError(res) {
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
            else gagal.push(this._mediaError(res));
        }

        for (const h of baru) {
            const res = await PandansariAPI.admin.createHero(this.heroFormData(h));
            if (!res.ok) gagal.push(this._mediaError(res));
        }

        for (const h of aktif.filter(x => !x._isNew && x._dirty)) {
            const res = await PandansariAPI.admin.updateHero(h.id_hero, this.heroFormData(h));
            if (!res.ok) gagal.push(this._mediaError(res));
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

    // === Kategori CMS (kartu kategori beranda) ===
    get cmsKatsAktif() { return this.cmsKats.filter(k => !k._deleted); },

    katOpsi() {
        const seen = new Set();
        const out = [];
        for (const c of this.cats) {
            const nama = String(c.nama_kategori ?? '').trim();
            if (nama && !seen.has(nama.toLowerCase())) {
                seen.add(nama.toLowerCase());
                out.push({ id_category: c.id_category, nama_kategori: nama });
            }
        }
        return out;
    },

    normalizeKat(k) {
        const aktif = k.status_aktif === true || k.status_aktif === 1 || k.status_aktif === '1';
        return {
            ...k,
            id_kategori_cms: k.id_kategori_cms ?? k.id_kategori ?? k.id ?? '',
            nama_kategori: k.nama_kategori ?? '',
            judul: k.judul ?? '',
            deskripsi: k.deskripsi ?? '',
            tautan_tujuan: k.tautan_tujuan ?? '',
            urutan_tampilan: Number(k.urutan_tampilan ?? 0),
            status_aktif: aktif ? 1 : 0,
            url_gambar: k.url_gambar ?? k.gambar ?? '',
            _isNew: false,
            _dirty: false,
            _deleted: false,
            _file: null,
            _preview: PandansariAPI.imgUrl(k.url_gambar ?? k.gambar ?? ''),
            _dimensi: '',
        };
    },

    async loadCmsKats() {
        this.loadingKat = true;
        const res = await PandansariAPI.admin.cmsKategori();
        this.loadingKat = false;
        if (!res.ok) {
            this.cmsKats = [];
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
            return;
        }
        this.cmsKats.forEach(k => {
            if (k._preview && k._preview.startsWith('blob:')) URL.revokeObjectURL(k._preview);
        });
        const raw = res.data;
        const list = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : []);
        this.cmsKats = list.map(k => this.normalizeKat(k));
        this.pendingKatDeletes = [];
        if (!this.lastSavedKatAt) {
            const stamp = this.cmsKats.map(k => k.diperbarui_pada).filter(Boolean).sort().pop();
            if (stamp) this.lastSavedKatAt = this.formatTanggal(stamp);
        }
    },

    nextKatUrutan() {
        return this.cmsKatsAktif.reduce((max, k) => Math.max(max, Number(k.urutan_tampilan) || 0), 0) + 1;
    },

    draftKat() {
        return {
            id_kategori_cms: 'baru-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
            nama_kategori: '',
            judul: '',
            deskripsi: '',
            tautan_tujuan: '',
            urutan_tampilan: this.nextKatUrutan(),
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

    tambahKat() {
        this.cmsKats.push(this.draftKat());
        this.pesan(true, 'Kartu kategori baru ditambahkan. Pilih kategori, isi detail, lalu simpan.');
    },

    pilihNamaKat(k, event) {
        k.nama_kategori = event.target.value;
        k.judul = k.nama_kategori;
        k._dirty = true;
    },

    hapusKat(k) {
        if (k._isNew) {
            if (k._preview && k._preview.startsWith('blob:')) URL.revokeObjectURL(k._preview);
            this.cmsKats = this.cmsKats.filter(x => x !== k);
            return;
        }
        if (!String(k.id_kategori_cms ?? '').trim()) {
            console.warn('[cms-kategori] item tanpa ID dari backend:', k);
            this.pesan(false, 'ID kategori tidak tersedia dari backend — hapus dibatalkan. Laporkan ke tim backend.');
            return;
        }
        if (!confirm('Hapus kategori ' + (k.nama_kategori || 'tanpa nama') + '? Perubahan diterapkan saat Simpan Semua.')) return;
        k._deleted = true;
        if (!this.pendingKatDeletes.includes(k.id_kategori_cms)) this.pendingKatDeletes.push(k.id_kategori_cms);
    },

    adaPerubahanKat() {
        return this.pendingKatDeletes.length > 0 || this.cmsKats.some(k => k._dirty || k._isNew || k._deleted);
    },

    batalKat() {
        if (this.adaPerubahanKat() && !confirm('Batalkan semua perubahan kategori utama?')) return;
        this.lastSavedKatAt = '';
        this.loadCmsKats();
    },

    katFormData(k) {
        const fd = new FormData();
        ['nama_kategori', 'judul', 'deskripsi', 'tautan_tujuan'].forEach(f => {
            const v = String(k[f] ?? '').trim();
            if (v !== '') fd.append(f, v);
        });
        fd.append('urutan_tampilan', String(Number(k.urutan_tampilan) || 0));
        fd.append('status_aktif', k.status_aktif ? '1' : '0');
        if (k._file) fd.append('gambar', k._file);
        return fd;
    },

    async simpanSemuaKat() {
        const aktif = this.cmsKatsAktif;
        const baru = aktif.filter(k => k._isNew);

        for (const k of aktif) {
            if (!String(k.nama_kategori ?? '').trim()) {
                this.pesan(false, 'Pilih kategori (JUDUL) pada setiap kartu kategori.');
                return;
            }
            if (!String(k.tautan_tujuan ?? '').trim()) {
                this.pesan(false, 'LINK TUJUAN wajib diisi pada kartu ' + (k.nama_kategori || 'tanpa nama') + '.');
                return;
            }
        }
        for (const k of baru) {
            if (!k._file) {
                this.pesan(false, 'Unggah gambar background untuk kartu kategori baru.');
                return;
            }
        }
        const nama = aktif.map(k => String(k.nama_kategori ?? '').trim().toLowerCase());
        if (new Set(nama).size !== nama.length) {
            this.pesan(false, 'Nama kategori harus unik — ada kartu dengan pilihan kategori yang sama.');
            return;
        }

        this.savingKat = true;
        const gagal = [];

        for (const id of [...this.pendingKatDeletes]) {
            if (!String(id ?? '').trim()) {
                this.pendingKatDeletes = this.pendingKatDeletes.filter(x => x !== id);
                continue;
            }
            const res = await PandansariAPI.admin.deleteCmsKategori(id);
            if (res.ok) this.pendingKatDeletes = this.pendingKatDeletes.filter(x => x !== id);
            else gagal.push(this._mediaError(res));
        }

        for (const k of baru) {
            const res = await PandansariAPI.admin.createCmsKategori(this.katFormData(k));
            if (!res.ok) gagal.push(this._mediaError(res));
        }

        for (const k of aktif.filter(x => !x._isNew && x._dirty)) {
            const id = String(k.id_kategori_cms ?? '').trim();
            if (!id) {
                console.warn('[cms-kategori] item tanpa ID dari backend:', k);
                gagal.push('ID kategori ' + (k.nama_kategori || 'tanpa nama') + ' tidak tersedia dari backend. Laporkan ke tim backend.');
                continue;
            }
            const res = await PandansariAPI.admin.updateCmsKategori(id, this.katFormData(k));
            if (!res.ok) gagal.push(this._mediaError(res));
        }

        this.savingKat = false;

        if (gagal.length) {
            this.pesan(false, gagal[0]);
            this.loadCmsKats();
            return;
        }

        this.lastSavedKatAt = '';
        await this.loadCmsKats();
        this.lastSavedKatAt = this.formatTanggal(new Date());
        this.pesan(true, 'Semua perubahan kategori utama berhasil disimpan.');
    },

    formatTanggal(value) {
        if (!value) return '';
        const d = value instanceof Date ? value : new Date(value);
        if (Number.isNaN(d.getTime())) return '';
        const tgl = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        const jam = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');
        return `${tgl}, ${jam} WIB`;
    },

    namaFileMedia(h) {
        if (h._file) return h._file.name;
        const p = String(h.url_gambar ?? h.gambar ?? '').split('?')[0];
        return p.split('/').filter(Boolean).pop() || 'Belum ada gambar';
    },

    ukuranFileMedia(h) {
        if (!h._file) return '';
        const mb = h._file.size / (1024 * 1024);
        return (mb >= 0.1 ? mb.toFixed(1) : mb.toFixed(2)) + ' MB';
    },

    metaMedia(h) {
        const parts = [this.namaFileMedia(h)];
        if (h._dimensi) parts.push(h._dimensi);
        const ukuran = this.ukuranFileMedia(h);
        if (ukuran) parts.push(ukuran);
        const tanggal = h._isNew ? '' : this.formatTanggal(h.diperbarui_pada ?? h.dibuat_pada);
        if (tanggal) parts.push('Diunggah ' + tanggal);
        return parts.join(' • ');
    },

    // === Galeri Atelier (GET/PUT /admin/cms/galeri + POST/PUT/DELETE gambar) ===
    baseJams() {
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']
            .map(h => ({ hari: h, buka: '', tutup: '', tutupHari: false }));
    },

    normalizeGaleriFoto(p) {
        return {
            ...p,
            id_foto: String(p.id_gambar_galeri ?? p.id_gambar ?? p.id ?? ''),
            alt: p.teks_alternatif ?? p.caption ?? '',
            url: PandansariAPI.imgUrl(p.url_gambar ?? p.gambar ?? ''),
            _dirty: false,
            _deleted: false,
            _file: null,
            _preview: '',
            _dimensi: '',
        };
    },

    async loadGaleri() {
        this.loadingGaleri = true;
        const res = await PandansariAPI.admin.cmsGaleri();
        this.loadingGaleri = false;
        if (!res.ok) {
            this.galeriId = ''; this.galeriJudul = ''; this.galeriFotos = []; this.galeriNew = [];
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
            return;
        }
        const d = res.data;
        if (!d) {
            this.galeriId = ''; this.galeriJudul = ''; this.galeriFotos = []; this.galeriNew = [];
            this.pendingGaleriDeletes = [];
            return;
        }
        this.galeriId = String(d.id_galeri ?? d.id ?? '');
        this.galeriJudul = d.judul ?? '';
        const raw = Array.isArray(d.gambar) ? d.gambar : (Array.isArray(d.foto) ? d.foto : (Array.isArray(d.data) ? d.data : []));
        this.galeriFotos = raw.map(p => this.normalizeGaleriFoto(p));
        this.galeriNew = [];
        this.pendingGaleriDeletes = [];
    },

    galeriBadge() {
        return (this.galeriFotos.length + this.galeriNew.length) + ' FOTO';
    },

    galeriFotoPreview(f) {
        return f._file ? f._preview : f.url;
    },

    metaGaleriFoto(f) {
        const parts = [];
        if (f._file) {
            parts.push(f._file.name);
            const mb = f._file.size / (1024 * 1024);
            parts.push((mb >= 0.1 ? mb.toFixed(1) : mb.toFixed(2)) + ' MB');
        } else {
            const p = String(f.url).split('?')[0];
            parts.push(p.split('/').filter(Boolean).pop() || 'foto-galeri');
        }
        if (f._dimensi) parts.push(f._dimensi);
        const tanggal = f._isNew ? '' : this.formatTanggal(f.diperbarui_pada ?? f.dibuat_pada);
        if (tanggal) parts.push('Terunggah ' + tanggal);
        return parts.join(' • ');
    },

    pilihGaleriBaru(files) {
        const list = Array.from(files ?? []).map(fl => {
            if (fl.size > 2 * 1024 * 1024) {
                this.pesan(false, fl.name + ' melebihi 2 MB dan dilewati.');
                return null;
            }
            return { _key: 'g-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7), _file: fl, _preview: URL.createObjectURL(fl), _dimensi: '' };
        }).filter(Boolean);
        if (!list.length) return;
        this.galeriNew.push(...list);
        this.pesan(true, list.length + ' foto ditambahkan. Terapkan dengan Simpan Semua.');
    },

    gantiGaleri(f, event) {
        const fl = event.target.files?.[0];
        event.target.value = '';
        if (!fl) return;
        if (fl.size > 2 * 1024 * 1024) { this.pesan(false, fl.name + ' melebihi 2 MB.'); return; }
        if (f._preview && f._preview.startsWith('blob:')) URL.revokeObjectURL(f._preview);
        f._file = fl;
        f._preview = URL.createObjectURL(fl);
        f._dimensi = '';
        f._dirty = true;
    },

    hapusGaleri(f) {
        if (f._file && f._preview && f._preview.startsWith('blob:')) URL.revokeObjectURL(f._preview);
        if (f._isNew || !f.id_foto) {
            this.galeriNew = this.galeriNew.filter(x => x._key !== f._key);
            return;
        }
        if (!confirm('Hapus foto ini dari galeri? Perubahan diterapkan saat Simpan Semua.')) {
            if (!f._dirty) { f._file = null; f._preview = ''; f._dimensi = ''; f._dirty = false; }
            return;
        }
        f._deleted = true;
        if (!this.pendingGaleriDeletes.includes(f.id_foto)) this.pendingGaleriDeletes.push(f.id_foto);
    },

    // === Profil usaha (GET/PUT /admin/cms/profil-usaha) ===
    async loadProfilUsaha() {
        const res = await PandansariAPI.admin.profilUsaha();
        if (!res.ok) {
            const errs = Object.values(res.errors ?? {}).flat();
            if (String(res.message ?? '').toLowerCase().includes('unauthenticate')) return;
            this.pesan(false, errs[0] ?? res.message);
            this.jams = this.baseJams();
            return;
        }
        const d = res.data;
        if (!d) {
            this.puSudahAda = false;
            this.pu = {
                nama_usaha: '',
                deskripsi_singkat: '',
                deskripsi_lengkap: '',
                alamat: '',
                link_google_maps: '',
                email_resmi: '',
                nomor_whatsapp: '',
                nomor_telepon: '',
            };
            this.puLogoUrl = ''; this.puLogoPending = null; this.puLogoPreview = '';
            this.jams = this.baseJams();
            return;
        }
        this.puSudahAda = true;
        this.pu = {
            nama_usaha: d.nama_usaha ?? '',
            deskripsi_singkat: d.deskripsi_singkat ?? '',
            deskripsi_lengkap: d.deskripsi_lengkap ?? '',
            alamat: d.alamat_lengkap ?? d.alamat ?? d.alamat_usaha ?? '',
            link_google_maps: d.tautan_google_maps ?? d.link_google_maps ?? d.url_google_maps ?? '',
            email_resmi: d.email_resmi ?? '',
            nomor_whatsapp: d.nomor_whatsapp ?? '',
            nomor_telepon: d.nomor_telepon ?? '',
        };
        this.puLogoUrl = PandansariAPI.imgUrl(d.url_logo ?? d.logo ?? '');
        this.puLogoPending = null;
        this.puLogoPreview = '';
        const j = Array.isArray(d.jam_operasional) ? d.jam_operasional : [];
        this.jams = this.baseJams().map(h => {
            const rec = j.find(x => String(x.hari ?? '').trim().toLowerCase() === h.hari.toLowerCase());
            const tutupRec = rec && (rec.tutup === true || rec.tutup === 'true' || rec.tutup === 1 || rec.tutup === '1' || (!rec.jam_buka && !rec.jam_tutup));
            return { hari: h.hari, buka: String(rec?.jam_buka ?? ''), tutup: String(rec?.jam_tutup ?? ''), tutupHari: !!tutupRec };
        });
    },

    tandaJam(jam) {
        if (!jam) return '';
        const m = String(jam).match(/^(\d{1,2}):(\d{0,2})/);
        if (!m) return '';
        return String(m[1].padStart(2, '0')) + ':' + String(m[2].padEnd(2, '0'));
    },

    puPayload() {
        const p = this.pu;
        const payload = { nama_usaha: String(p.nama_usaha ?? '').trim() || 'Batik Pandansari' };
        const ds = String(p.deskripsi_singkat ?? '').trim();
        if (ds) payload.deskripsi_singkat = ds;
        const dl = String(p.deskripsi_lengkap ?? '').trim();
        if (dl) payload.deskripsi_lengkap = dl;
        const alamat = String(p.alamat ?? '').trim();
        if (alamat) payload.alamat_lengkap = alamat;
        const maps = String(p.link_google_maps ?? '').trim();
        if (maps) payload.tautan_google_maps = maps;
        const email = String(p.email_resmi ?? '').trim();
        if (email) payload.email_resmi = email;
        const wa = String(p.nomor_whatsapp ?? '').trim().replace(/[\s-]/g, '');
        if (wa) payload.nomor_whatsapp = wa;
        const telp = String(p.nomor_telepon ?? '').trim().replace(/[\s-]/g, '');
        if (telp) payload.nomor_telepon = telp;
        payload.jam_operasional = this.jams.map(j =>
            j.tutupHari
                ? { hari: j.hari, tutup: true }
                : { hari: j.hari, jam_buka: this.tandaJam(j.buka), jam_tutup: this.tandaJam(j.tutup) }
        );
        return payload;
    },

    gantiPuLogo(event) {
        const fl = event.target.files?.[0];
        event.target.value = '';
        if (!fl) return;
        if (fl.size > 2 * 1024 * 1024) {
            this.pesan(false, fl.name + ' melebihi 2 MB. Logo tidak diganti.');
            return;
        }
        if (this.puLogoPreview && this.puLogoPreview.startsWith('blob:')) URL.revokeObjectURL(this.puLogoPreview);
        this.puLogoPending = fl;
        this.puLogoPreview = URL.createObjectURL(fl);
        this.puDirty = true;
    },

    batalkanPuLogo() {
        if (this.puLogoPreview && this.puLogoPreview.startsWith('blob:')) URL.revokeObjectURL(this.puLogoPreview);
        this.puLogoPending = null;
        this.puLogoPreview = '';
        this.puDirty = true;
    },

    // === Fasilitas (GET/POST/PUT/DELETE /admin/cms/fasilitas) ===
    fasBadge() {
        return this.fasList.filter(f => !f._deleted).length + ' FASILITAS';
    },

    normalizeFas(f) {
        const aktif = f.status_aktif === true || f.status_aktif === 1 || f.status_aktif === '1';
        return {
            ...f,
            id_fasilitas: String(f.id_fasilitas ?? f.id_fasilitas_usaha ?? f.id ?? ''),
            nama_fasilitas: f.nama_fasilitas ?? '',
            _dirty: false,
            _deleted: false,
            _isNew: false,
        };
    },

    async loadFasilitas() {
        this.loadingFas = true;
        const res = await PandansariAPI.admin.fasilitas();
        this.loadingFas = false;
        if (!res.ok) {
            this.fasList = [];
            const errs = Object.values(res.errors ?? {}).flat();
            if (!String(res.message ?? '').toLowerCase().includes('unauthenticate')) this.pesan(false, errs[0] ?? res.message);
            return;
        }
        const raw = res.data;
        const list = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : []);
        this.fasList = list.map(f => this.normalizeFas(f));
        this.pendingFasDeletes = [];
    },

    tambahFas() {
        const nama = this.fasNew.trim();
        if (!nama) { this.pesan(false, 'Nama fasilitas wajib diisi.'); return; }
        if (this.fasList.some(f => !f._deleted && f.nama_fasilitas.toLowerCase() === nama.toLowerCase())) {
            this.pesan(false, 'Fasilitas ' + nama + ' sudah ada di daftar.');
            return;
        }
        this.fasList.push({
            id_fasilitas: 'baru-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
            nama_fasilitas: nama,
            status_aktif: 1,
            _isNew: true, _dirty: true, _deleted: false,
        });
        this.fasNew = '';
        this.pesan(true, 'Fasilitas ' + nama + ' ditambahkan. Terapkan dengan Simpan Semua.');
    },

    mulaiEditFas(f) {
        this.fasEditId = f.id_fasilitas;
        this.fasEditNama = f.nama_fasilitas;
    },

    selesaiEditFas() {
        const f = this.fasList.find(x => x.id_fasilitas === this.fasEditId);
        const nama = this.fasEditNama.trim();
        this.fasEditId = ''; this.fasEditNama = '';
        if (!f || !nama || nama === f.nama_fasilitas) return;
        if (this.fasList.some(x => x !== f && !x._deleted && x.nama_fasilitas.toLowerCase() === nama.toLowerCase())) {
            this.pesan(false, 'Fasilitas ' + nama + ' sudah ada di daftar.');
            return;
        }
        f.nama_fasilitas = nama;
        f._dirty = true;
    },

    hapusFas(f) {
        if (f._isNew || !f.id_fasilitas || f.id_fasilitas.startsWith('baru-')) {
            this.fasList = this.fasList.filter(x => x !== f);
            return;
        }
        if (!confirm('Hapus fasilitas ' + f.nama_fasilitas + '? Perubahan diterapkan saat Simpan Semua.')) return;
        f._deleted = true;
        if (!this.pendingFasDeletes.includes(f.id_fasilitas)) this.pendingFasDeletes.push(f.id_fasilitas);
    },

    // === Simpan Semua (tab galeri) — galeri + profil usaha + fasilitas ===
    validasiGaleri() {
        for (const j of this.jams) {
            if (j.tutupHari) continue;
            const buka = this.tandaJam(j.buka);
            const tut = this.tandaJam(j.tutup);
            if (!buka && !tut) continue;
            if (!buka || !tut) {
                this.pesan(false, 'Jam ' + j.hari + ' belum lengkap — isi jam buka dan jam tutup, atau tandai hari tutup.');
                return false;
            }
        }
        const email = String(this.pu.email_resmi ?? '').trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            this.pesan(false, 'Format Email Resmi tidak valid.');
            return false;
        }
        const cekNomor = (v, label) => {
            const n = String(v ?? '').trim();
            if (!n) return null;
            if (!/^[0-9+()-]{5,25}$/.test(n)) return label + ' hanya boleh berisi angka, tanda +, dan tanda hubung (5-25 karakter).';
            return null;
        };
        const salah = cekNomor(this.pu.nomor_whatsapp, 'Nomor WhatsApp') ?? cekNomor(this.pu.nomor_telepon, 'Nomor Telepon');
        if (salah) {
            this.pesan(false, salah);
            return false;
        }
        return true;
    },

    async simpanSemuaGaleri() {
        if (!this.validasiGaleri()) return;
        const adaJam = this.jams.some(j => !j.tutupHari && (j.buka || j.tutup));
        const adaPu = this.puDirty || this.puLogoPending || (!this.puSudahAda && (String(this.pu.alamat ?? '').trim() || String(this.pu.link_google_maps ?? '').trim()));

        this.savingGaleri = true;
        const gagal = [];

        // 1. staged deletes — foto galeri
        for (const id of [...this.pendingGaleriDeletes]) {
            if (!String(id ?? '').trim()) {
                this.pendingGaleriDeletes = this.pendingGaleriDeletes.filter(x => x !== id);
                continue;
            }
            const res = await PandansariAPI.admin.deleteGaleriFoto(id);
            if (res.ok) this.pendingGaleriDeletes = this.pendingGaleriDeletes.filter(x => x !== id);
            else gagal.push(this._mediaError(res));
        }

        // 2. staged deletes — fasilitas
        for (const id of [...this.pendingFasDeletes]) {
            if (!String(id ?? '').trim() || id.startsWith('baru-')) {
                this.pendingFasDeletes = this.pendingFasDeletes.filter(x => x !== id);
                continue;
            }
            const res = await PandansariAPI.admin.deleteFasilitas(id);
            if (res.ok) this.pendingFasDeletes = this.pendingFasDeletes.filter(x => x !== id);
            else gagal.push(this._mediaError(res));
        }

        // 3. pastikan galeri ada sebelum unggah foto (unggah sebelum galeri ada -> 404)
        let perluGaleri = !!this.galeriNew.length || this.galeriFotos.some(f => f._dirty && f._file && !f._deleted);
        if (perluGaleri && !this.galeriId) {
            const r = await PandansariAPI.admin.updateCmsGaleri({ judul: this.galeriJudul.trim() || 'Galeri Atelier' });
            if (r.ok) {
                const d = r.data;
                this.galeriId = String(d?.id_galeri ?? d?.id ?? this.galeriId);
                this.galeriJudul = d?.judul ?? this.galeriJudul;
            } else {
                gagal.push(this._mediaError(r));
                perluGaleri = false;
            }
        }

        // 4. unggah foto baru + ganti file foto lama
        if (perluGaleri) {
            for (const g of this.galeriNew) {
                const fd = new FormData();
                fd.append('gambar', g._file);
                if (this.galeriJudul.trim()) fd.append('judul', this.galeriJudul.trim());
                const res = await PandansariAPI.admin.uploadGaleriFoto(fd);
                if (!res.ok) gagal.push(this._mediaError(res));
            }
            for (const f of this.galeriFotos.filter(x => !x._deleted && x._dirty && x._file)) {
                const id = String(f.id_foto ?? '').trim();
                if (!id) { gagal.push('ID foto tidak tersedia dari backend untuk kartu ' + (f.alt || 'tanpa keterangan') + '. Laporkan ke tim backend.'); continue; }
                const fd = new FormData();
                fd.append('gambar', f._file);
                if (this.galeriJudul.trim()) fd.append('judul', this.galeriJudul.trim());
                const res = await PandansariAPI.admin.updateGaleriFoto(id, fd);
                if (!res.ok) gagal.push(this._mediaError(res));
            }
        }

        // 6. profil usaha (PUT parsial; JSON, atau multipart saat ganti logo)
        if (adaPu) {
            const payload = this.puPayload();
            let res;
            if (this.puLogoPending) {
                const fd = new FormData();
                Object.entries(payload).forEach(([k, v]) => {
                    if (k === 'jam_operasional') {
                        v.forEach((jam, i) => {
                            fd.append(`jam_operasional[${i}][hari]`, jam.hari);
                            if (jam.tutup) fd.append(`jam_operasional[${i}][tutup]`, '1');
                            else {
                                fd.append(`jam_operasional[${i}][jam_buka]`, jam.jam_buka || '');
                                fd.append(`jam_operasional[${i}][jam_tutup]`, jam.jam_tutup || '');
                            }
                        });
                    } else fd.append(k, String(v));
                });
                fd.append('logo', this.puLogoPending);
                res = await PandansariAPI.admin.updateProfilUsaha(fd);
            } else res = await PandansariAPI.admin.updateProfilUsaha(payload);
            if (!res.ok) gagal.push(this._mediaError(res));
        }

        // 7. fasilitas — baru
        for (const f of this.fasList.filter(x => x._isNew && !x._deleted)) {
            const res = await PandansariAPI.admin.createFasilitas({ nama_fasilitas: f.nama_fasilitas.trim(), status_aktif: '1' });
            if (!res.ok) gagal.push(this._mediaError(res));
        }
        // 8. fasilitas — ubah nama
        for (const f of this.fasList.filter(x => !x._isNew && !x._deleted && x._dirty)) {
            const id = String(f.id_fasilitas ?? '').trim();
            if (!id) { gagal.push('ID fasilitas tidak tersedia dari backend untuk ' + f.nama_fasilitas + '. Laporkan ke tim backend.'); continue; }
            const res = await PandansariAPI.admin.updateFasilitas(id, { nama_fasilitas: f.nama_fasilitas.trim(), status_aktif: '1' });
            if (!res.ok) gagal.push(this._mediaError(res));
        }

        this.savingGaleri = false;

        if (gagal.length) {
            this.pesan(false, gagal[0]);
            await Promise.all([this.loadGaleri(), this.loadProfilUsaha(), this.loadFasilitas()]);
            return;
        }

        await Promise.all([this.loadGaleri(), this.loadProfilUsaha(), this.loadFasilitas()]);
        this.lastSavedGaleriAt = this.formatTanggal(new Date());
        this.psuDirtySet(false);
        this.pesan(true, 'Semua perubahan galeri, alamat, dan fasilitas berhasil disimpan.');
    },

    psuDirtySet(v) {
        this.puDirty = v;
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; return; }
        const me = await PandansariAPI.me();
        if (!me.ok || me.data?.peran !== 'admin') { this.notAdmin = true; return; }
        this.loadMe();
        this.loadKategori();
        this.loadHeroes();
        this.loadCmsKats();
        this.loadGaleri();
        this.loadProfilUsaha();
        this.loadFasilitas();
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
            Konfigurasi identitas atelier, alamat titik jemput logistik RajaOngkir, manajemen konten beranda, dan galeri atelier.
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
            Profil &amp; Identitas
        </button>
        <button @click="activeTab = 'info'"
                :class="activeTab === 'info' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Kelola Banner Hero
        </button>
        <button @click="activeTab = 'kategori'"
                :class="activeTab === 'kategori' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Kelola 3 Kategori Utama
        </button>
        <button @click="activeTab = 'galeri'"
                :class="activeTab === 'galeri' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'"
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Galeri &amp; Alamat Atelier
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
    <!-- TAB 2: KELOLA 3 KATEGORI UTAMA (CMS)       -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'kategori'" x-transition class="space-y-6">

        <!-- Judul seksi -->
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Kelola 3 Kategori Utama</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Atur kartu kategori yang tampil di beranda. Kategori dipilih dari daftar kategori produk; perubahan diterapkan setelah Simpan Semua.
                </p>
            </div>
            <button type="button" @click="loadCmsKats()" :disabled="loadingKat"
                    class="px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer disabled:opacity-60 w-fit">
                <i class="fa-solid fa-rotate" :class="loadingKat ? 'fa-spin' : ''"></i>
                <span>Muat ulang</span>
            </button>
        </div>

        <!-- Loading -->
        <div x-show="loadingKat" x-cloak class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center">
            <p class="text-xs text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat kategori utama…</p>
        </div>

        <!-- Empty state -->
        <div x-show="!loadingKat && !cmsKatsAktif.length" x-cloak
             class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center space-y-3">
            <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-300 text-2xl mx-auto">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Belum ada kategori utama</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                Tambahkan kartu kategori pertama lewat area Tambah Kategori Baru di bawah.
            </p>
        </div>

        <!-- Daftar kartu kategori -->
        <div class="space-y-6">
            <template x-for="(k, index) in cmsKatsAktif" :key="k.id_kategori_cms">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

                    <!-- Header kartu -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 bg-[#0A192F] text-sky-300 rounded-xl flex items-center justify-center text-sm shrink-0">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[9px] font-black tracking-[0.18em] text-slate-400 uppercase"
                                   x-text="index === 0 ? 'Kategori Utama 1' : 'Kategori Utama ' + (index + 1)"></p>
                                <p class="text-sm font-extrabold text-slate-900 truncate" x-text="k.nama_kategori || 'Pilih kategori…'"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span x-show="k._dirty || k._isNew" class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[9px] font-extrabold uppercase tracking-wider">
                                Belum disimpan
                            </span>
                            <button type="button"
                                    @click="k.status_aktif = k.status_aktif ? 0 : 1; k._dirty = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer"
                                    :class="k.status_aktif ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="k.status_aktif ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span x-text="k.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </button>
                            <button type="button" @click="hapusKat(k)"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer">
                                <i class="fa-regular fa-trash-can"></i>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pt-5">
                        <!-- Preview background card -->
                        <div class="relative w-full aspect-[3/2] rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                            <template x-if="k._preview">
                                <img :src="k._preview" :alt="k.nama_kategori || 'Kategori'"
                                     class="w-full h-full object-cover"
                                     @load="k._dimensi = $event.target.naturalWidth + ' × ' + $event.target.naturalHeight + ' px'">
                            </template>
                            <template x-if="!k._preview">
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-2">
                                    <i class="fa-regular fa-image text-2xl"></i>
                                    <span class="text-[11px] font-bold">Unggah background card</span>
                                </div>
                            </template>
                            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg bg-black/55 text-white text-[9px] font-extrabold tracking-widest backdrop-blur"
                                  x-text="'KATEGORI ' + (index + 1)"></span>
                        </div>

                        <!-- Meta info + aksi gambar -->
                        <div class="mt-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <p class="text-[11px] text-slate-500 font-medium break-all" x-text="metaMedia(k)"></p>
                            <div class="flex items-center gap-2 shrink-0">
                                <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>Unggah / Ganti gambar</span>
                                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp"
                                           @change="pilihGambar(k, $event)">
                                </label>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">JPG, PNG, WEBP • Maks 2 MB</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form kartu -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-medium">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Judul</label>
                            <select x-model="k.nama_kategori" @change="pilihNamaKat(k, $event)"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                                <option value="">-- Pilih Kategori --</option>
                                <template x-for="c in katOpsi()" :key="c.id_category">
                                    <option :value="c.nama_kategori" x-text="c.nama_kategori"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">
                                Deskripsi <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span>
                            </label>
                            <input type="text" x-model="k.deskripsi" @input="k._dirty = true"
                                   placeholder="Contoh: Koleksi batik tulis tangan dengan motif klasik"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Link Tujuan</label>
                            <input type="text" x-model="k.tautan_tujuan" @input="k._dirty = true"
                                   placeholder="/kategori/batik-tulis"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Area tambah kategori -->
        <div @click="tambahKat()"
             class="bg-white rounded-2xl border-2 border-dashed border-slate-200 hover:border-sky-300 hover:bg-slate-50/60 p-10 text-center space-y-3 transition cursor-pointer">
            <div class="w-14 h-14 rounded-full bg-sky-100 text-sky-500 border border-sky-200 flex items-center justify-center text-xl mx-auto">
                <i class="fa-solid fa-plus"></i>
            </div>
            <div class="space-y-1">
                <p class="text-sm font-extrabold text-slate-900">Tambah Kategori Baru</p>
                <p class="text-xs text-slate-500 font-medium">Tambah kategori baru untuk menampilkan koleksi spesial</p>
            </div>
        </div>

        <!-- Footer aksi sticky -->
        <div class="sticky bottom-0 z-20 -mx-6 sm:-mx-8 px-6 sm:px-8 py-4 bg-[#FAF8F5]/95 backdrop-blur border-t border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-[11px] text-slate-500 font-semibold"
                   x-text="lastSavedKatAt ? 'Perubahan terakhir disimpan pada ' + lastSavedKatAt : 'Belum ada perubahan tersimpan.'"></p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="batalKat()" :disabled="savingKat"
                            class="px-5 py-3 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-extrabold hover:bg-slate-50 transition cursor-pointer disabled:opacity-50">
                        Batal
                    </button>
                    <button type="button" @click="simpanSemuaKat()" :disabled="savingKat"
                            class="px-6 py-3 rounded-xl bg-sky-400 hover:bg-sky-500 text-white text-xs font-extrabold shadow-sm transition cursor-pointer disabled:opacity-60 flex items-center gap-2">
                        <i class="fa-solid" :class="savingKat ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="savingKat ? 'Menyimpan…' : 'SIMPAN SEMUA PERUBAHAN'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Manajemen kategori produk (sumber dropdown) -->
        <div class="pt-2">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Manajemen kategori produk <span class="normal-case font-semibold text-slate-400">— dipakai halaman katalog &amp; sebagai pilihan dropdown di atas</span>
            </p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Kategori Produk</h2>
                        <span class="text-[10px] text-slate-400 font-bold">GET /categories • POST/PUT/DELETE /admin/categories</span>
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
                            <p class="text-[11px] text-slate-500 font-medium break-all" x-text="metaMedia(h)"></p>
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

    <!-- ========================================== -->
    <!-- TAB 4: GALERI, ALAMAT & FASILITAS ATELIER  -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'galeri'" x-transition class="space-y-6">

        <!-- Judul seksi -->
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Galeri &amp; Alamat Atelier</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Unggah foto galeri, atur alamat &amp; jam operasional, dan kelola daftar fasilitas. Perubahan diterapkan setelah Simpan Semua.
                </p>
            </div>
            <button type="button" @click="loadGaleri(); loadProfilUsaha(); loadFasilitas()" :disabled="loadingGaleri || loadingFas"
                    class="px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer disabled:opacity-60 w-fit">
                <i class="fa-solid fa-rotate" :class="(loadingGaleri || loadingFas) ? 'fa-spin' : ''"></i>
                <span>Muat ulang</span>
            </button>
        </div>

        <!-- A. GALERI ATELIER -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0A192F] text-sky-300 rounded-xl flex items-center justify-center text-sm shrink-0">
                        <i class="fa-regular fa-images"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black tracking-[0.18em] text-slate-400 uppercase">Konten Beranda</p>
                        <h3 class="text-base font-extrabold text-slate-900">Galeri Atelier</h3>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[9px] font-extrabold uppercase tracking-wider"
                          x-show="pendingGaleriDeletes.length || galeriFotos.some(f => f._dirty) || galeriNew.length">
                        Belum disimpan
                    </span>
                    <span class="inline-block px-3 py-1.5 bg-sky-50 border border-sky-200 text-sky-800 text-[9px] font-extrabold uppercase tracking-wider rounded-lg"
                          x-text="galeriBadge()"></span>
                </div>
            </div>

            <!-- Loading -->
            <div x-show="loadingGaleri" x-cloak class="py-10 text-center">
                <p class="text-xs text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat galeri…</p>
            </div>

            <!-- Kartu foto galeri aktif -->
            <div x-show="!loadingGaleri && galeriFotos.length" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <template x-for="f in galeriFotos" :key="'gl-' + f.id_foto">
                    <div :class="f._deleted ? 'opacity-40 grayscale' : ''"
                         class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50">
                        <div class="relative w-full aspect-[16/10] overflow-hidden bg-slate-100">
                            <img :src="galeriFotoPreview(f)" :alt="f.alt || 'Foto galeri atelier'"
                                 class="w-full h-full object-cover"
                                 x-show="galeriFotoPreview(f)"
                                 @load="f._dimensi = $event.target.naturalWidth + ' × ' + $event.target.naturalHeight + ' px'">
                            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg bg-black/55 text-white text-[9px] font-extrabold tracking-widest backdrop-blur"
                                  x-text="f._deleted ? 'AKAN DIHAPUS' : (f._dirty ? 'GANTI FOTO' : 'FOTO GALERI')"></span>
                        </div>
                        <div class="p-4 space-y-2.5">
                            <div>
                                <p class="text-sm font-extrabold text-slate-900 truncate" x-text="f.alt || (galeriJudul || 'Foto galeri atelier')"></p>
                                <p class="text-xs text-slate-500 mt-0.5">Galeri Atelier — foto koleksi &amp; suasana atelier</p>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium break-all" x-text="metaGaleriFoto(f)"></p>
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span x-text="f._file ? 'Ganti lagi' : 'Ganti Foto'"></span>
                                    <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp"
                                           @change="gantiGaleri(f, $event)">
                                </label>
                                <button type="button" @click="hapusGaleri(f)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer">
                                    <i class="fa-regular fa-trash-can"></i>
                                    <span x-text="f._deleted ? 'Batalkan hapus' : 'Hapus'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Kartu foto baru (belum tersimpan) -->
            <div x-show="galeriNew.length" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <template x-for="g in galeriNew" :key="g._key">
                    <div class="rounded-xl border-2 border-sky-300 bg-sky-50/40 overflow-hidden">
                        <div class="relative w-full aspect-[16/10] overflow-hidden bg-sky-100">
                            <img :src="g._preview" alt="Foto baru" class="w-full h-full object-cover" x-show="g._preview"
                                 @load="g._dimensi = $event.target.naturalWidth + ' × ' + $event.target.naturalHeight + ' px'">
                            <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg bg-sky-500/90 text-white text-[9px] font-extrabold tracking-widest backdrop-blur">
                                FOTO BARU
                            </span>
                        </div>
                        <div class="p-4 space-y-2.5">
                            <div>
                                <p class="text-sm font-extrabold text-slate-900 truncate" x-text="g._file?.name ?? 'Foto baru'"></p>
                                <p class="text-xs text-slate-500 mt-0.5">Belum tersimpan — terapkan dengan Simpan Semua</p>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium break-all" x-text="metaGaleriFoto(g)"></p>
                            <button type="button" @click="hapusGaleri(g)"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer">
                                <i class="fa-regular fa-trash-can"></i>
                                <span>Batalkan</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty state -->
            <div x-show="!loadingGaleri && !galeriFotos.length && !galeriNew.length" x-cloak
                 class="py-10 text-center space-y-2">
                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-300 text-xl mx-auto">
                    <i class="fa-regular fa-images"></i>
                </div>
                <p class="text-sm font-extrabold text-slate-900">Belum ada foto galeri</p>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">Gunakan area Tambah Foto Galeri / Atelier di bawah untuk mengunggah foto pertama.</p>
            </div>
        </div>

        <!-- D. Tambah foto galeri (dropzone) -->
        <div @dragover.prevent="galeriDragging = true" @dragleave.prevent="galeriDragging = false"
             @drop.prevent="galeriDragging = false; pilihGaleriBaru($event.dataTransfer?.files ?? [])"
             @click="$refs.galeriPicker.click()"
             class="bg-white rounded-2xl border-2 border-dashed p-10 text-center space-y-3 transition cursor-pointer"
             :class="galeriDragging ? 'border-sky-400 bg-sky-50/60' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50/60'">
            <div class="w-14 h-14 rounded-full bg-sky-100 text-sky-500 border border-sky-200 flex items-center justify-center text-xl mx-auto">
                <i class="fa-solid fa-cloud-arrow-up"></i>
            </div>
            <div class="space-y-1">
                <p class="text-sm font-extrabold text-slate-900">TAMBAH FOTO GALERI / ATELIER</p>
                <p class="text-xs text-slate-500 font-medium">Klik atau seret file • unggah foto baru • JPG, PNG, WEBP • Maks 2 MB</p>
            </div>
            <input x-ref="galeriPicker" type="file" class="hidden" accept="image/jpeg,image/png,image/webp" multiple
                   @change="pilihGaleriBaru($event.target.files); $event.target.value = ''">
        </div>

        <!-- B. Alamat & informasi operasional -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0A192F] text-sky-300 rounded-xl flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black tracking-[0.18em] text-slate-400 uppercase">Profil Usaha — Profil Usaha Atelier</p>
                        <h3 class="text-base font-extrabold text-slate-900">Alamat &amp; Informasi Operasional</h3>
                    </div>
                </div>
                <span x-show="puDirty" class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[9px] font-extrabold uppercase tracking-wider">
                    Belum disimpan
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-medium">
                <div class="md:col-span-2 flex items-center gap-4 bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-white border border-slate-200 flex items-center justify-center shrink-0">
                        <template x-if="puLogoPreview || puLogoUrl">
                            <img :src="puLogoPreview || puLogoUrl" alt="Logo usaha" class="w-full h-full object-contain p-1">
                        </template>
                        <template x-if="!puLogoPreview && !puLogoUrl">
                            <i class="fa-regular fa-image text-slate-300 text-xl"></i>
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-extrabold text-slate-900" x-text="puLogoPending ? puLogoPending.name : (puLogoUrl ? 'Logo usaha aktif' : 'Logo usaha belum diunggah')"></p>
                        <p class="text-[11px] text-slate-500 mt-0.5" x-text="puLogoPending ? 'Belum disimpan — terapkan dengan Simpan Semua' : 'Tampil di profil toko • JPG, PNG, WEBP • Maks 2 MB'"></p>
                        <div class="flex items-center gap-2 mt-2" x-show="puLogoPending">
                            <button type="button" @click="batalkanPuLogo()"
                                    class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-600 text-[9px] font-extrabold uppercase tracking-wider hover:bg-slate-100 transition cursor-pointer">
                                Batalkan ganti logo
                            </button>
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer shrink-0">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span x-text="puLogoPending ? 'Ganti lagi' : 'Ganti Logo'"></span>
                        <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" @change="gantiPuLogo($event)">
                    </label>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Nama Usaha <span class="text-slate-300 font-bold normal-case tracking-normal">(wajib saat pertama)</span></label>
                    <input type="text" x-model="pu.nama_usaha" @input="puDirty = true"
                           placeholder="Contoh: Batik Pandansari"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Deskripsi Singkat <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span></label>
                    <input type="text" x-model="pu.deskripsi_singkat" @input="puDirty = true"
                           placeholder="Contoh: Batik tulis & cap asli Sragen"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Deskripsi Lengkap <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span></label>
                    <textarea x-model="pu.deskripsi_lengkap" @input="puDirty = true" rows="3"
                              placeholder="Belum diisi — ceritakan tentang atelier, pendekatan 10n, dan keanggunan nuwo keluarga"
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Alamat Lengkap</label>
                    <textarea x-model="pu.alamat" @input="puDirty = true" rows="2"
                              placeholder="Belum diisi — masukkan alamat lengkap atelier"
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Link Google Maps</label>
                    <input type="text" x-model="pu.link_google_maps" @input="puDirty = true"
                           placeholder="Belum diisi — tempel tautan Google Maps"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Email Resmi <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span></label>
                    <input type="text" x-model="pu.email_resmi" @input="puDirty = true"
                           placeholder="Contoh: halo@batikpandansari.com"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Nomor WhatsApp <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span></label>
                    <input type="text" x-model="pu.nomor_whatsapp" @input="puDirty = true"
                           placeholder="Contoh: 6281234567890"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Nomor Telepon <span class="text-slate-300 font-bold normal-case tracking-normal">(opsional)</span></label>
                    <input type="text" x-model="pu.nomor_telepon" @input="puDirty = true"
                           placeholder="Contoh: 08123456789"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
            </div>

            <!-- Jam buka per hari -->
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Jam Buka — atur hari &amp; jam operasional</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <template x-for="(j, ji) in jams" :key="'jam-' + j.hari">
                        <div class="flex items-center gap-3 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                            <span class="text-xs font-extrabold text-slate-800 w-16 shrink-0" x-text="j.hari"></span>
                            <button type="button"
                                    @click="j.tutupHari = !j.tutupHari; puDirty = true"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[9px] font-extrabold uppercase tracking-wider transition cursor-pointer shrink-0"
                                    :class="j.tutupHari ? 'bg-slate-200 text-slate-600 border-slate-300' : 'bg-sky-100 text-sky-700 border-sky-200 hover:bg-sky-200'"
                                    x-text="j.tutupHari ? 'Tutup' : 'Buka'"></button>
                            <div class="flex-1 flex items-center gap-2 justify-end" x-show="!j.tutupHari">
                                <input type="time" x-model="j.buka" @input="puDirty = true"
                                       class="bg-white border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400 w-[104px]">
                                <span class="text-[10px] font-black text-slate-400">—</span>
                                <input type="time" x-model="j.tutup" @input="puDirty = true"
                                       class="bg-white border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400 w-[104px]">
                            </div>
                            <span x-show="j.tutupHari" class="flex-1 text-right text-[10px] font-bold text-slate-400 uppercase tracking-wider">Libur sepanjang hari</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- C. Fasilitas -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0A192F] text-sky-300 rounded-xl flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-star"></i>
                    </div>
                    <div>
                        <p class="text-[9px] font-black tracking-[0.18em] text-slate-400 uppercase">Kemudahan kunjungan</p>
                        <h3 class="text-base font-extrabold text-slate-900">Fasilitas Atelier</h3>
                    </div>
                </div>
                <span class="inline-block px-3 py-1.5 bg-sky-50 border border-sky-200 text-sky-800 text-[9px] font-extrabold uppercase tracking-wider rounded-lg"
                      x-text="fasBadge()"></span>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">
                    Fasilitas — sesuaikan ketersediaan
                    <span x-show="pendingFasDeletes.length || fasList.some(f => f._dirty || f._isNew)" class="text-amber-600 normal-case tracking-normal">• ada perubahan belum disimpan</span>
                </label>
                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="f in fasList.filter(x => !x._deleted)" :key="'fas-' + f.id_fasilitas">
                        <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-bold text-slate-700 transition"
                              :class="f._isNew ? 'ring-2 ring-sky-300 bg-sky-50 border-sky-200 text-sky-900' : ''">
                            <i class="fa-solid fa-check text-[9px] text-emerald-500"></i>
                            <template x-if="fasEditId === f.id_fasilitas">
                                <input type="text" x-model="fasEditNama" @keydown.enter.prevent="selesaiEditFas()" @blur="selesaiEditFas()"
                                       class="bg-white border border-sky-300 rounded-lg px-2 py-1 text-xs font-bold w-40 focus:outline-none focus:ring-2 focus:ring-sky-400">
                            </template>
                            <template x-if="fasEditId !== f.id_fasilitas">
                                <button type="button" @click="mulaiEditFas(f);" class="cursor-pointer hover:text-sky-700 transition" x-text="f.nama_fasilitas"></button>
                            </template>
                            <button type="button" @click="hapusFas(f)" class="text-rose-400 hover:text-rose-600 transition cursor-pointer">
                                <i class="fa-solid fa-xmark text-[11px]"></i>
                            </button>
                        </span>
                    </template>
                    <span x-show="!loadingFas && !fasList.some(x => !x._deleted)" class="text-xs text-slate-400 font-medium">
                        Belum ada fasilitas — tambahkan lewat bar Kelola Fasilitas di bawah.
                    </span>
                </div>
                <p x-show="loadingFas" class="text-xs text-slate-400 font-semibold py-3">
                    <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat fasilitas…
                </p>
            </div>

            <!-- Bar kelola fasilitas -->
            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                <span class="text-[9px] font-black tracking-[0.18em] text-slate-400 uppercase shrink-0">Kelola Fasilitas</span>
                <input type="text" x-model="fasNew" @keydown.enter.prevent="tambahFas()"
                       placeholder="Tambah fasilitas baru — contoh: Layanan Fitting, Area Parkir"
                       class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                <button type="button" @click="tambahFas()"
                        class="px-4 py-2 rounded-lg bg-sky-400 hover:bg-sky-500 text-white text-[10px] font-extrabold uppercase tracking-wider transition cursor-pointer shrink-0">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah
                </button>
            </div>
            <p class="text-[11px] text-slate-400 font-medium">
                <i class="fa-solid fa-circle-info mr-1"></i> Klik nama fasilitas untuk mengubah (Enter atau klik luar untuk simpan), ikon silang untuk menghapus — perubahan diterapkan saat Simpan Semua.
            </p>
        </div>

        <!-- Footer aksi sticky -->
        <div class="sticky bottom-0 z-20 -mx-6 sm:-mx-8 px-6 sm:px-8 py-4 bg-[#FAF8F5]/95 backdrop-blur border-t border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-[11px] text-slate-500 font-semibold"
                   x-text="lastSavedGaleriAt ? 'Perubahan terakhir disimpan pada ' + lastSavedGaleriAt : 'Belum ada perubahan tersimpan.'"></p>
                <div class="flex items-center gap-2">
                    <button type="button" @click="loadGaleri(); loadProfilUsaha(); loadFasilitas(); lastSavedGaleriAt = ''" :disabled="savingGaleri"
                            class="px-5 py-3 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-extrabold hover:bg-slate-50 transition cursor-pointer disabled:opacity-50">
                        Batal
                    </button>
                    <button type="button" @click="simpanSemuaGaleri()" :disabled="savingGaleri"
                            class="px-6 py-3 rounded-xl bg-sky-400 hover:bg-sky-500 text-white text-xs font-extrabold shadow-sm transition cursor-pointer disabled:opacity-60 flex items-center gap-2">
                        <i class="fa-solid" :class="savingGaleri ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="savingGaleri ? 'Menyimpan…' : 'SIMPAN SEMUA PERUBAHAN'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    </div>

</div>

@endsection
