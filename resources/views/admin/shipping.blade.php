@extends('layouts.admin')

@section('content')
<div x-data="shippingAdmin()" class="space-y-6">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wider text-sky-700">TRANSAKSI & LOGISTIK</p>
        <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Pengaturan Ongkos Kirim</h1>
        <p class="mt-1 text-sm text-slate-500">Tentukan lokasi asal toko dan tarif berdasarkan jarak rute OpenStreetMap.</p>
    </div>

    <div x-show="notice" x-cloak class="rounded-xl border px-4 py-3 text-sm font-semibold"
         :class="noticeOk ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
         x-text="notice"></div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">Lokasi Asal Toko</h2>
                <p class="mt-1 text-xs text-slate-500">Lokasi ini dipakai sebagai titik awal untuk menghitung jarak setiap pesanan.</p>
            </div>
            <span class="w-fit rounded-full px-3 py-1 text-[11px] font-bold"
                  :class="originReady ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'"
                  x-text="originReady ? 'Lokasi siap' : 'Lokasi belum diatur'"></span>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="space-y-3">
                <label class="block text-xs font-semibold text-slate-700">
                    Cari alamat toko di OpenStreetMap
                    <div class="mt-1 flex gap-2">
                        <input type="search" x-model="originSearch" @keydown.enter.prevent="searchOrigin()"
                               placeholder="Contoh: Batik Pandansari, Sragen"
                               class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <button type="button" @click="searchOrigin()" :disabled="searchingOrigin"
                                class="rounded-xl bg-sky-700 px-4 py-2 text-xs font-bold text-white disabled:opacity-60">
                            <span x-show="!searchingOrigin">Cari</span>
                            <span x-show="searchingOrigin">Mencari...</span>
                        </button>
                    </div>
                </label>
                <p x-show="originSearchMessage" class="text-xs"
                   :class="originSearchError ? 'text-rose-700' : 'text-slate-500'" x-text="originSearchMessage"></p>
                <div x-show="originResults.length" class="max-h-56 space-y-1 overflow-y-auto">
                    <template x-for="(result, index) in originResults" :key="`${result.latitude}-${result.longitude}-${index}`">
                        <button type="button" @click="selectOrigin(result)"
                                class="block w-full rounded-xl border border-slate-100 px-3 py-2 text-left text-xs text-slate-700 hover:border-sky-400 hover:bg-sky-50"
                                x-text="result.label"></button>
                    </template>
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-xs font-semibold text-slate-700">
                    Nama usaha
                    <input type="text" x-model="origin.nama_usaha"
                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </label>
                <label class="block text-xs font-semibold text-slate-700">
                    Alamat asal
                    <textarea rows="2" x-model="origin.alamat_lengkap"
                              class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100"></textarea>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-xs font-semibold text-slate-700">
                        Latitude
                        <input type="number" step="0.0000001" min="-90" max="90" x-model="origin.latitude"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    </label>
                    <label class="block text-xs font-semibold text-slate-700">
                        Longitude
                        <input type="number" step="0.0000001" min="-180" max="180" x-model="origin.longitude"
                               class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    </label>
                </div>
                <button type="button" @click="saveOrigin()" :disabled="savingOrigin"
                        class="rounded-xl bg-[#0F1A24] px-4 py-2.5 text-xs font-bold text-white disabled:opacity-60">
                    <span x-show="!savingOrigin">Simpan Lokasi Asal</span>
                    <span x-show="savingOrigin">Menyimpan...</span>
                </button>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">Zona dan Tarif Pengiriman</h2>
                <p class="mt-1 text-xs text-slate-500">Jarak minimum termasuk dalam zona; jarak maksimum menjadi batas eksklusif.</p>
            </div>
            <button type="button" @click="resetZone()" class="w-fit rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                Tambah Zona
            </button>
        </div>

        <form @submit.prevent="saveZone()" class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2 xl:grid-cols-6">
            <label class="text-xs font-semibold text-slate-700 xl:col-span-2">
                Nama zona
                <input required maxlength="100" x-model="zoneForm.nama_zona" placeholder="Contoh: Sragen Kota"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none">
            </label>
            <label class="text-xs font-semibold text-slate-700">
                Jarak min. (km)
                <input required type="number" min="0" max="999999" step="0.01" x-model="zoneForm.jarak_min_km"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none">
            </label>
            <label class="text-xs font-semibold text-slate-700">
                Jarak maks. (km)
                <input required type="number" min="0.01" max="999999" step="0.01" x-model="zoneForm.jarak_maks_km"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none">
            </label>
            <label class="text-xs font-semibold text-slate-700">
                Biaya dasar (Rp)
                <input required type="number" min="0" max="999999999" step="1" x-model="zoneForm.biaya_dasar"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none">
            </label>
            <label class="text-xs font-semibold text-slate-700">
                Biaya per kg (Rp)
                <input required type="number" min="0" max="999999999" step="1" x-model="zoneForm.biaya_per_kg"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none">
            </label>
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                <input type="checkbox" x-model="zoneForm.status_aktif" class="rounded border-slate-300 text-sky-700 focus:ring-sky-500">
                Zona aktif
            </label>
            <div class="flex items-center gap-2 sm:col-span-2 xl:col-span-5">
                <button type="submit" :disabled="savingZone"
                        class="rounded-lg bg-sky-700 px-4 py-2 text-xs font-bold text-white disabled:opacity-60"
                        x-text="savingZone ? 'Menyimpan...' : (zoneForm.id_zona ? 'Simpan Perubahan Zona' : 'Simpan Zona Baru')"></button>
                <button type="button" @click="resetZone()" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700">Batal</button>
            </div>
        </form>

        <p x-show="loadingZones" class="py-8 text-center text-sm text-slate-500">Memuat daftar zona...</p>
        <div x-show="!loadingZones" class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Zona</th>
                        <th class="px-3 py-3">Rentang jarak (km)</th>
                        <th class="px-3 py-3">Biaya dasar</th>
                        <th class="px-3 py-3">Biaya/kg</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="zone in zones" :key="zone.id_zona">
                        <tr>
                            <td class="px-3 py-3 font-semibold text-slate-800" x-text="zone.nama_zona"></td>
                            <td class="px-3 py-3 text-slate-600">
                                <span x-text="`${zone.jarak_min_km} km ≤ jarak < ${zone.jarak_maks_km} km`"></span>
                            </td>
                            <td class="px-3 py-3 text-slate-600" x-text="formatRupiah(zone.biaya_dasar)"></td>
                            <td class="px-3 py-3 text-slate-600" x-text="formatRupiah(zone.biaya_per_kg)"></td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2 py-1 text-[10px] font-bold"
                                      :class="zone.status_aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                      x-text="zone.status_aktif ? 'Aktif' : 'Nonaktif'"></span>
                            </td>
                            <td class="px-3 py-3">
                                <button type="button" @click="editZone(zone)" class="font-bold text-sky-700 hover:underline">Ubah</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!zones.length">
                        <td colspan="6" class="px-3 py-8 text-center text-slate-500">Belum ada zona. Tambahkan rentang jarak dan tarif untuk mengaktifkan cek ongkir.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-[11px] text-slate-500">Tarif dihitung dengan rumus biaya dasar + (berat dibulatkan ke atas, minimum 1 kg × biaya per kg). Zona aktif tidak boleh memiliki rentang yang tumpang tindih.</p>
    </section>
</div>

<script>
    function shippingAdmin() {
        return {
            zones: [],
            loadingZones: true,
            savingZone: false,
            savingOrigin: false,
            searchingOrigin: false,
            originResults: [],
            originSearch: '',
            originSearchMessage: '',
            originSearchError: false,
            origin: {
                nama_usaha: '',
                alamat_lengkap: '',
                latitude: '',
                longitude: '',
            },
            originExists: false,
            zoneForm: {},
            notice: '',
            noticeOk: true,

            get originReady() {
                return this.originExists
                    && this.origin.latitude !== ''
                    && this.origin.latitude !== null
                    && this.origin.longitude !== ''
                    && this.origin.longitude !== null;
            },

            pesan(ok, message) {
                this.noticeOk = ok;
                this.notice = message;
            },

            formatRupiah(value) {
                return 'Rp ' + Number(value ?? 0).toLocaleString('id-ID');
            },

            resetZone() {
                this.zoneForm = {
                    nama_zona: '',
                    jarak_min_km: '',
                    jarak_maks_km: '',
                    biaya_dasar: 0,
                    biaya_per_kg: 0,
                    status_aktif: true,
                };
            },

            async loadOrigin() {
                const res = await PandansariAPI.admin.profilUsaha();
                if (!res?.ok) {
                    this.pesan(false, res?.message || 'Gagal memuat profil usaha.');
                    return;
                }

                const data = res.data;
                this.originExists = Boolean(data);
                this.origin = {
                    nama_usaha: data?.nama_usaha ?? '',
                    alamat_lengkap: data?.alamat_lengkap ?? '',
                    latitude: data?.latitude ?? '',
                    longitude: data?.longitude ?? '',
                };
                this.originSearch = this.origin.alamat_lengkap;
            },

            async searchOrigin() {
                const query = this.originSearch.trim();
                if (query.length < 3) {
                    this.originSearchError = true;
                    this.originSearchMessage = 'Masukkan minimal 3 karakter untuk mencari lokasi.';
                    return;
                }

                this.searchingOrigin = true;
                this.originSearchMessage = '';
                this.originSearchError = false;
                try {
                    const res = await PandansariAPI.searchShippingAddresses(query);
                    if (!res?.ok) {
                        this.originResults = [];
                        this.originSearchError = true;
                        this.originSearchMessage = res?.message || 'Pencarian lokasi gagal.';
                        return;
                    }

                    this.originResults = Array.isArray(res.data) ? res.data : [];
                    this.originSearchMessage = this.originResults.length
                        ? 'Pilih hasil yang paling sesuai dengan lokasi toko.'
                        : 'Lokasi tidak ditemukan. Coba kata pencarian yang lebih lengkap.';
                } catch (error) {
                    this.originResults = [];
                    this.originSearchError = true;
                    this.originSearchMessage = 'Layanan pencarian lokasi tidak dapat dihubungi.';
                } finally {
                    this.searchingOrigin = false;
                }
            },

            selectOrigin(result) {
                this.origin.latitude = result.latitude;
                this.origin.longitude = result.longitude;
                this.origin.alamat_lengkap = result.label;
                this.originSearch = result.label;
                this.originResults = [];
                this.originSearchError = false;
                this.originSearchMessage = 'Koordinat lokasi toko dipilih.';
            },

            async saveOrigin() {
                if (String(this.origin.latitude ?? '').trim() === ''
                    || String(this.origin.longitude ?? '').trim() === '') {
                    this.pesan(false, 'Pilih atau masukkan koordinat latitude dan longitude.');
                    return;
                }

                const latitude = Number(this.origin.latitude);
                const longitude = Number(this.origin.longitude);
                if (!this.origin.nama_usaha.trim()) {
                    this.pesan(false, 'Nama usaha wajib diisi.');
                    return;
                }
                if (!Number.isFinite(latitude) || latitude < -90 || latitude > 90
                    || !Number.isFinite(longitude) || longitude < -180 || longitude > 180) {
                    this.pesan(false, 'Pilih atau masukkan koordinat latitude dan longitude yang valid.');
                    return;
                }

                this.savingOrigin = true;
                try {
                    const res = await PandansariAPI.admin.updateProfilUsaha({
                        nama_usaha: this.origin.nama_usaha.trim(),
                        alamat_lengkap: this.origin.alamat_lengkap.trim() || null,
                        latitude,
                        longitude,
                    });
                    if (!res?.ok) {
                        const errors = Object.values(res?.errors ?? {}).flat();
                        this.pesan(false, errors[0] || res?.message || 'Lokasi asal gagal disimpan.');
                        return;
                    }

                    this.originExists = true;
                    this.pesan(true, 'Lokasi asal pengiriman berhasil disimpan.');
                } catch (error) {
                    this.pesan(false, 'Terjadi kesalahan saat menyimpan lokasi asal.');
                } finally {
                    this.savingOrigin = false;
                }
            },

            async loadZones() {
                this.loadingZones = true;
                try {
                    const res = await PandansariAPI.admin.shippingZones();
                    if (!res?.ok) {
                        this.pesan(false, res?.message || 'Gagal memuat daftar zona ongkir.');
                        return;
                    }
                    this.zones = Array.isArray(res.data) ? res.data : [];
                } catch (error) {
                    this.pesan(false, 'Terjadi kesalahan saat memuat daftar zona ongkir.');
                } finally {
                    this.loadingZones = false;
                }
            },

            editZone(zone) {
                this.zoneForm = {
                    id_zona: zone.id_zona,
                    nama_zona: zone.nama_zona,
                    jarak_min_km: zone.jarak_min_km,
                    jarak_maks_km: zone.jarak_maks_km,
                    biaya_dasar: zone.biaya_dasar,
                    biaya_per_kg: zone.biaya_per_kg,
                    status_aktif: Boolean(zone.status_aktif),
                };
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            async saveZone() {
                this.savingZone = true;
                const payload = {
                    nama_zona: this.zoneForm.nama_zona.trim(),
                    jarak_min_km: Number(this.zoneForm.jarak_min_km),
                    jarak_maks_km: Number(this.zoneForm.jarak_maks_km),
                    biaya_dasar: Number(this.zoneForm.biaya_dasar),
                    biaya_per_kg: Number(this.zoneForm.biaya_per_kg),
                    status_aktif: Boolean(this.zoneForm.status_aktif),
                };

                try {
                    const res = this.zoneForm.id_zona
                        ? await PandansariAPI.admin.updateShippingZone(this.zoneForm.id_zona, payload)
                        : await PandansariAPI.admin.createShippingZone(payload);
                    if (!res?.ok) {
                        const errors = Object.values(res?.errors ?? {}).flat();
                        this.pesan(false, errors[0] || res?.message || 'Zona ongkir gagal disimpan.');
                        return;
                    }

                    this.resetZone();
                    await this.loadZones();
                    this.pesan(true, res.message || 'Zona ongkir berhasil disimpan.');
                } catch (error) {
                    this.pesan(false, 'Terjadi kesalahan saat menyimpan zona ongkir.');
                } finally {
                    this.savingZone = false;
                }
            },

            async init() {
                this.resetZone();
                await Promise.all([this.loadOrigin(), this.loadZones()]);
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('shippingAdmin', () => shippingAdmin());
    });

    if (window.Alpine) {
        Alpine.data('shippingAdmin', () => shippingAdmin());
    }
</script>
@endsection
