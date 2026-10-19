<!-- Main Header Wrapper -->
<header class="sticky top-0 z-40 w-full bg-white border-b border-slate-100 shadow-sm" x-data="{ mobileMenuOpen: false }">
    

    <!-- Main Navbar Container -->
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 py-3">
        <div class="flex items-center justify-between gap-4">
            
            <!-- Logo Brand (Icon Berlian + Teks) -->
            <a href="/" class="flex items-center gap-2.5 shrink-0">
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
                <form action="/katalog" method="GET" class="relative hidden sm:block w-48 md:w-64 lg:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="Cari motif, kain, sarimbit..." 
                           class="w-full pl-8 pr-3 py-2 text-xs bg-slate-50/80 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-slate-400 font-['Plus_Jakarta_Sans'] placeholder:text-slate-400">
                </form>

               <!-- Tombol Icon Wishlist / Favorit -->
                <a href="/wishlist" 
                class="relative p-1.5 transition {{ request()->is('wishlist') || request()->is('favorit') ? 'text-black font-bold' : 'text-slate-600 hover:text-black' }}">
                    <!-- Jika di halaman wishlist, gunakan icon heart solid (terisi), jika tidak gunakan regular (garis) -->
                    <i class="{{ request()->is('wishlist') || request()->is('favorit') ? 'fa-solid fa-heart' : 'fa-regular fa-heart' }} text-lg"></i>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-black text-white text-[9px] font-bold rounded-full flex items-center justify-center font-['Plus_Jakarta_Sans']">
                        6
                    </span>
                </a>

             <!-- Tombol Icon Keranjang Belanja -->
                        <a href="/cart" 
                        class="relative p-1.5 transition {{ request()->is('cart') || request()->is('keranjang') ? 'text-black' : 'text-slate-600 hover:text-black' }}">
                            
                            <!-- SVG Icon Keranjang Belanja (Full Hitam saat di halaman Cart, Outline saat di luar Cart) -->
                            <svg xmlns="http://www.w3.org/2000/svg" 
                                fill="{{ request()->is('cart') || request()->is('keranjang') ? 'currentColor' : 'none' }}" 
                                viewBox="0 0 24 24" 
                                stroke-width="1.8" 
                                stroke="currentColor" 
                                class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.25 10.5a.75.75 0 100-1.5.75.75 0 000 1.5zm7.5 0a.75.75 0 100-1.5.75.75 0 000 1.5z" />
                            </svg>

                            <span class="absolute -top-1 -right-1 w-4 h-4 {{ request()->is('cart') || request()->is('keranjang') ? 'bg-black' : 'bg-[#0369A1]' }} text-white text-[9px] font-bold rounded-full flex items-center justify-center font-['Plus_Jakarta_Sans']">
                                3
                            </span>
                        </a>
                <!-- WhatsApp CS Button -->
                <a href="https://wa.me/6281234567890?text=Halo%20Batik%20Pandansari,%20saya%20ingin%20konsultasi" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="hidden md:inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-sm font-['Plus_Jakarta_Sans']">
                    <i class="fa-regular fa-comment-dots text-sm"></i>
                    <span>WhatsApp CS</span>
                </a>

             <!-- User Profile Button (status dari API: GET /me — dropdown profil/keluar) -->
             <div class="relative" x-data="{
                     open: false,
                     logged: false,
                     name: 'Pengguna',
                     async init() {
                         if (!PandansariAPI.getToken()) return;
                         const res = await PandansariAPI.me();
                         if (res.ok) {
                             this.logged = true;
                             this.name = res.data?.data?.nama_pengguna ?? res.data?.data?.name ?? res.data?.nama_pengguna ?? res.data?.name ?? 'Pengguna';
                         } else {
                             // Token usang tidak valid — bersihkan
                             PandansariAPI.clearToken();
                         }
                     },
                     async keluar() {
                         // Coba cabut token di server lebih dulu, lalu selalu bersihkan token di sisi client
                         await PandansariAPI.logout();
                         PandansariAPI.clearToken();
                         window.location.href = '/';
                     }
                 }" @click.outside="open = false">
                 <button @click="open = !open" 
                 class="w-8 h-8 bg-black text-white rounded-full flex items-center justify-center text-xs hover:bg-slate-800 transition {{ request()->is('profile') || request()->is('orders') || request()->is('address') || request()->is('security') ? 'ring-2 ring-sky-500' : '' }}">
                     <i class="fa-regular" :class="logged ? 'fa-user-check' : 'fa-user'"></i>
                 </button>

                 <!-- Dropdown -->
                 <div x-show="open" x-transition.opacity.duration.150ms
                      class="absolute right-0 mt-2 w-52 bg-white border border-slate-200/80 rounded-xl shadow-lg py-2 z-50 font-['Plus_Jakarta_Sans']">
                     
                     <template x-if="logged">
                         <div>
                             <div class="px-4 py-2 border-b border-slate-100">
                                 <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Masuk sebagai</p>
                                 <p class="text-xs font-bold text-slate-800 truncate" x-text="name"></p>
                             </div>
                             <a href="/profile" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900"><i class="fa-regular fa-user w-4 mr-1.5"></i>Profil Saya</a>
                             <a href="/orders" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900"><i class="fa-solid fa-box w-4 mr-1.5"></i>Pesanan Saya</a>
                             <button @click="keluar()"
                                     class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 border-t border-slate-100 mt-1">
                                 <i class="fa-solid fa-arrow-right-from-bracket w-4 mr-1.5"></i> Keluar
                             </button>
                         </div>
                     </template>

                     <template x-if="!logged">
                         <div>
                             <a href="/login" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900"><i class="fa-solid fa-arrow-right-to-bracket w-4 mr-1.5"></i> Masuk</a>
                             <a href="/register" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900"><i class="fa-regular fa-id-card w-4 mr-1.5"></i> Daftar</a>
                         </div>
                     </template>
                 </div>
             </div>

                <!-- Mobile Hamburger Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-1.5 text-slate-800 hover:text-black">
                    <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark text-xl' : 'fa-bars text-lg'"></i>
                </button>
            </div>
        </div>

        <!-- Navigation Links Row (Desktop) dengan Tanda Halaman Aktif Otomatis -->
        <nav class="hidden lg:flex items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-slate-600 mt-3 pt-2 font-['Plus_Jakarta_Sans'] overflow-x-auto">
            
            <a href="/" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request()->is('/') ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                BERANDA
            </a>
            
            <a href="/katalog" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request()->is('katalog') && !request('category') ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                KATALOG
            </a>
            
            <a href="/katalog?category=tulis" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request('category') == 'tulis' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                BATIK TULIS
            </a>
            
            <a href="/katalog?category=cap" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request('category') == 'cap' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                BATIK CAP
            </a>
            
            <a href="/katalog?category=handprint" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request('category') == 'handprint' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                BAHAN HANDPRINT
            </a>
            
            <a href="/katalog?category=wanita" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request('category') == 'wanita' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                PAKAIAN WANITA
            </a>
            
            <a href="/katalog?category=pria" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request('category') == 'pria' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                PAKAIAN PRIA
            </a>
            
            <a href="/katalog?category=anak" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request('category') == 'anak' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                PAKAIAN ANAK-ANAK
            </a>
            
            <a href="/katalog?category=sarimbit" class="px-2.5 py-1 rounded-md transition whitespace-nowrap {{ request('category') == 'sarimbit' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'hover:text-black' }}">
                SARIMBIT
            </a>
            
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
         class="lg:hidden bg-white border-t border-slate-100 px-4 py-4 space-y-2 font-['Plus_Jakarta_Sans'] text-xs font-bold uppercase">
        
        <a href="/" class="block py-1.5 px-2 rounded-md {{ request()->is('/') ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">BERANDA</a>
        <a href="/katalog" class="block py-1.5 px-2 rounded-md {{ request()->is('katalog') && !request('category') ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">KATALOG</a>
        <a href="/katalog?category=tulis" class="block py-1.5 px-2 rounded-md {{ request('category') == 'tulis' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">BATIK TULIS</a>
        <a href="/katalog?category=cap" class="block py-1.5 px-2 rounded-md {{ request('category') == 'cap' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">BATIK CAP</a>
        <a href="/katalog?category=handprint" class="block py-1.5 px-2 rounded-md {{ request('category') == 'handprint' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">BAHAN HANDPRINT</a>
        <a href="/katalog?category=wanita" class="block py-1.5 px-2 rounded-md {{ request('category') == 'wanita' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">PAKAIAN WANITA</a>
        <a href="/katalog?category=pria" class="block py-1.5 px-2 rounded-md {{ request('category') == 'pria' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">PAKAIAN PRIA</a>
        <a href="/katalog?category=anak" class="block py-1.5 px-2 rounded-md {{ request('category') == 'anak' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">PAKAIAN ANAK-ANAK</a>
        <a href="/katalog?category=sarimbit" class="block py-1.5 px-2 rounded-md {{ request('category') == 'sarimbit' ? 'bg-slate-100 text-slate-900 font-extrabold' : 'text-slate-700 hover:text-black' }}">SARIMBIT</a>
    </div>
</header>