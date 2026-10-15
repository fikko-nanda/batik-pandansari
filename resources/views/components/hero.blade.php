<section class="bg-[#FAF8F5] py-6 md:py-8" 
         x-data="{ 
            activeSlide: 0, 
            slides: [
                {
                    badge: 'MAHAKARYA WASTRA NUSANTARA',
                    title: 'Artistry in<br><span class=\'italic font-normal text-[#ADD8E6]\'>Every Thread</span>',
                    description: 'Menghadirkan keagungan warisan budaya Nusantara melalui karya batik tulis dan cap otentik yang ditutur dan dicanting halus khusus untuk Anda.',
                    image: '{{ asset('images/hero-bg.jpg') }}'
                },
                {
                    badge: 'SERAGAM & CUSTOM DESAIN',
                    title: 'Kustomisasi Batik<br><span class=\'italic font-normal text-[#ADD8E6]\'>Instansi & Pernikahan</span>',
                    description: 'Melayani pembuatan batik custom seragam kantor, instansi, hingga busana acara keluarga bernilai seni tinggi.',
                    image: '{{ asset('images/hero-bg.jpg') }}'
                }
            ],
            next() { this.activeSlide = (this.activeSlide + 1) % this.slides.length; },
            prev() { this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length; },
            timer: null
         }"
         x-init="timer = setInterval(() => { next() }, 5000)"
         @mouseenter="clearInterval(timer)"
         @mouseleave="timer = setInterval(() => { next() }, 5000)">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="relative rounded-[28px] overflow-hidden min-h-[480px] flex items-center p-8 md:p-14 bg-[#0F1A24] shadow-xl">
            
            <!-- Loop Slide Gambar Background -->
            <template x-for="(slide, index) in slides" :key="index">
                <div x-show="activeSlide === index"
                     x-transition:enter="transition opacity duration-700 ease-in-out"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition opacity duration-500 ease-in-out"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute inset-0 w-full h-full">
                    <img :src="slide.image" alt="Batik Pandansari Hero" class="w-full h-full object-cover object-center">
                    <div class="absolute inset-0 bg-gradient-to-r from-[#0F1A24]/95 via-[#0F1A24]/75 to-transparent"></div>
                </div>
            </template>

            <!-- Teks Hero -->
            <div class="relative z-10 max-w-xl text-white">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#2B4053]/80 border border-[#ADD8E6]/30 backdrop-blur-md mb-6">
                    <span class="w-2 h-2 rounded-full bg-[#ADD8E6]"></span>
                    <span class="text-[11px] font-semibold tracking-wider text-[#ADD8E6] uppercase font-['Plus_Jakarta_Sans']" x-html="slides[activeSlide].badge"></span>
                </div>

                <h1 class="text-4xl md:text-5xl font-['Playfair_Display'] leading-[1.18] text-white mb-5 font-normal" x-html="slides[activeSlide].title"></h1>

                <p class="text-slate-200 text-sm md:text-base leading-relaxed mb-8 font-light max-w-lg font-['Plus_Jakarta_Sans']" x-text="slides[activeSlide].description"></p>

                <div class="flex flex-wrap items-center gap-4">
                    <a href="#katalog" class="bg-[#ADD8E6] text-[#0F1A24] font-bold text-xs tracking-wider uppercase px-6 py-3.5 rounded-xl shadow-md hover:bg-sky-200 transition font-['Plus_Jakarta_Sans']">
                        JELAJAHI KOLEKSI UTAMA
                    </a>
                    <a href="#marketplace" class="bg-white/10 text-white border border-white/30 backdrop-blur-md font-bold text-xs tracking-wider uppercase px-6 py-3.5 rounded-xl shadow-md hover:bg-white/20 transition font-['Plus_Jakarta_Sans']">
                        OFFICIAL STORE & MARKETPLACE
                    </a>
                </div>
            </div>

            <!-- Navigasi Arrow -->
            <button @click="prev()" class="absolute left-5 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/40 border border-white/20 text-white flex items-center justify-center hover:bg-black/70 backdrop-blur-md transition z-20">
                <i class="fa-solid fa-chevron-left text-sm"></i>
            </button>
            <button @click="next()" class="absolute right-5 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/40 border border-white/20 text-white flex items-center justify-center hover:bg-black/70 backdrop-blur-md transition z-20">
                <i class="fa-solid fa-chevron-right text-sm"></i>
            </button>

            <!-- Indikator Slide -->
            <div class="absolute bottom-5 left-8 md:left-14 z-20 flex items-center gap-2">
                <template x-for="(slide, index) in slides" :key="index">
                    <button @click="activeSlide = index" 
                            class="h-2 rounded-full transition-all duration-300"
                            :class="activeSlide === index ? 'w-8 bg-[#ADD8E6]' : 'w-2 bg-white/40 hover:bg-white/70'"></button>
                </template>
            </div>
        </div>
    </div>
</section>