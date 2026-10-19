<!-- TOP NAVBAR ADMIN -->
<header class="bg-white border-b border-slate-200/80 py-3.5 px-6 sticky top-0 z-30 flex items-center justify-between gap-4">
    
    <!-- Search Input Header -->
    <div class="relative w-full max-w-md">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" 
               placeholder="Cari wastra, no. resi, ID pesanan, atau kolektor..." 
               class="w-full text-xs font-medium border border-slate-200 rounded-xl pl-9 pr-3 py-2 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
    </div>

    <!-- Right Action Items -->
    <div class="flex items-center gap-3 shrink-0 text-xs">
        
        <!-- Month Selector -->
        <div class="bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5">
            <i class="fa-regular fa-calendar text-slate-500"></i>
            <span>Okt 2026</span>
        </div>

        <!-- Live Midtrans Status Badge -->
        <div class="bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl font-bold flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span class="text-[11px]">Midtrans Live</span>
        </div>

        <!-- Notification Icon -->
        <button class="w-9 h-9 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center hover:bg-slate-200 transition relative">
            <i class="fa-regular fa-bell text-sm"></i>
            <span class="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full"></span>
        </button>

        <!-- Profile Avatar -->
        <div class="w-9 h-9 bg-black text-white rounded-full flex items-center justify-center font-bold text-xs">
            <i class="fa-solid fa-user"></i>
        </div>
    </div>

</header>