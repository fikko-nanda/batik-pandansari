@extends('layouts.admin')

@section('content')

    <!-- 1. WELCOME BANNER (DARK BLUE) -->
    <div class="bg-[#0A192F] text-white p-6 sm:p-8 rounded-2xl relative overflow-hidden shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2 max-w-2xl relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                Sugeng Rawuh, <span class="italic font-normal">K. R. H. Danureja</span>
            </h1>
            <p class="text-xs text-slate-300 leading-relaxed font-normal">
                Ikhtisar perputaran wastra adilihung, status gerbang bayar Midtrans Snap, serta logistik kurir RajaOngkir Pro terverifikasi per hari ini.
            </p>
        </div>

        <button type="button" class="bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 font-bold text-xs px-4 py-2.5 rounded-xl border border-sky-400/30 transition flex items-center gap-2 shrink-0 self-start md:self-center cursor-pointer">
            <i class="fa-solid fa-rotate text-xs"></i>
            <span>SIN KRONISASI</span>
        </button>

        <!-- Background Circles -->
        <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none">
            <div class="w-64 h-64 border-4 border-dashed border-white rounded-full flex items-center justify-center">
                <div class="w-40 h-40 border-2 border-white rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- 2. STATS METRIC CARDS (3 COLUMN GRID) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <!-- Metric 1: Total Pendapatan Kotor -->
        <div class="bg-[#0A192F] text-white p-6 rounded-2xl border border-slate-800 shadow-sm relative space-y-3">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 bg-slate-800 text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-400 px-2.5 py-1 rounded-full font-bold flex items-center gap-1">
                    <i class="fa-solid fa-arrow-up text-[9px]"></i> +14.2% bln lalu
                </span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">TOTAL PENDAPATAN KOTOR</span>
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    <span class="text-lg font-bold text-slate-400">Rp</span> 148.850.000
                </div>
            </div>
        </div>

        <!-- Metric 2: Pesanan Masuk -->
        <div class="bg-[#0A192F] text-white p-6 rounded-2xl border border-slate-800 shadow-sm relative space-y-3">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 bg-slate-800 text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
                <span class="text-[10px] bg-sky-500/20 text-sky-300 px-2.5 py-1 rounded-full font-bold">
                    ⚡ +8 baru hari ini
                </span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">PESANAN MASUK</span>
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight flex items-baseline gap-1.5">
                    <span>84</span>
                    <span class="text-xs font-normal text-slate-400">Transaksi</span>
                </div>
            </div>
        </div>

        <!-- Metric 3: Nilai Keranjang (AOV) -->
        <div class="bg-[#0A192F] text-white p-6 rounded-2xl border border-slate-800 shadow-sm relative space-y-3">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 bg-slate-800 text-sky-400 rounded-xl flex items-center justify-center text-sm">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <span class="text-[9px] bg-slate-800 text-slate-300 px-2.5 py-1 rounded-full font-bold uppercase tracking-wider">
                    LUXURY TIER
                </span>
            </div>

            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">NILAI KERANJANG (AOV)</span>
                <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    <span class="text-lg font-bold text-slate-400">Rp</span> 1.772.000
                </div>
            </div>
        </div>

    </div>

    <!-- 3. TABEL TRANSAKSI TERBARU & TERTUNDA -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6">
        
        <!-- Table Filter Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-[#0A192F] text-white rounded-xl flex items-center justify-center text-sm shrink-0">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#0F1A24]">Transaksi Terbaru & Tertunda</h3>
                    <p class="text-xs text-slate-500">Pantauan pesanan masuk, gateway Midtrans</p>
                </div>
            </div>

            <!-- Tabs Filters -->
            <div class="flex items-center gap-2 self-start md:self-auto text-xs">
                <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1">
                    <button class="bg-[#0A192F] text-white px-3 py-1.5 rounded-lg font-bold shadow-sm">SEMUA</button>
                    <button class="text-slate-600 hover:text-black px-3 py-1.5 rounded-lg font-bold">LUNAS</button>
                    <button class="text-slate-600 hover:text-black px-3 py-1.5 rounded-lg font-bold">MENUNGGU</button>
                </div>

                <button class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-sliders text-xs"></i>
                    <span>Filter Detail</span>
                </button>
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-sky-50/60 text-slate-700 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">ID PESANAN</th>
                        <th class="py-3 px-4">KOLEKTOR WASTRA</th>
                        <th class="py-3 px-4">KARYA WASTRA</th>
                        <th class="py-3 px-4">TOTAL BAYAR</th>
                        <th class="py-3 px-4">KURIR RAJAONGKIR</th>
                        <th class="py-3 px-4">MIDTRANS BAYAR</th>
                        <th class="py-3 px-4 text-center">STATUS</th>
                        <th class="py-3 px-4 text-center">TINDAKAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    
                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-4 font-bold text-sky-700">
                            #BN-88294
                            <span class="block text-[10px] font-normal text-slate-400">10:42 WIB</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <strong class="text-slate-800 block">Raden Ayu Kartika</strong>
                            <span class="text-[10px] text-slate-400">Menteng, Jakarta Pusat</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ asset('images/m1.png') }}" class="w-9 h-10 object-cover rounded-lg border border-slate-100 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                                <div>
                                    <strong class="text-slate-800 block truncate">Parang Kencana Silk</strong>
                                    <span class="text-[10px] text-slate-400">Batik Tulis Halus 2.5m</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-bold text-slate-800">Rp 4.850.000</td>
                        <td class="py-3.5 px-4">
                            <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold text-[9px] uppercase">SICEPAT</span>
                            <span class="text-[10px] text-slate-500 block">BEST (Next Day)</span>
                            <span class="text-[9px] text-slate-400 font-mono block">002948219102</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <strong class="text-slate-800 block">BCA Virtual Account</strong>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="bg-amber-100 text-amber-800 font-bold text-[10px] px-3 py-1 rounded-full inline-block">
                                200 SETTLED
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <button class="w-8 h-8 bg-slate-100 text-slate-700 hover:bg-[#0A192F] hover:text-white rounded-xl transition inline-flex items-center justify-center">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-4 font-bold text-sky-700">
                            #BN-88289
                            <span class="block text-[10px] font-normal text-slate-400">09:15 WIB</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <strong class="text-slate-800 block">Prof. Dr. Wardiman</strong>
                            <span class="text-[10px] text-slate-400">Coblong, Kota Bandung</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ asset('images/tulis.jpg') }}" class="w-9 h-10 object-cover rounded-lg border border-slate-100 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=100'">
                                <div>
                                    <strong class="text-slate-800 block truncate">Kawung Prada Kraton</strong>
                                    <span class="text-[10px] text-slate-400">Batik Tulis Soga Alami</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-bold text-slate-800">Rp 2.950.000</td>
                        <td class="py-3.5 px-4">
                            <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold text-[9px] uppercase">JNE</span>
                            <span class="text-[10px] text-slate-500 block">YES (Yakin Esok Sampai)</span>
                            <span class="text-[9px] text-slate-400 font-mono block">JNE-881920041</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <strong class="text-slate-800 block">Mandiri Bill Payment</strong>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="bg-amber-100 text-amber-800 font-bold text-[10px] px-3 py-1 rounded-full inline-block">
                                200 SETTLED
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <button class="w-8 h-8 bg-slate-100 text-slate-700 hover:bg-[#0A192F] hover:text-white rounded-xl transition inline-flex items-center justify-center">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 3 -->
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-4 font-bold text-sky-700">
                            #BN-88285
                            <span class="block text-[10px] font-normal text-slate-400">08:02 WIB</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <strong class="text-slate-800 block">G.K.R. Mangkubumi</strong>
                            <span class="text-[10px] text-slate-400">Kraton, D.I. Yogyakarta</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ asset('images/m2.png') }}" class="w-9 h-10 object-cover rounded-lg border border-slate-100 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1598033129183-c4f50c736f10?q=80&w=100'">
                                <div>
                                    <strong class="text-slate-800 block truncate">Sido Mukti Temanten</strong>
                                    <span class="text-[10px] text-slate-400">Batik Cap Kombinasi Tulis</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-bold text-slate-800">Rp 1.450.000</td>
                        <td class="py-3.5 px-4">
                            <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-bold text-[9px] uppercase">POS</span>
                            <span class="text-[10px] text-slate-500 block">Pos Kilat Khusus</span>
                            <span class="text-[9px] text-amber-600 font-semibold block">Menunggu Pick-up</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <strong class="text-slate-800 block">GoPay / QRIS Snap</strong>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="bg-orange-100 text-orange-800 font-bold text-[10px] px-3 py-1 rounded-full inline-block">
                                MENUNGGU BAYAR
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <button class="w-8 h-8 bg-slate-100 text-slate-700 hover:bg-[#0A192F] hover:text-white rounded-xl transition inline-flex items-center justify-center">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
            <span>Menampilkan <strong>5</strong> dari <strong>84</strong> pesanan aktif hari ini</span>

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

@endsection