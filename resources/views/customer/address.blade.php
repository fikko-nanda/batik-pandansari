@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        showAddModal: false,
        editingId: null,          // null = tambah baru
        addresses: [],
        loading: true,
        needLogin: false,
        errorMsg: '',
        saving: false,
        msg: '',
        msgOk: false,
        user: null,
        addressesCount: 0,
        form: { label_alamat: '', nama_penerima: '', nomor_telepon: '', nama_provinsi: '', nama_kota: '', nama_kecamatan: '', kode_pos: '', alamat_lengkap: '', alamat_utama: false },

        get isEdit() { return this.editingId !== null; },
        initialOf(u) {
            const n = u?.nama_pengguna ?? '';
            return n.split(' ').map(x => x[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || 'BP';
        },

        lineOf(a) {
            return [a.alamat_lengkap, a.nama_kecamatan, a.nama_kota, a.nama_provinsi, a.kode_pos].filter(Boolean).join(', ');
        },
        updatedLabel(a) {
            try { return 'Terakhir diperbarui: ' + new Date(a.diperbarui_pada).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }); }
            catch { return ''; }
        },

        async load() {
            this.loading = true;
            const res = await PandansariAPI.addresses();
            this.loading = false;
            if (res.ok) this.addresses = res.data ?? [];
            else if (res.status === 401) this.needLogin = true;
            else this.errorMsg = res.message;
            return res;
        },

        bukaTambah() {
            this.editingId = null;
            this.form = { label_alamat: '', nama_penerima: '', nomor_telepon: '', nama_provinsi: '', nama_kota: '', nama_kecamatan: '', kode_pos: '', alamat_lengkap: '', alamat_utama: false };
            this.showAddModal = true;
        },
        bukaUbah(a) {
            this.editingId = a.id_user_address;
            this.form = {
                label_alamat: a.label_alamat ?? '',
                nama_penerima: a.nama_penerima ?? '',
                nomor_telepon: a.nomor_telepon ?? '',
                nama_provinsi: a.nama_provinsi ?? '',
                nama_kota: a.nama_kota ?? '',
                nama_kecamatan: a.nama_kecamatan ?? '',
                kode_pos: a.kode_pos ?? '',
                alamat_lengkap: a.alamat_lengkap ?? '',
                alamat_utama: !!a.alamat_utama,
            };
            this.showAddModal = true;
        },

        async simpan() {
            this.saving = true;
            const res = this.isEdit
                ? await PandansariAPI.updateAddress(this.editingId, this.form)
                : await PandansariAPI.addAddress(this.form);
            this.saving = false;
            if (res.ok) {
                this.showAddModal = false;
                this.tampil(this.isEdit ? 'Alamat berhasil diperbarui.' : 'Alamat berhasil ditambahkan.', true);
                await this.load();
            } else {
                this.tampil(res.message + (Object.values(res.errors ?? {}).flat().join(' ') || ''), false);
            }
        },
        async hapus(a) {
            if (!confirm('Hapus alamat "' + (a.label_alamat ?? '') + '"?')) return;
            const res = await PandansariAPI.deleteAddress(a.id_user_address);
            if (res.ok) { this.tampil('Alamat berhasil dihapus.', true); await this.load(); }
            else this.tampil(res.message, false);
        },

        async loadIdentity() {
            this.user = PandansariAPI.getUser();
            if (PandansariAPI.getToken()) {
                await PandansariAPI.me();
                this.user = PandansariAPI.getUser();
                this.addressesCount = this.addresses.length;
            }
        },
        async keluar() {
            await PandansariAPI.logout();
            PandansariAPI.clearSession();
            window.location.href = '/';
        },

        tampil(msg, ok) {
            this.msg = msg; this.msgOk = ok;
            setTimeout(() => { this.msg = ''; }, 4000);
        },

        async init() {
            if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
            await this.load();
            await this.loadIdentity();
        }
     }">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb Navigation -->
        <nav class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-6">
            <a href="/" class="hover:text-black transition">Beranda</a>
            <span>/</span>
            <a href="/account" class="hover:text-black transition">Akun Saya</a>
            <span>/</span>
            <span class="text-black font-bold">Buku Alamat Pengiriman</span>
        </nav>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDEBAR: Informasi Profil & Navigasi Akun (4 Kolom) -->
            <div class="lg:col-span-4 space-y-4">
                
                <!-- Card Profile Header (identitas dari API) -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm text-center space-y-3">
                    <!-- Avatar Inisial -->
                    <div class="relative w-16 h-16 bg-[#1E293B] text-white font-bold text-xl rounded-2xl flex items-center justify-center mx-auto shadow-md">
                        <span x-text="initialOf(user) || 'BP'"></span>
                    </div>

                    <!-- User Information -->
                    <div>
                        <h2 class="text-lg font-bold text-[#0F1A24]" x-text="user?.nama_pengguna ?? 'Pengguna'"></h2>
                        <p class="text-xs text-slate-400 font-medium" x-text="user?.email ?? ''"></p>
                    </div>
                </div>

                <!-- Navigation Menu Sidebar -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden text-xs divide-y divide-slate-100">
                    <a href="/profile" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-regular fa-user text-slate-400 text-sm"></i>
                            <span class="font-semibold">Profil & Biodata Diri</span>
                        </div>
                        <i class="fa-solid fa-angle-right text-slate-300"></i>
                    </a>

                    <a href="/orders" class="flex items-center justify-between p-4 text-slate-600 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-receipt text-slate-400 text-sm"></i>
                            <span class="font-semibold">Riwayat Pesanan</span>
                        </div>
                        <i class="fa-solid fa-angle-right text-slate-300"></i>
                    </a>

                    <!-- Active Menu: Buku Alamat -->
                    <a href="/address" class="flex items-center justify-between p-4 bg-sky-50/50 text-[#0369A1] font-bold border-l-4 border-[#0369A1]">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-location-dot text-[#0369A1] text-sm"></i>
                            <span>Buku Alamat Pengiriman</span>
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
                        <i class="fa-solid fa-angle-right text-slate-300"></i>
                    </a>

                    <div class="p-4 flex items-center justify-between bg-slate-50/50">
                        <a href="#" @click.prevent="keluar()" class="text-rose-600 font-bold flex items-center gap-2 hover:underline">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar Akun</span>
                        </a>
                        <span class="text-[9px] font-bold text-rose-500 uppercase">SIGN OUT</span>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDE: Content Utama Buku Alamat Pengiriman (8 Kolom) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Header Section & Tombol Tambah Alamat -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-[10px] font-bold tracking-wider uppercase text-slate-400 block mb-1">DESTINASI PENGHANTARAN</span>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Buku Alamat Pengiriman</h1>
                        <p class="text-xs text-slate-500 mt-1">Kelola alamat tujuan pengiriman pesanan wastra Anda dengan presisi kurasi.</p>
                    </div>

                    <button @click="bukaTambah()"
                            type="button" 
                            class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] font-bold text-xs px-4 py-3 rounded-xl transition flex items-center justify-center gap-2 shrink-0 shadow-sm cursor-pointer">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>TAMBAH ALAMAT BARU</span>
                    </button>
                </div>

                <!-- Banner Status -->
                <template x-if="needLogin">
                    <div class="bg-sky-50 border border-sky-200 text-[#0369A1] text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-3">
                        <span><i class="fa-solid fa-lock mr-1.5"></i> Masuk untuk mengelola buku alamat.</span>
                        <a href="/login" class="bg-[#0F1A24] text-white text-xs font-bold px-4 py-2 rounded-xl">MASUK →</a>
                    </div>
                </template>
                <template x-if="errorMsg">
                    <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span x-text="errorMsg"></span>
                    </div>
                </template>
                <div x-show="msg" class="rounded-xl text-xs font-semibold px-4 py-3 flex items-center gap-2"
                     :class="msgOk ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'">
                    <i :class="msgOk ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                    <span x-text="msg"></span>
                </div>

                <!-- Lista Alamat Pengiriman (dari GET /api/addresses) -->
                <div class="space-y-4" x-show="!needLogin">
                    <!-- Loading -->
                    <template x-if="loading">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                            <i class="fa-solid fa-circle-notch fa-spin text-xl text-sky-500"></i>
                            <p class="text-xs text-slate-500 mt-3 font-semibold">Memuat alamat…</p>
                        </div>
                    </template>

                    <!-- Kosong -->
                    <template x-if="!loading && !addresses.length">
                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                            <i class="fa-regular fa-address-book text-3xl text-slate-300 block mb-3"></i>
                            <p class="text-sm font-bold text-[#0F1A24]">Belum ada alamat tersimpan</p>
                            <p class="text-xs text-slate-500 mt-1">Tambahkan alamat untuk mempermudah proses checkout.</p>
                            <button type="button" @click="bukaTambah()" class="inline-block mt-4 bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">TAMBAH ALAMAT PERTAMA →</button>
                        </div>
                    </template>

                    <template x-for="item in addresses" :key="item.id_user_address">
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4 relative">

                            <!-- Badges Header Alamat & Action Ubah -->
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                                    <span x-show="item.alamat_utama" class="bg-[#BAE6FD] text-[#0369A1] px-2.5 py-1 rounded-md uppercase flex items-center gap-1">
                                        ★ ALAMAT UTAMA
                                    </span>
                                    <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md uppercase" x-text="item.label_alamat ?? 'Alamat'"></span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="button" @click="bukaUbah(item)" class="text-xs font-bold text-slate-600 hover:text-black transition flex items-center gap-1 cursor-pointer">
                                        <i class="fa-regular fa-pen-to-square"></i> Ubah Alamat
                                    </button>
                                    <button type="button" @click="hapus(item)" class="text-xs font-bold text-rose-500 hover:text-rose-700 transition flex items-center gap-1 cursor-pointer">
                                        <i class="fa-regular fa-trash-can"></i> Hapus
                                    </button>
                                </div>
                            </div>

                            <!-- Detail Rincian Alamat -->
                            <div class="space-y-1.5 text-xs">
                                <h3 class="text-base font-bold text-[#0F1A24]" x-text="item.nama_penerima"></h3>

                                <p class="text-slate-600 font-medium flex items-center gap-2">
                                    <i class="fa-solid fa-phone text-slate-400 text-[11px]"></i>
                                    <span x-text="item.nomor_telepon"></span>
                                </p>

                                <p class="text-slate-600 leading-relaxed pt-1" x-text="lineOf(item)"></p>
                            </div>

                            <!-- Footer Log -->
                            <div class="flex items-center justify-end pt-2 border-t border-slate-100 text-[11px]">
                                <span class="text-slate-400 text-[10px]" x-text="updatedLabel(item)"></span>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- Modal Tambah / Ubah Alamat (POST/PUT /api/addresses) -->
                <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(15, 26, 36, 0.5);">
                    <div @click.outside="showAddModal = false" class="bg-white w-full max-w-lg rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto">

                        <div class="flex items-center justify-between p-5 border-b border-slate-100">
                            <h2 class="text-base font-bold text-[#0F1A24]" x-text="isEdit ? 'Ubah Alamat' : 'Tambah Alamat Baru'"></h2>
                            <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer p-1"><i class="fa-solid fa-xmark"></i></button>
                        </div>

                        <form @submit.prevent="simpan()" class="p-5 space-y-3 text-xs">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Label Alamat <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="form.label_alamat" placeholder="cth: Rumah, Kantor" required
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Penerima <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="form.nama_penerima" required
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nomor Telepon <span class="text-rose-500">*</span></label>
                                    <input type="text" x-model="form.nomor_telepon" required
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Provinsi</label>
                                    <input type="text" x-model="form.nama_provinsi"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kota/Kabupaten</label>
                                    <input type="text" x-model="form.nama_kota"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kecamatan</label>
                                    <input type="text" x-model="form.nama_kecamatan"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Kode Pos</label>
                                <input type="text" x-model="form.kode_pos"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alamat Lengkap <span class="text-rose-500">*</span></label>
                                <textarea x-model="form.alamat_lengkap" rows="3" required placeholder="Jl., RT/RW, patokan…"
                                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                            </div>
                            <label class="flex items-center gap-2 font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" x-model="form.alamat_utama" class="rounded text-sky-600 focus:ring-sky-500 w-4 h-4">
                                <span>Jadikan alamat utama</span>
                            </label>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button type="button" @click="showAddModal = false"
                                        class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition cursor-pointer">BATAL</button>
                                <button type="submit" :disabled="saving"
                                        class="bg-[#0F1A24] hover:bg-slate-800 text-white font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 cursor-pointer disabled:opacity-60">
                                    <span x-show="!saving" x-text="isEdit ? 'SIMPAN PERUBAHAN' : 'SIMPAN ALAMAT'"></span>
                                    <span x-show="saving"><i class="fa-solid fa-circle-notch fa-spin"></i> MENYIMPAN…</span>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection