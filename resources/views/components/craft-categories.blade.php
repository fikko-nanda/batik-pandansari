<section class="bg-[#FAF8F5] py-14 md:py-16"
         x-data="{
            kats: [
                { title: 'BATIK TULIS', image: '{{ asset('images/katalog1.webp') }}', cta: '/katalog?category=tulis' },
                { title: 'BATIK CAP', image: '{{ asset('images/katalog2.webp') }}', cta: '/katalog?category=cap' },
                { title: 'BATIK HANDPRINT', image: '{{ asset('images/hero-bg.jpg') }}', cta: '/katalog?category=handprint' }
            ],
            /** Ambil kartu kategori utama dari CMS (GET /cms/kategori); fallback tetap kartu statis bila kosong/gagal. */
            async muatKategori() {
                try {
                    const res = await PandansariAPI.cmsKategori();
                    if (!res.ok || !Array.isArray(res.data) || !res.data.length) return;
                    const fmt = s => String(s ?? '').trim();
                    const mapped = res.data
                        .filter(k => k.status_aktif === undefined || k.status_aktif === true || k.status_aktif === 1 || k.status_aktif === '1')
                        .map((k, i) => {
                            const f = this.kats[i % this.kats.length] ?? this.kats[0];
                            return {
                                title: fmt(k.judul) || fmt(k.nama_kategori) || f.title,
                                image: PandansariAPI.imgUrl(k.url_gambar ?? k.gambar ?? '') || f.image,
                                cta: fmt(k.tautan_tujuan) || '/katalog',
                            };
                        })
                        .filter(s => s.title && s.image);
                    if (mapped.length) this.kats = mapped;
                } catch { /* fallback kartu statis */ }
            }
         }"
         x-init="muatKategori()">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 text-center">

        <!-- Subtitle Top Biru -->
        <span class="text-[11px] font-bold text-[#0369A1] tracking-widest uppercase block mb-1 font-['Plus_Jakarta_Sans']">
            RAGAM TEKNIK & PROSES
        </span>

        <!-- Main Title dengan Font Playfair Display -->
        <h2 class="text-2xl md:text-3xl font-bold font-['Playfair_Display'] text-[#0F1A24] mb-10">
            3 Kategori Utama Mahakarya Pandansari
        </h2>

        <!-- 3 Cards Grid (dinamis dari CMS, fallback kartu statis) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
            <template x-for="(k, index) in kats" :key="'katcms-' + index">
                <div class="relative h-64 rounded-2xl overflow-hidden shadow-md flex items-end p-6 bg-[#0F1A24] group">
                    <img :src="k.image"
                         :alt="k.title"
                         class="absolute inset-0 w-full h-full object-cover transition transform duration-500 group-hover:scale-105 opacity-60">

                    <div class="absolute inset-0 bg-gradient-to-t from-[#0F1A24]/90 via-[#0F1A24]/40 to-transparent"></div>

                    <div class="relative z-10 w-full">
                        <h3 class="font-['Playfair_Display'] text-2xl font-bold text-white mb-3 tracking-wide" x-text="k.title"></h3>
                        <a :href="k.cta"
                           class="inline-flex items-center gap-2 text-xs font-semibold text-white bg-[#0B2545] border border-sky-400/30 backdrop-blur-md px-5 py-2.5 rounded-xl hover:bg-[#0369A1] transition font-['Plus_Jakarta_Sans']">
                            Jelajahi Koleksinya <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</section>
