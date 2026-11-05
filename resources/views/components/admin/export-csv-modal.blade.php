<div x-data="{
        terbuka: false,
        path: '',
        filename: 'export.csv',
        extraParams: {},
        dari: '',
        sampai: '',
        memuat: false,
        error: '',

        buka(detail = {}) {
            this.path = detail.path ?? '';
            this.filename = detail.filename ?? 'export.csv';
            this.extraParams = detail.extraParams ?? {};
            this.dari = '';
            this.sampai = '';
            this.error = '';
            this.memuat = false;
            this.terbuka = true;
        },

        tutup() {
            if (this.memuat) return;
            this.terbuka = false;
        },

        async unduh() {
            if (!this.path) return;

            if (this.dari && this.sampai && this.dari > this.sampai) {
                this.error = 'Tanggal mulai tidak boleh setelah tanggal akhir.';
                return;
            }

            this.memuat = true;
            this.error = '';

            const res = await PandansariAPI.downloadCsv(this.path, this.filename, {
                ...this.extraParams,
                start_date: this.dari || undefined,
                end_date: this.sampai || undefined,
            });

            this.memuat = false;

            if (res.ok) {
                this.terbuka = false;
                return;
            }

            this.error = res.message ?? 'Gagal mengunduh CSV.';
        },
    }"
         @buka-export-csv.window="buka($event.detail)"
         @keydown.escape.window="tutup()">

        <div x-show="terbuka" x-cloak
             class="fixed inset-0 z-[9999] bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-100 my-auto"
                 @click.outside="tutup()">

                <div class="bg-[#0A192F] text-white p-5 px-6 rounded-t-2xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 bg-sky-500/20 border border-sky-400/30 text-sky-400 rounded-xl flex items-center justify-center text-base shrink-0">
                            <i class="fa-solid fa-file-csv"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-extrabold text-white text-base leading-tight">Ekspor CSV</h3>
                            <p class="text-[11px] text-slate-300 truncate">Pilih rentang tanggal data yang diunduh.</p>
                        </div>
                    </div>
                    <button type="button" @click="tutup()" :disabled="memuat"
                            class="w-8 h-8 rounded-xl bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-700 transition flex items-center justify-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Tutup">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="space-y-1">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Dari tanggal</span>
                            <input type="date" x-model="dari"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </label>
                        <label class="space-y-1">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Sampai tanggal</span>
                            <input type="date" x-model="sampai"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        </label>
                    </div>

                    <p class="text-[10px] font-medium text-slate-400">
                        Kosongkan kedua tanggal untuk mengunduh seluruh data.
                    </p>

                    <p x-show="error" x-cloak class="text-[11px] font-bold text-rose-600" x-text="error"></p>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" @click="tutup()" :disabled="memuat"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition cursor-pointer disabled:opacity-60">
                            Batal
                        </button>
                        <button type="button" @click="unduh()" :disabled="memuat"
                                class="bg-[#0A192F] hover:bg-slate-800 text-white font-bold px-5 py-2.5 rounded-xl transition cursor-pointer disabled:opacity-60 flex items-center gap-2">
                            <i class="fa-solid fa-download text-[11px]" x-show="!memuat"></i>
                            <i class="fa-solid fa-circle-notch fa-spin" x-show="memuat"></i>
                            <span x-text="memuat ? 'Mengunduh…' : 'Unduh CSV'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
</div>
