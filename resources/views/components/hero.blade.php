<section class="relative w-full bg-[#0F1A24] overflow-hidden"
         x-data="{
            activeSlide: 0,
            slides: [
                {
                    badge: 'MAHAKARYA WASTRA NUSANTARA',
                    title: 'Artistry in<br><span class=\'italic font-normal text-[#ADD8E6]\'>Every Thread</span>',
                    description: 'Menghadirkan keagungan warisan budaya Nusantara melalui karya batik tulis dan cap otentik yang ditutur dan dicanting halus khusus untuk Anda.',
                    image: '{{ asset('images/katalog1.webp') }}',
                    cta: '/katalog',
                    ctaText: 'JELAJAHI KOLEKSI UTAMA'
                },
                {
                    badge: 'SERAGAM & CUSTOM DESAIN',
                    title: 'Kustomisasi Batik<br><span class=\'italic font-normal text-[#ADD8E6]\'>Instansi & Pernikahan</span>',
                    description: 'Melayani pembuatan batik custom seragam kantor, instansi, hingga busana acara keluarga bernilai seni tinggi.',
                    image: '{{ asset('images/katalog2.webp') }}',
                    cta: '/katalog',
                    ctaText: 'JELAJAHI KOLEKSI UTAMA'
                }
            ],
            next() { this.activeSlide = (this.activeSlide + 1) % this.slides.length; },
            prev() { this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length; },
            timer: null,
            startTimer() { clearInterval(this.timer); this.timer = setInterval(() => { this.next() }, 5000); },
            /** Ambil slide dari CMS (GET /cms/hero); fallback tetap slide statis bila kosong/gagal. */
            async muatCms() {
                try {
                    const res = await PandansariAPI.cmsHeroes();
                    if (!res.ok || !Array.isArray(res.data) || !res.data.length) return;
                    const fallback = this.slides[0];
                    const mapped = res.data
                        .map(h => ({
                            badge: String(h.label_badge ?? '').trim() || fallback.badge,
                            title: String(h.judul ?? '').trim() || fallback.title,
                            description: String(h.subjudul ?? '').trim() || fallback.description,
                            image: PandansariAPI.imgUrl(h.url_gambar ?? h.gambar ?? '') || fallback.image,
                            cta: String(h.tautan_tombol ?? '').trim() || '/katalog',
                            ctaText: String(h.teks_tombol ?? '').trim() || fallback.ctaText,
                        }))
                        .filter(s => s.image);
                    if (!mapped.length) return;
                    this.slides = mapped;
                    this.activeSlide = 0;
                    this.startTimer();
                } catch { /* fallback slide statis */ }
            }
         }"
         x-init="muatCms(); startTimer()"
         @mouseenter="clearInterval(timer)"
         @mouseleave="startTimer()">

    <!-- CONTAINER HERO FULL WIDTH -->
    <div class="relative w-full min-h-[520px] md:min-h-[580px] flex items-center shadow-2xl">
        
        <!-- Loop Slide Gambar Background Full Screen -->
        <template x-for="(slide, index) in slides" :key="index">
            <div x-show="activeSlide === index"
                 x-transition:enter="transition opacity duration-700 ease-in-out"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition opacity duration-500 ease-in-out"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 w-full h-full">
                <img :src="slide.image"
                     x-on:error="$event.target.src = '{{ asset('images/hero-bg.jpg') }}'"
                     alt="Batik Pandansari Hero"
                     class="w-full h-full object-cover object-center">
                <!-- Dark Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-[#0F1A24]/95 via-[#0F1A24]/80 to-transparent"></div>
            </div>
        </template>

        <!-- Teks Hero Content -->
        <div class="relative z-10 w-full max-w-[1280px] mx-auto px-6 sm:px-10 md:px-16 py-12 md:py-20 text-white">
            <div class="max-w-xl">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#2B4053]/80 border border-[#ADD8E6]/30 backdrop-blur-md mb-6">
                    <span class="w-2 h-2 rounded-full bg-[#ADD8E6]"></span>
                    <span class="text-[11px] font-semibold tracking-wider text-[#ADD8E6] uppercase font-['Plus_Jakarta_Sans']" x-html="slides[activeSlide].badge"></span>
                </div>

                <!-- Judul Utama -->
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-['Playfair_Display'] leading-[1.15] text-white mb-5 font-normal" x-html="slides[activeSlide].title"></h1>

                <!-- Deskripsi -->
                <p class="text-slate-200 text-sm md:text-base leading-relaxed mb-8 font-light max-w-lg font-['Plus_Jakarta_Sans']" x-text="slides[activeSlide].description"></p>

                <!-- Tombol CTA -->
                <div class="flex flex-wrap items-center gap-4">
                    <a :href="slides[activeSlide].cta" class="bg-[#ADD8E6] text-[#0F1A24] font-bold text-xs tracking-wider uppercase px-6 py-3.5 rounded-xl shadow-md hover:bg-sky-200 transition font-['Plus_Jakarta_Sans']">
                        <span x-text="slides[activeSlide].ctaText"></span>
                    </a>
                    <a href="#ecommerce-partners"
                       @click.prevent="document.getElementById('ecommerce-partners')?.scrollIntoView({ behavior: 'smooth' })"
                       class="bg-white/10 text-white border border-white/30 backdrop-blur-md font-bold text-xs tracking-wider uppercase px-6 py-3.5 rounded-xl shadow-md hover:bg-white/20 transition font-['Plus_Jakarta_Sans']">
                        OFFICIAL STORE & MARKETPLACE
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigasi Arrow -->
        <button @click="prev()" class="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/40 border border-white/20 text-white flex items-center justify-center hover:bg-black/70 backdrop-blur-md transition z-20 cursor-pointer">
            <i class="fa-solid fa-chevron-left text-sm"></i>
        </button>
        <button @click="next()" class="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/40 border border-white/20 text-white flex items-center justify-center hover:bg-black/70 backdrop-blur-md transition z-20 cursor-pointer">
            <i class="fa-solid fa-chevron-right text-sm"></i>
        </button>

        <!-- Indikator Slide (Dots) -->
        <div class="absolute bottom-6 left-6 sm:left-10 md:left-16 z-20 flex items-center gap-2">
            <template x-for="(slide, index) in slides" :key="index">
                <button @click="activeSlide = index" 
                        class="h-2 rounded-full transition-all duration-300 cursor-pointer"
                        :class="activeSlide === index ? 'w-8 bg-[#ADD8E6]' : 'w-2 bg-white/40 hover:bg-white/70'"></button>
            </template>
        </div>

    </div>
</section>