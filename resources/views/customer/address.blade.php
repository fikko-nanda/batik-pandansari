@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen pt-6 sm:pt-8 pb-12 sm:pb-16 font-['Plus_Jakarta_Sans']"
     x-data="customerAddress">

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
                                    <span class="px-2.5 py-1 rounded-md uppercase bg-slate-100 text-slate-600">ALAMAT PENGIRIMAN</span>
                                    <span x-show="item.latitude && item.longitude" class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-md uppercase flex items-center gap-1">
                                        <i class="fa-solid fa-location-dot"></i> KOORDINAT OSM OK
                                    </span>
                                    <span x-show="!item.latitude || !item.longitude" class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-md uppercase flex items-center gap-1">
                                        <i class="fa-solid fa-triangle-exclamation"></i> BELUM ADA TITIK PETA
                                    </span>
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
                            <!-- Alert Error Modal -->
                            <div x-show="modalError" x-cloak class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-start gap-2">
                                <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0"></i>
                                <span x-text="modalError"></span>
                            </div>

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
                                    <input type="tel"
                                           inputmode="numeric"
                                           pattern="[0-9]*"
                                           maxlength="20"
                                           x-model="form.nomor_telepon"
                                           required
                                           @input="form.nomor_telepon = $event.target.value.replace(/\D/g, ''); $event.target.value = form.nomor_telepon"
                                           @keydown="if(!['0','1','2','3','4','5','6','7','8','9','Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes($event.key) && !$event.ctrlKey && !$event.metaKey) $event.preventDefault()"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                            </div>

                            <!-- Wilayah: Provinsi, Kota/Kabupaten, Kecamatan (Tervalidasi Huruf & Spasi + Dropdown OSM) -->
                            <div class="space-y-1">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <!-- Field Provinsi -->
                                    <div class="relative">
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                            Provinsi <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="text"
                                                   x-model="form.nama_provinsi"
                                                   required
                                                   maxlength="60"
                                                   placeholder="cth: Jawa Tengah"
                                                   @input="handleWilayahInput('provinsi', $event)"
                                                   @keydown="filterWilayahKey($event)"
                                                   @focus="if(form.nama_provinsi && form.nama_provinsi.trim().length >= 3) searchOsm('provinsi')"
                                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                            <div x-show="osmLoading && osmActiveField === 'provinsi'" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                                                <i class="fa-solid fa-circle-notch fa-spin text-sky-500"></i>
                                            </div>
                                        </div>

                                        <!-- Dropdown OSM untuk Provinsi -->
                                        <div x-show="osmActiveField === 'provinsi' && (osmLoading || osmResults.length > 0)"
                                             x-cloak
                                             @click.outside="if (osmActiveField === 'provinsi') osmActiveField = ''"
                                             class="absolute left-0 top-full mt-1 w-full min-w-[280px] sm:min-w-[340px] max-w-md bg-white border border-slate-200 rounded-xl shadow-2xl z-50 max-h-56 overflow-y-auto text-xs py-1">
                                            <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 flex items-center justify-between">
                                                <span>Pilihan Wilayah OpenStreetMap</span>
                                                <i class="fa-solid fa-map-location-dot text-sky-500"></i>
                                            </div>
                                            <template x-if="osmLoading">
                                                <div class="px-3 py-4 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
                                                    <i class="fa-solid fa-circle-notch fa-spin text-sky-600"></i>
                                                    <span>Mencari di OpenStreetMap...</span>
                                                </div>
                                            </template>
                                            <template x-if="!osmLoading && osmResults.length === 0">
                                                <div class="px-3 py-3 text-center text-slate-400 text-xs">
                                                    Tidak ada wilayah ditemukan.
                                                </div>
                                            </template>
                                            <template x-for="(loc, idx) in osmResults" :key="'prov-' + idx">
                                                <button type="button"
                                                        @click="selectOsm(loc)"
                                                        class="w-full text-left px-3 py-2 hover:bg-sky-50 transition border-b border-slate-100 last:border-b-0 flex items-start gap-2.5 cursor-pointer">
                                                    <i class="fa-solid fa-map-pin text-sky-600 mt-0.5 shrink-0"></i>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-bold text-slate-800 text-[11px] truncate"
                                                             x-text="[loc.nama_provinsi, loc.nama_kota].filter(Boolean).join(', ') || loc.label"></div>
                                                        <div class="text-[10px] text-slate-500 truncate"
                                                             x-text="loc.nama_kecamatan ? 'Kec. ' + loc.nama_kecamatan : loc.label"></div>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Field Kota/Kabupaten -->
                                    <div class="relative">
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                            Kota/Kabupaten <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="text"
                                                   x-model="form.nama_kota"
                                                   required
                                                   maxlength="60"
                                                   placeholder="cth: Surakarta"
                                                   @input="handleWilayahInput('kota', $event)"
                                                   @keydown="filterWilayahKey($event)"
                                                   @focus="if(form.nama_kota && form.nama_kota.trim().length >= 3) searchOsm('kota')"
                                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                            <div x-show="osmLoading && osmActiveField === 'kota'" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                                                <i class="fa-solid fa-circle-notch fa-spin text-sky-500"></i>
                                            </div>
                                        </div>

                                        <!-- Dropdown OSM untuk Kota -->
                                        <div x-show="osmActiveField === 'kota' && (osmLoading || osmResults.length > 0)"
                                             x-cloak
                                             @click.outside="if (osmActiveField === 'kota') osmActiveField = ''"
                                             class="absolute left-0 top-full mt-1 w-full min-w-[280px] sm:min-w-[340px] max-w-md bg-white border border-slate-200 rounded-xl shadow-2xl z-50 max-h-56 overflow-y-auto text-xs py-1">
                                            <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 flex items-center justify-between">
                                                <span>Pilihan Wilayah OpenStreetMap</span>
                                                <i class="fa-solid fa-map-location-dot text-sky-500"></i>
                                            </div>
                                            <template x-if="osmLoading">
                                                <div class="px-3 py-4 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
                                                    <i class="fa-solid fa-circle-notch fa-spin text-sky-600"></i>
                                                    <span>Mencari di OpenStreetMap...</span>
                                                </div>
                                            </template>
                                            <template x-if="!osmLoading && osmResults.length === 0">
                                                <div class="px-3 py-3 text-center text-slate-400 text-xs">
                                                    Tidak ada wilayah ditemukan.
                                                </div>
                                            </template>
                                            <template x-for="(loc, idx) in osmResults" :key="'kota-' + idx">
                                                <button type="button"
                                                        @click="selectOsm(loc)"
                                                        class="w-full text-left px-3 py-2 hover:bg-sky-50 transition border-b border-slate-100 last:border-b-0 flex items-start gap-2.5 cursor-pointer">
                                                    <i class="fa-solid fa-map-pin text-sky-600 mt-0.5 shrink-0"></i>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-bold text-slate-800 text-[11px] truncate"
                                                             x-text="[loc.nama_kota, loc.nama_provinsi].filter(Boolean).join(', ') || loc.label"></div>
                                                        <div class="text-[10px] text-slate-500 truncate"
                                                             x-text="loc.nama_kecamatan ? 'Kec. ' + loc.nama_kecamatan : loc.label"></div>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Field Kecamatan -->
                                    <div class="relative">
                                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                            Kecamatan <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="text"
                                                   x-model="form.nama_kecamatan"
                                                   required
                                                   maxlength="60"
                                                   placeholder="cth: Laweyan"
                                                   @input="handleWilayahInput('kecamatan', $event)"
                                                   @keydown="filterWilayahKey($event)"
                                                   @focus="if(form.nama_kecamatan && form.nama_kecamatan.trim().length >= 3) searchOsm('kecamatan')"
                                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                            <div x-show="osmLoading && osmActiveField === 'kecamatan'" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                                                <i class="fa-solid fa-circle-notch fa-spin text-sky-500"></i>
                                            </div>
                                        </div>

                                        <!-- Dropdown OSM untuk Kecamatan -->
                                        <div x-show="osmActiveField === 'kecamatan' && (osmLoading || osmResults.length > 0)"
                                             x-cloak
                                             @click.outside="if (osmActiveField === 'kecamatan') osmActiveField = ''"
                                             class="absolute left-0 sm:left-auto sm:right-0 top-full mt-1 w-full min-w-[280px] sm:min-w-[340px] max-w-md bg-white border border-slate-200 rounded-xl shadow-2xl z-50 max-h-56 overflow-y-auto text-xs py-1">
                                            <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 flex items-center justify-between">
                                                <span>Pilihan Wilayah OpenStreetMap</span>
                                                <i class="fa-solid fa-map-location-dot text-sky-500"></i>
                                            </div>
                                            <template x-if="osmLoading">
                                                <div class="px-3 py-4 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
                                                    <i class="fa-solid fa-circle-notch fa-spin text-sky-600"></i>
                                                    <span>Mencari di OpenStreetMap...</span>
                                                </div>
                                            </template>
                                            <template x-if="!osmLoading && osmResults.length === 0">
                                                <div class="px-3 py-3 text-center text-slate-400 text-xs">
                                                    Tidak ada wilayah ditemukan.
                                                </div>
                                            </template>
                                            <template x-for="(loc, idx) in osmResults" :key="'kec-' + idx">
                                                <button type="button"
                                                        @click="selectOsm(loc)"
                                                        class="w-full text-left px-3 py-2 hover:bg-sky-50 transition border-b border-slate-100 last:border-b-0 flex items-start gap-2.5 cursor-pointer">
                                                    <i class="fa-solid fa-map-pin text-sky-600 mt-0.5 shrink-0"></i>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-bold text-slate-800 text-[11px] truncate"
                                                             x-text="[loc.nama_kecamatan, loc.nama_kota].filter(Boolean).join(', ') || loc.label"></div>
                                                        <div class="text-[10px] text-slate-500 truncate"
                                                             x-text="[loc.nama_provinsi, loc.kode_pos ? 'Kode Pos: ' + loc.kode_pos : ''].filter(Boolean).join(' • ')"></div>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Indikator koordinat OSM terhubung -->
                                <template x-if="form.latitude && form.longitude">
                                    <div class="pt-1 flex items-center gap-1.5 text-[11px] text-emerald-600 font-semibold">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>Koordinat OpenStreetMap terhubung (<span x-text="Number(form.latitude).toFixed(4) + ', ' + Number(form.longitude).toFixed(4)"></span>)</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Kode Pos (Hanya Angka & Maksimal 5 Digit) -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                    Kode Pos <span class="text-slate-400 font-normal">(5 digit angka)</span>
                                </label>
                                <input type="text"
                                       x-model="form.kode_pos"
                                       maxlength="5"
                                       inputmode="numeric"
                                       placeholder="cth: 57148"
                                       @input="form.kode_pos = $event.target.value.replace(/\D/g, '').slice(0, 5); $event.target.value = form.kode_pos"
                                       @keydown="if(!['0','1','2','3','4','5','6','7','8','9','Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes($event.key) && !$event.ctrlKey && !$event.metaKey) $event.preventDefault()"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alamat Lengkap <span class="text-rose-500">*</span></label>
                                <textarea x-model="form.alamat_lengkap" @input="hapusKoordinat()" rows="3" required placeholder="Jl., RT/RW, patokan…"
                                          class="w-full border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                            </div>
                            <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-3 space-y-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Cari lokasi untuk ongkir</label>
                                    <div class="flex gap-2">
                                        <input type="search" x-model="pencarianLokasi" @input="hapusKoordinat()"
                                               @keydown.enter.prevent="cariLokasi()"
                                               placeholder="Cari nama jalan, desa, atau kecamatan"
                                               class="min-w-0 flex-1 border border-slate-200 rounded-lg px-3 py-2 bg-white text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                        <button type="button" @click="cariLokasi()" :disabled="mencariLokasi"
                                                class="shrink-0 rounded-lg bg-sky-700 px-3 py-2 text-[11px] font-bold text-white disabled:opacity-60">
                                            <span x-show="!mencariLokasi">Cari</span>
                                            <span x-show="mencariLokasi">Mencari...</span>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-[10px] text-slate-500">Pilih hasil lokasi yang paling tepat agar jarak dan ongkir bisa dihitung.</p>
                                </div>
                                <p x-show="pesanLokasi" class="text-[11px]" :class="lokasiError ? 'text-rose-700' : 'text-slate-600'" x-text="pesanLokasi"></p>
                                <div x-show="hasilLokasi.length" class="space-y-1">
                                    <template x-for="(hasil, index) in hasilLokasi" :key="`${hasil.latitude}-${hasil.longitude}-${index}`">
                                        <button type="button" @click="pilihLokasi(hasil)"
                                                class="block w-full rounded-lg border border-sky-100 bg-white px-3 py-2 text-left text-[11px] text-slate-700 hover:border-sky-400">
                                            <span x-text="hasil.label"></span>
                                        </button>
                                    </template>
                                </div>
                                <p x-show="form.latitude !== null && form.longitude !== null" class="text-[10px] font-semibold text-emerald-700">
                                    Lokasi dipilih: <span x-text="Number(form.latitude).toFixed(6)"></span>,
                                    <span x-text="Number(form.longitude).toFixed(6)"></span>
                                </p>
                            </div>
                            <label class="flex items-center gap-2 font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" x-model="form.alamat_utama" class="rounded text-sky-600 focus:ring-sky-500 w-4 h-4">
                                <span>Jadikan alamat utama</span>
                            </label>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button type="button" @click="showAddModal = false"
                                        class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition cursor-pointer">BATAL</button>
                                <button type="submit" :disabled="saving || isSubmitting"
                                        class="bg-[#0F1A24] hover:bg-slate-800 text-white font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                    <span x-show="!saving && !isSubmitting" x-text="isEdit ? 'SIMPAN PERUBAHAN' : 'SIMPAN ALAMAT'"></span>
                                    <span x-show="saving || isSubmitting"><i class="fa-solid fa-circle-notch fa-spin"></i> MENYIMPAN…</span>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
    /**
     * Komponen Alpine.js Halaman Buku Alamat Pengiriman
     * Mengelola daftar alamat, penambahan alamat baru, pengubahan,
     * dan penghapusan alamat pengiriman pelanggan.
     */
    function customerAddress() {
        return {
            showAddModal: false,
            editingId: null, // null = tambah baru
            addresses: [],
            loading: true,
            needLogin: false,
            errorMsg: '',
            saving: false,
            isSubmitting: false,
            modalError: '',
            msg: '',
            msgOk: false,
            user: null,
            addressesCount: 0,
            pencarianLokasi: '',
            hasilLokasi: [],
            mencariLokasi: false,
            pesanLokasi: '',
            lokasiError: false,
            form: {
                label_alamat: '',
                nama_penerima: '',
                nomor_telepon: '',
                nama_provinsi: '',
                nama_kota: '',
                nama_kecamatan: '',
                kode_pos: '',
                alamat_lengkap: '',
                alamat_utama: false,
                latitude: null,
                longitude: null
            },
            osmResults: [],
            osmActiveField: '',
            osmLoading: false,
            _osmTimer: null,

            get isEdit() {
                return this.editingId !== null;
            },

            initialOf(u) {
                const n = u?.nama_pengguna ?? '';
                return n.split(' ').map(x => x[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() || 'BP';
            },

            lineOf(a) {
                return [a.alamat_lengkap, a.nama_kecamatan, a.nama_kota, a.nama_provinsi, a.kode_pos].filter(Boolean).join(', ');
            },

            updatedLabel(a) {
                try {
                    return 'Terakhir diperbarui: ' + new Date(a.diperbarui_pada).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                } catch {
                    return '';
                }
            },

            hapusKoordinat() {
                this.form.latitude = null;
                this.form.longitude = null;
                this.hasilLokasi = [];
            },

            async cariLokasi() {
                const query = this.pencarianLokasi.trim();
                if (query.length < 3) {
                    this.lokasiError = true;
                    this.pesanLokasi = 'Masukkan minimal 3 karakter untuk mencari lokasi.';
                    return;
                }

                this.mencariLokasi = true;
                this.pesanLokasi = '';
                this.lokasiError = false;
                try {
                    const res = await PandansariAPI.searchShippingAddresses(query);
                    if (!res?.ok) {
                        this.hasilLokasi = [];
                        this.lokasiError = true;
                        this.pesanLokasi = res?.message || 'Pencarian lokasi gagal.';
                        return;
                    }

                    this.hasilLokasi = Array.isArray(res.data) ? res.data : [];
                    this.pesanLokasi = this.hasilLokasi.length
                        ? 'Pilih salah satu hasil lokasi berikut.'
                        : 'Lokasi tidak ditemukan. Coba kata pencarian yang lebih lengkap.';
                } catch (error) {
                    this.hasilLokasi = [];
                    this.lokasiError = true;
                    this.pesanLokasi = 'Layanan pencarian lokasi tidak dapat dihubungi.';
                } finally {
                    this.mencariLokasi = false;
                }
            },

            pilihLokasi(hasil) {
                this.form.latitude = hasil.latitude;
                this.form.longitude = hasil.longitude;
                this.form.nama_provinsi = hasil.nama_provinsi || this.form.nama_provinsi;
                this.form.nama_kota = hasil.nama_kota || this.form.nama_kota;
                this.form.nama_kecamatan = hasil.nama_kecamatan || this.form.nama_kecamatan;
                this.form.kode_pos = hasil.kode_pos || this.form.kode_pos;
                this.form.alamat_lengkap = hasil.label || this.form.alamat_lengkap;
                this.pencarianLokasi = hasil.label || this.pencarianLokasi;
                this.hasilLokasi = [];
                this.lokasiError = false;
                this.pesanLokasi = 'Koordinat lokasi berhasil dipilih.';
            },

            async load() {
                this.loading = true;
                try {
                    const res = await PandansariAPI.addresses();
                    this.loading = false;
                    if (res && res.ok) {
                        this.addresses = res.data ?? [];
                    } else if (res && res.status === 401) {
                        this.needLogin = true;
                    } else {
                        this.errorMsg = res?.message || 'Gagal memuat alamat.';
                    }
                    return res;
                } catch (err) {
                    this.loading = false;
                    this.errorMsg = 'Terjadi kesalahan saat memuat alamat.';
                    return { ok: false };
                }
            },

            bukaTambah() {
                this.editingId = null;
                this.modalError = '';
                this.saving = false;
                this.isSubmitting = false;
                this.form = {
                    label_alamat: '',
                    nama_penerima: '',
                    nomor_telepon: '',
                    nama_provinsi: '',
                    nama_kota: '',
                    nama_kecamatan: '',
                    kode_pos: '',
                    alamat_lengkap: '',
                    alamat_utama: false,
                    latitude: null,
                    longitude: null
                };
                this.osmResults = [];
                this.osmActiveField = '';
                this.osmLoading = false;
                this.pencarianLokasi = '';
                this.pesanLokasi = '';
                this.lokasiError = false;
                this.hasilLokasi = [];
                this.showAddModal = true;
            },

            bukaUbah(a) {
                this.editingId = a.id_user_address;
                this.modalError = '';
                this.saving = false;
                this.isSubmitting = false;
                this.form = {
                    label_alamat: a.label_alamat ?? '',
                    nama_penerima: a.nama_penerima ?? '',
                    nomor_telepon: a.nomor_telepon ?? '',
                    nama_provinsi: a.nama_provinsi ?? '',
                    nama_kota: a.nama_kota ?? '',
                    nama_kecamatan: a.nama_kecamatan ?? '',
                    kode_pos: a.kode_pos ?? '',
                    alamat_lengkap: a.alamat_lengkap ?? '',
                    alamat_utama: Boolean(a.alamat_utama),
                    latitude: a.latitude != null ? Number(a.latitude) : null,
                    longitude: a.longitude != null ? Number(a.longitude) : null,
                };
                this.osmResults = [];
                this.osmActiveField = '';
                this.osmLoading = false;
                this.pencarianLokasi = a.alamat_lengkap ?? '';
                this.pesanLokasi = (a.latitude && a.longitude) ? 'Koordinat telah terpasang.' : '';
                this.lokasiError = false;
                this.hasilLokasi = [];
                this.showAddModal = true;
            },

            handleWilayahInput(field, e) {
                const raw = e.target.value;
                const sanitized = raw.replace(/[^a-zA-Z\s]/g, '');
                this.form['nama_' + field] = sanitized;
                e.target.value = sanitized;

                clearTimeout(this._osmTimer);
                if (sanitized.trim().length >= 3) {
                    this._osmTimer = setTimeout(() => {
                        this.searchOsm(field);
                    }, 350);
                } else {
                    this.osmResults = [];
                    this.osmActiveField = '';
                }
            },

            filterWilayahKey(e) {
                const isNav = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter'].includes(e.key);
                const isMod = e.ctrlKey || e.metaKey || e.altKey;
                const isLetterOrSpace = /^[a-zA-Z\s]$/.test(e.key);

                if (!isLetterOrSpace && !isNav && !isMod) {
                    e.preventDefault();
                }
            },

            async searchOsm(field) {
                this.osmActiveField = field;
                const fieldVal = (this.form['nama_' + field] || '').trim();
                if (fieldVal.length < 3) {
                    this.osmResults = [];
                    return;
                }

                let q = fieldVal;
                if (field === 'kecamatan') {
                    if (this.form.nama_kota) q += ' ' + this.form.nama_kota;
                } else if (field === 'kota') {
                    if (this.form.nama_provinsi) q += ' ' + this.form.nama_provinsi;
                }
                q = q.trim();

                this.osmLoading = true;
                try {
                    const res = await PandansariAPI.fetch('/shipping/address-search?q=' + encodeURIComponent(q));
                    if (res && res.ok && Array.isArray(res.data)) {
                        this.osmResults = res.data;
                    } else {
                        this.osmResults = [];
                    }
                } catch (e) {
                    this.osmResults = [];
                } finally {
                    this.osmLoading = false;
                }
            },

            selectOsm(loc) {
                if (loc.nama_provinsi) {
                    this.form.nama_provinsi = String(loc.nama_provinsi).replace(/[^a-zA-Z\s]/g, '').trim();
                }
                if (loc.nama_kota) {
                    this.form.nama_kota = String(loc.nama_kota).replace(/[^a-zA-Z\s]/g, '').trim();
                }
                if (loc.nama_kecamatan) {
                    this.form.nama_kecamatan = String(loc.nama_kecamatan).replace(/[^a-zA-Z\s]/g, '').trim();
                }
                if (loc.kode_pos) {
                    this.form.kode_pos = String(loc.kode_pos).replace(/\D/g, '').slice(0, 5);
                }
                this.form.latitude = loc.latitude != null ? Number(loc.latitude) : null;
                this.form.longitude = loc.longitude != null ? Number(loc.longitude) : null;
                this.osmResults = [];
                this.osmActiveField = '';
            },

            async simpan() {
                this.isSubmitting = true;
                this.saving = true;
                this.modalError = '';

                // Sesuaikan objek payload agar nama atribut persis sesuai aturan validasi backend & DB
                const payload = {
                    label_alamat: this.form.label_alamat || this.form.label || 'Rumah',
                    nama_penerima: this.form.nama_penerima,
                    nomor_telepon: this.form.nomor_telepon,
                    telepon: this.form.nomor_telepon, // alias fallback
                    nama_provinsi: this.form.nama_provinsi,
                    provinsi: this.form.nama_provinsi, // alias fallback
                    nama_kota: this.form.nama_kota,
                    kota: this.form.nama_kota, // alias fallback
                    nama_kecamatan: this.form.nama_kecamatan,
                    kecamatan: this.form.nama_kecamatan, // alias fallback
                    kode_pos: this.form.kode_pos,
                    alamat_lengkap: this.form.alamat_lengkap,
                    latitude: this.form.latitude != null ? Number(this.form.latitude) : null,
                    longitude: this.form.longitude != null ? Number(this.form.longitude) : null,
                    alamat_utama: Boolean(this.form.alamat_utama),
                    is_primary: Boolean(this.form.alamat_utama), // alias fallback
                };

                try {
                    const res = this.isEdit
                        ? await PandansariAPI.updateAddress(this.editingId, payload)
                        : await PandansariAPI.addAddress(payload);

                    if (res && res.ok) {
                        this.showAddModal = false;
                        this.tampil(this.isEdit ? 'Alamat berhasil diperbarui.' : 'Alamat berhasil ditambahkan.', true);
                        await this.load();
                    } else {
                        const errDetails = res?.errors ? Object.values(res.errors).flat().join(' ') : '';
                        const msg = (res?.message || 'Gagal menyimpan alamat.') + (errDetails ? ' (' + errDetails + ')' : '');
                        this.modalError = msg;
                        this.tampil(msg, false);
                    }
                } catch (e) {
                    console.error('[Address] Simpan error:', e);
                    this.modalError = 'Terjadi kesalahan sistem saat menyimpan alamat. Silakan coba lagi.';
                    this.tampil(this.modalError, false);
                } finally {
                    this.saving = false;
                    this.isSubmitting = false;
                }
            },

            async hapus(a) {
                if (!confirm('Hapus alamat ' + (a.label_alamat ?? '') + '?')) return;
                try {
                    const res = await PandansariAPI.deleteAddress(a.id_user_address);
                    if (res && res.ok) {
                        this.tampil('Alamat berhasil dihapus.', true);
                        await this.load();
                    } else {
                        this.tampil(res?.message || 'Gagal menghapus alamat.', false);
                    }
                } catch (e) {
                    this.tampil('Terjadi kesalahan saat menghapus alamat.', false);
                }
            },

            async loadIdentity() {
                this.user = PandansariAPI.getUser();
                if (PandansariAPI.getToken()) {
                    try {
                        await PandansariAPI.me();
                        this.user = PandansariAPI.getUser();
                        this.addressesCount = this.addresses.length;
                    } catch (e) {
                        console.warn('[Address] Error loadIdentity:', e);
                    }
                }
            },

            async keluar() {
                try {
                    await PandansariAPI.logout();
                    PandansariAPI.clearSession();
                } catch (e) {}
                window.location.href = '/';
            },

            tampil(msg, ok) {
                this.msg = msg;
                this.msgOk = ok;
                setTimeout(() => {
                    this.msg = '';
                }, 4000);
            },

            async init() {
                if (!PandansariAPI.getToken()) {
                    this.needLogin = true;
                    this.loading = false;
                    return;
                }
                await this.load();
                await this.loadIdentity();
            }
        };
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('customerAddress', () => customerAddress());
    });

    if (window.Alpine) {
        Alpine.data('customerAddress', () => customerAddress());
    }
</script>
@endsection