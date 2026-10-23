@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Data API ===
    users: [],
    meta: null,
    page: 1,
    perPage: 10,
    searchQuery: '',
    selectedRole: 'ALL',
    summary: null,
    loading: true,
    notice: '', noticeOk: true,

    // === Modal tambah pengguna ===
    showModalTambah: false,
    savingUser: false,
    form: { nama_pengguna: '', email: '', nomor_telepon: '', peran: 'pelanggan', kata_sandi: '' },

    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    tanggal(s) {
        if (!s) return '—';
        try { return new Date(s).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); } catch { return s; }
    },
    inisial(nama) {
        return (nama ?? '').split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '?';
    },
    roleBadge(peran) {
        return peran === 'admin' ? 'bg-amber-100 text-amber-800 font-bold' : 'bg-slate-100 text-slate-700';
    },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 4500); },

    async loadUsers() {
        this.loading = true;
        const res = await PandansariAPI.admin.users({
            page: this.page, per_page: this.perPage,
            search: this.searchQuery.trim() || undefined,
            peran: this.selectedRole !== 'ALL' ? this.selectedRole : undefined,
        });
        this.loading = false;
        if (res.ok) {
            this.users = res.data ?? [];
            this.meta = res.meta;
            this.page = res.meta?.current_page ?? this.page;
        } else this.pesan(false, res.message);
    },
    async loadSummary() {
        const res = await PandansariAPI.admin.usersSummary();
        if (res.ok) this.summary = res.data;
    },
    setRole(r) { this.selectedRole = r; this.page = 1; this.loadUsers(); },
    goto(p) { if (p < 1 || (this.meta && p > this.meta.last_page)) return; this.page = p; this.loadUsers(); },
    resetFilter() { this.searchQuery = ''; this.selectedRole = 'ALL'; this.page = 1; this.loadUsers(); },

    // === Tambah pengguna (POST /users) ===
    async simpanUser() {
        if (!this.form.nama_pengguna.trim() || !this.form.email.trim() || !this.form.kata_sandi) {
            this.pesan(false, 'Nama, email, dan kata sandi wajib diisi.'); return;
        }
        this.savingUser = true;
        const res = await PandansariAPI.admin.createUser({
            nama_pengguna: this.form.nama_pengguna.trim(),
            email: this.form.email.trim(),
            nomor_telepon: this.form.nomor_telepon.trim() || undefined,
            peran: this.form.peran,
            kata_sandi: this.form.kata_sandi,
            kata_sandi_confirmation: this.form.kata_sandi,
        });
        this.savingUser = false;
        if (res.ok) {
            this.showModalTambah = false;
            this.form = { nama_pengguna: '', email: '', nomor_telepon: '', peran: 'pelanggan', kata_sandi: '' };
            this.pesan(true, 'Pengguna ' + res.data?.nama_pengguna + ' berhasil ditambahkan.');
            this.loadUsers();
            this.loadSummary();
        } else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.pesan(false, errs[0] ?? res.message);
        }
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (!me.ok || me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await Promise.all([this.loadUsers(), this.loadSummary()]);
        this.loading = false;
    }
}" class="space-y-6 text-slate-800 font-sans pb-10">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka direktori pengguna</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER TITLE & BUTTON -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-[10px] font-black text-amber-700 uppercase tracking-widest block mb-0.5">RELASI KOLEKTOR • ATELIER PANDANSARI</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Userlist
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                GET /users — cari & filter peran; tambah pengguna via POST /users (peran: pelanggan/admin).
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button type="button" @click="PandansariAPI.admin.usersExport()"
                    class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-500"></i>
                <span>Ekspor CSV</span>
            </button>
            <button type="button" @click="showModalTambah = true"
                    class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Tambah Pengguna</span>
            </button>
        </div>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. STATISTIC SUMMARY CARDS (GET /admin/users/summary) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="bg-[#AEE2F0] text-slate-900 rounded-2xl p-5 relative overflow-hidden shadow-sm flex items-center justify-between">
            <div class="space-y-1 z-10">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-600 block">TOTAL PENGGUNA</span>
                <div class="text-3xl font-black text-slate-900 tracking-tight" x-text="summary ? num(summary.total_pengguna) : '…'"></div>
                <span class="text-[10px] font-bold text-slate-600 block"
                      x-text="summary ? (num(summary.per_peran?.pelanggan ?? 0) + ' pelanggan • ' + num(summary.per_peran?.admin ?? 0) + ' admin') : '…'"></span>
            </div>
            <div class="w-10 h-10 bg-white/60 rounded-xl flex items-center justify-center text-slate-700 text-lg shrink-0 z-10">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="bg-[#0A192F] text-white rounded-2xl p-5 relative overflow-hidden shadow-sm flex items-center justify-between">
            <div class="space-y-1 z-10">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block">PENGGUNA AKTIF</span>
                <div class="text-3xl font-black text-white tracking-tight" x-text="summary ? num(summary.pengguna_aktif) : '…'"></div>
                <span class="text-[10px] font-bold text-slate-400 block"
                      x-text="summary && summary.total_pengguna ? Math.round(summary.pengguna_aktif / summary.total_pengguna * 100) + '% dari seluruh pengguna' : '…'"></span>
            </div>
            <div class="w-10 h-10 bg-sky-900/80 rounded-xl flex items-center justify-center text-sky-400 text-lg shrink-0 z-10 border border-sky-800">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>

    </div>

    <!-- 3. FILTER CONTROL STRIP -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 flex flex-wrap items-center gap-3 text-xs">

        <div class="relative flex-1 min-w-[240px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text"
                   x-model.debounce.600ms="searchQuery"
                   @input.debounce.600ms="page = 1; loadUsers()"
                   placeholder="Cari nama atau email pengguna..."
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <!-- Filter Peran (server-side) -->
        <div class="flex items-center gap-1.5">
            <button type="button" @click="setRole('ALL')"
                    class="px-3 py-2 rounded-xl font-bold transition cursor-pointer text-xs"
                    :class="selectedRole === 'ALL' ? 'bg-[#0A192F] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">Semua</button>
            <button type="button" @click="setRole('pelanggan')"
                    class="px-3 py-2 rounded-xl font-bold transition cursor-pointer text-xs"
                    :class="selectedRole === 'pelanggan' ? 'bg-[#0A192F] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">Pelanggan</button>
            <button type="button" @click="setRole('admin')"
                    class="px-3 py-2 rounded-xl font-bold transition cursor-pointer text-xs"
                    :class="selectedRole === 'admin' ? 'bg-[#0A192F] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">Admin</button>
        </div>

        <button type="button" @click="resetFilter()" class="text-slate-500 hover:text-slate-900 font-bold px-2 py-2.5 transition cursor-pointer">
            Reset
        </button>

    </div>

    <!-- 4. DIREKTORI PELANGGAN & TIM ATELIER TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Direktori Pelanggan & Tim Atelier</h2>
                <span class="text-[11px] font-bold text-slate-400">
                    <span x-text="num(meta?.total ?? 0)"></span> pengguna terdaftar • halaman <span x-text="meta?.current_page ?? 1"></span>/<span x-text="meta?.last_page ?? 1"></span>
                </span>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-3">PENGGUNA & KONTAK</th>
                        <th class="py-3 px-3">PERAN</th>
                        <th class="py-3 px-3">ID PENGGUNA</th>
                        <th class="py-3 px-3">BERGABUNG</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    <template x-if="loading">
                        <tr><td colspan="4" class="py-10 text-center text-slate-400 font-semibold"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat pengguna…</td></tr>
                    </template>
                    <template x-if="!loading && !users.length">
                        <tr><td colspan="4" class="py-10 text-center text-slate-400 font-semibold">Tidak ada pengguna untuk filter ini.</td></tr>
                    </template>

                    <template x-for="user in users" :key="user.id_user">
                        <tr class="hover:bg-slate-50/80 transition">

                            <!-- Pengguna & Kontak -->
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-slate-100 text-slate-700 rounded-full flex items-center justify-center font-black text-xs shrink-0 border border-slate-200" x-text="inisial(user.nama_pengguna)"></div>
                                    <div>
                                        <strong class="text-slate-900 font-extrabold block text-xs" x-text="user.nama_pengguna"></strong>
                                        <span class="text-[10px] text-slate-500 block" x-text="user.email"></span>
                                        <span class="text-[9px] text-slate-400 font-mono block mt-0.5" x-text="user.nomor_telepon ?? '—'"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Peran -->
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] inline-block uppercase" :class="roleBadge(user.peran)" x-text="user.peran"></span>
                            </td>

                            <!-- ID -->
                            <td class="py-3.5 px-3 font-mono text-[10px] text-slate-500">
                                <span x-text="(user.id_user ?? '').slice(0, 13) + '…'"></span>
                            </td>

                            <!-- Bergabung -->
                            <td class="py-3.5 px-3">
                                <span class="text-slate-500 text-[11px]" x-text="tanggal(user.dibuat_pada)"></span>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-semibold text-slate-500">
            <div class="flex items-center gap-2">
                <span>Menampilkan halaman <strong x-text="meta?.current_page ?? 1"></strong> — total <strong x-text="num(meta?.total ?? 0)"></strong> pengguna</span>
            </div>

            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold">
                <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40 flex items-center gap-1">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    <span>Sebelumnya</span>
                </button>

                <span class="px-2 text-slate-700" x-text="(meta?.current_page ?? 1) + ' / ' + (meta?.last_page ?? 1)"></span>

                <button type="button" @click="goto(page + 1)" :disabled="meta && page >= meta.last_page"
                        class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40 flex items-center gap-1">
                    <span>Selanjutnya</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
            </div>
        </div>

    </div>

    <!-- 5. BOTTOM NOTICE ALERT -->
    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-shield-halved text-sky-700"></i>
            <span>Data pelanggan bersifat rahasia. API saat ini hanya mendukung lihat & tambah pengguna (belum ada ubah/hapus).</span>
        </div>
        <span class="text-[10px] text-slate-400 shrink-0">GET /users • POST /users</span>
    </div>

    </div>

    <!-- ===== MODAL TAMBAH PENGGUNA ===== -->
    <div x-show="showModalTambah" x-cloak
         class="fixed inset-0 z-[9999] bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
         @keydown.escape.window="showModalTambah = false">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-100" @click.outside="showModalTambah = false">

            <div class="bg-[#0A192F] text-white p-5 px-6 rounded-t-2xl flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base">Tambah Pengguna</h3>
                    <p class="text-[11px] text-slate-300">POST /users — peran hanya pelanggan atau admin.</p>
                </div>
                <button type="button" @click="showModalTambah = false" class="w-8 h-8 rounded-xl bg-slate-800/80 text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center justify-center cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form @submit.prevent="simpanUser()" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="form.nama_pengguna" required
                           class="w-full border border-slate-200 rounded-xl p-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500"
                           placeholder="Contoh: Sekar Larasati">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                    <input type="email" x-model="form.email" required
                           class="w-full border border-slate-200 rounded-xl p-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500"
                           placeholder="nama@email.com">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor telepon</label>
                    <input type="text" x-model="form.nomor_telepon"
                           class="w-full border border-slate-200 rounded-xl p-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500"
                           placeholder="08xxxxxxxxxx">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Peran <span class="text-rose-500">*</span></label>
                        <select x-model="form.peran" class="w-full border border-slate-200 rounded-xl p-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <option value="pelanggan">Pelanggan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kata sandi awal <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="form.kata_sandi" required autocomplete="off"
                               class="w-full border border-slate-200 rounded-xl p-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500"
                               placeholder="min. 8 karakter">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showModalTambah = false" class="px-5 py-2.5 border border-slate-200 hover:bg-slate-100 rounded-xl font-bold text-slate-600 transition">Batal</button>
                    <button type="submit" :disabled="savingUser" class="px-5 py-2.5 bg-sky-400 hover:bg-sky-500 text-slate-900 font-extrabold rounded-xl transition shadow-sm disabled:opacity-60">
                        <span x-show="!savingUser">Simpan Pengguna</span>
                        <span x-show="savingUser"><i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
