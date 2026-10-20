<!-- MODAL POPUP: TAMBAH MAHAKARYA BARU (FULLSCREEN TELEPORT TO BODY) -->
<template x-teleport="body">
    <div x-show="showModalTambah" 
         x-cloak
         class="fixed inset-0 z-[9999] w-screen h-screen bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 overflow-y-auto" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-white w-full max-w-4xl rounded-2xl overflow-hidden shadow-2xl border border-slate-100 my-auto flex flex-col max-h-[90vh]" @click.away="showModalTambah = false">
            
            <!-- Modal Header (Dark Royal Navy) -->
            <div class="bg-[#0A192F] text-white p-5 px-6 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-sky-500/20 border border-sky-400/30 text-sky-400 rounded-xl flex items-center justify-center text-base shrink-0">
                        <i class="fa-solid fa-shirt"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-white text-base leading-tight">Tambah Mahakarya Baru</h3>
                        <p class="text-[11px] text-slate-300">Lengkapi identitas wastra, foto, dan stok awal untuk koleksi Anda.</p>
                    </div>
                </div>
                <button @click="showModalTambah = false" type="button" class="w-8 h-8 rounded-xl bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-700 transition flex items-center justify-center cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Form Body (Scrollable) — submit → POST /products (+ varian awal + gambar) -->
            <form @submit.prevent="
                tambahProduk({
                    nama_produk: $event.target.nama.value,
                    id_category: $event.target.kategori.value,
                    deskripsi: $event.target.deskripsi.value,
                    sku: $event.target.sku.value,
                    harga: $event.target.harga.value,
                    stok: $event.target.stok.value,
                    file: ($refs.foto.files || [])[0] || null
                });
            " class="p-6 overflow-y-auto space-y-6 text-xs text-slate-700 font-medium">

                <!-- Grid Main Content (2 Column Layout) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- LEFT COLUMN: Form Inputs (7 Cols) -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- 1. Identitas Mahakarya -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                                <i class="fa-solid fa-pen-nib text-sky-600"></i>
                                <span>Identitas mahakarya</span>
                            </div>

                            <!-- Nama Mahakarya -->
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Nama mahakarya <span class="text-sky-500">*</span></label>
                                <input type="text" name="nama" required class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500" placeholder="Contoh: Batik Tulis Kawung Soga">
                            </div>

                            <!-- Kategori & SKU Induk -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Kategori produk <span class="text-sky-500">*</span></label>
                                    <select name="kategori" required class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium">
                                        <option value="">Pilih kategori…</option>
                                        <template x-for="c in categories" :key="c.id_category">
                                            <option :value="c.id_category" x-text="c.nama_kategori"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">SKU induk <span class="text-sky-500">*</span></label>
                                    <input type="text" name="sku" required class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-mono" placeholder="Contoh: BP-KWG-015">
                                </div>
                            </div>

                            <!-- Bahan Kain -->
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Bahan kain <span class="text-sky-500">*</span></label>
                                <select name="bahan" class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium">
                                    <option value="">Pilih bahan</option>
                                    <option value="Sutra Alam Baron">Sutra Alam Baron</option>
                                    <option value="Katun Primisima Sanforized">Katun Primisima Sanforized</option>
                                    <option value="Sutra Crepe">Sutra Crepe</option>
                                </select>
                            </div>

                            <!-- Deskripsi & Cerita Motif -->
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Deskripsi & cerita motif</label>
                                <textarea name="deskripsi" rows="3" class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500" placeholder="Ceritakan motif, teknik pewarnaan, dan keunikan wastra ini..."></textarea>
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        <!-- 2. Harga & Stok Awal -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                                <i class="fa-solid fa-tags text-sky-600"></i>
                                <span>Harga & stok awal</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Harga jual <span class="text-sky-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">Rp</span>
                                        <input type="number" name="harga" required class="w-full border border-slate-200 rounded-xl pl-9 pr-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-bold" placeholder="0">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Stok awal <span class="text-sky-500">*</span></label>
                                    <input type="number" name="stok" required class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-bold" placeholder="Jumlah  0">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Ukuran / dimensi</label>
                                    <input type="text" name="dimensi_kain" class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500" placeholder="Contoh: 240 x 115 cm">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Satuan produk</label>
                                    <select name="satuan" class="w-full border border-slate-200 rounded-xl p-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium">
                                        <option value="Lembar">Lembar</option>
                                        <option value="Set">Set</option>
                                        <option value="Pcs">Pcs</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Info Alert Note -->
                            <div class="bg-sky-50 border border-sky-100 rounded-xl p-3 flex items-center gap-2 text-sky-800 text-[11px]">
                                <i class="fa-regular fa-circle-question text-sky-500 text-sm shrink-0"></i>
                                <span>Varian ukuran dan stok tambahan dapat diatur setelah mahakarya disimpan.</span>
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        <!-- 3. Ukuran Detail (M, L, XL, XXL Cards) -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                                <i class="fa-solid fa-ruler-combined text-sky-600"></i>
                                <span>Ukuran detail</span>
                            </div>

                            <!-- Card Ukuran M -->
                            <div class="bg-slate-50/80 border border-slate-200/80 p-4 rounded-xl space-y-3">
                                <h4 class="font-bold text-slate-800 text-xs">Ukuran M</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lingkar dada</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 52 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Panjang</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 58 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lengan</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 62 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Ukuran lainnya</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: Lingkar pinggang, lebar bahu, dll">
                                    </div>
                                </div>
                            </div>

                            <!-- Card Ukuran L -->
                            <div class="bg-slate-50/80 border border-slate-200/80 p-4 rounded-xl space-y-3">
                                <h4 class="font-bold text-slate-800 text-xs">Ukuran L</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lingkar dada</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 56 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Panjang</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 60 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lengan</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 64 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Ukuran lainnya</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: Lingkar pinggang, lebar bahu, dll">
                                    </div>
                                </div>
                            </div>

                            <!-- Card Ukuran XL -->
                            <div class="bg-slate-50/80 border border-slate-200/80 p-4 rounded-xl space-y-3">
                                <h4 class="font-bold text-slate-800 text-xs">Ukuran XL</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lingkar dada</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 104 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Panjang</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 62 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lengan</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 66 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Ukuran lainnya</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: Lingkar pinggang, lebar bahu, dll">
                                    </div>
                                </div>
                            </div>

                            <!-- Card Ukuran XXL -->
                            <div class="bg-slate-50/80 border border-slate-200/80 p-4 rounded-xl space-y-3">
                                <h4 class="font-bold text-slate-800 text-xs">Ukuran XXL</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lingkar dada</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 110 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Panjang</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 64 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Lengan</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: 68 cm">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Ukuran lainnya</label>
                                        <input type="text" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs" placeholder="Contoh: Lingkar pinggang, lebar bahu, dll">
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Foto Upload Area (5 Cols) -->
                    <div class="lg:col-span-5 space-y-3">
                        <div class="flex items-center gap-2 text-sky-900 font-extrabold text-xs">
                            <i class="fa-regular fa-images text-sky-600"></i>
                            <span>Foto mahakarya</span>
                        </div>

                        <!-- Upload Drag & Drop Box -->
                        <label class="border-2 border-dashed border-sky-200/80 rounded-2xl p-6 text-center bg-sky-50/30 hover:bg-sky-50/60 transition cursor-pointer space-y-3 block">
                            <input type="file" name="foto" x-ref="foto" accept="image/jpeg,image/png,image/webp" class="hidden"
                                   @change="namaFoto = ($refs.foto.files || [])[0]?.name ?? ''">
                            <div class="w-12 h-12 bg-white rounded-xl shadow-sm text-sky-500 flex items-center justify-center mx-auto text-xl">
                                <i class="fa-regular fa-image"></i>
                            </div>
                            <div>
                                <p class="font-bold text-slate-700 text-xs" x-text="namaFoto || 'Klik untuk pilih foto'"></p>
                                <p class="text-[11px] text-slate-400">JPG / PNG / WebP, maks. 2 MB</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 border border-sky-400/40 text-sky-700 bg-white hover:bg-sky-50 font-bold rounded-xl text-xs transition shadow-sm">
                                <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i>
                                <span x-text="namaFoto ? 'Ganti Foto' : 'Pilih Foto'"></span>
                            </span>
                        </label>

                        <p class="text-[10px] text-slate-400 leading-relaxed">
                            Foto pertama menjadi sampul produk. Foto dapat ditambahkan lagi setelah produk tersimpan.
                        </p>
                    </div>

                </div>

                <!-- Modal Footer Controls -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><span class="text-sky-500">*</span> Kolom wajib diisi</span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showModalTambah = false" class="px-5 py-2.5 border border-slate-200 hover:bg-slate-100 rounded-xl font-bold text-slate-600 transition text-xs">Batal</button>
                        <button type="submit" :disabled="savingProduk" class="px-5 py-2.5 bg-sky-400 hover:bg-sky-500 text-slate-900 font-extrabold rounded-xl transition shadow-sm text-xs flex items-center gap-2 disabled:opacity-60">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span x-show="!savingProduk">Simpan Mahakarya</span>
                            <span x-show="savingProduk"><i class="fa-solid fa-circle-notch fa-spin"></i> Menyimpan…</span>
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>
</template>