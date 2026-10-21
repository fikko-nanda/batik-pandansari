<!-- MODAL WIZARD: TAMBAH / EDIT PRODUK -->
<template x-teleport="body">
    <div x-show="showModalTambah"
         class="fixed inset-0 z-[9999] w-screen h-screen bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="closeModal()">

        <div class="bg-white w-full max-w-5xl rounded-2xl overflow-hidden shadow-2xl border border-slate-100 my-auto flex flex-col max-h-[92vh]"
             @click.away="closeModal()">

            <!-- Header -->
            <div class="bg-[#0A192F] text-white p-5 px-6 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-sky-500/20 border border-sky-400/30 text-sky-400 rounded-xl flex items-center justify-center text-base shrink-0">
                        <i class="fa-solid fa-shirt"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-white text-base leading-tight"
                            x-text="formMode === 'create' ? 'Tambah Produk Baru' : 'Edit Produk'"></h3>
                        <p class="text-[11px] text-slate-300">Lengkapi identitas, atribut, varian, dan panduan ukuran produk.</p>
                    </div>
                </div>
                <button @click="closeModal()" type="button" class="w-8 h-8 rounded-xl bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-700 transition flex items-center justify-center cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Step navigation -->
            <div class="bg-slate-50 border-b border-slate-200 px-6 py-2 flex items-center gap-1 shrink-0 overflow-x-auto">
                <button type="button" @click="formStep = 'identitas'"
                        :class="formStep === 'identitas' ? 'bg-white text-[#0A192F] shadow-sm' : 'text-slate-600 hover:bg-white'"
                        class="px-4 py-2 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2 shrink-0">
                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px]">A</span>
                    <span>Identitas</span>
                </button>
                <button type="button" @click="formStep = 'atribut'"
                        :class="formStep === 'atribut' ? 'bg-white text-[#0A192F] shadow-sm' : 'text-slate-600 hover:bg-white'"
                        class="px-4 py-2 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2 shrink-0">
                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px]">B</span>
                    <span>Atribut</span>
                </button>
                <button type="button" @click="formStep = 'varian'"
                        :class="formStep === 'varian' ? 'bg-white text-[#0A192F] shadow-sm' : 'text-slate-600 hover:bg-white'"
                        class="px-4 py-2 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2 shrink-0">
                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px]">C</span>
                    <span>Varian</span>
                    <span class="bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full text-[10px]" x-text="variantRows.length"></span>
                </button>
                <button type="button" @click="formStep = 'panduan'"
                        :class="formStep === 'panduan' ? 'bg-white text-[#0A192F] shadow-sm' : 'text-slate-600 hover:bg-white'"
                        class="px-4 py-2 rounded-xl font-bold text-xs transition cursor-pointer flex items-center gap-2 shrink-0">
                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px]">D</span>
                    <span>Panduan Ukuran</span>
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="saveProduct()" novalidate class="flex-1 overflow-y-auto p-6 space-y-6 text-xs text-slate-700 font-medium">

                <!-- ================= A. IDENTITAS ================= -->
                <div x-show="formStep === 'identitas'" class="space-y-6">
                    <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                        <i class="fa-solid fa-pen-nib text-sky-600"></i>
                        <span>Identitas Produk</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <div class="lg:col-span-7 space-y-4">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Nama Produk <span class="text-sky-500">*</span></label>
                                <input type="text" x-model="form.nama_produk" required maxlength="150"
                                       class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                                       placeholder="Contoh: Batik Tulis Kawung Soga">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">SKU Produk <span class="text-sky-500">*</span></label>
                                    <input type="text" x-model="form.sku" required maxlength="100"
                                           @input="form.sku = form.sku.toUpperCase()"
                                           class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono uppercase"
                                           placeholder="Contoh: BP-KWG-015">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Kategori <span class="text-sky-500">*</span></label>
                                    <select x-model="form.id_category" required
                                            class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium">
                                        <option value="">Pilih kategori...</option>
                                        <template x-for="c in categories" :key="c.id_category">
                                            <option :value="c.id_category" x-text="c.nama_kategori"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Berat Produk (gram) <span class="text-sky-500">*</span></label>
                                <input type="number" x-model.number="form.berat_produk" required min="1" max="1000000" step="1"
                                       class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-bold"
                                       placeholder="Contoh: 500">
                                <p class="text-[10px] text-slate-400 mt-1">Berat ini otomatis dipakai sebagai berat setiap varian untuk perhitungan ongkir.</p>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Deskripsi Produk</label>
                                <textarea x-model="form.deskripsi" rows="4"
                                          class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                                          placeholder="Ceritakan motif, teknik pewarnaan, dan keunikan wastra ini..."></textarea>
                            </div>
                        </div>

                        <div class="lg:col-span-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                                    <i class="fa-regular fa-images text-sky-600"></i>
                                    <span>Foto Produk</span>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400">
                                    <span x-text="photoTotal()"></span>/<span x-text="photoMax"></span>
                                </span>
                            </div>

                            <label class="border-2 border-dashed border-sky-200/80 rounded-2xl p-6 text-center bg-sky-50/30 hover:bg-sky-50/60 transition cursor-pointer space-y-3 block">
                                <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden"
                                       @change="addPhotos($event)">
                                <div class="w-12 h-12 bg-white rounded-xl shadow-sm text-sky-500 flex items-center justify-center mx-auto text-xl">
                                    <i class="fa-regular fa-image"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-700 text-xs">Klik untuk pilih foto</p>
                                    <p class="text-[11px] text-slate-400">JPG / PNG / WebP, maks. 2 MB per file. Maksimal <span x-text="photoMax"></span> foto.</p>
                                </div>
                            </label>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" x-show="existingPhotos.length || newPhotos.length">
                                <template x-for="(foto, idx) in existingPhotos" :key="foto.id_image ?? ('old-' + idx)">
                                    <div class="relative group bg-white border border-slate-200 rounded-xl overflow-hidden">
                                        <img :src="foto.url" class="w-full h-24 object-cover" :alt="foto.alt_text ?? 'Foto produk'">
                                        <span x-show="foto.is_primary" class="absolute top-1 left-1 bg-amber-400 text-slate-900 text-[9px] font-extrabold px-2 py-0.5 rounded-full">COVER</span>
                                        <button type="button" @click="removeExistingPhoto(idx)"
                                                class="absolute top-1 right-1 w-7 h-7 rounded-lg bg-white/95 text-rose-600 hover:bg-rose-50 transition flex items-center justify-center"
                                                title="Hapus foto">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                        <div class="bg-slate-900/70 text-white text-[9px] px-2 py-1">Foto tersimpan</div>
                                    </div>
                                </template>

                                <template x-for="(foto, idx) in newPhotos" :key="foto.key">
                                    <div class="relative group bg-white border border-sky-200 rounded-xl overflow-hidden">
                                        <img :src="foto.url" class="w-full h-24 object-cover" alt="Foto baru">
                                        <span x-show="foto.is_primary" class="absolute top-1 left-1 bg-amber-400 text-slate-900 text-[9px] font-extrabold px-2 py-0.5 rounded-full">COVER</span>
                                        <button type="button" @click="setNewCover(idx)"
                                                class="absolute top-1 right-9 w-7 h-7 rounded-lg bg-white/95 text-amber-600 hover:bg-amber-50 transition flex items-center justify-center"
                                                title="Jadikan cover">
                                            <i class="fa-solid fa-star text-[10px]"></i>
                                        </button>
                                        <button type="button" @click="removeNewPhoto(idx)"
                                                class="absolute top-1 right-1 w-7 h-7 rounded-lg bg-white/95 text-rose-600 hover:bg-rose-50 transition flex items-center justify-center"
                                                title="Hapus foto">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                        <div class="bg-sky-700/80 text-white text-[9px] px-2 py-1">Foto baru</div>
                                    </div>
                                </template>
                            </div>

                            <div class="bg-sky-50 border border-sky-100 rounded-xl p-3 flex items-start gap-2 text-sky-800 text-[11px]">
                                <i class="fa-regular fa-circle-question text-sky-500 text-sm shrink-0 mt-0.5"></i>
                                <span>Foto pertama otomatis menjadi cover. Untuk mengubah cover, tandai salah satu foto baru sebagai cover sebelum menyimpan.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= B. ATRIBUT ================= -->
                <div x-show="formStep === 'atribut'" class="space-y-5">
                    <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                        <i class="fa-solid fa-tags text-sky-600"></i>
                        <span>Atribut Produk</span>
                    </div>

                    <div class="max-w-xl">
                        <label class="block font-bold text-slate-700 mb-1">Bahan Kain</label>
                        <input type="text" x-model="form.bahan_kain" maxlength="100"
                               class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                               placeholder="Contoh: Katun Primisima Sanforized">
                        <p class="text-[10px] text-slate-400 mt-1">Opsional, maksimal 100 karakter.</p>
                    </div>
                </div>

                <!-- ================= C. VARIAN ================= -->
                <div x-show="formStep === 'varian'" class="space-y-6">
                    <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                        <i class="fa-solid fa-boxes-packing text-sky-600"></i>
                        <span>Varian Ukuran & Warna</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <label class="block font-bold text-slate-700">Ukuran <span class="text-sky-500">*</span></label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="s in sizeOptions" :key="s">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" :value="s" x-model="sizesSelected" class="hidden peer">
                                        <span class="inline-flex items-center justify-center min-w-[48px] px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 peer-checked:bg-sky-400 peer-checked:border-sky-400 peer-checked:text-slate-900 transition"
                                              x-text="s"></span>
                                    </label>
                                </template>
                            </div>
                            <p class="text-[10px] text-slate-400">Wajib pilih minimal satu ukuran.</p>
                        </div>

                        <div class="space-y-3">
                            <label class="block font-bold text-slate-700">Warna <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="c in colorOptions" :key="c">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" :value="c" x-model="colorsSelected" class="hidden peer">
                                        <span class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 peer-checked:bg-sky-400 peer-checked:border-sky-400 peer-checked:text-slate-900 transition"
                                              x-text="c"></span>
                                    </label>
                                </template>
                            </div>
                            <p class="text-[10px] text-slate-400">Jika warna tidak dipilih, varian hanya dibuat per ukuran.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="generateVariants()"
                                class="bg-[#0A192F] hover:bg-slate-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>Buat Kombinasi Varian</span>
                        </button>
                        <span class="text-[10px] text-slate-400 font-bold">
                            <span x-text="variantRows.length"></span> kombinasi
                        </span>
                    </div>

                    <div x-show="variantRows.length" class="space-y-3">
                        <div class="flex flex-wrap items-end gap-2 bg-slate-50 border border-slate-200 rounded-xl p-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Harga semua varian</label>
                                <input type="number" x-model.number="bulkHarga" min="0" step="1"
                                       class="w-36 border border-slate-200 rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                                       placeholder="0">
                            </div>
                            <button type="button" @click="applyAllHarga()"
                                    class="px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg text-[10px] font-bold text-slate-700 transition cursor-pointer">
                                Terapkan Harga
                            </button>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Stok semua varian</label>
                                <input type="number" x-model.number="bulkStok" min="0" step="1"
                                       class="w-32 border border-slate-200 rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500"
                                       placeholder="0">
                            </div>
                            <button type="button" @click="applyAllStok()"
                                    class="px-3 py-2 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg text-[10px] font-bold text-slate-700 transition cursor-pointer">
                                Terapkan Stok
                            </button>
                        </div>

                        <div class="overflow-x-auto border border-slate-200 rounded-xl">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-sky-50/70 text-slate-700 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                                        <th class="py-3 px-4">Varian</th>
                                        <th class="py-3 px-4">SKU</th>
                                        <th class="py-3 px-4">Harga</th>
                                        <th class="py-3 px-4">Stok</th>
                                        <th class="py-3 px-4">Berat (gram)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <template x-for="(row, idx) in variantRows" :key="row.key">
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-2.5 px-4">
                                                <strong class="text-slate-800" x-text="row.nama_varian"></strong>
                                                <div class="text-[10px] text-slate-400">
                                                    <span x-text="row.ukuran"></span>
                                                    <span x-show="row.warna" x-text="' / ' + row.warna"></span>
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-4">
                                                <input type="text" x-model="row.sku" maxlength="100"
                                                       @input="row.sku = row.sku.toUpperCase()"
                                                       class="w-44 border border-slate-200 rounded-lg p-2 text-[10px] font-mono focus:outline-none focus:ring-2 focus:ring-sky-500">
                                            </td>
                                            <td class="py-2.5 px-4">
                                                <div class="relative w-32">
                                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] font-bold">Rp</span>
                                                    <input type="number" x-model.number="row.harga" min="0" step="1"
                                                           class="w-full border border-slate-200 rounded-lg pl-8 pr-2 py-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-sky-500"
                                                           placeholder="0">
                                                </div>
                                            </td>
                                            <td class="py-2.5 px-4">
                                                <input type="number" x-model.number="row.stok" min="0" step="1"
                                                       class="w-24 border border-slate-200 rounded-lg p-2 text-[10px] font-bold focus:outline-none focus:ring-2 focus:ring-sky-500"
                                                       placeholder="0">
                                            </td>
                                            <td class="py-2.5 px-4">
                                                <input type="number" :value="Number(form.berat_produk) || 500" disabled
                                                       class="w-24 border border-slate-100 bg-slate-50 rounded-lg p-2 text-[10px] text-slate-500">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-[10px] text-slate-400">
                            SKU varian dibuat otomatis dari SKU produk + ukuran + warna. Kamu tetap bisa mengubahnya manual.
                        </p>
                    </div>

                    <div x-show="!variantRows.length" class="bg-slate-50 border border-dashed border-slate-200 rounded-xl p-6 text-center text-xs text-slate-400 font-semibold">
                        Pilih ukuran (dan warna bila perlu), lalu klik "Buat Kombinasi Varian".
                    </div>
                </div>

                <!-- ================= D. PANDUAN UKURAN ================= -->
                <div x-show="formStep === 'panduan'" class="space-y-5">
                    <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                        <i class="fa-solid fa-ruler-combined text-sky-600"></i>
                        <span>Panduan Ukuran (Size Chart)</span>
                    </div>

                    <div class="max-w-xl space-y-3">
                        <label class="border-2 border-dashed border-sky-200/80 rounded-2xl p-6 text-center bg-sky-50/30 hover:bg-sky-50/60 transition cursor-pointer space-y-3 block">
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                                   @change="pickSizeChart($event)">
                            <div class="w-12 h-12 bg-white rounded-xl shadow-sm text-sky-500 flex items-center justify-center mx-auto text-xl">
                                <i class="fa-solid fa-ruler-combined"></i>
                            </div>
                            <div>
                                <p class="font-bold text-slate-700 text-xs">Klik untuk pilih 1 foto size chart</p>
                                <p class="text-[11px] text-slate-400">JPG / PNG / WebP, maks. 2 MB.</p>
                            </div>
                        </label>

                        <div x-show="newSizeChart || (existingSizeChart && !deleteSizeChart)" class="relative bg-white border border-slate-200 rounded-xl overflow-hidden">
                            <img :src="newSizeChart?.url ?? existingSizeChart?.url" class="w-full max-h-72 object-contain bg-slate-50" alt="Panduan ukuran">
                            <button type="button" @click="removeSizeChart()"
                                    class="absolute top-2 right-2 w-8 h-8 rounded-lg bg-white/95 text-rose-600 hover:bg-rose-50 transition flex items-center justify-center"
                                    title="Hapus size chart">
                                <i class="fa-solid fa-trash text-xs"></i>
                            </button>
                        </div>

                        <p class="text-[10px] text-slate-400">Size chart ini khusus untuk produk ini dan akan tampil di halaman detail produk.</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <span class="text-[11px] text-slate-400">
                        <span x-show="savingStep" x-text="savingStep"></span>
                        <span x-show="!savingStep">Kolom bertanda <span class="text-sky-500">*</span> wajib diisi.</span>
                    </span>

                    <div class="flex items-center gap-2 self-end">
                        <button type="button" @click="closeModal()"
                                class="px-5 py-2.5 border border-slate-200 hover:bg-slate-100 rounded-xl font-bold text-slate-600 transition text-xs">
                            Batal
                        </button>
                        <button type="button" x-show="formStep !== 'identitas'" @click="prevStep()"
                                class="px-5 py-2.5 border border-slate-200 hover:bg-slate-100 rounded-xl font-bold text-slate-600 transition text-xs flex items-center gap-2">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>Kembali</span>
                        </button>
                        <button type="button" x-show="formStep !== 'panduan'" @click="nextStep()"
                                class="px-5 py-2.5 bg-[#0A192F] hover:bg-slate-800 text-white font-bold rounded-xl transition text-xs flex items-center gap-2">
                            <span>Lanjut</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                        <button type="submit" x-show="formStep === 'panduan'" :disabled="savingProduk"
                                class="px-5 py-2.5 bg-sky-400 hover:bg-sky-500 text-slate-900 font-extrabold rounded-xl transition shadow-sm text-xs flex items-center gap-2 disabled:opacity-60">
                            <i class="fa-solid" :class="savingProduk ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                            <span x-text="savingProduk ? 'Menyimpan...' : 'Simpan & Aktifkan'"></span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</template>
