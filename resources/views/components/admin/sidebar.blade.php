<!-- SIDEBAR ADMIN (DARK ROYAL NAVY) -->
<aside class="w-64 bg-[#0A192F] text-slate-300 flex flex-col justify-between shrink-0 min-h-screen relative overflow-hidden">
    
    <div class="relative z-10">
        <!-- Brand Header Sidebar -->
        <a href="{{ route('admin.dashboard') }}" class="p-6 flex items-center gap-3 border-b border-slate-800/80 block">
            <div class="w-9 h-9 bg-sky-500 rounded-xl flex items-center justify-center text-slate-900 font-bold shadow-md">
                <i class="fa-regular fa-gem text-lg"></i>
            </div>
            <div>
                <h2 class="text-base font-extrabold text-white tracking-tight leading-none">Pandansari</h2>
                <span class="text-[9px] font-bold text-sky-400 tracking-[1.5px] uppercase block mt-1">PUSAT KURASI WASTRA</span>
            </div>
        </a>

        <!-- Navigation Links -->
        <div class="px-3 py-5 space-y-5 text-xs font-semibold">
            
            <!-- UTAMA -->
            <div>
                <span class="px-3 text-[9px] font-bold text-slate-500 uppercase tracking-wider block mb-2">UTAMA</span>
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-sky-500/10 text-sky-400 font-bold border-l-4 border-sky-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <i class="fa-solid fa-border-all text-sm"></i>
                    <span>Dashboard & Ringkasan</span>
                </a>
            </div>

            <!-- KATALOG WASTRA -->
            <div>
                <span class="px-3 text-[9px] font-bold text-slate-500 uppercase tracking-wider block mb-2">KATALOG WASTRA</span>
                <div class="space-y-1">
                    <a href="{{ route('admin.products') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.products') ? 'bg-sky-500/10 text-sky-400 font-bold border-l-4 border-sky-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <i class="fa-solid fa-shirt text-sm"></i>
                        <span>Manajemen Produk</span>
                    </a>
                    <a href="{{ route('admin.stock') }}" 
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.stock') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-boxes-packing text-sm"></i>
                            <span>Kurasi Stok & Varian</span>
                        </a>
                    <a href="{{ route('admin.inventory-movements') }}" 
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.inventory-movements') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-history text-sm"></i>
                            <span>Riwayat Stok</span>
                        </a>
                </div>
            </div>

            <!-- TRANSAKSI & LOGISTIK -->
            <div>
                <span class="px-3 text-[9px] font-bold text-slate-500 uppercase tracking-wider block mb-2">TRANSAKSI & LOGISTIK</span>
                <div class="space-y-1">
                    <a href="{{ route('admin.orders') }}" 
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.orders') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-receipt text-sm"></i>
                            <span>Pesanan Masuk</span>
                        </a>
                    <!-- Integrasi RajaOngkir -->
                        <a href="{{ route('admin.rajaongkir') }}" 
                        class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.rajaongkir') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-truck-fast text-sm"></i>
                            <span>Integrasi RajaOngkir</span>
                        </a>
                    <!-- Gerbang Midtrans -->
                    <a href="{{ route('admin.midtrans') }}" 
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.midtrans') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fa-solid fa-credit-card text-sm"></i>
                        <span>Gerbang Midtrans</span>
                    </a>
                </div>
            </div>

            <!-- LAPORAN & PENGATURAN -->
            <div>
                <span class="px-3 text-[9px] font-bold text-slate-500 uppercase tracking-wider block mb-2">LAPORAN & PENGATURAN</span>
                <div class="space-y-1">
                    <a href="{{ route('admin.sales-report') }}" 
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.sales-report') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-chart-line text-sm"></i>
                    <span>Laporan Penjualan</span>
                </a>
                     <a href="{{ route('admin.settings') }}" 
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.settings') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-sliders text-sm"></i>
                    <span>Pengaturan Toko</span>
                </a>
                    <a href="{{ route('admin.create-promo') }}" 
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.create-promo') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fa-solid fa-ticket text-sm"></i>
                        <span>Buat Promo</span>
                    </a>
                    <a href="{{ route('admin.userlist') }}" 
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.userlist') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fa-solid fa-users text-sm"></i>
                        <span>Userlist</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Bottom Section Container (Ornamen Batik Megamendung) -->
    <div class="relative z-10 space-y-1">
        <div class="px-2 pt-1 pointer-events-none flex justify-center opacity-90">
            <img src="{{ asset('images/batik-sidebar.png') }}" 
                 alt="Ornamen Batik Megamendung" 
                 class="w-full h-80 object-contain mix-blend-screen filter brightness-200 contrast-150">
        </div>

        <!-- Footer Profile Admin -->
        <div class="p-4 border-t border-slate-800/80 flex items-center gap-3 bg-[#0A192F]">
            <div class="w-9 h-9 bg-emerald-700/80 text-white rounded-xl flex items-center justify-center font-bold text-xs shrink-0">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="overflow-hidden">
                <h4 class="text-xs font-bold text-white truncate">K. R. H. Danureja</h4>
                <p class="text-[10px] text-slate-400 truncate">Kurator Utama</p>
            </div>
        </div>
    </div>

</aside>