@extends('layouts.admin')

@section('content')

<div x-data="{
    searchQuery: '',
    selectedCategory: 'ALL',

    // Data Stok & Varian Cepat
    items: [
        {
            id: 1,
            sku: 'BP-PRG-01',
            name: 'Kemeja Parang Kencana Silk',
            sentra: 'Sentra Giriloyo • Pewarna Alami Sogan',
            category: 'SUTRA ALAMI',
            catStyle: 'bg-amber-100/70 text-amber-900 border border-amber-200/50',
            sizes: { s: 8, m: 4, l: 1, xl: 0 },
            total: 13,
            unit: 'Lembar',
            status: 'Aman',
            statusNote: 'Varian XL Kosong',
            statusStyle: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            rowBg: 'hover:bg-slate-50/80',
            active: true,
            img: '{{ asset('images/m1.png') }}'
        },
        {
            id: 2,
            sku: 'BP-KWG-04',
            name: 'Kawung Gold Prada Halus',
            sentra: 'Sentra Imogiri • Canting 0.3mm',
            category: 'BATIK TULIS HALUS',
            catStyle: 'bg-[#0A192F] text-white',
            sizes: { s: 1, m: 1, l: 0, xl: 0 },
            total: 2,
            unit: 'Lembar',
            status: 'Stok Menipis',
            statusNote: '⚠️ Sisa 2 Lembar (L & XL Habis)',
            statusStyle: 'bg-sky-200 text-sky-900 border border-sky-300 font-bold',
            rowBg: 'bg-sky-50/30 hover:bg-sky-50/60',
            active: true,
            img: '{{ asset('images/tulis.jpg') }}'
        },
        {
            id: 3,
            sku: 'BP-MGM-09',
            name: 'Megamendung Indigo Blue',
            sentra: 'Sentra Cirebon • Tembaga Cap Presisi',
            category: 'CAP HALUS PRIMISSIMA',
            catStyle: 'bg-amber-100/70 text-amber-900 border border-amber-200/50',
            sizes: { s: 6, m: 7, l: 5, xl: 3 },
            total: 21,
            unit: 'Lembar',
            status: 'Aman',
            statusNote: 'Lengkap Semua Ukuran',
            statusStyle: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            rowBg: 'hover:bg-slate-50/80',
            active: true,
            img: '{{ asset('images/cap.jpg') }}'
        },
        {
            id: 4,
            sku: 'BP-SKJ-02',
            name: 'Sarimbit Sekar Jagad Madura',
            sentra: 'Sentra Tanjungbumi • Pasangan Harmoni',
            category: 'SARIMBIT SET',
            catStyle: 'bg-amber-100/70 text-amber-900 border border-amber-200/50',
            sizes: { s: 3, m: 4, l: 2, xl: 2 },
            total: 11,
            unit: 'Pasang',
            status: 'Aman',
            statusNote: 'Rotasi Normal',
            statusStyle: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            rowBg: 'hover:bg-slate-50/80',
            active: true,
            img: '{{ asset('images/m2.png') }}'
        },
        {
            id: 5,
            sku: 'BP-TRN-05',
            name: 'Truntum Garuda Sogan',
            sentra: 'Sentra Kraton Surakarta • Kolektor Grade',
            category: 'BATIK TULIS HALUS',
            catStyle: 'bg-[#0A192F] text-white',
            sizes: { s: 0, m: 1, l: 0, xl: 0 },
            total: 1,
            unit: 'Lembar',
            status: 'Kritis (Restock!)',
            statusNote: '🚨 Kritis: 1 Lembar Tersisa',
            statusStyle: 'bg-rose-100 text-rose-800 border border-rose-200 font-bold',
            rowBg: 'bg-rose-50/40 hover:bg-rose-50/70',
            active: true,
            img: '{{ asset('images/tulis.jpg') }}'
        },
        {
            id: 6,
            sku: 'BP-SDM-03',
            name: 'Sidomukti Temanten',
            sentra: 'Sentra Pekalongan • Kain Upacara Adat',
            category: 'BATIK TULIS HALUS',
            catStyle: 'bg-[#0A192F] text-white',
            sizes: { s: 2, m: 2, l: 1, xl: 1 },
            total: 6,
            unit: 'Lembar',
            status: 'Aman',
            statusNote: 'Stok Terjaga',
            statusStyle: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            rowBg: 'hover:bg-slate-50/80',
            active: false,
            img: '{{ asset('images/m1.png') }}'
        }
    ],

    // Total hitungan lembar fisik
    calculateTotal(item) {
        return parseInt(item.sizes.s || 0) + parseInt(item.sizes.m || 0) + parseInt(item.sizes.l || 0) + parseInt(item.sizes.xl || 0);
    },

    get filteredItems() {
        return this.items.filter(i => {
            let matchesSearch = i.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                i.sku.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                i.sentra.toLowerCase().includes(this.searchQuery.toLowerCase());
            return matchesSearch;
        });
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- 1. HEADER BANNER TITLE & ACTION BUTTONS -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Kurasi Stok & Varian Cepat
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Pusat pembaruan inventaris seketika, penyesuaian saldo lembar kain, dan peringatan dini stok kritis untuk kurasi wastra adilihung.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button type="button" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-download text-slate-500"></i>
                <span>Unduh</span>
            </button>
            <button type="button" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#96d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-bolt text-slate-800"></i>
                <span>+ Perbarui Stok Cepat</span>
            </button>
        </div>
    </div>

    <!-- 2. SUMMARY CARDS WITH WAYANG BACKGROUND -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        
        <!-- Card 1: Total Produk Aktif -->
        <div class="bg-[#AEE2F0]/50 border border-sky-200/80 rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between shadow-sm">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest">TOTAL PRODUK AKTIF</span>
                <div class="text-3xl sm:text-4xl font-black text-slate-900">124 SKU</div>
            </div>

            <div class="relative z-10 mt-6 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i class="fa-regular fa-circle-check text-sky-700 text-sm"></i>
                <span>100% Terverifikasi Kurator</span>
            </div>

            <!-- Icon Badge & Wayang Watermark -->
            <div class="absolute right-4 top-4 w-9 h-9 bg-sky-200/80 rounded-xl flex items-center justify-center text-sky-800 text-sm">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <div class="absolute right-2 -bottom-6 z-0 opacity-30 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang" class="h-36 w-auto object-contain">
            </div>
        </div>

        <!-- Card 2: Total Lembar Batik -->
        <div class="bg-[#AEE2F0]/50 border border-sky-200/80 rounded-2xl p-6 relative overflow-hidden flex flex-col justify-between shadow-sm">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest">TOTAL LEMBAR BATIK</span>
                <div class="text-3xl sm:text-4xl font-black text-slate-900">842 <span class="text-xl font-bold text-slate-700">Lembar</span></div>
            </div>

            <div class="relative z-10 mt-6 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i class="fa-solid fa-boxes-stacked text-sky-700 text-sm"></i>
                <span>Tersebar di 4 Sentra Batik</span>
            </div>

            <!-- Icon Badge & Wayang Watermark -->
            <div class="absolute right-4 top-4 w-9 h-9 bg-sky-200/80 rounded-xl flex items-center justify-center text-sky-800 text-sm">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="absolute right-2 -bottom-6 z-0 opacity-30 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang" class="h-36 w-auto object-contain">
            </div>
        </div>

    </div>

    <!-- 3. SEARCH & FILTER BAR -->
    <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-1 items-center gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       x-model="searchQuery" 
                       placeholder="Cari berdasarkan SKU, nama kain, atau sentra..." 
                       class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <select class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                <option value="ALL">Semua Kategori</option>
                <option value="TULIS">Batik Tulis</option>
                <option value="CAP">Batik Cap</option>
                <option value="SARIMBIT">Sarimbit</option>
            </select>
        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500 font-bold self-end md:self-auto">
            <i class="fa-solid fa-[#AEE2F0] text-sky-500"></i>
            <span>Menampilkan <strong class="text-slate-900" x-text="filteredItems.length"></strong> 6 dari 124 SKU</span>
        </div>
    </div>

    <!-- 4. TABLE STOK & VARIAN CEPAT -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/80 text-slate-700 text-[10px] font-extrabold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4 w-28">KODE SKU</th>
                        <th class="py-3.5 px-4">KARYA & MOTIF WASTRA</th>
                        <th class="py-3.5 px-4">KATEGORI</th>
                        <th class="py-3.5 px-4 text-center">PENYESUAIAN VARIAN UKURAN</th>
                        <th class="py-3.5 px-4">STATUS & REKOMENDASI</th>
                        <th class="py-3.5 px-4 text-center">STATUS TOKO</th>
                        <th class="py-3.5 px-4 text-center">AKSI CEPAT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <template x-for="item in filteredItems" :key="item.id">
                        <tr :class="item.rowBg" class="transition">
                            
                            <!-- Kode SKU -->
                            <td class="py-4 px-4 font-mono font-black text-slate-900 text-xs" x-text="item.sku"></td>

                            <!-- Karya & Motif Wastra -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <img :src="item.img" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                                    <div>
                                        <strong class="text-slate-900 text-sm font-extrabold block" x-text="item.name"></strong>
                                        <span class="text-[10px] text-slate-500 block font-medium" x-text="item.sentra"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="py-4 px-4">
                                <span class="text-[9px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider inline-block text-center max-w-[110px]" :class="item.catStyle" x-text="item.category"></span>
                            </td>

                            <!-- Steppers Varian Ukuran (S, M, L, XL & Total) -->
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-center gap-3">
                                    <!-- S, M, L, XL Box Stepper Grid -->
                                    <div class="bg-slate-100/80 p-1.5 rounded-xl border border-slate-200/60 flex items-center gap-2">
                                        <!-- S -->
                                        <div class="text-center">
                                            <span class="text-[9px] font-bold text-slate-400 block mb-0.5">S</span>
                                            <input type="number" min="0" x-model.number="item.sizes.s" class="w-7 h-7 text-center font-black text-xs bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-sky-400" :class="item.sizes.s === 0 ? 'text-rose-600' : 'text-slate-800'">
                                        </div>
                                        <!-- M -->
                                        <div class="text-center">
                                            <span class="text-[9px] font-bold text-slate-400 block mb-0.5">M</span>
                                            <input type="number" min="0" x-model.number="item.sizes.m" class="w-7 h-7 text-center font-black text-xs bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-sky-400" :class="item.sizes.m === 0 ? 'text-rose-600' : 'text-slate-800'">
                                        </div>
                                        <!-- L -->
                                        <div class="text-center">
                                            <span class="text-[9px] font-bold text-slate-400 block mb-0.5">L</span>
                                            <input type="number" min="0" x-model.number="item.sizes.l" class="w-7 h-7 text-center font-black text-xs bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-sky-400" :class="item.sizes.l === 0 ? 'text-rose-600' : 'text-slate-800'">
                                        </div>
                                        <!-- XL -->
                                        <div class="text-center">
                                            <span class="text-[9px] font-bold text-slate-400 block mb-0.5">XL</span>
                                            <input type="number" min="0" x-model.number="item.sizes.xl" class="w-7 h-7 text-center font-black text-xs bg-white rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-sky-400" :class="item.sizes.xl === 0 ? 'text-rose-600' : 'text-slate-800'">
                                        </div>
                                    </div>

                                    <!-- Total Badge -->
                                    <span class="bg-amber-100/80 text-amber-900 border border-amber-200 text-[11px] font-extrabold px-3 py-1.5 rounded-xl shrink-0" x-text="calculateTotal(item) + ' ' + item.unit"></span>
                                </div>
                            </td>

                            <!-- Status & Rekomendasi -->
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold inline-block" :class="item.statusStyle" x-text="item.status"></span>
                                    <p class="text-[10px] font-semibold text-slate-600" x-text="item.statusNote"></p>
                                </div>
                            </td>

                            <!-- Status Toko Toggle Switch -->
                            <td class="py-4 px-4 text-center">
                                <button type="button" @click="item.active = !item.active" class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none" :class="item.active ? 'bg-sky-600' : 'bg-slate-300'">
                                    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="item.active ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </td>

                            <!-- Aksi Cepat (Simpan & Reset) -->
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="alert('Perubahan stok ' + item.sku + ' berhasil disimpan!')" class="w-8 h-8 rounded-xl bg-sky-100 text-sky-800 hover:bg-sky-200 transition flex items-center justify-center cursor-pointer" title="Simpan Stok">
                                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                                    </button>
                                    <button type="button" @click="item.sizes = {s:0, m:0, l:0, xl:0}" class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition flex items-center justify-center cursor-pointer" title="Reset Varian">
                                        <i class="fa-solid fa-rotate-left text-xs"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer Table Sync Info & Pagination -->
        <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-medium text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-rotate text-sky-500 animate-spin"></i>
                <span>Terakhir disimpan: <strong>3 menit lalu</strong> oleh <strong>K.R.H. Danureja</strong> • 124 produk tersinkronisasi otomatis ke etalase online.</span>
            </div>

            <!-- Pagination Buttons -->
            <div class="flex items-center gap-1 self-end sm:self-auto font-bold">
                <button type="button" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100">&lt;</button>
                <button type="button" class="w-7 h-7 rounded-lg bg-[#AEE2F0] text-slate-900 flex items-center justify-center">1</button>
                <button type="button" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100">2</button>
                <button type="button" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100">3</button>
                <span class="px-1 text-slate-400">...</span>
                <button type="button" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100">21</button>
                <button type="button" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100">&gt;</button>
            </div>
        </div>
    </div>

</div>

@endsection