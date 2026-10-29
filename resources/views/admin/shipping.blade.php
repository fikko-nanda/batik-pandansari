@extends('layouts.admin')

@section('content')
<div x-data="adminShipping()" x-init="init()" class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6 font-['Plus_Jakarta_Sans']">

    <!-- Loading Awal -->
    <template x-if="loading && !needLogin && !notAdmin">
        <div class="py-16 text-center">
            <i class="fa-solid fa-circle-notch fa-spin text-2xl text-sky-500"></i>
            <p class="text-xs text-slate-500 mt-3 font-semibold">Memuat konfigurasi pengiriman &amp; logistik…</p>
        </div>
    </template>

    <!-- State Login / Bukan Admin -->
    <template x-if="needLogin">
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
            <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
            <p class="text-sm font-bold text-[#0F1A24]">Sesi berakhir — silakan login sebagai admin.</p>
            <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
        </div>
    </template>
    <template x-if="notAdmin">
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
            <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
            <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
        </div>
    </template>

    <!-- Toast Alert Teleported ke Body (z-[70] di atas backdrop modal) -->
    <template x-teleport="body">
        <div x-show="notice" x-cloak x-transition
             class="fixed top-4 right-4 z-[70] max-w-md text-sm font-bold rounded-2xl px-5 py-4 shadow-2xl border flex items-start gap-3"
             :class="noticeOk ? 'bg-emerald-600 text-white border-emerald-700' : 'bg-rose-600 text-white border-rose-700'"
             role="alert">
            <i class="fa-solid mt-0.5" :class="noticeOk ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
            <div class="flex-1">
                <span x-text="notice"></span>
            </div>
            <button type="button" @click="notice = ''" class="text-white/80 hover:text-white transition cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </template>

    <div x-show="!loading && !needLogin && !notAdmin" x-cloak class="space-y-6">

        <!-- 1. HEADER BANNER -->
        <div class="bg-[#0A192F] text-white p-6 sm:p-8 rounded-2xl relative overflow-hidden shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-3 max-w-2xl relative z-10">
                <span class="text-[10px] font-bold tracking-[0.2em] uppercase text-sky-400 block">LOGISTIK &amp; KALKULASI RUTE</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                    Pengaturan Ongkir &amp; Zona Tarif Pengiriman
                </h1>
                <p class="text-xs text-slate-300 leading-relaxed font-normal">
                    Atur titik koordinat toko/gudang asal pengiriman (OpenStreetMap) dan kelola rentang tarif zona berbasis rute OSRM.
                </p>
            </div>
            <div class="relative z-10 flex flex-wrap items-center gap-2">
                <button type="button" @click="bukaModalZona()"
                        class="bg-sky-400 hover:bg-sky-300 text-slate-900 font-bold text-xs px-4 py-3 rounded-xl transition flex items-center gap-2 shadow-sm cursor-pointer">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>TAMBAH ZONA BARU</span>
                </button>
                <button type="button" @click="loadData()"
                        class="bg-slate-800 hover:bg-slate-700 text-white p-3 rounded-xl transition cursor-pointer" title="Segarkan Data">
                    <i class="fa-solid fa-rotate-right text-xs"></i>
                </button>
            </div>
        </div>

        <!-- 2. STATUS ALERT: LOKASI ASAL TOKO -->
        <div x-show="!isOriginSet" x-cloak
             class="bg-rose-50 border-2 border-rose-300 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-rose-900">PERINGATAN: Lokasi Asal Toko Belum Dikonfigurasi!</h3>
                    <p class="text-xs text-rose-700 mt-1 leading-relaxed">
                        Checkout pelanggan saat ini terkunci karena sistem tidak dapat menghitung rute OSRM tanpa koordinat asal toko. Tentukan koordinat toko di bawah ini lalu klik Simpan.
                    </p>
                </div>
            </div>
            <button type="button" @click="focusSearchToko()"
                    class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl whitespace-nowrap transition self-start sm:self-auto cursor-pointer">
                Tentukan Lokasi Toko &darr;
            </button>
        </div>

        <div x-show="isOriginSet" x-cloak
             class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3 text-xs text-emerald-800 shadow-sm">
            <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="flex-1 min-w-0">
                <span class="font-extrabold block">Titik Koordinat Asal Toko Terkonfigurasi Aktif</span>
                <span class="text-[11px] text-emerald-700 truncate block">
                    Latitude: <strong x-text="pu.latitude"></strong> • Longitude: <strong x-text="pu.longitude"></strong> • Alamat: <span x-text="pu.alamat_lengkap || '-'"></span>
                </span>
            </div>
        </div>

        <!-- 3. KARTU 1: TITIK KOORDINAT ASAL TOKO (PROFIL USAHA) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Titik Koordinat Asal Toko (Origin Point)</h2>
                        <p class="text-[11px] text-slate-500">Digunakan oleh routing engine OSRM sebagai titik start untuk menghitung jarak ke pelanggan.</p>
                    </div>
                </div>
                <span x-show="puDirty" class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-[9px] font-extrabold uppercase tracking-wider">
                    Perubahan belum disimpan
                </span>
            </div>

            <form @submit.prevent="simpanLokasiToko()" class="space-y-4 text-xs font-medium">

                <!-- Pencarian OpenStreetMap untuk Toko -->
                <div class="relative">
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                        Cari Titik Alamat Toko di OpenStreetMap (Nominatim)
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text"
                               x-ref="searchTokoInput"
                               x-model="osmQuery"
                               @input.debounce.350ms="searchOsmStore()"
                               placeholder="Ketik nama jalan, kelurahan, atau kota toko (min. 3 karakter)…"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-10 py-2.5 font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <div x-show="osmLoading" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sky-600">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </div>
                    </div>

                    <!-- Dropdown Hasil Pencarian OSM Toko -->
                    <div x-show="osmResults.length > 0"
                         x-cloak
                         @click.outside="osmResults = []"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl z-40 max-h-56 overflow-y-auto py-1">
                        <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 flex items-center justify-between">
                            <span>Pilih Alamat Toko</span>
                            <i class="fa-solid fa-map-pin text-sky-500"></i>
                        </div>
                        <template x-for="(loc, idx) in osmResults" :key="'store-' + idx">
                            <button type="button"
                                    @click="selectOsmStore(loc)"
                                    class="w-full text-left px-3 py-2 hover:bg-sky-50 transition border-b border-slate-100 last:border-b-0 flex items-start gap-2.5 cursor-pointer">
                                <i class="fa-solid fa-location-dot text-sky-600 mt-1 shrink-0"></i>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-800 text-[11px] truncate" x-text="loc.label"></div>
                                    <div class="text-[10px] text-slate-500" x-text="'Koordinat: ' + loc.latitude + ', ' + loc.longitude"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Input Detail Alamat Lengkap Toko -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                        Alamat Lengkap Toko / Atelier <span class="text-rose-500">*</span>
                    </label>
                    <textarea x-model="pu.alamat_lengkap"
                              @input="puDirty = true"
                              rows="2"
                              required
                              placeholder="Masukkan nama jalan, nomor, RT/RW, kelurahan, dan kota toko…"
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>

                <!-- Latitude & Longitude -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            Latitude Asal Toko <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="any"
                               x-model="pu.latitude"
                               @input="puDirty = true"
                               required
                               placeholder="Contoh: -7.5684"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            Longitude Asal Toko <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="any"
                               x-model="pu.longitude"
                               @input="puDirty = true"
                               required
                               placeholder="Contoh: 110.8252"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit"
                            :disabled="savingOrigin"
                            class="bg-[#0A192F] hover:bg-slate-800 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <i class="fa-solid" :class="savingOrigin ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="savingOrigin ? 'Menyimpan…' : 'SIMPAN LOKASI ASAL TOKO'"></span>
                    </button>
                </div>
            </form>
        </div>

        <!-- 4. KARTU 2: DAFTAR ZONA TARIF PENGIRIMAN -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Daftar Zona Tarif Pengiriman</h2>
                        <p class="text-[11px] text-slate-500">Tarif otomatis dihitung dengan rumus: Biaya Dasar + (Berat dibulatkan kg × Tarif/kg).</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Cari nama zona…"
                           class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>

            <!-- Warning jika tidak ada zona aktif -->
            <div x-show="!hasActiveZones && zones.length > 0" x-cloak
                 class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>PERINGATAN: Tidak ada zona tarif yang aktif. Aktifkan minimal satu zona agar pelanggan dapat checkout.</span>
            </div>

            <!-- Tabel Zona Tarif -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 border-y border-slate-200/80 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="py-3 px-4">Nama Zona</th>
                            <th class="py-3 px-4">Rentang Jarak</th>
                            <th class="py-3 px-4">Biaya Dasar</th>
                            <th class="py-3 px-4">Biaya per Kg</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-if="filteredZones.length === 0">
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-medium">
                                    <i class="fa-solid fa-layer-group text-2xl block mb-2 text-slate-300"></i>
                                    Belum ada zona pengiriman. Klik "TAMBAH ZONA BARU" untuk membuat zona tarif.
                                </td>
                            </tr>
                        </template>

                        <template x-for="z in filteredZones" :key="z.id_zona">
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4 font-bold text-slate-900" x-text="z.nama_zona"></td>
                                <td class="py-3.5 px-4 font-medium font-mono text-slate-600">
                                    <span x-text="Number(z.jarak_min_km).toFixed(1)"></span> &ndash; <span x-text="Number(z.jarak_maks_km).toFixed(1)"></span> km
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800" x-text="formatRp(z.biaya_dasar)"></td>
                                <td class="py-3.5 px-4 font-bold text-slate-800" x-text="formatRp(z.biaya_per_kg) + ' / kg'"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <button type="button" @click="toggleStatusZona(z)"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider cursor-pointer transition"
                                            :class="z.status_aktif ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="z.status_aktif ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                        <span x-text="z.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                                    </button>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button type="button" @click="editZona(z)"
                                            class="bg-slate-100 hover:bg-sky-100 text-slate-700 hover:text-sky-700 font-bold px-3 py-1.5 rounded-lg transition text-xs cursor-pointer">
                                        <i class="fa-regular fa-pen-to-square mr-1"></i> Ubah
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL FORM: TAMBAH / EDIT ZONA TARIF (Teleported ke Body) -->
    <template x-teleport="body">
        <div x-show="showModalZona" x-cloak
             class="fixed inset-0 z-50 w-screen h-screen bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto"
             @keydown.escape.window="tutupModalZona()">

            <div class="bg-white w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl border border-slate-100 flex flex-col"
                 @click.away="tutupModalZona()">

                <!-- Header Modal -->
                <div class="bg-[#0A192F] text-white p-5 px-6 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-sm" x-text="isEditZona ? 'Ubah Zona Pengiriman' : 'Tambah Zona Pengiriman Baru'"></h3>
                            <p class="text-[11px] text-slate-300">Tentukan rentang jarak dan nominal tarif pengiriman.</p>
                        </div>
                    </div>
                    <button type="button" @click="tutupModalZona()" class="text-slate-400 hover:text-white transition cursor-pointer">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <!-- Alert Error di Dalam Modal -->
                <div x-show="modalError" x-cloak class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 shrink-0"></i>
                    <span class="flex-1" x-text="modalError"></span>
                </div>

                <!-- Form Modal -->
                <form @submit.prevent="simpanZona()" class="p-6 space-y-4 text-xs font-medium">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">
                            Nama Zona <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               x-model="formZona.nama_zona"
                               required
                               maxlength="100"
                               placeholder="Contoh: Zona 1 - Dalam Kota Surakarta"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Jarak Min (km) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   x-model="formZona.jarak_min_km"
                                   required
                                   placeholder="0"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Jarak Maks (km) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number"
                                   step="0.01"
                                   min="0.01"
                                   x-model="formZona.jarak_maks_km"
                                   required
                                   placeholder="15"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Biaya Dasar (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number"
                                   min="0"
                                   x-model="formZona.biaya_dasar"
                                   required
                                   placeholder="10000"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Biaya per Kg (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number"
                                   min="0"
                                   x-model="formZona.biaya_per_kg"
                                   required
                                   placeholder="5000"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 pt-1 cursor-pointer">
                        <input type="checkbox" x-model="formZona.status_aktif" class="rounded text-sky-600 focus:ring-sky-500 w-4 h-4">
                        <span class="font-bold text-slate-800 text-xs">Zona Aktif (berlaku untuk kalkulasi ongkir)</span>
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="tutupModalZona()"
                                class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition cursor-pointer">
                            BATAL
                        </button>
                        <button type="submit"
                                :disabled="savingZona"
                                class="bg-[#0A192F] hover:bg-slate-800 text-white font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                            <i class="fa-solid" :class="savingZona ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                            <span x-text="savingZona ? 'Menyimpan…' : (isEditZona ? 'SIMPAN PERUBAHAN' : 'BUAT ZONA')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>

<script>
    function adminShipping() {
        return {
            loading: true,
            needLogin: false,
            notAdmin: false,
            notice: '',
            noticeOk: true,
            _timerNotice: null,

            // Data Profil Usaha (Lokasi Asal)
            pu: {
                alamat_lengkap: '',
                latitude: '',
                longitude: '',
            },
            puDirty: false,
            savingOrigin: false,

            // Pencarian OSM Toko
            osmQuery: '',
            osmLoading: false,
            osmResults: [],

            // Data Zona Pengiriman
            zones: [],
            searchQuery: '',

            // Modal Form Zona
            showModalZona: false,
            isEditZona: false,
            editingZonaId: null,
            savingZona: false,
            modalError: '',
            formZona: {
                nama_zona: '',
                jarak_min_km: 0,
                jarak_maks_km: 10,
                biaya_dasar: 10000,
                biaya_per_kg: 5000,
                status_aktif: true,
            },

            get isOriginSet() {
                const lat = this.pu.latitude != null ? String(this.pu.latitude).trim() : '';
                const lng = this.pu.longitude != null ? String(this.pu.longitude).trim() : '';
                return lat !== '' && lng !== '';
            },

            get hasActiveZones() {
                return (this.zones || []).some(z => Boolean(z.status_aktif));
            },

            get filteredZones() {
                const q = this.searchQuery.trim().toLowerCase();
                if (!q) return this.zones;
                return (this.zones || []).filter(z => (z.nama_zona || '').toLowerCase().includes(q));
            },

            pesan(ok, msg) {
                this.noticeOk = ok;
                this.notice = msg;
                clearTimeout(this._timerNotice);
                this._timerNotice = setTimeout(() => { this.notice = ''; }, 6000);
            },

            formatRp(val) {
                return 'Rp ' + Number(val ?? 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
            },

            focusSearchToko() {
                this.$refs.searchTokoInput?.focus();
            },

            async loadData() {
                this.loading = true;
                await Promise.all([this.loadProfilUsaha(), this.loadZones()]);
                this.loading = false;
            },

            async loadProfilUsaha() {
                try {
                    const res = await PandansariAPI.admin.profilUsaha();
                    if (res.ok && res.data) {
                        this.pu.alamat_lengkap = res.data.alamat_lengkap ?? res.data.alamat ?? '';
                        this.pu.latitude = res.data.latitude ?? '';
                        this.pu.longitude = res.data.longitude ?? '';
                        this.puDirty = false;
                    }
                } catch (e) {
                    console.error('[Shipping] Error loadProfilUsaha:', e);
                }
            },

            async searchOsmStore() {
                const q = this.osmQuery.trim();
                if (q.length < 3) {
                    this.osmResults = [];
                    return;
                }
                this.osmLoading = true;
                try {
                    const res = await PandansariAPI.searchOsmAddress(q);
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

            selectOsmStore(loc) {
                this.pu.alamat_lengkap = loc.label || this.pu.alamat_lengkap;
                this.pu.latitude = loc.latitude ?? '';
                this.pu.longitude = loc.longitude ?? '';
                this.puDirty = true;
                this.osmResults = [];
                this.osmQuery = '';
            },

            async simpanLokasiToko() {
                if (!this.pu.latitude || !this.pu.longitude) {
                    this.pesan(false, 'Koordinat latitude dan longitude wajib diisi.');
                    return;
                }
                this.savingOrigin = true;
                try {
                    const payload = {
                        alamat_lengkap: this.pu.alamat_lengkap,
                        latitude: Number(this.pu.latitude),
                        longitude: Number(this.pu.longitude),
                    };
                    const res = await PandansariAPI.admin.updateProfilUsaha(payload);
                    if (res && res.ok) {
                        this.puDirty = false;
                        this.pesan(true, 'Titik koordinat asal toko berhasil disimpan.');
                        await this.loadProfilUsaha();
                    } else {
                        const errs = Object.values(res?.errors ?? {}).flat().join(' ');
                        this.pesan(false, (res?.message || 'Gagal menyimpan lokasi toko.') + ' ' + errs);
                    }
                } catch (e) {
                    this.pesan(false, 'Terjadi kesalahan saat menyimpan lokasi toko.');
                } finally {
                    this.savingOrigin = false;
                }
            },

            async loadZones() {
                try {
                    const res = await PandansariAPI.admin.shippingZones();
                    if (res && res.ok && Array.isArray(res.data)) {
                        this.zones = res.data;
                    } else {
                        this.zones = [];
                    }
                } catch (e) {
                    this.zones = [];
                }
            },

            bukaModalZona() {
                this.isEditZona = false;
                this.editingZonaId = null;
                this.modalError = '';
                this.formZona = {
                    nama_zona: '',
                    jarak_min_km: 0,
                    jarak_maks_km: 10,
                    biaya_dasar: 10000,
                    biaya_per_kg: 5000,
                    status_aktif: true,
                };
                this.showModalZona = true;
            },

            editZona(z) {
                this.isEditZona = true;
                this.editingZonaId = z.id_zona;
                this.modalError = '';
                this.formZona = {
                    nama_zona: z.nama_zona ?? '',
                    jarak_min_km: Number(z.jarak_min_km ?? 0),
                    jarak_maks_km: Number(z.jarak_maks_km ?? 0),
                    biaya_dasar: Number(z.biaya_dasar ?? 0),
                    biaya_per_kg: Number(z.biaya_per_kg ?? 0),
                    status_aktif: Boolean(z.status_aktif),
                };
                this.showModalZona = true;
            },

            tutupModalZona() {
                this.showModalZona = false;
                this.modalError = '';
            },

            async simpanZona() {
                this.modalError = '';
                const min = Number(this.formZona.jarak_min_km);
                const max = Number(this.formZona.jarak_maks_km);

                if (max <= min) {
                    this.modalError = 'Jarak maksimum harus lebih besar dari jarak minimum.';
                    return;
                }

                this.savingZona = true;
                try {
                    const payload = {
                        nama_zona: this.formZona.nama_zona,
                        jarak_min_km: min,
                        jarak_maks_km: max,
                        biaya_dasar: Number(this.formZona.biaya_dasar),
                        biaya_per_kg: Number(this.formZona.biaya_per_kg),
                        status_aktif: Boolean(this.formZona.status_aktif),
                    };

                    const res = this.isEditZona
                        ? await PandansariAPI.admin.updateShippingZone(this.editingZonaId, payload)
                        : await PandansariAPI.admin.createShippingZone(payload);

                    if (res && res.ok) {
                        this.showModalZona = false;
                        this.pesan(true, this.isEditZona ? 'Zona tarif berhasil diperbarui.' : 'Zona tarif baru berhasil dibuat.');
                        await this.loadZones();
                    } else {
                        const errs = Object.values(res?.errors ?? {}).flat().join(' ');
                        this.modalError = (res?.message || 'Gagal menyimpan zona pengiriman.') + ' ' + errs;
                    }
                } catch (e) {
                    this.modalError = 'Terjadi kesalahan saat memproses zona pengiriman.';
                } finally {
                    this.savingZona = false;
                }
            },

            async toggleStatusZona(z) {
                try {
                    const nextStatus = !z.status_aktif;
                    const res = await PandansariAPI.admin.updateShippingZone(z.id_zona, {
                        status_aktif: nextStatus
                    });
                    if (res && res.ok) {
                        z.status_aktif = nextStatus;
                        this.pesan(true, `Zona "${z.nama_zona}" diubah menjadi ${nextStatus ? 'Aktif' : 'Nonaktif'}.`);
                    } else {
                        this.pesan(false, res?.message || 'Gagal mengubah status zona.');
                    }
                } catch (e) {
                    this.pesan(false, 'Terjadi kesalahan saat mengubah status zona.');
                }
            },

            async init() {
                if (!PandansariAPI.getToken()) {
                    this.needLogin = true;
                    this.loading = false;
                    return;
                }
                const peran = PandansariAPI.peranUser();
                if (peran !== 'admin') {
                    this.notAdmin = true;
                    this.loading = false;
                    return;
                }
                await this.loadData();
            }
        };
    }
</script>
@endsection

