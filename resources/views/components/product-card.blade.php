@props([
    'badge' => 'TERBARU',
    'subtitle' => 'SOLO • ATELIER WANITA',
    'title' => 'Tunik Batik Cheongsam',
    'description' => 'Aksen kerah cheongsam klasik oriental dengan motif burung hong prada...',
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
        <button class="absolute top-3 right-3 w-7 h-7 bg-white/90 rounded-full flex items-center justify-center text-slate-700 shadow-sm hover:text-rose-500 transition">
            <i class="fa-regular fa-heart text-xs"></i>
        </button>
    </div>
    <div class="p-4 flex-1 flex flex-col justify-between">
        <div>
            <span class="text-[10px] font-semibold text-slate-400 tracking-wide uppercase block mb-0.5 font-['Plus_Jakarta_Sans']">{{ $subtitle }}</span>
            <h4 class="font-bold text-[#0F1A24] text-base mb-1 font-['Plus_Jakarta_Sans']">{{ $title }}</h4>
            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed font-['Plus_Jakarta_Sans']">{{ $description }}</p>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-baseline font-['Plus_Jakarta_Sans']">
            <span class="font-bold text-[#0F1A24] text-base">{{ $price }}</span>
            <span class="text-[10px] font-medium text-slate-500">{{ $material }}</span>
        </div>
    </div>
    <div class="p-4 pt-0">
        <button class="w-full bg-[#0F1A24] text-white text-xs font-semibold py-2.5 rounded-xl flex items-center justify-center gap-1.5 hover:bg-slate-800 transition font-['Plus_Jakarta_Sans']">
            <i class="fa-solid fa-cart-plus text-xs"></i> + Keranjang
        </button>
    </div>
</div>