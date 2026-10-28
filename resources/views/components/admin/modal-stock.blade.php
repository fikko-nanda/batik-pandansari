<template x-teleport="body">
    <div x-show="stockModalOpen" x-cloak
         class="fixed inset-0 z-[9999] w-screen h-screen bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="closeStockModal()">

        <div class="bg-white w-full max-w-5xl rounded-2xl overflow-hidden shadow-2xl border border-slate-100 my-auto flex flex-col max-h-[92vh]"
             @click.away="closeStockModal()">

            <!-- Header -->
            <div class="bg-[#0A192F] text-white p-5 px-6 flex items-center justify-between gap-4 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 bg-sky-500/20 border border-sky-400/30 text-sky-400 rounded-xl flex items-center justify-center text-base shrink-0">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-white text-base leading-tight">Atur Stok Varian</h3>
                        <p class="text-[11px] text-slate-300 truncate" x-text="stockProduct?.nama_produk ?? 'Pilih produk'"></p>
                    </div>
                </div>
                <button type="button" @click="closeStockModal()" :disabled="savingStock"
                        class="w-8 h-8 rounded-xl bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-700 transition flex items-center justify-center cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Tutup">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="overflow-y-auto p-4 sm:p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                    <!-- Daftar varian -->
                    <section class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 space-y-4">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
                            <img :src="stockProduct ? coverOf(stockProduct) : ''" alt=""
                                 class="w-12 h-12 object-cover rounded-xl border border-slate-200 bg-slate-100 shrink-0"
                                 onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                            <div class="min-w-0">
                                <strong class="block text-sm font-extrabold text-slate-900 truncate" x-text="stockProduct?.nama_produk ?? '-'"></strong>
                                <span class="block text-[10px] text-slate-500 font-medium" x-text="stockProduct?.category?.nama_kategori ?? 'Tanpa kategori'"></span>
                                <span class="block text-[10px] text-slate-400" x-text="activeVariants(stockProduct).length + ' varian aktif'"></span>
                            </div>
                            <span class="ml-auto shrink-0 rounded-xl border border-amber-200 bg-amber-100/80 px-3 py-1.5 text-[11px] font-extrabold text-amber-900"
                                  x-text="totalStok(stockProduct) + ' unit'"></span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">Daftar Varian</h4>
                                <p class="text-[10px] text-slate-500 font-medium">Pilih varian untuk mengatur stoknya.</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <template x-for="v in activeVariants(stockProduct)" :key="v.id_variant">
                                <button type="button" @click="selectStockVariant(v)" :disabled="savingStock"
                                        class="w-full text-left rounded-xl border p-3 transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-3"
                                        :class="String(stockSelectedId) === String(v.id_variant) ? 'border-sky-400 bg-white ring-2 ring-sky-100' : 'border-slate-200 bg-white/80 hover:border-sky-300 hover:bg-white'">
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                          :class="String(stockSelectedId) === String(v.id_variant) ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-500'">
                                        <i class="fa-solid fa-shirt text-xs"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-xs font-extrabold text-slate-900 truncate" x-text="variantLabel(v)"></span>
                                        <span class="block text-[10px] font-mono text-slate-400 truncate" x-text="v.sku || 'Tanpa SKU'"></span>
                                    </span>
                                    <span class="text-right shrink-0">
                                        <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Stok</span>
                                        <span class="block text-sm font-black"
                                              :class="Number(v.stok ?? 0) <= 0 ? 'text-rose-600' : (Number(v.stok ?? 0) <= 3 ? 'text-amber-600' : 'text-emerald-700')"
                                              x-text="num(v.stok)"></span>
                                        <span class="inline-block mt-1 rounded-full border px-1.5 py-0.5 text-[9px] font-bold"
                                              :class="stockLevelInfo(v.stok).style"
                                              x-text="stockLevelInfo(v.stok).label"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </section>

                    <!-- Form stok -->
                    <section class="rounded-2xl border border-sky-200 bg-sky-50/50 p-4 sm:p-5">
                        <template x-if="selectedStockVariant()">
                            <div class="space-y-5">
                                <div class="flex items-start justify-between gap-3 border-b border-sky-100 pb-3">
                                    <div class="min-w-0">
                                        <span class="block text-[9px] font-black uppercase tracking-widest text-sky-600">Varian terpilih</span>
                                        <h4 class="text-sm font-extrabold text-slate-900 truncate" x-text="variantLabel(selectedStockVariant())"></h4>
                                        <p class="text-[10px] font-mono text-slate-400 truncate" x-text="selectedStockVariant()?.sku || 'Tanpa SKU'"></p>
                                    </div>
                                    <span class="shrink-0 rounded-full border px-2 py-1 text-[9px] font-bold"
                                          :class="stockLevelInfo(selectedStockVariant()?.stok).style"
                                          x-text="stockLevelInfo(selectedStockVariant()?.stok).label"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Stok saat ini</label>
                                        <div class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-2xl font-black text-slate-800"
                                             x-text="num(selectedStockVariant()?.stok)"></div>
                                    </div>
                                    <div>
                                        <label for="stock-draft" class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Stok baru</label>
                                        <input id="stock-draft" type="number" min="0" step="1" inputmode="numeric"
                                               x-model.number="stockDraft"
                                               @input="clearStockFeedback()"
                                               @keydown.enter.prevent="saveStockVariant()"
                                               :disabled="savingStock"
                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-2xl font-black text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400 disabled:opacity-60">
                                    </div>
                                </div>

                                <p class="text-[10px] font-semibold text-slate-500">
                                    Isi jumlah stok akhir. Angka harus bulat dan tidak boleh negatif.
                                </p>

                                <div class="rounded-xl border border-sky-100 bg-white p-3 space-y-2 text-xs">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-medium text-slate-500">Total varian ini</span>
                                        <strong class="font-extrabold text-slate-900"
                                                x-text="stockDraftValid() ? num(stockDraftValue()) + ' unit' : '—'"></strong>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-medium text-slate-500">Total produk setelah simpan</span>
                                        <strong class="font-extrabold text-slate-900" x-text="num(stockDraftPreviewTotal()) + ' unit'"></strong>
                                    </div>
                                    <div x-show="stockDraftValid() && stockDraftDiff() !== 0" class="flex items-center justify-between gap-3 border-t border-slate-100 pt-2">
                                        <span class="font-medium text-slate-500">Perubahan</span>
                                        <strong class="font-extrabold"
                                                :class="stockDraftDiff() > 0 ? 'text-emerald-700' : 'text-rose-700'"
                                                x-text="(stockDraftDiff() > 0 ? '+' : '') + num(stockDraftDiff()) + ' unit'"></strong>
                                    </div>
                                </div>

                                <div x-show="stockError || (stockDraft !== '' && !stockDraftValid())"
                                     class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] font-bold text-rose-700"
                                     x-text="stockError || 'Stok baru harus berupa angka bulat minimal 0.'"></div>

                                <div x-show="stockSuccess"
                                     class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700"
                                     x-text="stockSuccess"></div>

                                <p x-show="stockDraftValid() && stockDraftDiff() === 0"
                                   class="text-[10px] font-semibold text-slate-400">
                                    Stok belum berubah.
                                </p>

                                <div class="flex items-center gap-2 pt-1">
                                    <button type="button" @click="closeStockModal()" :disabled="savingStock"
                                            class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                                        Tutup
                                    </button>
                                    <button type="button" @click="saveStockVariant()" :disabled="!canSaveStock()"
                                            class="flex-1 rounded-xl bg-sky-400 px-4 py-2.5 text-xs font-extrabold text-slate-900 hover:bg-sky-500 transition shadow-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fa-solid" :class="savingStock ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                                        <span x-text="savingStock ? 'Menyimpan...' : 'Simpan Stok'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-if="!selectedStockVariant()">
                            <div class="rounded-xl border border-dashed border-sky-200 bg-white/60 p-6 text-center text-xs font-semibold text-slate-400">
                                Pilih varian di sebelah kiri untuk mengatur stok.
                            </div>
                        </template>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
