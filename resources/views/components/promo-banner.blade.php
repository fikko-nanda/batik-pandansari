<section class="bg-[#FAF8F5] py-10 md:py-14"
    x-data="{
        promos: [],
        activeIndex: 0,
        copied: '',
        loading: true,
        timer: null,
        async init() {
            if (typeof PandansariAPI === 'undefined') { this.loading = false; return; }
            try {
                let data = [];
                const res = await PandansariAPI.cmsPromosPublik();
                if (res.ok && res.data) {
                    data = Array.isArray(res.data)
                        ? res.data
                        : (Array.isArray(res.data.data) ? res.data.data : [res.data]);
                }
                if (!data.length) {
                    const fallback = await PandansariAPI.cmsPromoPublik();
                    if (fallback.ok && fallback.data) data = [fallback.data];
                }
                this.promos = data.map((p, index) => {
                    const v = p.voucher ?? p;
                    const gambar = p.url_gambar ?? p.gambar ?? v.url_gambar ?? '';
                    const productId = v.id_product_peruntukan ?? p.id_product_peruntukan;
                    const tautan = productId ? '/detail/' + productId : '/katalog';
                    return {
                        id: p.id_cms_promo ?? p.id ?? v.id_voucher ?? index,
                        gambar: PandansariAPI.imgUrl(gambar),
                        badge: p.badge ?? v.badge ?? '',
                        judul: v.nama_voucher ?? p.nama_voucher ?? 'Promo Spesial',
                        deskripsi: v.deskripsi ?? p.deskripsi ?? '',
                        kode: v.kode_voucher ?? p.kode_voucher ?? '',
                        tautan: tautan || '/katalog',
                    };
                }).filter(p => p.kode);
            } catch (e) {
                console.warn('Promo beranda pakai tampilan statis', e);
            }
            this.loading = false;
            this.startAuto();
        },
        get activePromo() { return this.promos[this.activeIndex] ?? null; },
        get hasPromos() { return this.promos.length > 0; },
        get hasMultiple() { return this.promos.length > 1; },
        goTo(index) {
            if (!this.promos.length) return;
            this.activeIndex = (index + this.promos.length) % this.promos.length;
            this.copied = '';
            this.startAuto();
        },
        next() { this.goTo(this.activeIndex + 1); },
        prev() { this.goTo(this.activeIndex - 1); },
        startAuto() {
            this.stopAuto();
            if (this.promos.length > 1) {
                this.timer = setInterval(() => {
                    this.activeIndex = (this.activeIndex + 1) % this.promos.length;
                    this.copied = '';
                }, 6500);
            }
        },
        stopAuto() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        destroy() { this.stopAuto(); },
        legacyCopy(text) {
            const el = document.createElement('textarea');
            el.value = text;
            el.setAttribute('readonly', '');
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            el.remove();
        },
        async copyCode(code) {
            if (!code) return;
            try {
                await navigator.clipboard.writeText(code);
            } catch {
                this.legacyCopy(code);
            }
            this.copied = code;
            setTimeout(() => { if (this.copied === code) this.copied = ''; }, 2000);
        }
    }"
    @mouseenter="stopAuto()" @mouseleave="startAuto()" @focusin="stopAuto()" @focusout="startAuto()">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="relative rounded-[24px] overflow-hidden min-h-[420px] flex items-center p-8 md:p-14 bg-[#0F1A24] shadow-lg border border-slate-200/60">

            <template x-for="(p, index) in promos" :key="p.id ?? index">
                <div x-show="activeIndex === index" class="absolute inset-0">
                    <img :src="p.gambar || '{{ asset('images/katalog1.webp') }}'" :alt="p.judul"
                         class="absolute inset-0 w-full h-full object-cover object-center"
                         x-on:error="$el.src = '{{ asset('images/katalog1.webp') }}'">
                    <div class="absolute inset-0 bg-gradient-to-r from-[#0F1A24]/90 via-[#0F1A24]/60 to-transparent"></div>
                </div>
            </template>

            <div x-show="hasPromos" x-cloak class="relative z-10 max-w-xl text-white space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900/60 border border-slate-700/60 backdrop-blur-md">
                    <span class="text-[10px] font-semibold text-slate-300 tracking-wider uppercase font-['Plus_Jakarta_Sans']"
                          x-text="activePromo?.badge || '✦ EDISI TERBATAS'"></span>
                </div>

                <h2 class="text-3xl md:text-5xl font-bold font-['Plus_Jakarta_Sans'] leading-[1.15] text-white"
                    x-text="activePromo?.judul"></h2>

                <p x-show="activePromo?.deskripsi" class="text-sm text-slate-300/90 leading-relaxed line-clamp-3 font-['Plus_Jakarta_Sans']"
                   x-text="activePromo?.deskripsi"></p>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 backdrop-blur-md rounded-xl px-4 py-3">
                        <span class="text-[9px] font-black uppercase tracking-[0.18em] text-white/60">Kode</span>
                        <code class="font-mono text-lg font-black tracking-wider text-amber-300" x-text="activePromo?.kode"></code>
                    </div>
                    <button type="button" @click="copyCode(activePromo?.kode)"
                            class="inline-flex items-center justify-center gap-2 bg-amber-300 hover:bg-amber-200 text-[#0A192F] text-xs font-black tracking-wider uppercase px-5 py-3.5 rounded-xl shadow-md transition cursor-pointer">
                        <i class="fa-solid" :class="copied === activePromo?.kode ? 'fa-check' : 'fa-copy'"></i>
                        <span x-text="copied === activePromo?.kode ? 'TERSALIN' : 'SALIN KODE'"></span>
                    </button>
                </div>

                <p class="text-[11px] sm:text-xs text-slate-300/90 font-medium">
                    Masukkan kode ini saat checkout untuk mendapatkan promo.
                </p>

                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <a :href="activePromo?.tautan || '/katalog'"
                       class="inline-flex items-center gap-2 bg-[#0B2545] border border-sky-400/30 text-white text-xs font-bold tracking-wider uppercase px-6 py-3.5 rounded-xl shadow-md hover:bg-[#0369A1] transition font-['Plus_Jakarta_Sans']">
                        <i class="fa-solid fa-bag-shopping text-xs"></i>
                        <span>LIHAT PROMO</span>
                    </a>
                    <div x-show="hasMultiple" class="flex items-center gap-2 ml-auto">
                        <button type="button" @click="prev()" aria-label="Promo sebelumnya"
                                class="w-9 h-9 rounded-full bg-white/10 border border-white/20 text-white hover:bg-white/20 transition cursor-pointer">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                        <div class="flex items-center gap-1.5">
                            <template x-for="(p, index) in promos" :key="'dot-' + (p.id ?? index)">
                                <button type="button" @click="goTo(index)" :aria-label="'Promo ' + (index + 1)"
                                        class="h-1.5 rounded-full transition-all cursor-pointer"
                                        :class="activeIndex === index ? 'w-6 bg-amber-300' : 'w-1.5 bg-white/40 hover:bg-white/70'"></button>
                            </template>
                        </div>
                        <button type="button" @click="next()" aria-label="Promo berikutnya"
                                class="w-9 h-9 rounded-full bg-white/10 border border-white/20 text-white hover:bg-white/20 transition cursor-pointer">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="!hasPromos" class="absolute inset-0">
                <img src="{{ asset('images/katalog1.webp') }}" alt="Promo Spesial Koleksi Sarimbit" class="absolute inset-0 w-full h-full object-cover object-center">
                <div class="absolute inset-0 bg-gradient-to-r from-[#0F1A24]/90 via-[#0F1A24]/60 to-transparent"></div>
            </div>

            <div x-show="!hasPromos" class="relative z-10 max-w-xl text-white">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900/60 border border-slate-700/60 backdrop-blur-md mb-4">
                    <span class="text-[10px] font-semibold text-slate-300 tracking-wider uppercase font-['Plus_Jakarta_Sans']">
                        ✦ EDISI TERBATAS
                    </span>
                </div>

                <h2 class="text-3xl md:text-5xl font-bold font-['Plus_Jakarta_Sans'] leading-[1.15] text-white mb-6">
                    Promo Spesial<br>
                    <span class="italic font-normal text-[#ADD8E6] font-['Playfair_Display']">Koleksi Sarimbit</span>
                </h2>

                <a href="/katalog?category=sarimbit" class="inline-flex items-center gap-2 bg-[#0B2545] border border-sky-400/30 text-white text-xs font-bold tracking-wider uppercase px-6 py-3.5 rounded-xl shadow-md hover:bg-[#0369A1] transition font-['Plus_Jakarta_Sans']">
                    <i class="fa-solid fa-bag-shopping text-xs"></i> BELI KOLEKSI SARIMBIT
                </a>
            </div>

        </div>
    </div>
</section>
