@extends('layouts.admin')

@section('content')

<div x-data="{
    searchQuery: '',
    timeFilter: 'THIS_MONTH',
    courierFilter: 'ALL',
    paymentFilter: 'ALL',

    // Data Mutasi Buku Kas Harian
    mutations: [
        {
            id: 1,
            date: '28 Okt 2026',
            resi: 'ORD-PS-1957-048',
            statusBadge: 'Lunas Verifikasi!',
            collector: 'Raden Mas Haryo S.',
            address: 'Menteng, Jakarta Pusat',
            tier: 'Kolektor Utama',
            wastraTitle: 'Batik Tulis Parang Barong Kencana',
            wastraDetail: 'Sutra Baron 2.6m • Pewarna Alami Soga Jawa',
            channel: 'BCA Virtual Account',
            courier: 'POS Indonesia Sameday Air',
            resiNo: 'POS8825104081',
            grossAmount: 18650000,
            valuationNote: 'Incl. Valuasi VIP',
            mdrFee: 390000,
            mdrDetail: 'MDR: 240k | Ongk: 150k',
            netAmount: 18260000
        },
        {
            id: 2,
            date: '27 Okt 2026',
            resi: 'ORD-PS-1957-047',
            statusBadge: 'Lunas Verifikasi!',
            collector: 'Ibu Dra. Ratna Prawiro',
            address: 'Coblong, Kota Bandung',
            tier: 'Kolektor Regular',
            wastraTitle: 'Batik Tulis Sido Asih Kembar Serat Emas',
            wastraDetail: 'Mori Primissima Halus • Prada Emas Asli',
            channel: 'Mandiri Bill Payment',
            courier: 'JNE YES Express Box',
            resiNo: 'JNE0538174828',
            grossAmount: 12450000,
            valuationNote: 'Kotak Jati Included',
            mdrFee: 218000,
            mdrDetail: 'MDR: 120k | Ongk: 98k',
            netAmount: 12232000
        },
        {
            id: 3,
            date: '26 Okt 2026',
            resi: 'ORD-PS-1957-046',
            statusBadge: 'Lunas Verifikasi!',
            collector: 'Bapak Ir. Bambang Sujarwo',
            address: 'Gubeng, Surabaya',
            tier: 'Pelanggan Baru',
            wastraTitle: 'Batik Cap Kombinasi Truntum Gurdo (2 pcs)',
            wastraDetail: 'Katun Primissima - Isen-isen Manual Canting',
            channel: 'QRIS Dinamis Keraton',
            courier: 'TIKI Reguler Berasuransi',
            resiNo: 'TIK772819201',
            grossAmount: 4900000,
            valuationNote: 'Seri Pasangan',
            mdrFee: 72500,
            mdrDetail: 'MDR: 34.5k | Ongk: 38k',
            netAmount: 4827500
        },
        {
            id: 4,
            date: '25 Okt 2026',
            resi: 'ORD-PS-1957-045',
            statusBadge: 'Lunas Verifikasi!',
            collector: 'K.P.A. Wiwoho Hadiningrat',
            address: 'Kotagede, Yogyakarta',
            tier: 'Kurator Kerabat',
            wastraTitle: 'Batik Tulis Kawung Picis Indigofera Gedog Tuban',
            wastraDetail: 'Tenun Gedog Asli • Fermentasi Daun Tom Alami',
            channel: 'Kartu Kredit Visa Infinite',
            courier: 'Kurir Khusus Atelier (Baluwarti)',
            resiNo: 'Hand Delivery Staff',
            grossAmount: 24500000,
            valuationNote: 'Masterpiece Museum Grade',
            mdrFee: 539000,
            mdrDetail: 'MDR: 539k | Ongk: 0k (Self)',
            netAmount: 23961000
        }
    ],

    get filteredMutations() {
        return this.mutations.filter(m => {
            let matchSearch = m.resi.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                m.collector.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                m.wastraTitle.toLowerCase().includes(this.searchQuery.toLowerCase());
            return matchSearch;
        });
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- 1. HEADER TITLE -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Konsolidasi Pembukuan & Tata Kelola Atelier
            </h1>
        </div>

        <button type="button" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-xl transition shadow-sm flex items-center gap-2 self-start sm:self-auto cursor-pointer border border-slate-200/80">
            <i class="fa-solid fa-file-pdf text-rose-600 text-sm"></i>
            <span>Ekspor PDF Ringkas</span>
        </button>
    </div>

    <!-- 2. FILTER STRIP (KURUN WAKTU, LOGISTIK, KANAL) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-3xl">
        
        <!-- Kurun Waktu -->
        <div class="bg-slate-100/80 border border-slate-200/80 p-3 rounded-2xl flex items-center gap-3">
            <i class="fa-regular fa-calendar text-slate-600 text-base"></i>
            <div>
                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">KURUN WAKTU</span>
                <strong class="text-xs font-black text-slate-900 block leading-tight">Bulan Ini Okt 2026</strong>
            </div>
        </div>

        <!-- Logistik RajaOngkir -->
        <div class="bg-slate-100/80 border border-slate-200/80 p-3 rounded-2xl flex items-center gap-3">
            <i class="fa-solid fa-truck-fast text-slate-600 text-base"></i>
            <div>
                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">LOGISTIK RAJAONGKIR</span>
                <strong class="text-xs font-black text-slate-900 block leading-tight">Semua Ekspedisi</strong>
            </div>
        </div>

        <!-- Kanal Pembayaran -->
        <div class="bg-slate-100/80 border border-slate-200/80 p-3 rounded-2xl flex items-center gap-3">
            <i class="fa-solid fa-network-wired text-slate-600 text-base"></i>
            <div>
                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest block">KANAL PEMBAYARAN</span>
                <strong class="text-xs font-black text-slate-900 block leading-tight">Semua Saluran Midtrans</strong>
            </div>
        </div>

    </div>

    <!-- 3. OMZET & PAYMENT FEE SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        
        <!-- Card 1: Total Omzet Bersih (Dark Navy) -->
        <div class="bg-[#0A192F] text-white rounded-2xl p-6 relative overflow-hidden shadow-md flex flex-col justify-between">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">KAS MASUK UTAMA</span>
                <h3 class="text-base font-extrabold text-white">Total Omzet Bersih</h3>
                
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight pt-2">
                    Rp 141.055.050
                </div>

                <p class="text-[11px] text-slate-400 font-bold pt-2">Volume Penjualan: 48 Helai Koleksi</p>
            </div>

            <!-- Wayang Watermark Right -->
            <div class="absolute right-[-10px] bottom-[-20px] z-0 opacity-20 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang" class="h-40 w-auto object-contain filter brightness-150">
            </div>
        </div>

        <!-- Card 2: Potongan MDR Midtrans (Biru Cerah) -->
        <div class="bg-[#1EABF1] text-white rounded-2xl p-6 relative overflow-hidden shadow-md flex flex-col justify-between">
            <div class="relative z-10 space-y-1">
                <span class="text-[10px] font-black text-white/80 uppercase tracking-widest block">GATEWAY PAYMENT FEE</span>
                <h3 class="text-base font-extrabold text-white">Potongan MDR Midtrans</h3>
                
                <div class="text-3xl sm:text-4xl font-black text-white tracking-tight pt-2">
                    Rp 1.842.950
                </div>
            </div>

            <!-- Wayang Silhouette Background Kanan -->
            <div class="absolute right-[-10px] bottom-[-20px] z-0 opacity-25 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang" class="h-40 w-auto object-contain filter brightness-200">
            </div>
        </div>

    </div>

    <!-- 4. BUKU KAS HARIAN (MUTASI REAL-TIME) TABLE -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-book text-sky-700 text-base"></i>
                <h2 class="text-base font-extrabold text-slate-900">Buku Kas Harian (Mutasi Real-Time)</h2>
            </div>
            <span class="text-[11px] font-bold text-slate-400">Menampilkan 5 entri termutakhir dari 48 transaksi bulan ini</span>
        </div>

        <!-- Mutasi Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4">TANGGAL & RESI</th>
                        <th class="py-3.5 px-4">KOLEKTOR / PEMBELI</th>
                        <th class="py-3.5 px-4">RINGKASAN WASTRA</th>
                        <th class="py-3.5 px-4">KANAL & EKSPEDISI</th>
                        <th class="py-3.5 px-4 text-right">DEBET (BRUTO)</th>
                        <th class="py-3.5 px-4 text-right">KREDIT (MDR+ONGKIR)</th>
                        <th class="py-3.5 px-4 text-right">NETTO KAS BERSIH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    <template x-for="item in filteredMutations" :key="item.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            
                            <!-- Tanggal & Resi -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-extrabold block text-xs" x-text="item.date"></strong>
                                <span class="text-[10px] text-slate-400 font-mono block" x-text="item.resi"></span>
                                <span class="bg-sky-800 text-white text-[9px] font-extrabold px-2 py-0.5 rounded inline-block mt-1" x-text="item.statusBadge"></span>
                            </td>

                            <!-- Kolektor / Pembeli -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-black block text-xs" x-text="item.collector"></strong>
                                <span class="text-[10px] text-slate-500 block" x-text="item.address"></span>
                                <span class="text-[10px] font-bold text-sky-700 block mt-0.5" x-text="item.tier"></span>
                            </td>

                            <!-- Ringkasan Wastra -->
                            <td class="py-4 px-4 max-w-xs">
                                <strong class="text-slate-900 text-xs font-bold block leading-tight" x-text="item.wastraTitle"></strong>
                                <span class="text-[10px] text-slate-500 block leading-tight mt-0.5" x-text="item.wastraDetail"></span>
                            </td>

                            <!-- Kanal & Ekspedisi -->
                            <td class="py-4 px-4">
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-sky-500 shrink-0"></span>
                                        <strong class="text-slate-800 font-extrabold text-xs" x-text="item.channel"></strong>
                                    </div>
                                    <span class="text-[10px] text-slate-500 block pl-3" x-text="item.courier"></span>
                                    <span class="text-[10px] text-slate-400 font-mono block pl-3" x-text="'Resi: ' + item.resiNo"></span>
                                </div>
                            </td>

                            <!-- Debet (Bruto) -->
                            <td class="py-4 px-4 text-right">
                                <strong class="text-slate-900 font-black text-xs block" x-text="'Rp ' + item.grossAmount.toLocaleString('id-ID')"></strong>
                                <span class="text-[9px] text-slate-400 block" x-text="item.valuationNote"></span>
                            </td>

                            <!-- Kredit (MDR + Ongkir) -->
                            <td class="py-4 px-4 text-right">
                                <strong class="text-rose-600 font-black text-xs block" x-text="'- Rp ' + item.mdrFee.toLocaleString('id-ID')"></strong>
                                <span class="text-[9px] text-slate-400 block" x-text="item.mdrDetail"></span>
                            </td>

                            <!-- Netto Kas Bersih -->
                            <td class="py-4 px-4 text-right">
                                <strong class="text-slate-900 font-black text-sm block" x-text="'Rp ' + item.netAmount.toLocaleString('id-ID')"></strong>
                                <span class="text-[9px] text-slate-400 block">Cair ke Kas Sentra</span>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Synchronization & Pagination -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-semibold text-slate-500">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <span>Semua data terverifikasi otomatis oleh Midtrans Settlement Engine API v2</span>
            </div>

           <!-- Pagination Controls -->
            <div class="flex items-center gap-1.5 self-end sm:self-auto font-bold">
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer">&lt;</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-[#AEE2F0] text-slate-900 flex items-center justify-center font-extrabold">1</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer">2</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer">3</button>
                <button type="button" class="w-7 h-7 rounded-xl bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 cursor-pointer">&gt;</button>
            </div>
        </div>

    </div>

</div>

@endsection