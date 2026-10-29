{{-- Component kartu produk katalog — dipanggil dari shop/katalog.blade.php
    Memakai variabel Alpine `p` dari parent (template x-for pada shop/katalog.blade.php). --}}
<div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
    <div class="relative h-44 sm:h-72 bg-slate-100 overflow-hidden">
        <a :href="'/detail/' + p.id_product" class="block w-full h-full">
            <img :src="coverOf(p)" :alt="p.nama_produk" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out" :onerror="`this.src='${fallbackImage}'`">
        </a>

        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 bg-[#BAE6FD] text-[#0F1A24] text-[8px] sm:text-[9px] font-bold px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded sm:rounded-md uppercase tracking-wide shadow-sm z-10 max-w-[70%] truncate pointer-events-none">
            <span x-text="(p.category?.nama_kategori ?? 'KOLEKSI').toUpperCase()"></span>
        </span>

        <!-- Tombol Toggle Like / Wishlist -->
        <button @click.stop.prevent="typeof toggleLike === 'function' ? toggleLike(p) : toggleWishlist(p)"
                type="button"
                class="absolute top-2 right-2 sm:top-3 sm:right-3 w-7 h-7 sm:w-8 sm:h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                :class="(p.is_liked || (typeof wishlisted !== 'undefined' && wishlisted[p.id_product]?.liked)) ? 'text-red-500' : 'text-gray-400'"
                :disabled="p.isLoading || p.isLoadingWishlist || (typeof wishlistBusy !== 'undefined' && wishlistBusy[p.id_product])"
                :title="(p.is_liked || (typeof wishlisted !== 'undefined' && wishlisted[p.id_product]?.liked)) ? 'Batalkan Suka' : 'Sukai Produk'">
            <i class="text-[11px] sm:text-xs transition-colors duration-150"
               :class="(p.is_liked || (typeof wishlisted !== 'undefined' && wishlisted[p.id_product]?.liked))
                   ? 'fa-solid fa-heart text-red-500 fill-current'
                   : 'fa-regular fa-heart text-gray-400 stroke-current'"></i>
        </button>

        <div class="hidden sm:flex absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 items-center justify-center gap-2 p-4 pointer-events-none">
            <a :href="'/detail/' + p.id_product" class="pointer-events-auto bg-white/95 text-[#0F1A24] hover:bg-[#0F1A24] hover:text-white text-[11px] font-bold px-3.5 py-2 rounded-xl shadow-lg transform translate-y-3 group-hover:translate-y-0 transition-all duration-300">
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
            <a :href="'/detail/' + p.id_product" class="block">
                <h3 class="font-bold text-[#0F1A24] text-xs sm:text-sm mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2" x-text="p.nama_produk"></h3>
            </a>
            {{-- Deskripsi dihilangkan agar kartu lebih ringkas (compact) & sejajar rapi --}}
        </div>
        <!-- Elemen Harga (di atas tombol Lihat Detail) -->
        <div class="mt-2 sm:mt-3 pt-2 sm:pt-2.5 border-t border-slate-100">
            <span class="text-[8px] sm:text-[10px] text-slate-400 font-medium block leading-tight mb-0.5"
                  x-show="hasVariants(p)">
                Mulai dari
            </span>
            <template x-if="priceOf(p)">
                <span class="font-bold text-[#0F1A24] text-xs sm:text-base leading-tight block" x-text="priceOf(p)"></span>
            </template>
            <template x-if="!priceOf(p)">
                <span class="text-[10px] sm:text-xs text-slate-400 italic block">Harga belum tersedia</span>
            </template>
        </div>

        <!-- Tombol Aksi: Lihat Detail di atas tombol + Keranjang -->
        <div class="mt-2 sm:mt-3 space-y-1.5">
            <a :href="'/detail/' + p.id_product"
               class="w-full bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200 text-[10px] sm:text-xs font-bold py-1.5 sm:py-2 rounded-xl flex items-center justify-center gap-1.5 transition">
                <span>Lihat Detail</span>
                <i class="fa-solid fa-arrow-right text-[9px]"></i>
            </a>
            <button @click="keKeranjang(p)" type="button"
                    class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white text-[10px] sm:text-xs font-bold py-2 sm:py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition shadow-sm font-['Plus_Jakarta_Sans'] cursor-pointer">
                <i class="fa-solid fa-cart-plus text-xs"></i> + Keranjang
            </button>
        </div>
    </div>
</div>
