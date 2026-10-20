{{-- Component kartu produk katalog — dipanggil dari shop/katalog.blade.php
    Memakai variabel Alpine `p` dari parent (template x-for pada shop/katalog.blade.php). --}}
<div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
        <img :src="coverOf(p)" :alt="p.nama_produk" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out" :onerror="`this.src='${fallbackImage}'`">

        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10 max-w-[70%] truncate">
            <span x-text="(p.category?.nama_kategori ?? 'KOLEKSI').toUpperCase()"></span>
        </span>

        <button @click="toggleWishlist(p)" type="button"
                class="absolute top-2 right-2 sm:top-3 sm:right-3 w-6 h-6 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-slate-700 shadow-md hover:text-rose-500 hover:scale-110 active:scale-95 transition-all duration-200 z-10"
                :class="wishlisted[p.id_product] ? 'text-rose-500' : ''">
            <i class="" :class="wishlisted[p.id_product] ? 'fa-solid fa-heart text-rose-500' : 'fa-regular fa-heart' + ' text-[10px] sm:text-xs'"></i>
        </button>

        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4">
            <a :href="'/detail/' + p.id_product" class="bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
                Detail Produk
            </a>
        </div>
    </div>
    <div class="p-2.5 sm:p-4 flex-1 flex flex-col justify-between">
        <div>
            <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-0.5 line-clamp-1">
                <span x-text="(p.category?.nama_kategori ?? 'BATIK PANDANSARI').toUpperCase()"></span>
                <span x-show="p.category?.nama_kategori"> • BATIK PANDANSARI</span>
            </span>
            <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2" x-text="p.nama_produk"></h3>
            <p class="text-xs text-slate-500 line-clamp-2 hidden sm:block" x-text="p.deskripsi ?? ''"></p>
        </div>
        <div class="mt-2 sm:mt-4 pt-2 sm:pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
            <span class="font-bold text-[#0F1A24] text-xs sm:text-base" x-text="priceOf(p)"></span>
            <span class="text-[8px] sm:text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-medium text-slate-600 self-start sm:self-auto truncate max-w-[10rem]" x-show="sizesOf(p)" x-text="sizesOf(p)"></span>
        </div>

        <!-- + Keranjang (langsung dari kartu; varian aktif pertama dipilih otomatis) -->
        <button @click="keKeranjang(p)" type="button"
                class="mt-2 sm:mt-3 w-full bg-[#0F1A24] hover:bg-slate-800 text-white text-[10px] sm:text-xs font-bold py-2 sm:py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition shadow-sm font-['Plus_Jakarta_Sans']">
            <i class="fa-solid fa-cart-plus text-xs"></i> + Keranjang
        </button>
    </div>
</div>
