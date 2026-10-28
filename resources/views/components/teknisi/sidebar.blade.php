<aside class="w-64 bg-[#0A111E] text-white flex flex-col justify-between h-screen sticky top-0 shrink-0 border-r border-slate-800/80 font-sans z-30">
    
    <!-- TOP SECTION: LOGO & MENU -->
    <div class="space-y-6">
        
        <!-- HEADER / BRANDING -->
        <div class="p-4 border-b border-slate-800/80 flex items-center gap-3">
            <div class="w-8 h-8 bg-sky-500/20 text-sky-400 rounded-xl flex items-center justify-center font-black text-xs border border-sky-400/30 shrink-0">
                BP
            </div>
            <div>
                <h2 class="text-xs font-black text-white tracking-wider uppercase leading-tight">Batik Pandansari</h2>
                <span class="text-[9px] font-mono font-bold text-slate-400 tracking-widest block uppercase">ENGINEERING CORE</span>
            </div>
        </div>

        <!-- NAVIGATION MENU -->
        <div class="px-3 space-y-4">
            
            <div class="space-y-1">
                <p class="px-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">MONITORING & AUDIT</p>

                <!-- Dashboard -->
                <a href="{{ route('teknisi.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('teknisi.dashboard') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <i class="fa-solid fa-gauge-high text-sm w-4"></i>
                    <span>Dashboard Teknisi</span>
                </a>

                <!-- Audit Log & Teknis -->
                <a href="{{ route('teknisi.audit-log') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('teknisi.audit-log') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <i class="fa-solid fa-code-commit text-sm w-4"></i>
                    <span>Audit Log & Teknis</span>
                </a>

                <!-- Log Webhook -->
                <a href="{{ route('teknisi.webhook-logs') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('teknisi.webhook-logs') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-bolt text-sm w-4"></i>
                        <span>Log Webhook</span>
                    </div>
                    <span class="text-[9px] bg-sky-500/20 text-sky-300 px-1.5 py-0.5 rounded font-mono font-extrabold border border-sky-400/30">LIVE</span>
                </a>

                <!-- Log Notifikasi -->
                <a href="{{ route('teknisi.notification-logs') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('teknisi.notification-logs') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <i class="fa-solid fa-envelope text-sm w-4"></i>
                    <span>Log Notifikasi</span>
                </a>

                <!-- Failed Jobs -->
                <a href="{{ route('teknisi.failed-jobs') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('teknisi.failed-jobs') ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <i class="fa-solid fa-triangle-exclamation text-sm w-4"></i>
                    <span>Failed Jobs</span>
                </a>
            </div>

            <!-- PINTASAN: pratinjau sisi customer (berguna saat maintenance) -->
            <div class="pt-4 border-t border-slate-800/80 space-y-1">
                <p class="px-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">PINTASAN</p>
                <a href="{{ route('home') }}"
                   title="Lihat tampilan customer / toko (berguna saat maintenance)"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-emerald-300 bg-emerald-500/10 border border-emerald-400/30 hover:bg-emerald-500/20 hover:text-emerald-200 transition">
                    <i class="fa-solid fa-store text-sm w-4"></i>
                    <span>Dashboard Customer</span>
                    <i class="fa-solid fa-arrow-up-right-from-square ml-auto text-[10px] opacity-70"></i>
                </a>
            </div>

        </div>

    </div>

    <!-- BOTTOM SECTION: SYSTEM NODE FOOTER -->
    <div class="p-3 border-t border-slate-800/80 bg-[#070D18]">
        <div class="flex items-center justify-between text-[10px] font-mono font-bold text-slate-400">
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-slate-500 text-[10px]"></i>
                <span>TLS 1.3</span>
            </div>
            <button type="button"
                    onclick="PandansariAPI.keluarPanel()"
                    title="Keluar panel"
                    class="flex items-center gap-1.5 px-2 py-1 rounded-md text-rose-300 hover:text-white hover:bg-rose-500/20 transition cursor-pointer">
                <i class="fa-solid fa-arrow-right-from-bracket text-[10px]"></i>
                <span>Keluar</span>
            </button>
        </div>
        <div class="text-[9px] font-mono text-slate-400 mt-0.5">TLS 1.3 Active • 12ms</div>
    </div>

</aside>    