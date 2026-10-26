<div x-data="teknisiDashboard" class="space-y-6">

    <!-- GATE: belum login -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-slate-900">Silakan login (akun teknisi/admin) untuk membuka Engineering Console.</p>
        <a href="{{ route('login') }}" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>

    <div x-show="!needLogin" x-cloak class="space-y-6">

        <!-- KESEHATAN API -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-lg shrink-0"
                         :class="healthLoading ? 'bg-sky-100 text-sky-600' : (healthOk() ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700')">
                        <i class="fa-solid" :class="healthLoading ? 'fa-circle-notch fa-spin' : (healthOk() ? 'fa-heart-pulse' : 'fa-triangle-exclamation')"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 block">KESEHATAN API</span>
                        <strong class="text-sm font-black text-slate-900 block" x-text="healthStatusLabel()"></strong>
                        <span class="text-[10px] text-slate-500 font-mono block">GET /api/teknisi/health</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="item in healthItems()" :key="item.label">
                        <span class="max-w-[220px] px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-[10px] font-bold text-slate-600 flex items-center gap-1">
                            <span class="text-slate-400" x-text="item.label + ':'"></span>
                            <span class="font-mono text-slate-800 truncate" :title="item.value" x-text="item.value"></span>
                        </span>
                    </template>

                    <button type="button" @click="loadAll()"
                            class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-[10px] font-black text-slate-600 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            :disabled="loading || healthLoading">
                        <i class="fa-solid fa-rotate" :class="(loading || healthLoading) ? 'fa-spin' : ''"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            <div x-show="healthError" x-cloak class="mt-3 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2" x-text="healthError"></div>
            <div x-show="!healthLoading && health && !healthItems().length" class="mt-3 text-[11px] text-slate-400 font-semibold">
                Endpoint health tidak mengembalikan detail tambahan.
            </div>
        </div>

        <!-- PESAN ERROR DASHBOARD -->
        <div x-show="dashboardError" x-cloak
             class="text-xs font-bold rounded-xl px-4 py-3 border bg-rose-50 text-rose-700 border-rose-200"
             x-text="dashboardError"></div>

        <!-- KARTU RINGKASAN -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 text-xs">
            <template x-if="loading">
                <div class="sm:col-span-2 xl:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-8 text-center text-slate-400 font-bold">
                    <i class="fa-solid fa-circle-notch fa-spin mr-2 text-sky-500"></i>
                    Memuat ringkasan dashboard…
                </div>
            </template>

            <template x-for="card in cards()" :key="card.label">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 block" x-text="card.label"></span>
                        <div class="text-2xl font-black text-slate-900 truncate" x-text="card.value"></div>
                    </div>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0" :class="card.cls">
                        <i class="fa-solid" :class="card.icon"></i>
                    </div>
                </div>
            </template>
        </div>

        <!-- FALLBACK: nama kunci dashboard berbeda dari perkiraan -->
        <div x-show="!loading && !cards().length" x-cloak class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-800">RINGKASAN DATA DASHBOARD</h2>
                <span class="text-[10px] font-mono text-slate-400">GET /api/teknisi/dashboard</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <template x-for="item in genericStats()" :key="item.label">
                    <div class="bg-slate-50 rounded-xl border border-slate-200/70 px-3 py-2 min-w-0">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block truncate" :title="item.label" x-text="item.label"></span>
                        <strong class="text-sm font-black text-slate-900 block truncate" :title="item.value" x-text="item.value"></strong>
                    </div>
                </template>
            </div>

            <p x-show="!genericStats().length" class="text-[11px] text-slate-400 font-semibold">
                Endpoint dashboard tidak mengembalikan angka ringkasan yang bisa ditampilkan.
            </p>
        </div>

        <!-- JSON MENTAH (debug) -->
        <div class="bg-[#0A111E] text-slate-300 rounded-2xl border border-slate-800 shadow-xl p-5 space-y-4 font-mono">
            <div class="flex items-center justify-between gap-3 border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-code text-sky-400 text-sm"></i>
                    <h3 class="text-xs font-black text-white uppercase tracking-wider font-sans">RESPONS MENTAH</h3>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] text-slate-500">Diperbarui: <span class="text-slate-300" x-text="updatedText()"></span></span>
                    <button type="button" @click="showRaw = !showRaw"
                            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-[10px] font-black text-slate-200 cursor-pointer">
                        <i class="fa-solid" :class="showRaw ? 'fa-eye-slash' : 'fa-eye'"></i>
                        <span x-text="showRaw ? 'Sembunyikan JSON' : 'Tampilkan JSON'"></span>
                    </button>
                </div>
            </div>

            <div x-show="showRaw" x-cloak class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div>
                    <span class="text-[10px] font-black text-sky-400 uppercase tracking-wider block mb-1.5 font-sans">GET /api/teknisi/dashboard</span>
                    <div class="bg-[#050A12] border border-slate-800 rounded-xl p-3 max-h-64 overflow-auto">
                        <pre class="m-0 text-[10px] leading-relaxed text-sky-300 whitespace-pre"><code x-text="jsonDashboard()"></code></pre>
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-black text-emerald-400 uppercase tracking-wider block mb-1.5 font-sans">GET /api/teknisi/health</span>
                    <div class="bg-[#050A12] border border-slate-800 rounded-xl p-3 max-h-64 overflow-auto">
                        <pre class="m-0 text-[10px] leading-relaxed text-emerald-300 whitespace-pre"><code x-text="jsonHealth()"></code></pre>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
