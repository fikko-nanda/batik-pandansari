@props([
    'kind' => 'audit',
    'title' => 'Log Teknis',
    'description' => '',
    'icon' => 'fa-code-commit',
])

<div x-data="teknisiLogExplorer('{{ $kind }}')" class="space-y-6">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-0.5">MONITORING &amp; AUDIT</span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid {{ $icon }} text-sky-600 text-lg"></i>
                <span>{{ $title }}</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $description }}</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button" @click="load()" :disabled="loading"
                    class="px-3 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-[11px] font-black text-slate-600 flex items-center gap-2 shadow-sm cursor-pointer disabled:opacity-50">
                <i class="fa-solid fa-rotate" :class="loading ? 'fa-spin' : ''"></i>
                <span>Muat ulang</span>
            </button>
            <button type="button" @click="toggleAuto()"
                    :aria-pressed="autoRefresh"
                    :title="autoRefresh ? 'Klik untuk menonaktifkan auto-refresh' : 'Klik untuk mengaktifkan auto-refresh'"
                    class="px-3 py-2 rounded-xl border text-[11px] font-black flex items-center gap-2 shadow-sm cursor-pointer"
                    :class="autoRefresh ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                <span class="w-2 h-2 rounded-full" :class="autoRefresh ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300'"></span>
                <span x-text="autoRefresh ? 'Auto-refresh aktif' : 'Auto-refresh mati'"></span>
            </button>
        </div>
    </div>

    <!-- GATE: belum login -->
    <div x-show="needLogin" x-cloak class="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
        <i class="fa-solid fa-lock text-3xl text-slate-300"></i>
        <p class="text-sm font-bold text-slate-900">Silakan login (akun teknisi/admin) untuk membaca log sistem.</p>
        <a href="{{ route('login') }}" class="inline-block bg-[#0A192F] text-white text-xs font-bold px-6 py-3 rounded-xl">LOGIN</a>
    </div>

    <div x-show="!needLogin" x-cloak class="space-y-6">

        <!-- FILTER BAR -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
            <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="relative flex-1 min-w-[240px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" x-model="search" @input.debounce.400ms="page = 1; load()"
                           placeholder="Cari ID, aktor, event, status, atau isi pesan…"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>

                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold text-slate-500">
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 font-mono">GET {{ $kind === 'failed' ? '/api/teknisi/failed-jobs' : '/api/teknisi/' . $kind . '-logs' }}</span>
                    <span>Total <strong class="text-slate-800" x-text="totalRows.toLocaleString('id-ID')"></strong></span>
                    <span class="text-slate-300">•</span>
                    <span>Diperbarui <strong class="text-slate-800" x-text="updatedText()"></strong></span>
                    <span class="text-slate-300">•</span>
                    <label class="flex items-center gap-1">
                        <span>Baris</span>
                        <select x-model.number="perPage" @change="page = 1; load()"
                                class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-[10px] font-bold text-slate-700 focus:outline-none">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
            </div>

            <div x-show="error" x-cloak class="mt-3 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2" x-text="error"></div>
        </div>

        <!-- TABEL LOG -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[10px] font-black uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3.5 px-4 w-40">WAKTU</th>
                            <th class="py-3.5 px-4">EVENT / AKTOR</th>
                            <th class="py-3.5 px-4">TARGET / REFERENSI</th>
                            <th class="py-3.5 px-4 w-32">STATUS</th>
                            <th class="py-3.5 px-4 w-32">DETAIL</th>
                            <th class="py-3.5 px-4 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">
                                    <i class="fa-solid fa-circle-notch fa-spin mr-2 text-sky-500"></i>
                                    Memuat <span x-text="meta.label"></span>…
                                </td>
                            </tr>
                        </template>

                        <template x-if="!loading && !rows.length">
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">
                                    <i class="fa-regular fa-folder-open mr-2"></i>
                                    Tidak ada data log untuk filter ini.
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in rows" :key="row.key">
                            <tr @click="openDetail(row)"
                                @keydown.enter="openDetail(row)"
                                tabindex="0"
                                class="hover:bg-sky-50/60 focus:bg-sky-50/60 focus:outline-none transition cursor-pointer">
                                <td class="py-3.5 px-4 align-top">
                                    <strong class="text-slate-900 font-black text-[11px] block font-mono" x-text="row.time"></strong>
                                    <span class="text-[9px] text-slate-400 font-mono block" x-text="row.hasId ? ('#' + row.id) : 'tanpa ID'"></span>
                                </td>

                                <td class="py-3.5 px-4 align-top">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-200 text-slate-700" x-text="row.badge"></span>
                                        <strong class="text-slate-900 font-bold text-xs" x-text="row.title"></strong>
                                    </div>
                                    <span x-show="row.subtitle" class="text-[10px] text-slate-500 block mt-0.5 line-clamp-1" :title="row.subtitle" x-text="row.subtitle"></span>
                                </td>

                                <td class="py-3.5 px-4 align-top">
                                    <span class="text-[10px] font-mono text-slate-700 block truncate max-w-[260px]" :title="row.target" x-text="row.target || '—'"></span>
                                    <span x-show="row.targetDesc" class="text-[9px] text-slate-400 block truncate max-w-[260px]" :title="row.targetDesc" x-text="row.targetDesc"></span>
                                </td>

                                <td class="py-3.5 px-4 align-top">
                                    <span class="text-[9px] font-extrabold px-2 py-1 rounded-md border uppercase inline-block" :class="statusClass(row.status)" x-text="row.status || '—'"></span>
                                </td>

                                <td class="py-3.5 px-4 align-top">
                                    <span class="text-[10px] font-mono text-slate-600" x-text="row.extra || '—'"></span>
                                </td>

                                <td class="py-3.5 px-4 align-top text-right">
                                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- FOOTER + PAGINASI -->
            <div class="p-4 bg-slate-50/80 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs font-medium text-slate-500">
                <div>
                    Halaman <strong class="text-slate-800" x-text="page"></strong> dari <strong class="text-slate-800" x-text="lastPage"></strong>
                    — total <strong class="text-slate-800" x-text="totalRows.toLocaleString('id-ID')"></strong> rekaman
                    <span x-show="refreshing" class="ml-2 text-sky-600 font-bold"><i class="fa-solid fa-circle-notch fa-spin"></i> menyegarkan…</span>
                </div>
                <div class="flex items-center gap-1.5 font-bold self-end sm:self-auto">
                    <button type="button" @click="goto(1)" :disabled="page <= 1"
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">&laquo; Awal</button>
                    <button type="button" @click="goto(page - 1)" :disabled="page <= 1"
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">&lsaquo; Sebelumnya</button>
                    <span class="px-2 text-slate-700 font-mono" x-text="page + ' / ' + lastPage"></span>
                    <button type="button" @click="goto(page + 1)" :disabled="page >= lastPage"
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">Berikutnya &rsaquo;</button>
                    <button type="button" @click="goto(lastPage)" :disabled="page >= lastPage"
                            class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer disabled:opacity-40">Akhir &raquo;</button>
                </div>
            </div>
        </div>

        <p class="text-[10px] font-bold text-slate-400 flex items-center gap-1.5">
            <i class="fa-solid fa-circle-info"></i>
            Klik salah satu baris untuk memuat detail dari
            <span class="font-mono text-slate-500">{{ $kind === 'failed' ? '/api/teknisi/failed-jobs/{id}' : '/api/teknisi/' . $kind . '-logs/{id}' }}</span>
            dan menampilkan payload/response sebagai JSON.
        </p>
    </div>

    <!-- MODAL JSON VIEWER -->
    <div x-show="detailOpen" x-cloak
         @keydown.escape.window="closeDetail()"
         @click.self="closeDetail()"
         class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">

        <div class="w-full max-w-3xl bg-[#0A111E] border border-slate-700 rounded-2xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">

            <!-- HEADER MODAL -->
            <div class="flex items-start justify-between gap-3 p-5 border-b border-slate-800">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-code text-sky-400 text-sm"></i>
                        <h3 class="text-xs font-black text-white uppercase tracking-wider font-sans">DETAIL <span x-text="meta.label"></span></h3>
                    </div>
                    <p class="text-[10px] text-slate-400 font-mono mt-1 truncate" x-text="detailRow ? (detailRow.hasId ? ('ID: ' + detailRow.id) : 'ID tidak tersedia') : ''"></p>
                    <p class="text-[10px] text-slate-500 font-mono truncate" x-text="detailRow ? (detailRow.time + ' • ' + (detailRow.title || '')) : ''"></p>
                </div>
                <button type="button" @click="closeDetail()"
                        class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 flex items-center justify-center cursor-pointer shrink-0">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <!-- SUBHEADER STATUS -->
            <div class="px-5 pt-4 flex flex-wrap items-center gap-2 text-[10px] font-bold">
                <span class="px-2 py-1 rounded-md border uppercase" :class="statusClass(detailRow ? detailRow.status : '')" x-text="detailRow ? detailRow.status : '—'"></span>
                <span class="px-2 py-1 rounded-md bg-slate-800 border border-slate-700 text-slate-300 font-mono" x-text="detailRow ? meta.endpoint : ''"></span>
                <span x-show="detailError" class="px-2 py-1 rounded-md bg-amber-500/10 border border-amber-500/30 text-amber-300" x-text="detailError"></span>
            </div>

            <!-- TAB JSON -->
            <div class="px-5 pt-3">
                <div class="grid grid-cols-3 gap-1 bg-slate-900/80 p-1 rounded-xl text-center text-[10px] font-bold border border-slate-800 font-sans">
                    <button type="button" @click="detailTab = 'payload'"
                            class="py-1.5 rounded-lg transition cursor-pointer"
                            :class="detailTab === 'payload' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'">PAYLOAD</button>
                    <button type="button" @click="detailTab = 'response'"
                            class="py-1.5 rounded-lg transition cursor-pointer"
                            :class="detailTab === 'response' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'">RESPONSE</button>
                    <button type="button" @click="detailTab = 'all'"
                            class="py-1.5 rounded-lg transition cursor-pointer"
                            :class="detailTab === 'all' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'">DATA LENGKAP</button>
                </div>
            </div>

            <!-- JSON BODY -->
            <div class="p-5 flex-1 min-h-0">
                <div class="relative bg-[#050A12] border border-slate-800 rounded-xl p-4 h-[46vh] overflow-auto">
                    <div x-show="detailLoading" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-[#050A12]/90 z-10">
                        <i class="fa-solid fa-circle-notch fa-spin text-sky-400 text-lg"></i>
                        <span class="text-[10px] font-bold text-slate-400">Memuat detail dari API…</span>
                    </div>

                    <pre x-show="!detailLoading" class="m-0 text-[10px] leading-relaxed text-sky-300 whitespace-pre"><code x-text="detailJson()"></code></pre>
                </div>
            </div>

            <!-- FOOTER MODAL -->
            <div class="p-4 border-t border-slate-800 flex items-center justify-between gap-3 font-sans">
                <span class="text-[10px] text-slate-500 font-mono" x-text="detailLoading ? 'GET detail…' : 'JSON.stringify(data, null, 2)'"></span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="copyDetail()"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 text-xs font-bold flex items-center gap-2 cursor-pointer">
                        <i class="fa-regular fa-copy text-xs"></i>
                        <span>Salin JSON</span>
                    </button>
                    <button type="button" @click="closeDetail()"
                            class="px-4 py-2 rounded-xl bg-[#1EABF1] hover:bg-[#1892ce] text-white text-xs font-black cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>
