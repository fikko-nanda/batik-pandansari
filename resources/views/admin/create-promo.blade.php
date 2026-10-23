@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Form buat voucher (POST /vouchers) ===
    form: {
        nama_voucher: '', kode_voucher: '', deskripsi: '',
        tipe_diskon: 'persentase', nilai_diskon: 10,
        maksimal_diskon: '', minimal_pembelian: '',
        kuota_total: '', kuota_per_pengguna: 1,
        tanggal_mulai: '', tanggal_selesai: '',
    },
    saving: false,
    notice: '', noticeOk: true,

    // === Daftar voucher (GET /vouchers) ===
    vouchers: [],
    meta: null,
    page: 1,
    perPage: 8,
    loadingList: true,
    busyId: '',

    // === Ringkasan (GET /vouchers/summary) ===
    summary: null,

    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    fmt(n) { return PandansariAPI.formatRp(n); },
    tanggal(s) {
        if (!s) return '—';
        try { return new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return s; }
    },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 5000); },

    // === Simulasi diskon (seperti desain lama) ===
    get simulatedDiscount() {
        const subtotal = 1500000;
        if (this.form.tipe_diskon === 'persentase') {
            const calc = (subtotal * Number(this.form.nilai_diskon || 0)) / 100;
            return this.form.maksimal_diskon ? Math.min(calc, Number(this.form.maksimal_diskon)) : calc;
        }
        return Math.min(Number(this.form.nilai_diskon || 0), subtotal);
    },
    get simulatedTotal() { return 1500000 - this.simulatedDiscount; },

    copyCode() {
        navigator.clipboard?.writeText(this.form.kode_voucher);
        this.pesan(true, 'Kode ' + this.form.kode_voucher + ' disalin.');
    },

    // === Buat voucher ===
    async simpanVoucher(aktif) {
        const f = this.form;
        if (!f.nama_voucher.trim() || !f.kode_voucher.trim() || !f.tanggal_mulai || !f.tanggal_selesai) {
            this.pesan(false, 'Nama, kode, tanggal mulai & selesai wajib diisi.'); return;
        }
        if (Number(f.nilai_diskon || 0) <= 0) { this.pesan(false, 'Nilai diskon harus lebih dari 0.'); return; }
        this.saving = true;
        const res = await PandansariAPI.admin.createVoucher({
            kode_voucher: f.kode_voucher.trim().toUpperCase(),
            nama_voucher: f.nama_voucher.trim(),
            deskripsi: f.deskripsi.trim() || undefined,
            tipe_diskon: f.tipe_diskon,
            nilai_diskon: Number(f.nilai_diskon),
            minimal_pembelian: f.minimal_pembelian === '' ? undefined : Number(f.minimal_pembelian),
            maksimal_diskon: f.maksimal_diskon === '' ? undefined : Number(f.maksimal_diskon),
            kuota_total: f.kuota_total === '' ? undefined : Number(f.kuota_total),
            kuota_per_pengguna: Number(f.kuota_per_pengguna || 1),
            tanggal_mulai: f.tanggal_mulai,
            tanggal_selesai: f.tanggal_selesai,
            status_voucher: aktif ? 'aktif' : 'nonaktif',
        });
        this.saving = false;
        if (res.ok) {
            this.pesan(true, 'Voucher ' + res.data?.kode_voucher + ' berhasil dibuat' + (aktif ? ' dan aktif.' : ' (draf nonaktif).'));
            this.form = {
                nama_voucher: '', kode_voucher: '', deskripsi: '',
                tipe_diskon: 'persentase', nilai_diskon: 10,
                maksimal_diskon: '', minimal_pembelian: '',
                kuota_total: '', kuota_per_pengguna: 1,
                tanggal_mulai: '', tanggal_selesai: '',
            };
            this.muatSemua();
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },

    // === Daftar & aksi voucher ===
    async loadVouchers() {
        this.loadingList = true;
        const res = await PandansariAPI.admin.vouchers({ page: this.page, per_page: this.perPage });
        this.loadingList = false;
        if (res.ok) {
            this.vouchers = res.data ?? [];
            this.meta = res.meta;
        } else this.pesan(false, res.message);
    },
    async loadSummary() {
        const res = await PandansariAPI.admin.vouchersSummary();
        if (res.ok) this.summary = res.data;
    },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadVouchers(); },

    async toggleStatus(v) {
        this.busyId = v.id_voucher;
        const aktif = v.status_voucher === 'aktif';
        const res = aktif
            ? await PandansariAPI.admin.unpublishVoucher(v.id_voucher)
            : await PandansariAPI.admin.publishVoucher(v.id_voucher);
        this.busyId = '';
        if (res.ok) {
            v.status_voucher = aktif ? 'nonaktif' : 'aktif';
            this.pesan(true, v.kode_voucher + ' sekarang ' + v.status_voucher + '.');
            this.loadSummary();
        } else this.pesan(false, res.message);
    },
    async hapusVoucher(v) {
        if (!confirm('Nonaktifkan voucher ' + v.kode_voucher + '?')) return;
        this.busyId = v.id_voucher;
        const res = await PandansariAPI.admin.deleteVoucher(v.id_voucher);
        this.busyId = '';
        if (res.ok) {
            v.status_voucher = 'nonaktif';
            this.pesan(true, 'Voucher ' + v.kode_voucher + ' dinonaktifkan.');
            this.loadSummary();
        } else this.pesan(false, res.message);
    },

    muatSemua() { this.loadVouchers(); this.loadSummary(); },

    async init() {
        // default tanggal: hari ini & +7 hari
        const iso = (d) => d.toISOString().slice(0, 10);
        this.form.tanggal_mulai = iso(new Date());
        const akhir = new Date(); akhir.setDate(akhir.getDate() + 7);
        this.form.tanggal_selesai = iso(akhir);

        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loadingList = false; return; }
        const me = await PandansariAPI.me();
        if (!me.ok || me.data?.peran !== 'admin') { this.notAdmin = true; this.loadingList = false; return; }
        this.muatSemua();
    }
}" class="space-y-6 text-slate-800 font-sans pb-10">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuat promo</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER TITLE -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
            Buat Promo & Kelola Voucher
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            POST /vouchers — buat voucher nyata; aktif/nonaktifkan lewat publish/unpublish.
        </p>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 1b. RINGKASAN VOUCHER (summary API) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block">TOTAL VOUCHER</span>
            <div class="text-2xl font-black text-slate-900" x-text="summary ? num(summary.total_voucher) : '…'"></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block">AKTIF</span>
            <div class="text-2xl font-black text-emerald-700" x-text="summary ? num(summary.aktif) : '…'"></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block">TOTAL PENGGUNAAN</span>
            <div class="text-2xl font-black text-slate-900" x-text="summary ? num(summary.total_penggunaan) : '…'"></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block">TOTAL DISKON DIBERIKAN</span>
            <div class="text-2xl font-black text-slate-900" x-text="summary ? fmt(summary.total_diskon_diberikan) : '…'"></div>
        </div>
    </div>

    <!-- 2. INFO BANNER -->
    <div class="bg-sky-50 border border-sky-200/80 rounded-2xl p-3.5 flex items-center gap-3 text-xs text-sky-800 font-medium">
        <i class="fa-solid fa-circle-info text-sky-600 text-sm shrink-0"></i>
        <span>Voucher dibuat langsung tersimpan di database. Kolom bertanda * wajib diisi; kode bersifat unik.</span>
    </div>

    <!-- 3. MAIN GRID (FORM LEFT + PREVIEW RIGHT) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT COLUMN: FORM INPUTS (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- SECTION 1: DETAIL & KODE PROMO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Detail & kode promo</h2>
                        <p class="text-[10px] text-slate-400">Identitas penawaran yang ditampilkan kepada pelanggan.</p>
                    </div>
                </div>

                <div class="space-y-4 text-xs font-medium">
                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">NAMA PROMO *</label>
                        <input type="text" x-model="form.nama_voucher" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400" placeholder="Contoh: Pesona Wastra Oktober">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">KODE PROMO *</label>
                        <div class="relative">
                            <input type="text" x-model="form.kode_voucher" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono font-black text-slate-900 tracking-wider uppercase focus:outline-none focus:ring-2 focus:ring-sky-400 pr-10" placeholder="WASTRA15">
                            <button type="button" @click="copyCode()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 cursor-pointer" title="Salin Kode">
                                <i class="fa-regular fa-copy text-sm"></i>
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-1">Kode unik, tanpa spasi. Pelanggan memasukkannya saat checkout.</span>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">DESKRIPSI PROMO</label>
                        <textarea x-model="form.deskripsi" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 leading-relaxed" placeholder="Nikmati potongan untuk koleksi batik pilihan Pandansari…"></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: JENIS & NILAI DISKON (+ KUOTA) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Jenis & nilai diskon</h2>
                        <p class="text-[10px] text-slate-400">Tentukan manfaat promo, kuota, dan batas potongannya.</p>
                    </div>
                </div>

                <div class="space-y-4 text-xs font-medium">
                    <div class="grid grid-cols-2 gap-3">
                        <label @click="form.tipe_diskon = 'persentase'" :class="form.tipe_diskon === 'persentase' ? 'bg-[#AEE2F0]/50 border-sky-400 ring-2 ring-sky-300' : 'bg-slate-50 border-slate-200'" class="p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3">
                            <input type="radio" name="disc_type" value="persentase" x-model="form.tipe_diskon" class="mt-0.5 accent-sky-600">
                            <div>
                                <strong class="text-slate-900 font-extrabold block text-xs">Persentase</strong>
                                <span class="text-[10px] text-slate-500 block">Potongan dalam persen</span>
                            </div>
                        </label>

                        <label @click="form.tipe_diskon = 'nominal'" :class="form.tipe_diskon === 'nominal' ? 'bg-[#AEE2F0]/50 border-sky-400 ring-2 ring-sky-300' : 'bg-slate-50 border-slate-200'" class="p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3">
                            <input type="radio" name="disc_type" value="nominal" x-model="form.tipe_diskon" class="mt-0.5 accent-sky-600">
                            <div>
                                <strong class="text-slate-900 font-extrabold block text-xs">Nominal</strong>
                                <span class="text-[10px] text-slate-500 block">Potongan rupiah tetap</span>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">NILAI DISKON *</label>
                            <div class="relative">
                                <input type="number" x-model.number="form.nilai_diskon" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 font-black text-slate-400 text-xs" x-text="form.tipe_diskon === 'persentase' ? '%' : 'Rp'"></span>
                            </div>
                            <span class="text-[9px] text-slate-400 block mt-1">Diskon dihitung dari subtotal produk, tidak termasuk ongkir.</span>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">MAKSIMUM POTONGAN</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-extrabold text-slate-400 text-xs">Rp</span>
                                <input type="number" x-model.number="form.maksimal_diskon" min="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 pl-9 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400">
                            </div>
                            <span class="text-[9px] text-slate-400 block mt-1">Kosongkan = tanpa batas.</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100">
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">MINIMUM BELANJA</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-extrabold text-slate-400 text-xs">Rp</span>
                                <input type="number" x-model.number="form.minimal_pembelian" min="0" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 pl-9 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400">
                            </div>
                            <span class="text-[9px] text-slate-400 block mt-1">Kosongkan = tanpa minimum.</span>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">KUOTA TOTAL</label>
                            <div class="relative">
                                <input type="number" x-model.number="form.kuota_total" min="0" placeholder="Tak terbatas" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400 pr-20">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 font-extrabold text-slate-400 text-[10px]">penggunaan</span>
                            </div>
                            <span class="text-[9px] text-slate-400 block mt-1">Batas total penggunaan oleh seluruh pelanggan.</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">BATAS PER PELANGGAN</label>
                        <div class="relative">
                            <input type="number" x-model.number="form.kuota_per_pengguna" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400 pr-12">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 font-extrabold text-slate-400 text-[10px]">kali</span>
                        </div>
                        <span class="text-[9px] text-slate-400 block mt-1">Maksimal pemakaian voucher per akun pelanggan.</span>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: PERIODE PROMO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Periode promo</h2>
                        <p class="text-[10px] text-slate-400">Tanggal mulai & berlaku voucher.</p>
                    </div>
                </div>

                <div class="space-y-4 text-xs font-medium">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">MULAI *</label>
                            <input type="date" x-model="form.tanggal_mulai" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">BERAKHIR *</label>
                            <input type="date" x-model="form.tanggal_selesai" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400">Voucher terbit aktif langsung; setelah periode berakhir otomatis kedaluwarsa oleh sistem.</p>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: PREVIEW & RINGKASAN (5 COLS) -->
        <div class="lg:col-span-5 space-y-5">

            <!-- PRATINJAU PROMO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="font-extrabold text-slate-900 text-sm">Pratinjau promo</h3>
                    <i class="fa-solid fa-eye text-slate-400 text-xs"></i>
                </div>

                <div class="bg-[#0A192F] text-white rounded-2xl p-5 space-y-4 relative overflow-hidden shadow-md">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-300 block">Pandansari</span>
                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 block mt-0.5" x-text="form.nama_voucher || 'Nama promo'"></span>
                    </div>

                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white tracking-tight" x-text="'Diskon ' + (form.nilai_diskon || 0) + (form.tipe_diskon === 'persentase' ? '%' : ' Rp')"></div>
                        <p class="text-[10px] text-slate-300 leading-tight mt-1" x-text="form.maksimal_diskon ? ('Hingga Rp ' + Number(form.maksimal_diskon).toLocaleString('id-ID') + '.') : 'Tanpa batas potongan.'"></p>
                    </div>

                    <div class="bg-[#AEE2F0] text-slate-900 rounded-xl p-2.5 text-center font-mono font-black text-sm tracking-widest uppercase">
                        <span x-text="form.kode_voucher || 'KODE'"></span>
                    </div>

                    <div class="text-[9px] text-slate-400 space-y-0.5 border-t border-white/10 pt-3">
                        <p x-text="form.minimal_pembelian ? ('Min. belanja Rp ' + Number(form.minimal_pembelian).toLocaleString('id-ID')) : 'Tanpa minimum belanja'"></p>
                        <p x-text="tanggal(form.tanggal_mulai) + ' — ' + tanggal(form.tanggal_selesai)"></p>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400">Contoh tampilan voucher pelanggan setelah dipublikasikan.</p>
            </div>

            <!-- SIMULASI POTONGAN -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-5 space-y-3 text-xs">
                <h3 class="font-extrabold text-slate-900 text-sm">Simulasi potongan (Rp 1.500.000)</h3>

                <div class="space-y-2 text-[11px] font-bold">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Subtotal produk</span>
                        <span class="text-slate-900">Rp 1.500.000</span>
                    </div>

                    <div class="flex items-center justify-between text-emerald-700">
                        <span x-text="form.tipe_diskon === 'persentase' ? ('Diskon ' + (form.nilai_diskon || 0) + '%') : ('Diskon Rp ' + Number(form.nilai_diskon || 0).toLocaleString('id-ID'))"></span>
                        <span x-text="'- ' + fmt(simulatedDiscount)"></span>
                    </div>

                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-slate-900 text-xs">
                        <span>Total setelah diskon</span>
                        <span class="font-black" x-text="fmt(simulatedTotal)"></span>
                    </div>
                </div>

                <p class="text-[9px] text-slate-400">Belum termasuk biaya pengiriman.</p>
            </div>

            <!-- ALERT PERIKSA SEBELUM TERBIT -->
            <div class="bg-amber-50/80 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-amber-900">
                <i class="fa-regular fa-lightbulb text-amber-600 text-sm mt-0.5 shrink-0"></i>
                <div>
                    <strong class="font-bold block mb-0.5">Periksa sebelum terbit</strong>
                    <p class="text-[10px] text-amber-800 leading-tight">
                        Pastikan kode, periode, dan target promo sudah sesuai. Kode promo unik dan tidak dapat diubah setelah dibuat.
                    </p>
                </div>
            </div>

        </div>

    </div>

    <!-- 4. BOTTOM ACTION BAR -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-bold">
        <div class="flex items-center gap-2 text-slate-400 text-[11px]">
            <i class="fa-regular fa-circle-check"></i>
            <span>Voucher dibuat via POST /vouchers — pilih terbit aktif atau draf nonaktif.</span>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-auto">
            <button type="button" @click="simpanVoucher(false)" :disabled="saving"
                    class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl transition cursor-pointer disabled:opacity-60 flex items-center gap-2">
                <i class="fa-solid fa-box-archive text-xs"></i>
                <span x-show="!saving">Simpan Draf (nonaktif)</span>
                <span x-show="saving"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
            </button>
            <button type="button" @click="simpanVoucher(true)" :disabled="saving"
                    class="px-6 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-black rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-60">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span x-show="!saving">Publikasikan Promo</span>
                <span x-show="saving"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
            </button>
        </div>
    </div>

    <!-- 5. DAFTAR VOUCHER (kelola) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Kelola Voucher</h2>
                <span class="text-[11px] font-bold text-slate-400">
                    GET /vouchers — halaman <span x-text="meta?.current_page ?? 1"></span>/<span x-text="meta?.last_page ?? 1"></span> • total <span x-text="num(meta?.total ?? 0)"></span>
                </span>
            </div>
            <button type="button" @click="muatSemua()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer">
                <i class="fa-solid fa-rotate text-xs"></i>
                <span>Muat ulang</span>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-3">KODE & NAMA</th>
                        <th class="py-3 px-3">DISKON</th>
                        <th class="py-3 px-3 text-center">KUOTA</th>
                        <th class="py-3 px-3">PERIODE</th>
                        <th class="py-3 px-3 text-center">STATUS</th>
                        <th class="py-3 px-3 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <template x-if="loadingList">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat voucher…</td></tr>
                    </template>
                    <template x-if="!loadingList && !vouchers.length">
                        <tr><td colspan="6" class="py-10 text-center text-slate-400 font-semibold">Belum ada voucher.</td></tr>
                    </template>

                    <template x-for="v in vouchers" :key="v.id_voucher">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-3">
                                <strong class="font-mono font-black text-slate-900 block" x-text="v.kode_voucher"></strong>
                                <span class="text-[10px] text-slate-500 block" x-text="v.nama_voucher"></span>
                            </td>
                            <td class="py-3 px-3">
                                <strong class="text-slate-900 block" x-text="v.tipe_diskon === 'persentase' ? (v.nilai_diskon + '%') : fmt(v.nilai_diskon)"></strong>
                                <span class="text-[10px] text-slate-400 block" x-text="v.maksimal_diskon ? ('maks ' + fmt(v.maksimal_diskon)) : 'tanpa batas'"></span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="text-slate-800 font-bold" x-text="v.kuota_total ?? '∞'"></span>
                                <span class="block text-[9px] text-slate-400" x-text="'per user: ' + (v.kuota_per_pengguna ?? 1) + 'x'"></span>
                            </td>
                            <td class="py-3 px-3 text-[10px] text-slate-500">
                                <span x-text="tanggal(v.tanggal_mulai)"></span>
                                <span class="block" x-text="'s/d ' + tanggal(v.tanggal_selesai)"></span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase inline-block"
                                      :class="v.status_voucher === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                                      x-text="v.status_voucher"></span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="toggleStatus(v)" :disabled="busyId === v.id_voucher"
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-lg transition cursor-pointer disabled:opacity-50"
                                            :class="v.status_voucher === 'aktif' ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'"
                                            x-text="v.status_voucher === 'aktif' ? 'Nonaktifkan' : 'Publikasikan'"></button>
                                    <button type="button" @click="hapusVoucher(v)" :disabled="busyId === v.id_voucher"
                                            class="text-[10px] font-bold px-2.5 py-1 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 transition cursor-pointer disabled:opacity-50">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-1.5 font-bold text-xs">
            <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                    class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lt;</button>
            <span class="px-2 text-slate-500" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>
            <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                    class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer disabled:opacity-40">&gt;</button>
        </div>
    </div>

    </div>

</div>

@endsection
