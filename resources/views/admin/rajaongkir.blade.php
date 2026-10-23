@extends('layouts.admin')

@section('content')

<div x-data="{
    // === Gate admin ===
    needLogin: false,
    notAdmin: false,

    // === Konfigurasi & kesehatan API (GET /admin/shipping/config & /health) ===
    config: null,
    health: null,
    couriers: [],
    busyCourier: '',
    loading: true,
    notice: '', noticeOk: true,

    // === Simulasi tarif (destinations search + POST /admin/shipping/simulate) ===
    dstQuery: '',
    dstResults: [],
    dstOpen: false,
    dstBusy: false,
    dest: null,          // { id, label, ... }
    weight: 600,
    courier: 'jne',
    simulating: false,
    simResult: null,
    simError: '',

    num(n) { return Number(n ?? 0).toLocaleString('id-ID'); },
    fmt(n) { return PandansariAPI.formatRp(n); },
    pesan(ok, msg) { this.noticeOk = ok; this.notice = msg; setTimeout(() => this.notice = '', 5000); },

    setPreset(gram) { this.weight = gram; },

    // === Cari tujuan (min. 3 huruf) ===
    async cariTujuan() {
        const q = this.dstQuery.trim();
        if (q.length < 3) { this.dstResults = []; this.dstOpen = false; return; }
        this.dstBusy = true;
        const res = await PandansariAPI.shippingDestinations(q);
        this.dstBusy = false;
        if (res.ok) { this.dstResults = res.data ?? []; this.dstOpen = true; }
        else { this.dstResults = []; this.pesan(false, res.message); }
    },
    pilihTujuan(d) {
        this.dest = d;
        this.dstQuery = d.label;
        this.dstOpen = false;
        this.simResult = null;
        this.simError = '';
    },
    resetTujuan() { this.dest = null; this.dstQuery = ''; this.dstResults = []; this.simResult = null; this.simError = ''; },

    // === Simulasi (HATI-HATI: memakai kuota API RajaOngkir) ===
    async simulasi() {
        if (!this.dest) { this.pesan(false, 'Pilih tujuan dari hasil pencarian dulu.'); return; }
        if (!this.weight || this.weight <= 0) { this.pesan(false, 'Berat (gram) harus lebih dari 0.'); return; }
        this.simulating = true;
        this.simResult = null;
        this.simError = '';
        const res = await PandansariAPI.admin.simulateShipping(this.dest.id, Number(this.weight), this.courier);
        this.simulating = false;
        if (res.ok) this.simResult = res.data;
        else {
            const errs = Object.values(res.errors ?? {}).flat();
            this.simError = errs[0] ?? res.message;
        }
    },

    // === Toggle kurir aktif (PUT /admin/shipping/couriers/{code}) ===
    async toggleKurir(k) {
        this.busyCourier = k.kode;
        const res = await PandansariAPI.admin.setCourier(k.kode, !k.aktif);
        this.busyCourier = '';
        if (res.ok) {
            k.aktif = !k.aktif;
            this.pesan(true, 'Kurir ' + k.kode + ' ' + (k.aktif ? 'diaktifkan' : 'dinonaktifkan') + '.');
            this.loadConfig();
        } else this.pesan(false, res.message);
    },

    async loadConfig() {
        const [c, h, k] = await Promise.all([
            PandansariAPI.admin.shippingConfig(),
            PandansariAPI.admin.shippingHealth(),
            PandansariAPI.admin.shippingCouriers(),
        ]);
        if (c.ok) this.config = c.data;
        if (h.ok) this.health = h.data;
        if (k.ok) this.couriers = k.data ?? [];
    },

    async init() {
        if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
        const me = await PandansariAPI.me();
        if (!me.ok || me.data?.peran !== 'admin') { this.notAdmin = true; this.loading = false; return; }
        await this.loadConfig();
        this.loading = false;
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- Gate -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Silakan login (akun admin) untuk membuka konfigurasi ekspedisi</p>
        <a href="/login" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>
    <div x-show="notAdmin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-[#0F1A24]">Akun ini bukan admin — akses ditolak.</p>
    </div>

    <div x-show="!needLogin && !notAdmin" x-cloak class="space-y-6">

    <!-- 1. HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Integrasi Ekspedisi & RajaOngkir Engine
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Konfigurasi jalur kargo, status kesehatan API, dan simulasi tarif — data langsung dari /admin/shipping/*.
            </p>
        </div>
        <button type="button" @click="loadConfig()"
                class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer">
            <i class="fa-solid fa-rotate text-xs"></i>
            <span>Muat Ulang Status</span>
        </button>
    </div>

    <!-- Pesan -->
    <div x-show="notice" x-cloak
         class="text-xs font-bold rounded-xl px-4 py-3 border"
         :class="noticeOk ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
         x-text="notice"></div>

    <!-- 2. STATUS KESEHATAN API + KONFIGURASI -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="font-extrabold text-slate-900 text-sm">Status API (GET /admin/shipping/health)</h2>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                      :class="health ? (health.status === 'ok' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') : 'bg-slate-100 text-slate-500'"
                      x-text="loading ? '…' : (health?.status ?? '—')"></span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="chk in ['api_key_terisi', 'base_url_valid', 'origin_terisi', 'konektivitas_https']" :key="chk">
                    <div class="flex items-center gap-2 bg-slate-50 rounded-xl px-3 py-2.5">
                        <i class="fa-solid text-sm" :class="health?.checks?.[chk] ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-rose-500'"></i>
                        <span class="font-bold text-slate-700" x-text="chk.replaceAll('_', ' ')"></span>
                    </div>
                </template>
            </div>
            <p class="text-[10px] text-slate-400">
                <i class="fa-solid fa-gauge-high mr-1"></i>
                Kuota API: <strong x-text="health ? (health.memakai_kuota_api ? 'MEMAKAI kuota' : 'tidak memakai kuota') : '…'"></strong>
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="font-extrabold text-slate-900 text-sm">Konfigurasi (GET /admin/shipping/config)</h2>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-sky-100 text-sky-800">RajaOngkir</span>
            </div>
            <div class="space-y-2 font-medium">
                <div class="flex items-center justify-between bg-slate-50 rounded-xl px-3 py-2.5">
                    <span class="text-slate-500 font-bold">Origin ID</span>
                    <strong class="text-slate-900 font-mono" x-text="config?.origin_id ?? '…'"></strong>
                </div>
                <div class="flex items-center justify-between bg-slate-50 rounded-xl px-3 py-2.5 gap-3">
                    <span class="text-slate-500 font-bold shrink-0">Base URL</span>
                    <strong class="text-slate-900 font-mono text-[10px] text-right break-all" x-text="config?.base_url ?? '…'"></strong>
                </div>
                <div class="flex items-center justify-between bg-slate-50 rounded-xl px-3 py-2.5">
                    <span class="text-slate-500 font-bold">Sumber origin</span>
                    <strong class="text-slate-900" x-text="config?.sumber_origin ?? '…'"></strong>
                </div>
                <div class="flex items-center justify-between bg-slate-50 rounded-xl px-3 py-2.5">
                    <span class="text-slate-500 font-bold">API key</span>
                    <strong class="text-emerald-700" x-text="config?.api_key_terisi ? 'terisi ✓' : 'KOSONG'"></strong>
                </div>
            </div>
        </div>

    </div>

    <!-- 3. TOGGLE KURIR -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h2 class="font-extrabold text-slate-900 text-sm">Kurir aktif (GET /admin/shipping/couriers)</h2>
                <span class="text-[11px] text-slate-400 font-bold">
                    <span x-text="couriers.filter(k => k.aktif).length"></span>/<span x-text="couriers.length"></span> aktif — klik sakelar untuk menonaktifkan/mengaktifkan (PUT /admin/shipping/couriers/{code})
                </span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <template x-for="k in couriers" :key="k.kode">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200/80 rounded-xl px-3 py-2">
                    <span class="font-mono font-black text-xs uppercase" :class="k.aktif ? 'text-slate-900' : 'text-slate-400'" x-text="k.kode"></span>
                    <button type="button" @click="toggleKurir(k)" :disabled="busyCourier === k.kode"
                            class="relative inline-flex h-4 w-8 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none disabled:opacity-50"
                            :class="k.aktif ? 'bg-sky-600' : 'bg-slate-300'">
                        <span class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow transition duration-200"
                              :class="k.aktif ? 'translate-x-4' : 'translate-x-0'"></span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- 4. SIMULASI TARIF -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h2 class="font-extrabold text-slate-900 text-sm">Simulasi tarif (POST /admin/shipping/simulate)</h2>
                <span class="text-[11px] text-slate-400 font-bold">Cari tujuan → isi berat → pilih kurir → hitung. Setiap simulasi memakai 1 kuota API per kurir.</span>
            </div>
            <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 shrink-0">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>hemat kuota
            </span>
        </div>

        <!-- Pencarian tujuan -->
        <div class="relative">
            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Tujuan pengiriman (kota/kecamatan, min. 3 huruf)</label>
            <div class="relative">
                <i class="fa-solid fa-location-dot absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" x-model="dstQuery" @input.debounce.500ms="cariTujuan()"
                       @focus="dstQuery.length >= 3 && (dstOpen = true)"
                       placeholder="Contoh: Surakarta, Menteng, 57141…"
                       class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-9 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
                <i x-show="dstBusy" class="fa-solid fa-circle-notch fa-spin absolute right-3.5 top-1/2 -translate-y-1/2 text-sky-500 text-xs"></i>
            </div>

            <!-- Hasil -->
            <div x-show="dstOpen && dstResults.length" x-cloak @click.outside="dstOpen = false"
                 class="absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto text-xs">
                <template x-for="d in dstResults" :key="d.id">
                    <button type="button" @click="pilihTujuan(d)"
                            class="w-full text-left px-3.5 py-2.5 hover:bg-sky-50 border-b border-slate-100 last:border-0 cursor-pointer">
                        <strong class="text-slate-900 block" x-text="d.label"></strong>
                        <span class="text-[10px] text-slate-400 font-mono" x-text="'id: ' + d.id + ' • zip ' + (d.zip_code ?? '-')"></span>
                    </button>
                </template>
            </div>

            <div x-show="dstOpen && !dstResults.length && !dstBusy && dstQuery.length >= 3" x-cloak
                 class="absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg px-3.5 py-3 text-xs text-slate-400">
                Tujuan tidak ditemukan.
            </div>
        </div>

        <!-- Tujuan terpilih -->
        <div x-show="dest" x-cloak class="bg-sky-50 border border-sky-200 rounded-xl px-4 py-3 flex items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 min-w-0">
                <i class="fa-solid fa-check-circle text-sky-600 shrink-0"></i>
                <span class="text-slate-800 font-bold truncate" x-text="dest?.label"></span>
            </div>
            <button type="button" @click="resetTujuan()" class="text-slate-400 hover:text-rose-500 shrink-0 cursor-pointer" title="Ganti tujuan">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Berat + kurir -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-2">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Berat (gram)</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="g in [250, 500, 1000, 2000]" :key="g">
                        <button type="button" @click="setPreset(g)"
                                class="px-3 py-2 rounded-xl font-bold transition cursor-pointer"
                                :class="weight === g ? 'bg-[#0A192F] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                x-text="g + ' g'"></button>
                    </template>
                </div>
                <input type="number" min="1" x-model.number="weight"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-black focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <div class="space-y-2">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Kurir</label>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="k in couriers.filter(c => c.aktif)" :key="k.kode">
                        <button type="button" @click="courier = k.kode"
                                class="px-3 py-2 rounded-xl font-mono font-black uppercase text-xs transition cursor-pointer"
                                :class="courier === k.kode ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                x-text="k.kode"></button>
                    </template>
                </div>
            </div>
        </div>

        <button type="button" @click="simulasi()" :disabled="simulating || !dest"
                class="w-full bg-[#0A192F] hover:bg-slate-800 text-white font-extrabold text-xs px-5 py-3 rounded-xl transition cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2">
            <i class="fa-solid" :class="simulating ? 'fa-circle-notch fa-spin' : 'fa-calculator'"></i>
            <span x-text="simulating ? 'Menghitung tarif…' : 'HITUNG TARIF'"></span>
        </button>

        <!-- Hasil simulasi -->
        <div x-show="simResult" x-cloak class="border border-emerald-200 bg-emerald-50 rounded-2xl p-4 space-y-2 text-xs">
            <span class="block text-[9px] font-black uppercase tracking-widest text-emerald-700">HASIL SIMULASI</span>
            <template x-for="(r, i) in (Array.isArray(simResult) ? simResult : [simResult])" :key="i">
                <div class="flex items-center justify-between bg-white border border-emerald-200 rounded-xl px-4 py-3">
                    <div>
                        <strong class="text-slate-900 font-extrabold block" x-text="(r.service ?? r.name ?? courier).toString().toUpperCase() + (r.description ? ' — ' + r.description : '')"></strong>
                        <span class="text-[10px] text-slate-500" x-text="(r.etd ?? r.duration ?? '') + (r.service_type ? ' • ' + r.service_type : '')"></span>
                    </div>
                    <strong class="text-emerald-700 text-sm font-black" x-text="fmt(r.cost ?? r.price ?? 0)"></strong>
                </div>
            </template>
        </div>

        <div x-show="simError" x-cloak class="border border-rose-200 bg-rose-50 rounded-2xl p-4 text-xs text-rose-700 font-bold">
            <i class="fa-solid fa-circle-xmark mr-1.5"></i>
            <span x-text="simError"></span>
            <span class="block mt-1 text-[10px] font-medium text-rose-600">
                Jika berlanjut untuk semua tujuan, kemungkinan ada masalah di endpoint backend POST /shipping/cost atau /admin/shipping/simulate — laporkan ke tim backend.
            </span>
        </div>
    </div>

    </div>

</div>

@endsection
