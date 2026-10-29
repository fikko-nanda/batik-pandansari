<!-- SIDEBAR ADMIN (DARK ROYAL NAVY) -->
<div {{ $attributes->merge(['class' => 'w-64 bg-[#0A192F] text-slate-300 flex flex-col h-full relative overflow-hidden']) }}>
    
    <!-- Tombol Tutup (Hanya untuk HP) -->
    <button type="button" @click="sidebarOpen = false" class="lg:hidden absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition z-20" aria-label="Tutup menu">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>

    <!-- Konten Sidebar Flex Container -->
    <div class="relative z-10 flex flex-col h-full min-h-0">
        <!-- Brand Header Sidebar (shrink-0 agar tetap terkunci di posisi atas) -->
        <a href="{{ route('admin.dashboard') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }" class="shrink-0 p-5 lg:p-6 flex items-center gap-3 border-b border-slate-800/80">
            <div class="w-9 h-9 bg-sky-500 rounded-xl flex items-center justify-center text-slate-900 font-bold shadow-md shrink-0">
                <i class="fa-regular fa-gem text-lg"></i>
            </div>
            <div>
                <h2 class="text-base font-extrabold text-white tracking-tight leading-none">Pandansari</h2>
                <span class="text-[9px] font-bold text-sky-400 tracking-[1.5px] uppercase block mt-1">PUSAT KURASI WASTRA</span>
            </div>
        </a>

        <!-- Navigation Links (flex-1 overflow-y-auto agar scrollable secara independen) -->
        <nav class="flex-1 overflow-y-auto sidebar-scroll px-2.5 sm:px-3 py-3 sm:py-4 space-y-3 sm:space-y-4 text-[11px] sm:text-xs font-semibold">

            <!-- UTAMA -->
            <div>
                <span class="px-2.5 sm:px-3 text-[8px] sm:text-[9px] font-bold text-slate-500 uppercase tracking-[0.16em] block mb-1.5 sm:mb-2">UTAMA</span>
                <a href="{{ route('admin.dashboard') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                   class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-sky-500/10 text-sky-400 font-bold border-l-4 border-sky-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <i class="fa-solid fa-border-all text-sm"></i>
                    <span>Dashboard & Ringkasan</span>
                </a>
            </div>

            <!-- KATALOG WASTRA -->
            <div>
                <span class="px-2.5 sm:px-3 text-[8px] sm:text-[9px] font-bold text-slate-500 uppercase tracking-[0.16em] block mb-1.5 sm:mb-2">KATALOG WASTRA</span>
                <div class="space-y-0.5 sm:space-y-1">
                    <a href="{{ route('admin.products') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                       class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl transition {{ request()->routeIs('admin.products') ? 'bg-sky-500/10 text-sky-400 font-bold border-l-4 border-sky-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <i class="fa-solid fa-shirt text-sm"></i>
                        <span>Manajemen Produk</span>
                    </a>
                    <a href="{{ route('admin.stock') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                        class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl font-bold transition {{ request()->routeIs('admin.stock') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-boxes-packing text-sm"></i>
                            <span>Kurasi Stok & Varian</span>
                        </a>
                </div>
            </div>

            <!-- TRANSAKSI & LOGISTIK -->
            <div>
                <span class="px-2.5 sm:px-3 text-[8px] sm:text-[9px] font-bold text-slate-500 uppercase tracking-[0.16em] block mb-1.5 sm:mb-2">TRANSAKSI & LOGISTIK</span>
                <div class="space-y-0.5 sm:space-y-1">
                    <a href="{{ route('admin.orders') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                        class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl font-bold transition {{ request()->routeIs('admin.orders') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                            <i class="fa-solid fa-receipt text-sm"></i>
                            <span>Pesanan Masuk</span>
                        </a>
                   
                    <!-- Gerbang Midtrans -->
                    <a href="{{ route('admin.midtrans') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                    class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl font-bold transition {{ request()->routeIs('admin.midtrans') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fa-solid fa-credit-card text-sm"></i>
                        <span>Gerbang Midtrans</span>
                    </a>
                </div>
            </div>

            <!-- LAPORAN & PENGATURAN -->
            <div>
                <span class="px-2.5 sm:px-3 text-[8px] sm:text-[9px] font-bold text-slate-500 uppercase tracking-[0.16em] block mb-1.5 sm:mb-2">LAPORAN & PENGATURAN</span>
                <div class="space-y-0.5 sm:space-y-1">
                    <a href="{{ route('admin.sales-report') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl font-bold transition {{ request()->routeIs('admin.sales-report') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-chart-line text-sm"></i>
                    <span>Laporan Penjualan</span>
                </a>
                     <a href="{{ route('admin.settings') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl font-bold transition {{ request()->routeIs('admin.settings') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fa-solid fa-sliders text-sm"></i>
                    <span>Pengaturan Toko</span>
                </a>
                    <a href="{{ route('admin.create-promo') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl font-bold transition {{ request()->routeIs('admin.create-promo') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fa-solid fa-ticket text-sm"></i>
                        <span>Buat Promo</span>
                    </a>
                    <a href="{{ route('admin.userlist') }}" @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl font-bold transition {{ request()->routeIs('admin.userlist') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fa-solid fa-users text-sm"></i>
                        <span>Userlist</span>
                    </a>
                </div>
            </div>

            <!-- PINTASAN -->
            <div>
                <span class="px-2.5 sm:px-3 text-[8px] sm:text-[9px] font-bold text-slate-500 uppercase tracking-[0.16em] block mb-1.5 sm:mb-2">PINTASAN</span>
                <a href="{{ route('home') }}"
                   title="Lihat tampilan customer / toko (berguna saat maintenance)"
                   @click="if (window.innerWidth < 1024) { sidebarOpen = false }"
                   class="flex items-center gap-2.5 sm:gap-3 px-2.5 sm:px-3 py-2 sm:py-2.5 rounded-xl font-bold text-emerald-300 bg-emerald-500/10 border border-emerald-400/30 hover:bg-emerald-500/20 hover:text-emerald-200 transition">
                    <i class="fa-solid fa-store text-sm"></i>
                    <span>Dashboard Customer</span>
                    <i class="fa-solid fa-arrow-up-right-from-square ml-auto text-[10px] opacity-70"></i>
                </a>
            </div>
        </nav>

        <!-- Footer Profile Admin (shrink-0 agar tetap menempel di posisi bawah) -->
        <div class="shrink-0 p-3 sm:p-4 border-t border-slate-800/80 bg-[#0A192F] space-y-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 sm:w-9 sm:h-9 bg-emerald-700/80 text-white rounded-xl flex items-center justify-center font-bold text-[10px] sm:text-xs shrink-0">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="overflow-hidden">
                    <h4 class="text-[11px] sm:text-xs font-bold text-white truncate">K. R. H. Danureja</h4>
                    <p class="text-[10px] text-slate-400 truncate">Kurator Utama</p>
                </div>
            </div>

            <!-- Tombol Logout -->
            <button type="button"
                    onclick="window.PandansariAPI ? PandansariAPI.keluarPanel() : (window.location.href = '/login')"
                    class="w-full flex items-center justify-center gap-2 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl text-[10px] sm:text-[11px] font-bold text-rose-300 bg-rose-500/10 border border-rose-400/30 hover:bg-rose-500/20 hover:text-rose-200 transition cursor-pointer">
                <i class="fa-solid fa-arrow-right-from-bracket text-[11px]"></i>
                <span>Keluar dari Panel</span>
            </button>
        </div>
    </div>
</div>

</aside>