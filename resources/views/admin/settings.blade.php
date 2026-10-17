@extends('layouts.admin')

@section('content')

<div x-data="{
    activeTab: 'profile',
    
    // TAB 1: PROFIL & IDENTITAS
    storeName: 'Batik Pandansari - Mahakarya autentik',
    shortDesc: 'Atelier keraton yang menghadirkan karya batik autentik dengan motif khas Pandansari.',
    whatsapp: '+62 811-2890-9901',
    email: 'kurator@batikpandansari.id',

    // TAB 2: KELOLA BANNER HERO
    banners: [
        {
            id: 1,
            title: 'banner_utama_dashboard_v2.jpg',
            info: '1920 × 500 px • 1.5 MB • Diunggah 24 Des 2026',
            headline: 'Koleksi Batik Pandansari — Edisi Terbatas',
            subtitle: 'Temukan motif terbaru dan langka di balik setiap helai kain.',
            labelBtn: 'Isi label tombol',
            linkBtn: '/koleksi',
            img: '{{ asset('images/m1.png') }}',
            status: 'AKTIF'
        },
        {
            id: 2,
            title: 'banner_tambahan_edisi_terbatas.jpg',
            info: '1920 × 500 px • 1.4 MB • Diunggah 21 Des 2026',
            headline: 'Edisi Terbatas — Batik Pandansari',
            subtitle: 'Jelajahi koleksi terkurasi dari punden mahakarya.',
            labelBtn: '',
            linkBtn: '',
            img: '{{ asset('images/tulis.jpg') }}',
            status: 'AKTIF'
        },
        {
            id: 3,
            title: 'banner_tambahan_promo_kolektor.jpg',
            info: '1920 × 500 px • 1.2 MB • Diunggah 18 Des 2026',
            headline: 'Promo Kolektor — Batik Pandansari',
            subtitle: 'Dapatkan penawaran spesial untuk kolektor setia Atelier Pandansari.',
            labelBtn: '',
            linkBtn: '',
            img: '{{ asset('images/cap.jpg') }}',
            status: 'AKTIF'
        }
    ],

    // TAB 3: KELOLA 3 KATEGORI UTAMA
    categories: [
        {
            id: 1,
            title: 'BATIK TULIS',
            name: 'Batik Tulis',
            desc: 'Koleksi batik tulis tangan dengan motif klasik dan sentuhan modern.',
            link: '/kategori/batik-tulis',
            img: '{{ asset('images/tulis.jpg') }}'
        },
        {
            id: 2,
            title: 'BATIK CAP',
            name: 'Batik Cap',
            desc: 'Koleksi batik cap dengan motif geometris dan produksi skala menengah.',
            link: '/kategori/batik-cap',
            img: '{{ asset('images/cap.jpg') }}'
        },
        {
            id: 3,
            title: 'BATIK HANDPRINT',
            name: 'Batik Handprint',
            desc: 'Koleksi batik handprint dengan motif modern dan sentuhan artisanal.',
            link: '/kategori/batik-handprint',
            img: '{{ asset('images/m2.png') }}'
        }
    ],

    // TAB 4: GALERI & ALAMAT ATELIER
    galleryImg: '{{ asset('images/m1.png') }}',
    fullAddress: 'Dalem Kalitan & Sentra Wastra Pandansari, Jl. Gurawan No. 12, Pasar Kliwon, Surakarta 57118',
    googleMapsUrl: 'https://maps.google.com/?q=Dalem+Kalitan+Surakarta',
    openingHours: 'Senin - Sabtu: 09:00 - 17:00 WIB (Minggu Tutup)',
    facilities: ['Busana Fitting', 'Area Parkir', '+ Tambah'],

    saving: false,
    saveChanges() {
        this.saving = true;
        setTimeout(() => {
            this.saving = false;
            alert('Semua perubahan pengaturan atelier berhasil disimpan!');
        }, 600);
    }
}" class="space-y-6 text-slate-800 font-sans pb-8">

    <!-- 1. HEADER TITLE -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
            Pengaturan & Profil Atelier
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            Konfigurasi identitas resmi atelier keraton, alamat titik jemput logistik RajaOngkir, dan manajemen hak akses kurator.
        </p>
    </div>

    <!-- 2. NAVIGATION TABS -->
    <div class="flex flex-wrap items-center gap-2 p-1.5 bg-slate-100/80 rounded-2xl border border-slate-200/80 w-fit text-xs font-bold">
        <button @click="activeTab = 'profile'" 
                :class="activeTab === 'profile' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'" 
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Profil & Identitas
        </button>

        <button @click="activeTab = 'hero'" 
                :class="activeTab === 'hero' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'" 
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Kelola Banner Hero
        </button>

        <button @click="activeTab = 'categories'" 
                :class="activeTab === 'categories' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'" 
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Kelola 3 Kategori Utama
        </button>

        <button @click="activeTab = 'gallery'" 
                :class="activeTab === 'gallery' ? 'bg-[#0A192F] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50'" 
                class="px-4 py-2.5 rounded-xl transition cursor-pointer">
            Galeri & Alamat Atelier
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: PROFIL & IDENTITAS                  -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'profile'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                <i class="fa-solid fa-store"></i>
            </div>
            <h2 class="text-base font-extrabold text-slate-900">Profil & Identitas</h2>
        </div>

        <form @submit.prevent="saveChanges()" class="space-y-5 text-xs font-medium">
            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">NAMA TOKO & ATELIER RESMI</label>
                <input type="text" x-model="storeName" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">DESKRIPSI SINGKAT</label>
                <textarea x-model="shortDesc" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400 leading-relaxed"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">NOMOR WHATSAPP CONCIERGE / CS</label>
                    <input type="text" x-model="whatsapp" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">ALAMAT EMAIL RESMI</label>
                    <input type="email" x-model="email" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>
            </div>

            <!-- Upload Logo Section -->
            <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4 sm:p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">LAMBANG CAP RESMI & LOGO</span>
                    <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-100/80 border border-emerald-200 px-2 py-0.5 rounded uppercase tracking-wider">VEKTOR / HI-RES</span>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-[#0A192F] rounded-lg flex items-center justify-center p-1 border border-slate-300 shrink-0">
                            <img src="{{ asset('images/m1.png') }}" class="w-full h-full object-cover rounded" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=100'">
                        </div>
                        <div>
                            <strong class="text-slate-900 font-extrabold text-xs block">Segel_Pandansari_Prada_v2.svg</strong>
                            <span class="text-[10px] text-slate-400 block font-mono">Vektor Resolusi Tinggi • 420 KB</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold rounded-lg transition cursor-pointer">Ganti Logo</button>
                        <button type="button" class="px-2.5 py-1 bg-rose-100 hover:bg-rose-200 text-rose-700 text-[10px] font-bold rounded-lg transition cursor-pointer">Hapus</button>
                    </div>
                </div>

                <div class="border-2 border-dashed border-sky-200/80 rounded-2xl p-8 text-center bg-white hover:bg-sky-50/40 transition cursor-pointer space-y-2">
                    <div class="w-12 h-12 bg-[#AEE2F0]/50 text-sky-700 rounded-full flex items-center justify-center mx-auto text-lg font-bold">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div>
                        <strong class="text-slate-800 text-xs font-bold block">Tarik & lepas file logo di sini</strong>
                        <span class="text-[10px] text-slate-400 block">atau klik untuk memilih dari direktori lokal</span>
                    </div>
                    <div class="pt-2">
                        <span class="bg-slate-100 text-slate-500 text-[9px] font-bold px-3 py-1 rounded-lg uppercase tracking-wider inline-block">
                            PNG, JPG, SVG MAKS 5MB (REKOMENDASI 800X800PX)
                        </span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: KELOLA BANNER HERO                  -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'hero'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                <i class="fa-solid fa-images"></i>
            </div>
            <h2 class="text-base font-extrabold text-slate-900">Kelola Banner Hero</h2>
        </div>

        <div class="space-y-6 text-xs font-medium">
            <template x-for="(b, idx) in banners" :key="b.id">
                <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest" x-text="idx === 0 ? 'BANNER UTAMA DASHBOARD' : 'BANNER TAMBAHAN'"></span>
                        <span class="text-[9px] font-extrabold text-emerald-700 bg-emerald-100/80 border border-emerald-200 px-2 py-0.5 rounded uppercase tracking-wider" x-text="b.status"></span>
                    </div>

                    <div class="w-full h-40 sm:h-48 rounded-xl overflow-hidden border border-slate-200 relative">
                        <img :src="b.img" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800'">
                    </div>

                    <div class="flex items-center justify-between text-[10px]">
                        <div>
                            <strong class="text-slate-900 font-extrabold block text-xs" x-text="b.title"></strong>
                            <span class="text-slate-400 font-mono" x-text="b.info"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition cursor-pointer">Ganti Banner</button>
                            <button type="button" class="px-2.5 py-1 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold rounded-lg transition cursor-pointer">Hapus</button>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">HEADLINE</label>
                            <input type="text" x-model="b.headline" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">TEKS PENDUKUNG</label>
                            <input type="text" x-model="b.subtitle" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-medium text-slate-700">
                        </div>

                        <template x-if="idx === 0">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">LABEL TOMBOL</label>
                                    <input type="text" x-model="b.labelBtn" placeholder="Isi label tombol" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">LINK BUTTON</label>
                                    <input type="text" x-model="b.linkBtn" placeholder="/koleksi" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-mono">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <div class="border-2 border-dashed border-sky-200/80 rounded-2xl p-8 text-center bg-slate-50/50 hover:bg-sky-50/40 transition cursor-pointer space-y-2">
                <div class="w-10 h-10 bg-[#AEE2F0]/50 text-sky-700 rounded-full flex items-center justify-center mx-auto text-base font-bold">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <strong class="text-slate-800 text-xs font-bold block">Tarik & lepas file banner di sini</strong>
                <span class="text-[10px] text-slate-400 block">atau klik untuk menambahkan banner baru</span>
                <span class="bg-slate-100 text-slate-500 text-[9px] font-bold px-3 py-1 rounded-lg uppercase tracking-wider inline-block mt-2">
                    PNG, JPG, SVG MAKS 5MB (REKOMENDASI 1920X500PX)
                </span>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: KELOLA 3 KATEGORI UTAMA             -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'categories'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h2 class="text-base font-extrabold text-slate-900">Kelola 3 Kategori Utama</h2>
        </div>

        <div class="space-y-6 text-xs font-medium">
            <template x-for="c in categories" :key="c.id">
                <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest" x-text="c.title"></span>
                        <div class="flex items-center gap-2">
                            <button type="button" class="text-slate-500 hover:text-slate-800 text-[10px] font-bold">Edit</button>
                            <button type="button" class="text-rose-600 hover:text-rose-800 text-[10px] font-bold">Hapus</button>
                        </div>
                    </div>

                    <div class="w-full h-36 rounded-xl overflow-hidden border border-slate-200">
                        <img :src="c.img" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800'">
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">JUDUL</label>
                            <input type="text" x-model="c.name" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-bold text-slate-800">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">DESKRIPSI</label>
                            <input type="text" x-model="c.desc" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-medium text-slate-700">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">LINK TUJUAN</label>
                            <input type="text" x-model="c.link" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs font-mono text-slate-800">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">UNGGAH BACKGROUND CARD</label>
                            <input type="text" readonly value="Unggah / Ganti gambar • JPG, PNG • Maks. 5 MB" class="w-full bg-slate-100 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-400 cursor-pointer">
                        </div>
                    </div>
                </div>
            </template>

            <div class="border-2 border-dashed border-sky-200/80 rounded-2xl p-6 text-center bg-slate-50/50 hover:bg-sky-50/40 transition cursor-pointer space-y-1">
                <div class="w-10 h-10 bg-[#AEE2F0]/50 text-sky-700 rounded-full flex items-center justify-center mx-auto text-base font-bold mb-2">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <strong class="text-slate-800 text-xs font-bold block">Tambah Kategori Baru</strong>
                <span class="text-[10px] text-slate-400 block">Tambah kategori baru untuk menampilkan koleksi spesial</span>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: GALERI & ALAMAT ATELIER             -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'gallery'" x-transition class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-6">
        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <div class="w-9 h-9 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-sm">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <h2 class="text-base font-extrabold text-slate-900">Galeri & Alamat Atelier</h2>
        </div>

        <div class="space-y-5 text-xs font-medium">
            <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black text-slate-700 uppercase tracking-widest">GALERI ATELIER</span>
                    <span class="text-[9px] font-extrabold text-slate-400 uppercase">CONTOH</span>
                </div>

                <div class="w-full h-48 sm:h-56 rounded-xl overflow-hidden border border-slate-200 relative">
                    <img :src="galleryImg" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800'">
                </div>

                <div class="flex items-center justify-between text-[11px] pt-1">
                    <div>
                        <strong class="text-slate-900 font-extrabold block">Contoh tampilan foto galeri — ganti dengan foto atelier</strong>
                        <span class="text-slate-400 text-[10px]">Foto ilustrasi — unggah foto atelier yang sebenarnya</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition cursor-pointer text-[10px]">Ganti Foto</button>
                        <button type="button" class="px-2.5 py-1 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold rounded-lg transition cursor-pointer text-[10px]">Hapus</button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">ALAMAT LENGKAP</label>
                <input type="text" x-model="fullAddress" placeholder="Belum diisi — masukkan alamat lengkap atelier" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">LINK GOOGLE MAPS</label>
                <input type="text" x-model="googleMapsUrl" placeholder="Belum diisi — tempel tautan Google Maps" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">JAM BUKA</label>
                <input type="text" x-model="openingHours" placeholder="Belum diisi — atur hari dan jam operasional" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>

            <div class="bg-slate-50/80 border border-slate-200/80 rounded-2xl p-4 space-y-3">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block">FASILITAS — CONTOH OPSIONAL, SESUAIKAN KETERSREDIAAN</span>
                
                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="(f, i) in facilities" :key="i">
                        <span class="bg-white border border-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-xl flex items-center gap-2">
                            <span x-text="f"></span>
                        </span>
                    </template>
                </div>

                <div class="pt-2">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">KELOLA FASILITAS</label>
                    <input type="text" readonly value="+ Tambah fasilitas • Edit • Hapus" class="w-full bg-white border border-slate-200 rounded-xl p-3 text-xs text-slate-400 cursor-pointer">
                </div>
            </div>

            <div class="border-2 border-dashed border-sky-200/80 rounded-2xl p-6 text-center bg-slate-50/50 hover:bg-sky-50/40 transition cursor-pointer">
                <strong class="text-slate-800 text-xs font-bold block">TAMBAH FOTO GALERI / ATELIER</strong>
                <span class="text-[10px] text-slate-400 block mt-1">Unggah foto baru • JPG, PNG • Maks. 5 MB</span>
            </div>
        </div>
    </div>

    <!-- 5. INLINE SAVE BAR (DISATUKAN DI BAGIAN BAWAH KONTAINER) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-bold">
        <span class="text-slate-500 text-[11px] font-medium">Perubahan terakhir disimpan pada 28 Oktober 2026, 11:20 WIB</span>
        <div class="flex items-center gap-2 self-end sm:self-auto">
            <button type="button" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer">Batal</button>
            <button type="button" @click="saveChanges()" class="px-6 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-black rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-floppy-disk text-xs" x-show="!saving"></i>
                <i class="fa-solid fa-spinner animate-spin text-xs" x-show="saving"></i>
                <span>SIMPAN SEMUA PERUBAHAN</span>
            </button>
        </div>
    </div>

</div>

@endsection