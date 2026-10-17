@extends('layouts.admin')

@section('content')

<div x-data="{
    searchQuery: '',
    selectedRole: 'ALL',
    selectedStatus: 'ALL',

    // Data Userlist
    users: [
        {
            id: 1,
            avatar: 'AK',
            name: 'Raden Ayu Kartika',
            email: 'kartika.ayu@gmail.com',
            code: 'PLG-01248 • Jakarta Pusat',
            role: 'Pelanggan',
            roleBadge: 'bg-slate-100 text-slate-700',
            status: 'Aktif',
            statusBadge: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            orders: 12,
            totalSpend: 24850000,
            joinedDate: '24 Okt 2026'
        },
        {
            id: 2,
            avatar: 'WD',
            name: 'Prof. Dr. Wardiman',
            email: 'wardiman@gmail.com',
            code: 'PLG-01247 • Bandung',
            role: 'Pelanggan',
            roleBadge: 'bg-slate-100 text-slate-700',
            status: 'Aktif',
            statusBadge: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            orders: 8,
            totalSpend: 13800000,
            joinedDate: '23 Okt 2026'
        },
        {
            id: 3,
            avatar: 'GM',
            name: 'G.K.R. Mangkubumi',
            email: 'mangkubumi@gmail.com',
            code: 'PLG-01246 • Yogyakarta',
            role: 'Pelanggan',
            roleBadge: 'bg-slate-100 text-slate-700',
            status: 'Aktif',
            statusBadge: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            orders: 16,
            totalSpend: 42750000,
            joinedDate: '22 Okt 2026'
        },
        {
            id: 4,
            avatar: 'AP',
            name: 'Dr. Anindita Paramitha',
            email: 'anindita.p@gmail.com',
            code: 'PLG-01245 • Surabaya',
            role: 'Pelanggan',
            roleBadge: 'bg-slate-100 text-slate-700',
            status: 'Aktif',
            statusBadge: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            orders: 5,
            totalSpend: 12450000,
            joinedDate: '21 Okt 2026'
        },
        {
            id: 5,
            avatar: 'BH',
            name: 'Bambang Haryanto',
            email: 'bambang.h@gmail.com',
            code: 'PLG-01244 • Surakarta',
            role: 'Pelanggan',
            roleBadge: 'bg-slate-100 text-slate-700',
            status: 'Nonaktif',
            statusBadge: 'bg-slate-100 text-slate-600 border border-slate-200',
            orders: 2,
            totalSpend: 3850000,
            joinedDate: '20 Okt 2026'
        },
        {
            id: 6,
            avatar: 'SL',
            name: 'Sekar Larasati',
            email: 'sekar@batikpandansari.id',
            code: 'ADM-08010 • Surakarta',
            role: 'Admin',
            roleBadge: 'bg-amber-100 text-amber-800 font-bold',
            status: 'Aktif',
            statusBadge: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            orders: '-',
            totalSpend: null,
            joinedDate: '19 Okt 2026'
        },
        {
            id: 7,
            avatar: 'DP',
            name: 'Dimas Prasetya',
            email: 'dimas@batikpandansari.id',
            code: 'KUR-80808 • Surakarta',
            role: 'Kurator',
            roleBadge: 'bg-sky-100 text-sky-800 font-bold',
            status: 'Aktif',
            statusBadge: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
            orders: '-',
            totalSpend: null,
            joinedDate: '18 Okt 2026'
        },
        {
            id: 8,
            avatar: 'AW',
            name: 'Anindya Wardhana',
            email: 'anindya.w@gmail.com',
            code: 'PLG-01243 • Semarang',
            role: 'Pelanggan',
            roleBadge: 'bg-slate-100 text-slate-700',
            status: 'Diblokir',
            statusBadge: 'bg-rose-100 text-rose-800 border border-rose-200',
            orders: 1,
            totalSpend: 1250000,
            joinedDate: '17 Okt 2026'
        }
    ],

    get filteredUsers() {
        return this.users.filter(u => {
            let matchSearch = u.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                u.email.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                u.code.toLowerCase().includes(this.searchQuery.toLowerCase());
            let matchRole = this.selectedRole === 'ALL' || u.role === this.selectedRole;
            let matchStatus = this.selectedStatus === 'ALL' || u.status === this.selectedStatus;
            return matchSearch && matchRole && matchStatus;
        });
    },

    resetFilter() {
        this.searchQuery = '';
        this.selectedRole = 'ALL';
        this.selectedStatus = 'ALL';
    }
}" class="space-y-6 text-slate-800 font-sans pb-10">

    <!-- 1. HEADER TITLE & BUTTON -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-[10px] font-black text-amber-700 uppercase tracking-widest block mb-0.5">RELASI KOLEKTOR • ATELIER PANDANSARI</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Userlist
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola pelanggan dan tim atelier, pantau aktivitas belanja, serta atur status dan hak akses pengguna.
            </p>
        </div>

        <button type="button" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Tambah Pengguna</span>
        </button>
    </div>

    <!-- 2. STATISTIC SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        
        <!-- CARD 1: TOTAL PENGGUNA (BIRU CERAH) -->
        <div class="bg-[#AEE2F0] text-slate-900 rounded-2xl p-5 relative overflow-hidden shadow-sm flex items-center justify-between">
            <div class="space-y-1 z-10">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-600 block">TOTAL PENGGUNA</span>
                <div class="text-3xl font-black text-slate-900 tracking-tight">1.248</div>
                <span class="text-[10px] font-bold text-slate-600 block">1.238 pelanggan • 10 tim</span>
            </div>
            <div class="w-10 h-10 bg-white/60 rounded-xl flex items-center justify-center text-slate-700 text-lg shrink-0 z-10">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <!-- CARD 2: PENGGUNA AKTIF (DARK NAVY) -->
        <div class="bg-[#0A192F] text-white rounded-2xl p-5 relative overflow-hidden shadow-sm flex items-center justify-between">
            <div class="space-y-1 z-10">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block">PENGGUNA AKTIF</span>
                <div class="text-3xl font-black text-white tracking-tight">1.198</div>
                <span class="text-[10px] font-bold text-slate-400 block">96% dari seluruh pengguna</span>
            </div>
            <div class="w-10 h-10 bg-sky-900/80 rounded-xl flex items-center justify-center text-sky-400 text-lg shrink-0 z-10 border border-sky-800">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>

    </div>

    <!-- 3. TOP TABS CONTROL -->
    <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3">
        <button type="button" class="px-4 py-2 bg-[#0A192F] text-white font-extrabold text-xs rounded-xl shadow-sm flex items-center gap-2">
            <span>Semua Pengguna</span>
            <span class="bg-sky-500/30 text-sky-300 text-[10px] px-2 py-0.5 rounded-full font-mono">1.248</span>
        </button>
    </div>

    <!-- 4. FILTER CONTROL STRIP -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 flex flex-wrap items-center gap-3 text-xs">
        
        <!-- Search Bar -->
        <div class="relative flex-1 min-w-[240px]">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" x-model="searchQuery" placeholder="Cari nama, email, atau ID pengguna..." class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <!-- Filter Peran -->
        <select x-model="selectedRole" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 cursor-pointer">
            <option value="ALL">Semua Peran</option>
            <option value="Pelanggan">Pelanggan</option>
            <option value="Admin">Admin</option>
            <option value="Kurator">Kurator</option>
        </select>

        <!-- Filter Status -->
        <select x-model="selectedStatus" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 cursor-pointer">
            <option value="ALL">Semua Status</option>
            <option value="Aktif">Aktif</option>
            <option value="Nonaktif">Nonaktif</option>
            <option value="Diblokir">Diblokir</option>
        </select>

        <!-- Filter Tanggal -->
        <button type="button" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-700 flex items-center gap-2">
            <span>Tanggal Bergabung</span>
            <i class="fa-regular fa-calendar text-slate-400"></i>
        </button>

        <!-- Filter Action Buttons -->
        <button type="button" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold rounded-xl px-4 py-2.5 flex items-center gap-1.5 transition cursor-pointer">
            <i class="fa-solid fa-sliders text-xs"></i>
            <span>Filter</span>
        </button>

        <button type="button" @click="resetFilter()" class="text-slate-500 hover:text-slate-900 font-bold px-2 py-2.5 transition cursor-pointer">
            Reset
        </button>

    </div>

    <!-- 5. DIREKTORI PELANGGAN & TIM ATELIER TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Direktori Pelanggan & Tim Atelier</h2>
                <span class="text-[11px] font-bold text-slate-400">1.248 pengguna terdaftar • Data per 28 Oktober 2026</span>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto text-xs font-bold text-slate-600">
                <i class="fa-solid fa-arrow-down-short-wide text-slate-400"></i>
                <select class="bg-transparent font-bold text-slate-700 focus:outline-none cursor-pointer">
                    <option>Terbaru bergabung</option>
                    <option>Total belanja tertinggi</option>
                    <option>Pesanan terbanyak</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-3 w-8">
                            <input type="checkbox" class="rounded accent-sky-600">
                        </th>
                        <th class="py-3 px-3">PENGGUNA & KONTAK</th>
                        <th class="py-3 px-3">PERAN</th>
                        <th class="py-3 px-3">STATUS</th>
                        <th class="py-3 px-3 text-center">PESANAN</th>
                        <th class="py-3 px-3 text-right">TOTAL BELANJA</th>
                        <th class="py-3 px-3">BERGABUNG</th>
                        <th class="py-3 px-3 text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    <template x-for="user in filteredUsers" :key="user.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            
                            <!-- Checkbox -->
                            <td class="py-3.5 px-3">
                                <input type="checkbox" class="rounded accent-sky-600">
                            </td>

                            <!-- Pengguna & Kontak -->
                            <td class="py-3.5 px-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-slate-100 text-slate-700 rounded-full flex items-center justify-center font-black text-xs shrink-0 border border-slate-200" x-text="user.avatar"></div>
                                    <div>
                                        <strong class="text-slate-900 font-extrabold block text-xs" x-text="user.name"></strong>
                                        <span class="text-[10px] text-slate-500 block" x-text="user.email"></span>
                                        <span class="text-[9px] text-slate-400 font-mono block mt-0.5" x-text="user.code"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Peran -->
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] inline-block" :class="user.roleBadge" x-text="user.role"></span>
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold inline-block" :class="user.statusBadge" x-text="user.status"></span>
                            </td>

                            <!-- Pesanan -->
                            <td class="py-3.5 px-3 text-center">
                                <strong class="text-slate-800 font-bold" x-text="user.orders"></strong>
                            </td>

                            <!-- Total Belanja -->
                            <td class="py-3.5 px-3 text-right">
                                <strong class="text-slate-900 font-black" x-text="user.totalSpend ? 'Rp ' + user.totalSpend.toLocaleString('id-ID') : '—'"></strong>
                            </td>

                            <!-- Bergabung -->
                            <td class="py-3.5 px-3">
                                <span class="text-slate-500 text-[11px]" x-text="user.joinedDate"></span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" class="w-7 h-7 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg flex items-center justify-center transition cursor-pointer" title="Detail Pengguna">
                                        <i class="fa-regular fa-eye text-xs"></i>
                                    </button>
                                    <button type="button" class="w-7 h-7 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg flex items-center justify-center transition cursor-pointer" title="Opsi Lain">
                                        <i class="fa-solid fa-ellipsis text-xs"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-semibold text-slate-500">
            <div class="flex items-center gap-2">
                <span>Menampilkan 1–8 dari 1.248 pengguna</span>
                <select class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-700 cursor-pointer">
                    <option>8 / halaman</option>
                    <option>20 / halaman</option>
                    <option>50 / halaman</option>
                </select>
            </div>

            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold">
                <button type="button" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer flex items-center gap-1">
                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    <span>Sebelumnya</span>
                </button>

                <button type="button" class="w-7 h-7 rounded-xl bg-[#0A192F] text-white flex items-center justify-center font-black">1</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer">2</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer">3</button>
                <span class="px-1 text-slate-400">...</span>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer">156</button>

                <button type="button" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer flex items-center gap-1">
                    <span>Selanjutnya</span>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
            </div>
        </div>

    </div>

    <!-- 6. BOTTOM NOTICE ALERT -->
    <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 font-medium">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-shield-halved text-sky-700"></i>
            <span>Data pelanggan bersifat rahasia. Perubahan peran dan status akun dicatat dalam log aktivitas atelier.</span>
        </div>
        <span class="text-[10px] text-slate-400 shrink-0">Sinkronisasi terakhir 11:20 WIB</span>
    </div>

</div>

@endsection