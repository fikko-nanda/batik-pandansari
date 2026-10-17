@extends('layouts.admin')

@section('content')

<div x-data="{
    searchQuery: '',
    selectedStatus: 'ALL',

    // Data Rekonsiliasi & Audit Log Midtrans Snap
    transactions: [
        {
            id: 1,
            orderId: '#PS-2026-0891',
            date: '14 Okt 2026 • 14:22 WIB',
            collector: 'G.K.R. Mangkubumi',
            itemTitle: 'Batik Tulis Parang Rusak Barong (Sutra ATBM)',
            channel: 'BCA Virtual Account',
            grossAmount: 18500000,
            fee: 4500,
            netAmount: 18495500,
            status: 'Settlement Berhasil',
            statusDot: 'bg-sky-400',
            statusBadge: 'bg-sky-50 text-sky-800 border border-sky-200',
            img: '{{ asset('images/m1.png') }}'
        },
        {
            id: 2,
            orderId: '#PS-2026-0890',
            date: '14 Okt 2026 • 13:58 WIB',
            collector: 'Bramantyo Wardhana',
            itemTitle: 'Kain Panjang Sido Mukti Prada Emas',
            channel: 'QRIS Dinamis (GoPay)',
            grossAmount: 6200000,
            fee: 43400,
            netAmount: 6156600,
            status: 'Settlement Berhasil',
            statusDot: 'bg-sky-400',
            statusBadge: 'bg-sky-50 text-sky-800 border border-sky-200',
            img: '{{ asset('images/tulis.jpg') }}'
        },
        {
            id: 3,
            orderId: '#PS-2026-0889',
            date: '14 Okt 2026 • 12:10 WIB',
            collector: 'Tantri Puspitasari',
            itemTitle: 'Selendang Kawung Seling Nitik Primissima',
            channel: 'Mandiri Bill Payment',
            grossAmount: 4750000,
            fee: 4000,
            netAmount: 4746000,
            status: 'Menunggu Bayar',
            statusDot: 'bg-slate-400',
            statusBadge: 'bg-slate-100 text-slate-700 border border-slate-200',
            img: '{{ asset('images/cap.jpg') }}'
        },
        {
            id: 4,
            orderId: '#PS-2026-0888',
            date: '13 Okt 2026 • 21:04 WIB',
            collector: 'R.A. Anindita Kirana',
            itemTitle: 'Kain Panjang Sekar Jagad Indigofera',
            channel: 'Kartu Kredit Visa 3DS',
            grossAmount: 9800000,
            fee: 284200,
            netAmount: 9515800,
            status: 'Kadaluarsa / Batal',
            statusDot: 'bg-rose-500',
            statusBadge: 'bg-rose-50 text-rose-800 border border-rose-200',
            img: '{{ asset('images/m2.png') }}'
        }
    ],

    get filteredTransactions() {
        return this.transactions.filter(t => {
            let matchSearch = t.orderId.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                t.collector.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                t.itemTitle.toLowerCase().includes(this.searchQuery.toLowerCase());
            let matchStatus = this.selectedStatus === 'ALL' || t.status === this.selectedStatus;
            return matchSearch && matchStatus;
        });
    }
}" class="space-y-6 text-slate-800 font-sans">

    <!-- 1. HEADER TITLE & TOTAL DANA CARD -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Gerbang Pembayaran Digital &<br class="hidden    sm:inline"> Rekonsiliasi Midtrans Snap
            </h1>
        </div>

        <!-- TOTAL DANA BANNER CARD -->
        <div class="bg-[#188cc6] text-white rounded-[24px] p-6 relative overflow-hidden shadow-md w-full sm:w-[380px] shrink-0">
            <div class="relative z-10 space-y-2">
                <!-- Label Total Dana -->
                <span class="text-[11px] font-black text-white/90 uppercase tracking-widest block">TOTAL DANA</span>
                
                <!-- Nominal Angka -->
                <div class="text-3xl sm:text-[34px] font-black text-white tracking-tight leading-none pb-2">
                    Rp 128.450.000
                </div>

                <div class="pt-2 border-t border-white/20"></div>

                <!-- Label Bottom & Icon Gedung -->
                <div class="flex items-center justify-between text-[10px] font-black text-white/90 tracking-wider uppercase">
                    <span>RINGKASAN KAS MASUK</span>
                    <i class="fa-solid fa-building-columns text-sm text-white/90"></i>
                </div>
            </div>

            <!-- Panel Transparan Kanan (Wayang & Wallet Badge) -->
            <div class="absolute right-0 top-0 bottom-0 w-[120px] bg-white/10 backdrop-blur-[2px] z-0 flex flex-col justify-between items-end p-4 pointer-events-none">
                <!-- Icon Wallet Badge -->
                <div class="w-8 h-8 bg-white/30 rounded-xl flex items-center justify-center text-white text-sm shadow-sm">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>

            <!-- Wayang Silhouette Background Kanan -->
            <div class="absolute right-[-10px] bottom-[-15px] z-0 opacity-20 pointer-events-none">
                <img src="{{ asset('images/wayang.png') }}" alt="Wayang" class="h-36 w-auto object-contain filter brightness-200">
            </div>
        </div>
    </div>

    <!-- 2. REKONSILIASI KEUANGAN & AUDIT LOG CARD -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-5">
        
        <!-- Section Header & Filter Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-black text-sky-700 uppercase tracking-widest block">REKONSILIASI KEUANGAN & AUDIT LOG</span>
                <h2 class="text-base font-extrabold text-slate-900">Jurnal Transaksi Masuk & Integrasi Webhook</h2>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <div class="relative">
                    <select x-model="selectedStatus" class="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-xl pl-8 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-sky-400 cursor-pointer">
                        <option value="ALL">Filter Status: Semua Terdata</option>
                        <option value="Settlement Berhasil">Settlement Berhasil</option>
                        <option value="Menunggu Bayar">Menunggu Bayar</option>
                        <option value="Kadaluarsa / Batal">Kadaluarsa / Batal</option>
                    </select>
                    <i class="fa-solid fa-sliders absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>

                <button type="button" @click="selectedStatus = 'ALL'; searchQuery = ''" class="w-8 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition flex items-center justify-center cursor-pointer" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4">ID PESANAN</th>
                        <th class="py-3.5 px-4">KOLEKTOR & WASTRA</th>
                        <th class="py-3.5 px-4">KANAL BAYAR</th>
                        <th class="py-3.5 px-4 text-right">NILAI KOTOR</th>
                        <th class="py-3.5 px-4 text-right">BIAYA GATEWAY</th>
                        <th class="py-3.5 px-4 text-right">NETTO BERSIH</th>
                        <th class="py-3.5 px-4 text-center">STATUS WEBHOOK</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    <template x-for="item in filteredTransactions" :key="item.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            
                            <!-- ID Pesanan & Tanggal -->
                            <td class="py-4 px-4">
                                <strong class="text-slate-900 font-extrabold block text-xs" x-text="item.orderId"></strong>
                                <span class="text-[10px] text-slate-400 block" x-text="item.date"></span>
                            </td>

                            <!-- Kolektor & Wastra -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <img :src="item.img" class="w-10 h-10 object-cover rounded-xl border border-slate-200 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                                    <div>
                                        <strong class="text-slate-900 text-xs font-bold block" x-text="item.collector"></strong>
                                        <span class="text-[10px] text-slate-500 block" x-text="item.itemTitle"></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Kanal Bayar -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="item.statusDot"></span>
                                    <span class="font-extrabold text-slate-800 text-xs" x-text="item.channel"></span>
                                </div>
                            </td>

                            <!-- Nilai Kotor -->
                            <td class="py-4 px-4 text-right">
                                <strong class="text-slate-900 font-black text-xs" x-text="'Rp ' + item.grossAmount.toLocaleString('id-ID')"></strong>
                            </td>

                            <!-- Biaya Gateway -->
                            <td class="py-4 px-4 text-right">
                                <span class="text-slate-400 text-[11px] font-mono" x-text="'- Rp ' + item.fee.toLocaleString('id-ID')"></span>
                            </td>

                            <!-- Netto Bersih -->
                            <td class="py-4 px-4 text-right">
                                <strong class="text-emerald-700 font-black text-xs" x-text="'Rp ' + item.netAmount.toLocaleString('id-ID')"></strong>
                            </td>

                            <!-- Status Webhook -->
                            <td class="py-4 px-4 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-extrabold inline-block" :class="item.statusBadge" x-text="item.status"></span>
                            </td>

                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Legend & Pagination -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs font-semibold text-slate-500">
            
            <!-- Status Legend Indicator -->
            <div class="flex flex-wrap items-center gap-4 text-[11px]">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-sky-400"></span>
                    <span>Settlement Berhasil</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                    <span>Menunggu Bayar</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span>Kadaluarsa / Batal</span>
                </div>
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