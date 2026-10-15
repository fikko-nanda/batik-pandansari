<!-- Main Header Wrapper -->
<header class="sticky top-0 z-40 w-full bg-white border-b border-slate-100 shadow-sm" x-data="{ mobileMenuOpen: false }">

    <!-- Main Navbar Container -->
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 py-3">
        <div class="flex items-center justify-between gap-4">
            
            <!-- Logo Brand (Icon Berlian + Teks) -->
            <a href="#" class="flex items-center gap-2.5 shrink-0">
                <div class="w-9 h-9 bg-black rounded-xl flex items-center justify-center text-white">
                    <i class="fa-regular fa-gem text-base"></i>
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 leading-none font-['Plus_Jakarta_Sans']">
                        BATIK PANDANSARI
                    </h1>
                    <p class="text-[9px] font-bold tracking-[2.5px] text-slate-500 uppercase mt-0.5 font-['Plus_Jakarta_Sans']">
                        MAHARDIKA AUTENTIK
                    </p>
                </div>
            </a>

            <!-- Right Action Items (Search Bar, Wishlist, Cart, WA Button, User Icon) -->
            <div class="flex items-center gap-3 sm:gap-4">
                
                <!-- Search Bar Input -->
                <div class="relative hidden sm:block w-48 md:w-64 lg:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           placeholder="Cari motif, kain, sarimbit..." 
                           class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50/80 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 font-['Plus_Jakarta_Sans'] placeholder:text-slate-400">
                </div>

                <!-- Wishlist Icon dengan Badge 0 -->
                <a href="#" class="relative p-1.5 text-slate-800 hover:text-black transition">
                    <i class="fa-regular fa-heart text-lg"></i>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-black text-white text-[9px] font-bold rounded-full flex items-center justify-center font-['Plus_Jakarta_Sans']">
                        0
                    </span>
                </a>

                <!-- Cart Icon dengan Badge 0 -->
                <a href="#" class="relative p-1.5 text-slate-800 hover:text-black transition">
                    <i class="fa-solid fa-bag-shopping text-lg"></i>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-black text-white text-[9px] font-bold rounded-full flex items-center justify-center font-['Plus_Jakarta_Sans']">
                        0
                    </span>
                </a>

                <!-- WhatsApp CS Button (Hijau Rounded) -->
                <a href="https://wa.me/6281234567890?text=Halo%20Batik%20Pandansari,%20saya%20ingin%20konsultasi" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="hidden md:inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-sm font-['Plus_Jakarta_Sans']">
                    <i class="fa-regular fa-comment-dots text-sm"></i>
                    <span>WhatsApp CS</span>
                </a>

                <!-- User Profile Button -->
                <a href="#" class="w-8 h-8 bg-black text-white rounded-full flex items-center justify-center text-xs hover:bg-slate-800 transition">
                    <i class="fa-regular fa-user"></i>
                </a>

                <!-- Mobile Hamburger Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-1.5 text-slate-800 hover:text-black">
                    <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark text-xl' : 'fa-bars text-lg'"></i>
                </button>
            </div>
        </div>

        <!-- Navigation Links Row (Desktop) -->
        <nav class="hidden lg:flex items-center gap-5 text-[11px] font-bold uppercase tracking-wider text-slate-600 mt-3 pt-2 font-['Plus_Jakarta_Sans'] overflow-x-auto">
            <a href="#" class="px-2.5 py-1 bg-slate-100 text-slate-900 rounded-md whitespace-nowrap font-extrabold">BERANDA</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">KATALOG</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">BATIK TULIS</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">BATIK CAP</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">BAHAN HANDPRINT</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">PAKAIAN WANITA</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">PAKAIAN WANITA</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">PAKAIAN ANAK-ANAK</a>
            <a href="#" class="hover:text-black transition whitespace-nowrap">SARIMBIT</a>
        </nav>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden bg-white border-t border-slate-100 px-4 py-4 space-y-2.5 font-['Plus_Jakarta_Sans'] text-xs font-bold uppercase">
        
        <div class="relative w-full mb-3 sm:hidden">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" placeholder="Cari motif, kain, sarimbit..." class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none">
        </div>

        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">BERANDA</a>
        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">KATALOG</a>
        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">BATIK TULIS</a>
        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">BATIK CAP</a>
        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">BAHAN HANDPRINT</a>
        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">PAKAIAN WANITA</a>
        <a href="#" class="block py-1.5 px-2 bg-slate-100 text-slate-900 rounded-md font-extrabold">PAKAIAN PRIA</a>
        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">PAKAIAN ANAK-ANAK</a>
        <a href="#" class="block py-1.5 text-slate-700 hover:text-black">SARIMBIT</a>
        
        <a href="https://wa.me/6281234567890" target="_blank" class="flex md:hidden items-center justify-center gap-2 bg-[#25D366] text-white py-2 rounded-xl mt-3">
            <i class="fa-regular fa-comment-dots text-sm"></i>
            <span>WhatsApp CS</span>
        </a>
    </div>  
</header>