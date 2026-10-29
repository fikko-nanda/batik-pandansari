@props([
    'badge' => 'TERBARU',
    'subtitle' => 'SOLO • ATELIER WANITA',
    'title' => 'Tunik Batik Cheongsam',
    'description' => null,
    'price' => 'Rp 1.280.000',
    'material' => 'Katun Primis Super',
    'image' => asset('images/w1.png')
])

<div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden flex flex-col justify-between shadow-sm hover:shadow-md transition">
    <div class="relative h-64 bg-slate-50">
        <img src="{{ $image }}" alt="{{ $title }}" class="w-full h-full object-cover">
        <span class="absolute top-3 left-3 bg-sky-100 border border-sky-200 text-[#075985] text-[9px] font-bold px-2 py-0.5 rounded uppercase font-['Plus_Jakarta_Sans']">
            {{ $badge }}
        </span>
        <button type="button"
                @click.stop.prevent="typeof toggleWishlist === 'function' ? toggleWishlist(product) : (typeof toggleLike === 'function' ? toggleLike(product) : null)"
                class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10 cursor-pointer disabled:opacity-50"
                :class="(typeof product !== 'undefined' && product.is_liked) ? 'text-red-500' : 'text-gray-400'"
                :title="(typeof product !== 'undefined' && product.is_liked) ? 'Batalkan Suka' : 'Sukai Produk'">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-4 h-4 transition-colors duration-150"
                 :class="(typeof product !== 'undefined' && product.is_liked)
                     ? 'text-red-500 fill-red-500'
                     : 'text-gray-400 fill-none stroke-current'"
                 viewBox="0 0 24 24"
                 stroke-width="1.8"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
            </svg>
        </button>
    </div>
    <div class="p-4 flex-1 flex flex-col justify-between">
        <div>
            <span class="text-[10px] font-semibold text-slate-400 tracking-wide uppercase block mb-0.5 font-['Plus_Jakarta_Sans']">{{ $subtitle }}</span>
            <h4 class="font-bold text-[#0F1A24] text-base mb-1 font-['Plus_Jakarta_Sans'] leading-snug line-clamp-2">{{ $title }}</h4>
            {{-- Deskripsi dihilangkan agar tampilan kartu lebih ringkas & rapi --}}
        </div>
        <div class="mt-3 pt-3 border-t border-slate-100 flex justify-between items-end font-['Plus_Jakarta_Sans']">
            <div>
                <span class="text-[10px] text-slate-400 block font-medium leading-none mb-1">Mulai dari</span>
                <span class="font-bold text-[#0F1A24] text-base leading-tight">{{ $price }}</span>
            </div>
            <span class="text-[10px] font-medium text-slate-500 bg-slate-50 border border-slate-100 px-2 py-0.5 rounded">{{ $material }}</span>
        </div>
    </div>
    <div class="p-4 pt-0 space-y-1.5">
        <a href="/detail" class="w-full bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200 text-xs font-semibold py-2 rounded-xl flex items-center justify-center gap-1.5 transition font-['Plus_Jakarta_Sans']">
            <span>Lihat Detail</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
        <a href="/katalog" class="w-full bg-[#0F1A24] text-white text-xs font-semibold py-2.5 rounded-xl flex items-center justify-center gap-1.5 hover:bg-slate-800 transition font-['Plus_Jakarta_Sans']">
            <i class="fa-solid fa-cart-plus text-xs"></i> + Keranjang
        </a>
    </div>
</div>