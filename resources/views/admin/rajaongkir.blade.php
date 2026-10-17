@extends('layouts.admin')

@section('content')

<div x-data="{
    weight: 600,
    destination: 'Jakarta Pusat - Menteng, DKI Jakarta (10310)',
    calculating: false,

    setPreset(gram) {
        this.weight = gram;
    },

    recalculate() {
        this.calculating = true;
        setTimeout(() => {
            this.calculating = false;
        }, 500);
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- 1. HEADER TITLE & ACTION BUTTON -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Integrasi Ekspedisi & RajaOngkir Engine
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Konfigurasi jalur kargo resmi Keraton Surakarta Hadiningrat. Menjamin kalkulasi tarif presisi hingga tingkat kecamatan dan asuransi proteksi kain pusaka.
            </p>
        </div>

        <button type="button" @click="recalculate()" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer shrink-0">
            <i class="fa-solid fa-arrows-rotate text-xs" :class="calculating ? 'animate-spin' : ''"></i>
            <span>Ping Gateway</span>
        </button>
    </div>

    <!-- 2. CARD ALAMAT GUDANG ASAL PENGIRIMAN (ORIGIN) - BIRU MUDA -->
    <div class="bg-[#AEE2F0]/60 border border-sky-200/80 rounded-2xl p-6 relative overflow-hidden shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest block">ALAMAT GUDANG ASAL PENGIRIMAN (ORIGIN)</span>
            <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-700 bg-white/70 px-2.5 py-1 rounded-lg border border-sky-200">
                <i class="fa-solid fa-warehouse text-sky-700"></i>
                <span>Gudang Induk Pandansari</span>
            </div>
        </div>

        <!-- Input Grid Disabled (Surakarta / Pasar Kliwon) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Provinsi</label>
                <div class="relative">
                    <input type="text" readonly value="Jawa Tengah" class="w-full bg-white text-slate-800 text-xs font-bold rounded-xl p-2.5 pr-8 border border-sky-200/80 focus:outline-none cursor-not-allowed">
                    <i class="fa-solid fa-lock absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Kota / Kabupaten</label>
                <div class="relative">
                    <input type="text" readonly value="Surakarta (Solo)" class="w-full bg-white text-slate-800 text-xs font-bold rounded-xl p-2.5 pr-8 border border-sky-200/80 focus:outline-none cursor-not-allowed">
                    <i class="fa-solid fa-lock absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-600 mb-1">Kecamatan</label>
                <div class="relative">
                    <input type="text" readonly value="Pasar Kliwon" class="w-full bg-white text-slate-800 text-xs font-bold rounded-xl p-2.5 pr-8 border border-sky-200/80 focus:outline-none cursor-not-allowed">
                    <i class="fa-solid fa-circle-check absolute right-3 top-1/2 -translate-y-1/2 text-sky-600 text-xs"></i>
                </div>
            </div>
        </div>

        <!-- Detail Alamat -->
        <div class="bg-white/80 border border-sky-200/80 rounded-xl p-3 flex items-start gap-2.5 text-xs text-slate-700">
            <i class="fa-solid fa-location-dot text-sky-700 text-sm mt-0.5 shrink-0"></i>
            <div>
                <strong class="font-extrabold text-slate-900 block">Dalem Kalitan & Sentra Wastra Pandansari</strong>
                <p class="text-[11px] text-slate-600 leading-tight">Jl. Gurawan No. 12, Pasar Kliwon, Surakarta 57118 • Kode Origin RajaOngkir ID: 445 (Kec. Pasar Kliwon)</p>
            </div>
        </div>
    </div>

    <!-- 3. SECTION SUBTITLE -->
    <div class="flex items-center justify-between pt-2">
        <div>
            <span class="text-[10px] font-black text-sky-700 uppercase tracking-widest block">OPSI KURIR PENGIRIMAN KOLEKSI</span>
            <h2 class="text-base font-extrabold text-slate-900">Manajemen Ekspedisi Aktif</h2>
        </div>
        <span class="text-[11px] text-slate-400 font-bold">4 Aktif • 1 Nonaktif • Asuransi Tersedia</span>
    </div>

    <!-- 4. GRID SIMULASI ONGKIR & HASIL KUOTASI -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT COLUMN: KALKULATOR SIMULASI ONGKIR (5 Cols) -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            
            <div class="flex items-center gap-3 pb-2 border-b border-slate-100">
                <div class="w-9 h-9 bg-[#AEE2F0] text-slate-900 rounded-xl flex items-center justify-center text-sm font-bold shrink-0">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Kalkulator Simulasi Ongkir</h3>
                    <p class="text-[10px] text-slate-400">Engine Uji Beban & Estimasi Realtime</p>
                </div>
            </div>

            <!-- Form Inputs -->
            <div class="space-y-3 text-xs font-medium">
                
                <!-- Kota Asal -->
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-1">KOTA ASAL (DEFAULT GUDANG)</label>
                    <div class="relative">
                        <input type="text" readonly value="Surakarta, Jawa Tengah (ID: 445)" class="w-full bg-slate-100 text-slate-700 font-bold rounded-xl p-2.5 pr-8 border border-slate-200 focus:outline-none cursor-not-allowed">
                        <i class="fa-solid fa-lock absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    </div>
                </div>

                <!-- Kecamatan Tujuan -->
                <div>
                    <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-1">KECAMATAN / KOTA TUJUAN PEMBELI</label>
                    <input type="text" x-model="destination" class="w-full bg-slate-50 text-slate-800 font-bold rounded-xl p-2.5 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>

                <!-- Berat & Estimasi Muatan -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-1">BERAT TOTAL (GRAM)</label>
                        <div class="relative">
                            <input type="number" x-model.number="weight" class="w-full bg-slate-50 text-slate-900 font-black rounded-xl p-2.5 pr-12 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-400">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">gram</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-1">ESTIMASI MUATAN</label>
                        <input type="text" readonly :value="weight <= 300 ? '1 Kain Batik' : '2 Kemeja Batik Baron'" class="w-full bg-slate-100 text-slate-700 font-bold rounded-xl p-2.5 border border-slate-200 focus:outline-none cursor-not-allowed">
                    </div>
                </div>

                <!-- Preset Buttons -->
                <div class="pt-1">
                    <span class="text-[10px] font-bold text-slate-400 mr-2">Preset:</span>
                    <div class="inline-flex flex-wrap gap-1.5">
                        <button type="button" @click="setPreset(300)" :class="weight === 300 ? 'bg-[#AEE2F0] text-slate-900 font-extrabold' : 'bg-slate-100 text-slate-600'" class="px-2.5 py-1 rounded-lg text-[10px] transition cursor-pointer">300g (1 Lembar)</button>
                        <button type="button" @click="setPreset(600)" :class="weight === 600 ? 'bg-[#AEE2F0] text-slate-900 font-extrabold' : 'bg-slate-100 text-slate-600'" class="px-2.5 py-1 rounded-lg text-[10px] transition cursor-pointer">600g (2 Pcs)</button>
                        <button type="button" @click="setPreset(1500)" :class="weight === 1500 ? 'bg-[#AEE2F0] text-slate-900 font-extrabold' : 'bg-slate-100 text-slate-600'" class="px-2.5 py-1 rounded-lg text-[10px] transition cursor-pointer">1.5kg (Gift Box)</button>
                    </div>
                </div>

            </div>

            <!-- Footer Action & Cache Info -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <span class="text-slate-400 flex items-center gap-1 font-mono">
                    <i class="fa-solid fa-database text-xs"></i>
                    <span>Query cache: 0.04 ms</span>
                </span>
                <button type="button" @click="recalculate()" class="px-5 py-2.5 bg-[#0A192F] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition shadow-sm cursor-pointer">
                    HITUNG ULANG
                </button>
            </div>

        </div>

        <!-- RIGHT COLUMN: HASIL KALKULASI KUOTASI RESMI (7 Cols) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 flex flex-col justify-between">
            
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-truck-fast text-sky-700 text-sm"></i>
                        <h3 class="font-extrabold text-slate-900 text-sm">Hasil Kalkulasi Kuotasi Resmi</h3>
                    </div>
                    <span class="text-[10px] font-mono text-slate-400">Rute: SOC ➔ CGK/Menteng</span>
                </div>

                <!-- Kurir List Cards -->
                <div class="space-y-2.5">
                    
                    <!-- 1. SiCepat BEST -->
                    <div class="bg-slate-50/80 hover:bg-slate-100/80 p-3.5 rounded-2xl border border-slate-200/80 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-[#AEE2F0]/60 text-slate-900 font-black rounded-xl flex items-center justify-center text-xs shrink-0">
                                SIC
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="text-slate-900 text-xs font-black">SiCepat BEST</strong>
                                    <span class="bg-sky-600 text-white text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider">NEXT DAY</span>
                                </div>
                                <p class="text-[10px] text-slate-500">Estimasi tiba: 1 Hari Kerja • Proteksi Khusus Sutra</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <strong class="text-slate-900 text-sm font-black block">Rp 34.000</strong>
                            <span class="text-[9px] text-slate-400">Termasuk Asuransi</span>
                        </div>
                    </div>

                    <!-- 2. JNE Regular -->
                    <div class="bg-slate-50/80 hover:bg-slate-100/80 p-3.5 rounded-2xl border border-slate-200/80 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-200 text-slate-800 font-black rounded-xl flex items-center justify-center text-xs shrink-0">
                                JNE
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="text-slate-900 text-xs font-black">JNE Regular (REG)</strong>
                                    <span class="bg-slate-200 text-slate-700 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">STANDARD</span>
                                </div>
                                <p class="text-[10px] text-slate-500">Estimasi tiba: 2 - 3 Hari Kerja</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <strong class="text-slate-900 text-sm font-black block">Rp 22.000</strong>
                            <span class="text-[9px] text-slate-400">+ Rp 1.500 Asuransi</span>
                        </div>
                    </div>

                    <!-- 3. J&T Express -->
                    <div class="bg-slate-50/80 hover:bg-slate-100/80 p-3.5 rounded-2xl border border-slate-200/80 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-200 text-slate-800 font-black rounded-xl flex items-center justify-center text-xs shrink-0">
                                J&T
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="text-slate-900 text-xs font-black">J&T Express (EZ)</strong>
                                    <span class="bg-slate-200 text-slate-700 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">EKONOMIS</span>
                                </div>
                                <p class="text-[10px] text-slate-500">Estimasi tiba: 2 - 3 Hari Kerja • Jemput Paket Bebas Biaya</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <strong class="text-slate-900 text-sm font-black block">Rp 16.000</strong>
                            <span class="text-[9px] text-slate-400">Tanpa Asuransi</span>
                        </div>
                    </div>

                    <!-- 4. Pos Kilat Khusus -->
                    <div class="bg-slate-50/80 hover:bg-slate-100/80 p-3.5 rounded-2xl border border-slate-200/80 flex items-center justify-between transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-200 text-slate-800 font-black rounded-xl flex items-center justify-center text-xs shrink-0">
                                POS
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="text-slate-900 text-xs font-black">Pos Kilat Khusus</strong>
                                    <span class="bg-slate-200 text-slate-700 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">NASIONAL</span>
                                </div>
                                <p class="text-[10px] text-slate-500">Estimasi tiba: 2 - 4 Hari Kerja</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <strong class="text-slate-900 text-sm font-black block">Rp 19.500</strong>
                            <span class="text-[9px] text-slate-400">Asuransi Termasuk</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer Server Response Time -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500 font-medium">
                <span class="flex items-center gap-1.5">
                    <i class="fa-regular fa-circle-check text-emerald-600"></i>
                    <span>Data ditarik langsung dari server resmi RajaOngkir (Subdistrict Tier)</span>
                </span>
                <span class="font-mono">Response Time: <strong class="text-emerald-600">118ms</strong></span>
            </div>

        </div>

    </div>

</div>

@endsection