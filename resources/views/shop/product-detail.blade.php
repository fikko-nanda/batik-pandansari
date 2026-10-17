@extends('layouts.app')

@section('content')

    <!-- Container Utama Detail Produk dengan Alpine.js -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{
            // State untuk menampung gambar utama yang aktif
            activeImage: '{{ asset('images/m1.png') }}',
            selectedSize: 'L',
            qty: 1,

            // Daftar Galeri Foto
            images: [
                { 
                    src: '{{ asset('images/m1.png') }}', 
                    fallback: 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800&auto=format&fit=crop', 
                    label: 'Tampak Depan' 
                },
                { 
                    src: '{{ asset('images/m2.png') }}', 
                    fallback: 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?q=80&w=800&auto=format&fit=crop', 
                    label: 'Motif Samping' 
                },
                { 
                    src: '{{ asset('images/tulis.jpg') }}', 
                    fallback: 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=800&auto=format&fit=crop', 
                    label: 'Kerah & Kancing' 
                },
                { 
                    src: '{{ asset('images/cap.jpg') }}', 
                    fallback: 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?q=80&w=800&auto=format&fit=crop', 
                    label: 'Jahitan Furing' 
                }
            ],

            // Fungsi untuk mengganti gambar utama
            changeImage(img) {
                this.activeImage = img.src;
            }
         }">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

            <!-- 1. Breadcrumb Navigation & Share Button -->
            <div class="flex items-center justify-between gap-4 mb-4 sm:mb-6">
                <nav class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-medium text-slate-500 overflow-x-auto whitespace-nowrap">
                    <a href="/" class="hover:text-slate-900 transition">Beranda</a>
                    <span>/</span>
                    <a href="/katalog" class="hover:text-slate-900 transition">Katalog</a>
                    <span>/</span>
                    <a href="/katalog?cat=pria" class="hover:text-slate-900 transition">Pakaian Pria</a>
                    <span>/</span>
                    <span class="text-slate-900 font-bold">Kemeja Pria Parang Seling Indigo</span>
                </nav>

               
            </div>

            <!-- 2. Product Detail Section (Gallery + Info) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

                <!-- Left: Galeri Foto Produk (5 Kolom) -->
                <div class="lg:col-span-5 flex flex-col gap-3">
                    
                    <!-- 1. GAMBAR UTAMA (MEMANJANG KE BAWAH / PORTRAIT) -->
                    <div class="w-full h-[480px] sm:h-[580px] bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm relative shrink-0">
                        <img :src="activeImage" 
                             alt="Kemeja Pria Parang Seling Indigo" 
                             class="w-full h-full object-cover block transition-all duration-300"
                             onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800&auto=format&fit=crop'">
                    </div>

                    <!-- 2. THUMBNAIL 4 BIJI BERBARIS KE SAMPING (BISA DI-KLIK) -->
                    <div class="flex flex-row gap-2.5 sm:gap-3 w-full">
                        <template x-for="(img, idx) in images" :key="idx">
                            <button @click="changeImage(img)" 
                                    type="button" 
                                    class="flex-1 h-20 sm:h-24 bg-white rounded-xl overflow-hidden border-2 transition shadow-sm cursor-pointer relative shrink-0 group"
                                    :class="activeImage === img.src ? 'border-sky-500 ring-2 ring-sky-200' : 'border-slate-200 hover:border-slate-400'">
                                <img :src="img.src" 
                                     :alt="img.label" 
                                     class="w-full h-full object-cover block group-hover:scale-105 transition-transform duration-300"
                                     :onerror="`this.src='${img.fallback}'; if(activeImage === img.src) activeImage='${img.fallback}';`">
                                <span class="absolute bottom-0 inset-x-0 bg-slate-900/80 text-white text-[8px] sm:text-[9px] text-center py-0.5 font-medium truncate block" x-text="img.label"></span>
                            </button>
                        </template>
                    </div>

                </div>

                <!-- Right: Informasi & Form Pembelian (7 Kolom) -->
                <div class="lg:col-span-7 space-y-5">
                    
                    <!-- Title & Official Badge -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24] tracking-tight leading-snug">
                            Kemeja Pria Parang Seling Indigo
                        </h1>
                    </div>

                    <!-- Price Box -->
                    <div class="flex items-baseline gap-3">
                        <span class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Rp 1.350.000</span>
                        <span class="text-xs sm:text-sm text-slate-400 line-through">Rp 1.550.000</span>
                        <span class="bg-sky-100 text-[#0369A1] text-[10px] sm:text-xs font-bold px-2 py-0.5 rounded-md">Hemat 13%</span>
                    </div>

                    <!-- Shipping Info -->
                    <div class="flex items-center gap-2 text-xs text-slate-600 bg-white p-3 rounded-xl border border-slate-200/80 shadow-sm">
                        <i class="fa-solid fa-truck-fast text-sky-600 text-sm"></i>
                        <div>
                            <span class="font-bold text-[#0F1A24]">Dikirim dari Sragen</span>
                        </div>
                    </div>

                    <!-- Size Selector & Size Chart Link -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-[#0F1A24]">Ukuran:</span>
                            <button type="button" class="text-sky-600 hover:underline font-bold flex items-center gap-1 cursor-pointer">
                                <i class="fa-solid fa-ruler-horizontal text-xs"></i>
                                <span>Panduan Ukuran (Size Chart)</span>
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <template x-for="sz in ['S', 'M', 'L', 'XL', 'XXL']" :key="sz">
                                <button @click="selectedSize = sz" 
                                        type="button" 
                                        class="w-11 h-10 sm:w-12 sm:h-11 rounded-xl text-xs sm:text-sm font-bold border transition shadow-sm cursor-pointer"
                                        :class="selectedSize === sz ? 'bg-[#0F1A24] text-white border-[#0F1A24]' : 'bg-white text-slate-800 border-slate-200 hover:border-slate-400'">
                                    <span x-text="sz"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Size Chart Table Box -->
                    <div class="bg-white p-3.5 sm:p-4 rounded-xl border border-slate-200/80 shadow-sm space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-[#0F1A24]">
                            <span>TABEL UKURAN PRIA (cm)</span>
                            <span class="text-[10px] text-slate-400 font-normal">Pria Dewasa Regular Fit</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-[11px] text-center border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                                        <th class="py-1.5 px-2 text-left">Size</th>
                                        <th class="py-1.5 px-2">Lingkar Dada</th>
                                        <th class="py-1.5 px-2">Panjang</th>
                                        <th class="py-1.5 px-2">Lengan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-600">
                                    <tr :class="selectedSize === 'S' ? 'bg-sky-50 font-bold text-[#0369A1]' : ''">
                                        <td class="py-1.5 px-2 text-left font-bold">S</td>
                                        <td class="py-1.5 px-2">102 cm</td>
                                        <td class="py-1.5 px-2">71 cm</td>
                                        <td class="py-1.5 px-2">60 cm</td>
                                    </tr>
                                    <tr :class="selectedSize === 'M' ? 'bg-sky-50 font-bold text-[#0369A1]' : ''">
                                        <td class="py-1.5 px-2 text-left font-bold">M</td>
                                        <td class="py-1.5 px-2">106 cm</td>
                                        <td class="py-1.5 px-2">73 cm</td>
                                        <td class="py-1.5 px-2">61 cm</td>
                                    </tr>
                                    <tr :class="selectedSize === 'L' ? 'bg-sky-50 font-bold text-[#0369A1]' : ''">
                                        <td class="py-1.5 px-2 text-left font-bold">L</td>
                                        <td class="py-1.5 px-2">112 cm</td>
                                        <td class="py-1.5 px-2">75 cm</td>
                                        <td class="py-1.5 px-2">62 cm</td>
                                    </tr>
                                    <tr :class="selectedSize === 'XL' ? 'bg-sky-50 font-bold text-[#0369A1]' : ''">
                                        <td class="py-1.5 px-2 text-left font-bold">XL</td>
                                        <td class="py-1.5 px-2">118 cm</td>
                                        <td class="py-1.5 px-2">77 cm</td>
                                        <td class="py-1.5 px-2">63 cm</td>
                                    </tr>
                                    <tr :class="selectedSize === 'XXL' ? 'bg-sky-50 font-bold text-[#0369A1]' : ''">
                                        <td class="py-1.5 px-2 text-left font-bold">XXL</td>
                                        <td class="py-1.5 px-2">124 cm</td>
                                        <td class="py-1.5 px-2">79 cm</td>
                                        <td class="py-1.5 px-2">64 cm</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-bold text-[#0F1A24]">Kuantitas:</span>
                        <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm">
                            <button @click="if(qty > 1) qty--" type="button" class="w-9 h-9 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold transition cursor-pointer">-</button>
                            <span class="w-10 text-center text-xs font-bold text-[#0F1A24]" x-text="qty"></span>
                            <button @click="qty++" type="button" class="w-9 h-9 flex items-center justify-center text-slate-700 hover:bg-slate-100 font-bold transition cursor-pointer">+</button>
                        </div>
                        <span class="text-xs text-slate-400">Tersedia 8 buah</span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="space-y-2 pt-2">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <a href="/cart" class="bg-white hover:bg-slate-50 text-[#0F1A24] border border-slate-300 text-xs font-bold py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                                <i class="fa-solid fa-cart-plus text-xs"></i>
                                <span>+ Keranjang</span>
                            </a>
                            <a href="/cart" class="bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-xs font-bold py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                                <span>Beli Sekarang →</span>
                            </a>
                        </div>

                        <!-- WhatsApp Consultation Button -->
                        <a href="https://wa.me/6281234567890?text=Halo%20Batik%20Pandansari,%20saya%20ingin%20konsultasi%20ukuran%20Kemeja%20Pria%20Parang%20Seling%20Indigo" target="_blank" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2">
                            <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                            <span>Konsultasi Ukuran & Fitting via WhatsApp</span>
                        </a>
                    </div>

                    <!-- Guarantees Footer -->
                    <div class="flex items-center justify-around text-[11px] text-slate-500 pt-3 border-t border-slate-200/80">
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-check text-sky-600"></i> 100% Original</span>
                    </div>

                </div>

            </div>

            <!-- 3. Deskripsi Produk Detail Box -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-sm mb-12 space-y-4">
                <h2 class="text-base sm:text-lg font-bold text-[#0F1A24] tracking-tight uppercase border-b border-slate-100 pb-3">
                    DESKRIPSI PRODUK
                </h2>

                <div class="text-xs sm:text-sm text-slate-600 leading-relaxed space-y-3 font-['Plus_Jakarta_Sans']">
                    <p>
                        <strong class="text-[#0F1A24]">Kemeja Batik Pria Parang Seling Indigo Solo Klasik:</strong> dirancang eksklusif untuk pria yang mengutamakan kerapian berbusana formal kerja, maupun acara perjamuan resmi kenegaraan dan pesta resepsi.
                    </p>

                    <ul class="space-y-2 list-disc pl-5">
                        <li><strong class="text-[#0F1A24]">Kualitas Bahan Katun Primisima:</strong> Dibuat menggunakan kain katun primisima super halus, lembut di kulit, menyerap keringat dengan sangat baik, dan adem panas digunakan sepanjang hari.</li>
                        <li><strong class="text-[#0F1A24]">Pola Motif Menyambung (Pattern-Matched):</strong> Jahit pada belahan kancing depan dan kantong saku dipotong serta dijahit dengan presisi tinggi sehingga motif batik tetap mengalir utuh dan simetris tanpa potongan terputus.</li>
                        <li><strong class="text-[#0F1A24]">Lapisan Furing Katun Adem:</strong> Dilapisi furing katun hero kualitas premium yang sejuk dan tidak bikin gerah, melindungi kain batik serta memberikan siluet badan yang tegap dan kokoh saat dikenakan bersama jas maupun dasi.</li>
                        <li><strong class="text-[#0F1A24]">Jahitan Standar Batik / Tailor Halus:</strong> Menggunakan teknik jahitan stik balik rapi tanpa obras kasar yang terlihat, kerah tegak ber-interlining berkualitas agar rapi saat dikenakan bersama jas maupun dasi.</li>
                        <li><strong class="text-[#0F1A24]">Garansi Pengembalian & Tukar Ukuran:</strong> Garansi 7 hari tukar ukuran jika kemeja yang diterima kurang pas (Syarat: label belum dilepas dan belum dicuci).</li>
                    </ul>

                    <div class="pt-2 border-t border-slate-100 text-slate-500">
                        <strong class="text-[#0F1A24]">Petunjuk Perawatan:</strong><br>
                        Dianjurkan mencuci menggunakan lerak atau detergen cair lembut tanpa pemutih. Jemur di tempat teduh (jangan terpapar matahari langsung). Setrika dengan suhu sedang dari sisi furing dalam.
                    </div>
                </div>
            </div>

            <!-- 4. Koleksi Rekomendasi Pilihan Serupa -->
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <span class="text-[9px] sm:text-[10px] font-bold text-[#0369A1] uppercase tracking-wider block">KURASI PILIHAN MAHARDIKA</span>
                        <h2 class="text-lg sm:text-2xl font-bold text-[#0F1A24]">Koleksi Rekomendasi Pilihan Serupa</h2>
                    </div>
                    <a href="/katalog" class="text-xs font-bold text-slate-600 hover:text-black transition hidden sm:inline">Lihat Seluruh Koleksi Pria & Sarimbit →</a>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <!-- Item 1 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/cat-kids.jpg') }}" alt="Tunik Pesisiran" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1598033129183-c4f50c736f10?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN WANITA • SARIMBIT</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Tunik Pesisiran Navy Sekar</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 1.280.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>

                    <!-- Item 2 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/m2.png') }}" alt="Kemeja Sutra" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN PRIA • SUTRA KATUN</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Kemeja Sutra Megamendung...</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 2.450.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>

                    <!-- Item 3 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/tulis.jpg') }}" alt="Kemeja Cap" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN PRIA • BATIK CAP HANDMADE</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Kemeja Cap Sawunggaling...</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 890.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>

                    <!-- Item 4 -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition p-3 flex flex-col justify-between">
                        <div class="h-48 bg-slate-100 rounded-xl overflow-hidden mb-3">
                            <img src="{{ asset('images/m1.png') }}" alt="Kemeja Tulis" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?q=80&w=800&auto=format&fit=crop'">
                        </div>
                        <div>
                            <span class="text-[8px] font-bold text-[#0369A1] uppercase block mb-0.5">PAKAIAN PRIA • PRIMISSIMA</span>
                            <h3 class="font-bold text-[#0F1A24] text-xs truncate">Kemeja Tulis Gurdo Bimo...</h3>
                            <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 1.420.000</span>
                        </div>
                        <a href="/detail" class="mt-3 block text-center bg-slate-100 hover:bg-slate-200 text-[#0F1A24] text-[10px] font-bold py-2 rounded-lg transition">Lihat Detail</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection