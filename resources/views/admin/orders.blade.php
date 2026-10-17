@extends('layouts.admin')

@section('content')

<div x-data="{
    searchQuery: '',
    selectedEkspedisi: 'ALL',
    selectedPembayaran: 'ALL',
    selectedTab: 'ALL',
    selectAll: false,
    selectedRows: [],

    // Data Pesanan Masuk (Dummy Data Sesuai Desain)
    orders: [
        {
            id: 1,
            code: '#PS-2026-0894',
            date: '28 Okt 2026',
            time: '14:40 WIB',
            collector: 'Raden Mas Haryo S.',
            address: 'Menteng, Jakarta Pusat',
            phone: '+62 812-8821-4491',
            itemTitle: 'Kemeja Parang Kencana Silk',
            itemFabric: 'Sutra ATBM Baron Solo',
            itemVariant: 'Varian: Size L (Lengan Panjang)',
            totalPrice: 18650000,
            insuranceNote: '🛡️ Asuransi & Box Jati',
            paymentMethod: 'Midtrans BCA VA',
            paymentStatus: 'Lunas / Settled',
            paymentStatusStyle: 'bg-emerald-100 text-emerald-800',
            trxId: 'ID: TRX-MID-98201',
            tabCategory: 'DIPROSES',
            img: '{{ asset('images/m1.png') }}'
        },
        {
            id: 2,
            code: '#PS-2026-0893',
            date: '28 Okt 2026',
            time: '11:15 WIB',
            collector: 'G.K.R. Mangkubumi',
            address: 'Kraton, DI Yogyakarta',
            phone: '+62 811-2290-xxxx',
            itemTitle: 'Jarik Tulis Sido Mukti Canting 0',
            itemFabric: 'Primissima Gamelan 2.5m',
            itemVariant: 'Varian: Lembaran Kain Panjang',
            totalPrice: 24500000,
            insuranceNote: '📜 Sertifikat Hak Paten',
            paymentMethod: 'Midtrans QRIS',
            paymentStatus: 'Lunas / Settled',
            paymentStatusStyle: 'bg-emerald-100 text-emerald-800',
            trxId: 'GOPAY-AUTO-8812',
            tabCategory: 'DIPROSES',
            img: '{{ asset('images/tulis.jpg') }}'
        },
        {
            id: 3,
            code: '#PS-2026-0891',
            date: '28 Okt 2026',
            time: '02:02 WIB',
            collector: 'Prof. Dr. Bambang Sudiro',
            address: 'Coblong, Kota Bandung',
            phone: '+62 813-1992-0012',
            itemTitle: 'Selendang Ageng Megamendung',
            itemFabric: 'Sutra Baron Indigo Alam',
            itemVariant: 'Varian: Warna Sogan Gradasi',
            totalPrice: 8900000,
            insuranceNote: '📦 Kemasan Standar Eksklusif',
            paymentMethod: 'Mandiri Bill',
            paymentStatus: 'Menunggu Bayar',
            paymentStatusStyle: 'bg-amber-100 text-amber-800',
            trxId: 'Exp: 24 Jam',
            tabCategory: 'MENUNGGU',
            img: '{{ asset('images/cap.jpg') }}'
        },
        {
            id: 4,
            code: '#PS-2026-0887',
            date: '27 Okt 2026',
            time: '19:30 WIB',
            collector: 'Dra. Hj. Nurul Izzah',
            address: 'Gubeng, Surabaya',
            phone: '+62 821-4455-8810',
            itemTitle: 'Outer Kimono Kawung Semar',
            itemFabric: 'Batik Tulis Prada Emas',
            itemVariant: 'Varian: All Size Reguler',
            totalPrice: 12300000,
            insuranceNote: '🛡️ Asuransi Ekspedisi',
            paymentMethod: 'Kartu Kredit 3DS',
            paymentStatus: 'Lunas / Settled',
            paymentStatusStyle: 'bg-emerald-100 text-emerald-800',
            trxId: 'Visa **** 4012',
            tabCategory: 'DIKIRIM',
            img: '{{ asset('images/m2.png') }}'
        },
        {
            id: 5,
            code: '#PS-2026-0880',
            date: '27 Okt 2026',
            time: '08:10 WIB',
            collector: 'Anindya Wardhana',
            address: 'Semarang Tengah, Kota Semarang',
            phone: '+62 856-1182-xxxx',
            itemTitle: 'Batik Cap Sekar Jagad Solo',
            itemFabric: 'Katun Primissima Halus',
            itemVariant: 'Varian: Potongan 2 Meter',
            totalPrice: 3850000,
            insuranceNote: 'Batal Otomatis',
            paymentMethod: 'BNI Virtual Account',
            paymentStatus: 'Kedaluwarsa',
            paymentStatusStyle: 'bg-rose-100 text-rose-800',
            trxId: 'Batas Waktu Habis',
            tabCategory: 'DIBATALKAN',
            img: '{{ asset('images/tulis.jpg') }}'
        }
    ],

    toggleSelectAll() {
        if (this.selectAll) {
            this.selectedRows = this.filteredOrders.map(o => o.id);
        } else {
            this.selectedRows = [];
        }
    },

    get filteredOrders() {
        return this.orders.filter(o => {
            let matchSearch = o.code.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                o.collector.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                o.itemTitle.toLowerCase().includes(this.searchQuery.toLowerCase());
            let matchTab = this.selectedTab === 'ALL' || o.tabCategory === this.selectedTab;
            return matchSearch && matchTab;
        });
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- 1. HEADER TITLE & EXPORT BUTTON -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Pesanan Masuk & Logistik Transaksi
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Manajemen pemrosesan pesanan wastra adilihung, verifikasi gateway Midtrans secara real-time, dan otomasi pemenuhan ekspedisi kurir RajaOngkir.
            </p>
        </div>

        <button type="button" class="px-5 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer">
            <i class="fa-solid fa-file-export text-xs"></i>
            <span>Ekspor CSV</span>
        </button>
    </div>

    <!-- 2. ANTREAN PACKING & PICK-UP SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        
        <!-- Card 1: Antrean Packing -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">ANTREAN PACKING</span>
                <div class="text-2xl font-black text-slate-900">14 <span class="text-base font-bold text-slate-700">Pesanan</span></div>
                <p class="text-[10px] font-bold text-sky-700">Batik Tulis Prioritas</p>
            </div>
            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 text-base">
                <i class="fa-solid fa-[#AEE2F0]"></i>
                <i class="fa-solid fa-[#AEE2F0]"></i>
                <i class="fa-solid fa-box"></i>
            </div>
        </div>

        <!-- Card 2: Menunggu Pick-Up -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">MENUNGGU PICK-UP</span>
                <div class="text-2xl font-black text-slate-900">9 <span class="text-base font-bold text-slate-700">Paket</span></div>
                <p class="text-[10px] font-bold text-slate-500">SiCepat & JNE YES</p>
            </div>
            <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 text-base">
                <i class="fa-solid fa-truck-ramp-box"></i>
            </div>
        </div>

    </div>

    <!-- 3. STATUS TABS (FILTER KATALOG PESANAN) -->
    <div class="bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-sm inline-flex flex-wrap items-center gap-1 text-xs font-bold">
        <button @click="selectedTab = 'ALL'" 
                :class="selectedTab === 'ALL' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" 
                class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
            <span>Semua</span>
            <span class="px-1.5 py-0.5 text-[10px] rounded-md font-black" :class="selectedTab === 'ALL' ? 'bg-sky-500/30 text-sky-300' : 'bg-slate-100 text-slate-600'">84</span>
        </button>

        <button @click="selectedTab = 'MENUNGGU'" 
                :class="selectedTab === 'MENUNGGU' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" 
                class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
            <span>Menunggu Pembayaran</span>
            <span class="px-1.5 py-0.5 text-[10px] bg-slate-100 text-slate-600 rounded-md font-black">8</span>
        </button>

        <button @click="selectedTab = 'DIPROSES'" 
                :class="selectedTab === 'DIPROSES' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" 
                class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
            <span>Diproses</span>
            <span class="px-1.5 py-0.5 text-[10px] bg-slate-100 text-slate-600 rounded-md font-black">14</span>
        </button>

        <button @click="selectedTab = 'DIKIRIM'" 
                :class="selectedTab === 'DIKIRIM' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" 
                class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
            <span>Dikirim</span>
            <span class="px-1.5 py-0.5 text-[10px] bg-slate-100 text-slate-600 rounded-md font-black">23</span>
        </button>

        <button @click="selectedTab = 'SELESAI'" 
                :class="selectedTab === 'SELESAI' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" 
                class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
            <span>Selesai</span>
            <span class="px-1.5 py-0.5 text-[10px] bg-slate-100 text-slate-600 rounded-md font-black">36</span>
        </button>

        <button @click="selectedTab = 'DIBATALKAN'" 
                :class="selectedTab === 'DIBATALKAN' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'" 
                class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
            <span>Dibatalkan</span>
            <span class="px-1.5 py-0.5 text-[10px] bg-slate-100 text-slate-600 rounded-md font-black">3</span>
        </button>
    </div>

    <!-- 4. ADVANCED SEARCH & DROPDOWN FILTERS -->
    <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
        <div class="flex flex-1 flex-col sm:flex-row items-center gap-3">
            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                       x-model="searchQuery"
                       placeholder="Cari nomor pesanan, nama kolektor..." 
                       class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <!-- Filter Ekspedisi -->
            <select x-model="selectedEkspedisi" class="w-full sm:w-auto bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                <option value="ALL">Semua Ekspedisi (SiCepat, JNE, POS, J&T)</option>
                <option value="SICEPAT">SiCepat Express</option>
                <option value="JNE">JNE YES / REG</option>
                <option value="POS">POS Indonesia</option>
            </select>

            <!-- Filter Pembayaran -->
            <select x-model="selectedPembayaran" class="w-full sm:w-auto bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                <option value="ALL">Semua Pembayaran (VA, QRIS, Kartu)</option>
                <option value="BCA">Midtrans BCA VA</option>
                <option value="QRIS">Midtrans QRIS</option>
                <option value="MANDIRI">Mandiri Bill</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-[#AEE2F0]"></i>
                <i class="fa-solid fa-sliders text-xs"></i>
                <span>Filter Lanjutan</span>
            </button>
            <button type="button" @click="searchQuery = ''; selectedEkspedisi = 'ALL'; selectedPembayaran = 'ALL'; selectedTab = 'ALL';" class="p-2 text-slate-400 hover:text-slate-700 transition" title="Reset Filter">
                <i class="fa-solid fa-rotate-left"></i>
            </button>
        </div>
    </div>

    <!-- 5. ORDERS TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#AEE2F0]/40 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4 w-10 text-center">
                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        </th>
                        <th class="py-3.5 px-4">ID & WAKTU</th>
                        <th class="py-3.5 px-4">KOLEKTOR WASTRA</th>
                        <th class="py-3.5 px-4">KATALOG & KAIN</th>
                        <th class="py-3.5 px-4">TOTAL NOMINAL</th>
                        <th class="py-3.5 px-4">GATEWAY PEMBAYARAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    <template x-for="item in filteredOrders" :key="item.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            
                            <!-- Checkbox -->
                            <td class="py-4 px-4 text-center">
                                <input type="checkbox" :value="item.id" x-model="selectedRows" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            </td>

                            <!-- ID & Waktu -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-extrabold block text-xs" x-text="item.code"></strong>
                                <span class="text-[10px] text-slate-400 block" x-text="item.date"></span>
                                <span class="text-[10px] text-slate-400 block" x-text="item.time"></span>
                            </td>

                            <!-- Kolektor Wastra -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-bold block text-xs" x-text="item.collector"></strong>
                                <span class="text-[10px] text-slate-500 block font-normal" x-text="item.address"></span>
                                <span class="text-[10px] text-slate-400 block font-mono" x-text="item.phone"></span>
                            </td>

                            <!-- Katalog & Kain -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <img :src="item.img" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                                    <div>
                                        <strong class="text-slate-900 text-xs font-extrabold block" x-text="item.itemTitle"></strong>
                                        <span class="text-[10px] text-slate-500 block" x-text="item.itemFabric"></span>
                                        <span class="text-[10px] text-slate-400 block" x-text="item.itemVariant"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Total Nominal -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 text-sm font-black block" x-text="'Rp ' + item.totalPrice.toLocaleString('id-ID')"></strong>
                                <span class="text-[10px] text-slate-500 font-bold block mt-0.5" x-text="item.insuranceNote"></span>
                            </td>

                            <!-- Gateway Pembayaran -->
                            <td class="py-4 px-4">
                                <div class="space-y-1">
                                    <span class="bg-slate-100 text-slate-700 text-[10px] font-bold px-2.5 py-0.5 rounded-md inline-block" x-text="item.paymentMethod"></span>
                                    <div>
                                        <span class="text-[9px] font-extrabold px-2 py-0.5 rounded inline-block" :class="item.paymentStatusStyle" x-text="'● ' + item.paymentStatus"></span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono block" x-text="item.trxId"></span>
                                </div>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Info & Pagination -->
        <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-medium text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fa-regular fa-folder-open text-amber-700"></i>
                <span>Menampilkan <strong>1-5</strong> dari <strong>84</strong> total pesanan aktif</span>
            </div>

            <!-- Pagination Controls -->
            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold text-xs">
                <button type="button" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer">&lt; Sebelumnya</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-[#AEE2F0] text-slate-900 flex items-center justify-center font-extrabold">1</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 flex items-center justify-center cursor-pointer">2</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 flex items-center justify-center cursor-pointer">3</button>
                <span class="px-1 text-slate-400">...</span>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 flex items-center justify-center cursor-pointer">17</button>
                <button type="button" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer">Selanjutnya &gt;</button>
            </div>
        </div>
    </div>

    <!-- 6. MIDTRANS STATUS BANNER -->
    <div class="p-3 bg-slate-100 rounded-xl border border-slate-200/80 flex items-center gap-2 text-[11px] font-extrabold text-slate-700">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>MIDTRANS PRODUCTION ONLINE</span>
    </div>

</div>

@endsection