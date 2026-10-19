@extends('layouts.admin')

@section('content')

<div x-data="{
    selectedCategory: 'ALL',
    searchQuery: '',
    showModalTambah: false,

    // Data Produk Master (Dummy)
    products: [
        { id: 1, sku: '#BP-PRG-01', name: 'Kemeja Batik Parang Kencana Silk', category: 'SUTRA', catGroup: 'TULIS', fabric: 'Sutra Alam Baron • Pewarna Alami Tinggi', badge: '✓ EDISI TERBATAS MAHAKARYA', stock: 14, unit: 'lembar', price: 1850000, priceNote: 'Per Lembar Sutra', status: 'AKTIF', img: '{{ asset('images/m1.png') }}' },
        { id: 2, sku: '#BP-KWG-04', name: 'Kawung Gold Prada Halus', category: 'KAIN', catGroup: 'TULIS', fabric: 'Katun Primisima Sanforized • Prada Emas Asli', badge: '★ TERSISA 2 LEMBAR', stock: 2, unit: 'lembar', price: 2400000, priceNote: 'Sertifikat Canting #77', status: 'AKTIF', img: '{{ asset('images/tulis.jpg') }}' },
        { id: 3, sku: '#BP-MGM-09', name: 'Megamendung Indigo Blue Pesisir', category: 'DRESS', catGroup: 'CAP', fabric: 'Katun Primis Berkolor Tarum Alami • Cap Tempo Doeloe', badge: 'STOK STABIL', stock: 28, unit: 'lembar', price: 1250000, priceNote: 'Kategori Pakaian', status: 'AKTIF', img: '{{ asset('images/cap.jpg') }}' },
        { id: 4, sku: '#BP-SKJ-02', name: 'Sarimbit Pasangan Sekar Jagad Madura', category: 'SARIMBIT', catGroup: 'SARIMBIT', fabric: 'Set Kain & Selendang Sutra Crepe • Canting Tulis Halus', badge: '★ TERSISA 1 PASANG', stock: 1, unit: 'Set', price: 3750000, priceNote: 'Sepasang Pria & Wanita', status: 'AKTIF', img: '{{ asset('images/m2.png') }}' }
    ],

    // Data Varian Ukuran & Stok Stepper
    variants: [
        { id: 1, master: '#BP-PRG-01 Parang Kencana Silk', sentra: 'Sentra: Solo Mangkunegaran', size: 'S', dimension: 'LD: 100 cm • Plg: 72 cm', qty: 8 },
        { id: 2, master: '#BP-PRG-01 Parang Kencana Silk', sentra: 'Sentra: Solo Mangkunegaran', size: 'M', dimension: 'LD: 104 cm • Plg: 73 cm', qty: 4 },
        { id: 3, master: '#BP-PRG-01 Parang Kencana Silk', sentra: 'Sentra: Solo Mangkunegaran', size: 'L', dimension: 'LD: 108 cm • Plg: 74 cm', qty: 1 },
        { id: 4, master: '#BP-PRG-01 Parang Kencana Silk', sentra: 'Sentra: Solo Mangkunegaran', size: 'XL', dimension: 'LD: 112 cm • Plg: 76 cm', qty: 0 },
        { id: 5, master: '#BP-PRG-01 Parang Kencana Silk', sentra: 'Sentra: Solo Mangkunegaran', size: 'XXL', dimension: 'LD: 118 cm • Plg: 78 cm', qty: 1 },
        { id: 6, master: '#BP-KWG-04 Kawung Gold Prada Halus', sentra: 'Sentra: Cirebon Ngayogyakarta', size: 'M', dimension: 'LD: 104 cm • Plg: 73 cm', qty: 2 }
    ],

    // Filter Produk berdasarkan Kategori & Input Search
    get filteredProducts() {
        return this.products.filter(p => {
            let matchCategory = this.selectedCategory === 'ALL' || p.catGroup === this.selectedCategory;
            let matchSearch = p.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || p.sku.toLowerCase().includes(this.searchQuery.toLowerCase());
            return matchCategory && matchSearch;
        });
    },

    // Aksi Edit Harga
    editHarga(product) {
        let hargaBaru = prompt(`Masukkan harga baru untuk ${product.name}:`, product.price);
        if (hargaBaru !== null && !isNaN(hargaBaru) && hargaBaru > 0) {
            product.price = parseInt(hargaBaru);
            alert('Harga produk berhasil diperbarui!');
        }
    },

    // Aksi Hapus Stok
    hapusStok(product) {
        if (confirm(`Apakah Anda yakin ingin mengosongkan stok untuk ${product.name}?`)) {
            product.stock = 0;
            product.badge = 'STOK HABIS';
        }
    }
}" class="space-y-6">

    <!-- 1. HEADER BANNER WITH WAYANG & KATEGORI LENGKAP -->
    <div class="bg-[#0A192F] text-white p-6 sm:p-8 rounded-2xl relative overflow-hidden shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-4 max-w-2xl relative z-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                Katalog Mahakarya & Kurasi Varian Wastra
            </h1>
            <p class="text-xs text-slate-300 leading-relaxed font-normal">
                Pengelolaan silsilah canting, stok fisik per lembar kain, ketersediaan ukuran primisima, serta penyesuaian sertifikat orisinalitas wastra.
            </p>

            <!-- Search Bar Input & Download Icon (Biru Muda Pastel) -->
            <div class="flex items-center gap-2.5 pt-1">
                <div class="relative w-full max-w-md">
                    <i class="fa-solid fa-sliders absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-600 text-xs"></i>
                    <input type="text" 
                           x-model="searchQuery"
                           placeholder="Saring kode canting / nama..." 
                           class="w-full text-xs font-semibold bg-[#AEE2F0] text-slate-900 placeholder-slate-600 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-sky-400 border-none">
                </div>
                <button type="button" class="bg-[#AEE2F0] hover:bg-[#97d7e8] text-slate-900 p-2.5 px-3 rounded-xl transition cursor-pointer shrink-0 font-bold" title="Unduh Katalog">
                    <i class="fa-solid fa-download text-xs"></i>
                </button>
            </div>

            <!-- Badges Kategori Lengkap (Biru Muda Pastel) -->
            <div class="flex flex-wrap gap-2 pt-1 text-[10px] font-extrabold">
                <button @click="selectedCategory = 'ALL'" 
                        :class="selectedCategory === 'ALL' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    SEMUA BATIK
                </button>

                <button @click="selectedCategory = 'TULIS'" 
                        :class="selectedCategory === 'TULIS' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    BATIK TULIS
                </button>

                <button @click="selectedCategory = 'CAP'" 
                        :class="selectedCategory === 'CAP' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    BATIK CAP
                </button>

                <button @click="selectedCategory = 'HANDPRINT'" 
                        :class="selectedCategory === 'HANDPRINT' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    BAHAN HANDPRINT
                </button>

                <button @click="selectedCategory = 'WANITA'" 
                        :class="selectedCategory === 'WANITA' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    PAKAIAN WANITA
                </button>

                <button @click="selectedCategory = 'PRIA'" 
                        :class="selectedCategory === 'PRIA' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    PAKAIAN PRIA
                </button>

                <button @click="selectedCategory = 'ANAK'" 
                        :class="selectedCategory === 'ANAK' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider text-center leading-none">
                    PAKAIAN ANAK-ANAK
                </button>

                <button @click="selectedCategory = 'SARIMBIT'" 
                        :class="selectedCategory === 'SARIMBIT' ? 'bg-[#73b9cb] text-slate-900 shadow-inner' : 'bg-[#AEE2F0] text-slate-900 hover:bg-[#90d2e3]'" 
                        class="px-3.5 py-1.5 rounded-xl transition cursor-pointer uppercase tracking-wider">
                    SARIMBIT
                </button>
            </div>
        </div>

        <!-- Gambar Wayang Blend Menyatu di Sisi Kanan -->
        <div class="absolute right-0 bottom-0 top-0 z-0 opacity-25 pointer-events-none md:opacity-40 flex items-end justify-end">
            <img src="{{ asset('images/wayang.png') }}" alt="Wayang Wastra" class="h-full w-auto object-contain mix-blend-luminosity filter brightness-150">
        </div>
    </div>

    <!-- 2. STATS SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-sky-100/60 border border-sky-200/80 p-5 rounded-2xl flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider block mb-1">ARSIP KURASI AKTIF</span>
                <div class="text-2xl font-extrabold text-slate-800"><span x-text="products.length"></span> <span class="text-xs font-semibold text-slate-500">Container</span></div>
            </div>
            <div class="w-10 h-10 bg-white text-sky-600 rounded-xl flex items-center justify-center text-base shadow-sm">
                <i class="fa-solid fa-box-archive"></i>
            </div>
        </div>

        <div class="bg-slate-900 text-white p-5 rounded-2xl flex items-center justify-between border border-slate-800">
            <div>
                <span class="text-[9px] font-bold text-rose-400 uppercase tracking-wider block mb-0.5">PERHATIAN MENDESAK</span>
                <div class="text-2xl font-extrabold text-white">5 SKU <span class="text-xs font-semibold text-rose-300">Menipis</span></div>
                <p class="text-[9px] text-slate-400 mt-0.5">Perlu restock pewarnaan soga alami</p>
            </div>
            <div class="w-10 h-10 bg-rose-500/20 text-rose-400 rounded-xl flex items-center justify-center text-base border border-rose-500/30">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="bg-sky-100/60 border border-sky-200/80 p-5 rounded-2xl flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider block mb-1">VARIASI GALERI TOTAL</span>
                <div class="text-2xl font-extrabold text-slate-800">Rp 842.500.000</div>
            </div>
            <div class="w-10 h-10 bg-white text-sky-600 rounded-xl flex items-center justify-center text-base shadow-sm">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- 3. ACTION BAR & ARSIP MASTER -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <!-- Tombol Tambah Produk Interaktif -->
            <button type="button" @click="showModalTambah = true" class="bg-sky-400 hover:bg-sky-500 text-slate-900 font-extrabold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 self-start shadow-sm cursor-pointer">
                <i class="fa-solid fa-circle-plus"></i>
                <span>TAMBAH MAHAKARYA BARU</span>
            </button>

            <div class="flex items-center gap-3 self-end">
                <span class="text-xs text-slate-500 font-bold">Arsip Master</span>
                <span class="text-[10px] text-slate-400">Menampilkan <strong x-text="filteredProducts.length"></strong> karya terpilih</span>
            </div>
        </div>

        <!-- TABEL ARSIP MASTER PRODUK -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-sky-50/60 text-slate-700 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-4">WASTRA & CANTING</th>
                            <th class="py-3 px-4 text-center">SKU INDUK</th>
                            <th class="py-3 px-4 text-center">STOK TOTAL</th>
                            <th class="py-3 px-4">NILAI MAHAKARYA</th>
                            <th class="py-3 px-4 text-center">VISIBILITAS TOKO</th>
                            <th class="py-3 px-4 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <template x-for="p in filteredProducts" :key="p.id">
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <img :src="p.img" class="w-12 h-12 object-cover rounded-xl border border-slate-200 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-2">
                                                <span class="bg-[#0A192F] text-white text-[9px] font-bold px-1.5 py-0.5 rounded" x-text="p.category"></span>
                                                <strong class="text-slate-800 text-sm" x-text="p.name"></strong>
                                            </div>
                                            <p class="text-[10px] text-slate-400" x-text="p.fabric"></p>
                                            <span class="text-[9px] font-bold block" :class="p.stock > 0 ? 'text-sky-600' : 'text-rose-600'" x-text="p.badge"></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center font-mono text-[10px] text-slate-500 font-bold" x-text="p.sku"></td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-extrabold text-xs px-2.5 py-1 rounded-lg inline-block" :class="p.stock <= 2 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-800'" x-text="p.stock + ' ' + p.unit"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <strong class="text-slate-800 block" x-text="'Rp ' + p.price.toLocaleString('id-ID')"></strong>
                                    <span class="text-[10px] text-slate-400 block" x-text="p.priceNote"></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="bg-sky-700 text-white text-[10px] font-bold px-3 py-1 rounded-full inline-block" x-text="p.status"></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <button @click="editHarga(p)" class="bg-sky-100 hover:bg-sky-200 text-sky-800 text-[10px] font-bold px-2.5 py-1 rounded-lg w-20 transition cursor-pointer">Edit Harga</button>
                                        <button @click="hapusStok(p)" class="bg-rose-100 hover:bg-rose-200 text-rose-700 text-[10px] font-bold px-2.5 py-1 rounded-lg w-20 transition cursor-pointer">Hapus Stok</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. PUSAT KENDALI VARIAN UKURAN & SALDO STOK -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6">
        <div class="bg-[#0A192F] text-white p-4 rounded-xl flex items-center gap-3">
            <div class="w-8 h-8 bg-sky-500 text-slate-900 rounded-lg flex items-center justify-center text-sm font-bold shrink-0">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold">Pusat Kendali Varian Ukuran & Saldo Stok</h3>
                <p class="text-[11px] text-slate-300">Gunakan stepper kuantitas untuk memperbarui stok fisik canting secara langsung.</p>
            </div>
        </div>

        <!-- Varian Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">MASTER KAIN</th>
                        <th class="py-3 px-4 text-center">UKURAN</th>
                        <th class="py-3 px-4">DIMENSI / LINGKAR DADA</th>
                        <th class="py-3 px-4 text-center">PENYESUAIAN FISIK (STEPPER)</th>
                        <th class="py-3 px-4">INDIKATOR KONDISI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <template x-for="v in variants" :key="v.id">
                        <tr>
                            <td class="py-3.5 px-4">
                                <strong class="text-slate-800 block" x-text="v.master"></strong>
                                <span class="text-[10px] text-slate-400" x-text="v.sentra"></span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 font-extrabold flex items-center justify-center mx-auto text-slate-800" x-text="v.size"></span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-medium" x-text="v.dimension"></td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="if(v.qty > 0) v.qty--" class="w-6 h-6 bg-slate-100 rounded text-slate-600 font-bold hover:bg-slate-200 cursor-pointer">-</button>
                                    <span class="font-extrabold text-sm w-6 text-center" :class="v.qty === 0 ? 'text-rose-600' : 'text-slate-800'" x-text="v.qty"></span>
                                    <button @click="v.qty++" class="w-6 h-6 bg-slate-100 rounded text-slate-600 font-bold hover:bg-slate-200 cursor-pointer">+</button>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <template x-if="v.qty >= 3">
                                    <span class="bg-emerald-100 text-emerald-800 font-bold text-[10px] px-3 py-1 rounded-full inline-flex items-center gap-1">
                                        🔔 Aman (<span x-text="v.qty"></span> Lembar)
                                    </span>
                                </template>
                                <template x-if="v.qty > 0 && v.qty < 3">
                                    <span class="bg-amber-100 text-amber-800 font-bold text-[10px] px-3 py-1 rounded-full inline-flex items-center gap-1">
                                        ⚠️ Stok Menipis (Tersisa <span x-text="v.qty"></span> item)
                                    </span>
                                </template>
                                <template x-if="v.qty === 0">
                                    <span class="bg-rose-500 text-white font-bold text-[10px] px-3 py-1 rounded-full inline-flex items-center gap-1">
                                        Habis - PO Menunggu Malam
                                    </span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CALL MODAL POPUP COMPONENT -->
    <x-admin.modal-add-product />

</div>

@endsection