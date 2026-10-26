<section class="bg-[#FAF8F5] py-12 md:py-16 border-b border-slate-100"
         x-data="{
            // Gambar cadangan bila kategori belum punya produk / gambar gagal dimuat.
            fallback: {
                pria: '{{ asset('images/katalog1.webp') }}',
                wanita: '{{ asset('images/katalog2.webp') }}',
                anak: '{{ asset('images/hero-bg.jpg') }}',
                kain: '{{ asset('images/katalog1.webp') }}',
            },
            covers: {
                pria: '{{ asset('images/katalog1.webp') }}',
                wanita: '{{ asset('images/katalog2.webp') }}',
                anak: '{{ asset('images/hero-bg.jpg') }}',
                kain: '{{ asset('images/katalog1.webp') }}',
            },
            _kategoriPromise: null,

            /** Ambil sampul aktual dari produk pertama tiap kategori. */
            async init() {
                const daftar = [
                    { slug: 'pria', nama: 'pakaian pria', targetSlug: 'pakaian-pria' },
                    { slug: 'wanita', nama: 'pakaian wanita', targetSlug: 'pakaian-wanita' },
                    { slug: 'anak', nama: 'pakaian anak-anak', targetSlug: 'pakaian-anak-anak' },
                    { slug: 'kain', nama: 'batik cap', targetSlug: 'batik-cap' },
                ];

                // Resolusi kategori dijalankan paralel dengan percobaan filter slug.
                this._kategoriPromise = PandansariAPI.categories();

                await Promise.all(daftar.map(k => this.muatSampul(k)));
            },

            async muatSampul(kategori) {
                try {
                    // Kontrak yang diminta: coba filter slug lebih dulu.
                    let categoryParam = kategori.slug;
                    let res = await PandansariAPI.products({
                        category: categoryParam,
                        page: 1,
                        per_page: 1,
                    });
                    let produk = res.ok ? (res.data ?? [])[0] : null;
                    let gambar = this.sampulProduk(produk);

                    // Backend saat ini memfilter `category` dengan id_category.
                    // Bila slug belum menghasilkan produk, resolve ke id_category lalu ulangi.
                    if (!produk) {
                        const id = await this.idKategori(kategori);
                        if (id) {
                            categoryParam = id;
                            res = await PandansariAPI.products({
                                category: categoryParam,
                                page: 1,
                                per_page: 1,
                            });
                            produk = res.ok ? (res.data ?? [])[0] : null;
                            gambar = this.sampulProduk(produk);
                        }
                    }

                    // Produk pertama bisa belum punya gambar → cari sampel berikutnya.
                    if (!gambar) {
                        res = await PandansariAPI.products({
                            category: categoryParam,
                            page: 1,
                            per_page: 5,
                        });
                        const list = res.ok ? (res.data ?? []) : [];
                        const sampel = list.find(p => this.sampulProduk(p));
                        if (sampel) gambar = this.sampulProduk(sampel);
                    }

                    if (gambar) this.covers[kategori.slug] = gambar;
                } catch {
                    // Biarkan fallback statis tetap tampil.
                }
            },

            async idKategori({ nama, targetSlug }) {
                try {
                    const res = await this._kategoriPromise;
                    if (!res?.ok) return '';

                    const list = Array.isArray(res.data) ? res.data : [];
                    const bySlug = list.find(k => String(k.slug ?? '').toLowerCase() === targetSlug);
                    if (bySlug) return bySlug.id_category;

                    const byNama = list.find(k => String(k.nama_kategori ?? '').toLowerCase().includes(nama));
                    return byNama?.id_category ?? '';
                } catch {
                    return '';
                }
            },

            /** Utamakan images produk (coverUrl → media proxy), lalu field gambar alternatif. */
            sampulProduk(produk) {
                if (!produk) return '';

                const dariImages = PandansariAPI.coverUrl(produk.images);
                if (dariImages) return dariImages;

                const langsung = produk.foto_sampul ?? produk.gambar ?? produk.foto ?? '';
                if (Array.isArray(langsung)) return PandansariAPI.coverUrl(langsung);
                return typeof langsung === 'string' ? PandansariAPI.imgUrl(langsung) : '';
            },

            /** Jika gambar produk 404, kembalikan ke gambar fallback kategori. */
            gambarGagal(event, slug) {
                const fallback = this.fallback[slug] ?? '';
                if (fallback && event.target.src !== fallback) event.target.src = fallback;
            }
         }">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        
        <!-- Header Judul Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
            <div>
                <span class="text-[11px] font-bold text-[#0369A1] tracking-widest uppercase block mb-1 font-['Plus_Jakarta_Sans']">
                    PILIHAN BERDASARKAN KATEGORI
                </span>
                <h2 class="text-2xl md:text-3xl font-bold text-[#0F1A24] font-['Plus_Jakarta_Sans']">
                    Koleksi Busana & Batik Pilihan
                </h2>
            </div>
            <p class="text-xs text-slate-500 max-w-md leading-relaxed font-['Plus_Jakarta_Sans']">
                Temukan keanggunan busana batik yang nyaman dengan potongan presisi untuk seluruh anggota keluarga.
            </p>
        </div>

        <!-- 4 Grid Kartu Kategori Sesuai Figma -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Kartu 1: Kemeja & Hem Pria → Katalog kategori Pria -->
            <a href="/katalog?category=pria"
               class="group cursor-pointer bg-white rounded-2xl overflow-hidden border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-lg hover:-translate-y-1 transition duration-200">
                <div class="relative h-64 bg-slate-100 overflow-hidden">
                    <img :src="covers.pria"
                          x-on:error="gambarGagal($event, 'pria')"
                          alt="Kemeja & Hem Pria"
                          loading="lazy" decoding="async"
                          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3 left-3 bg-[#0284C7] text-white text-[10px] font-semibold px-2.5 py-1 rounded-md uppercase tracking-wider font-['Plus_Jakarta_Sans']">
                        BUSANA PRIA
                    </span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-[#0F1A24] text-base mb-1 font-['Plus_Jakarta_Sans']">Kemeja & Hem Pria</h3>
                        <p class="text-xs text-slate-500 leading-relaxed font-['Plus_Jakarta_Sans']">
                            Kemeja lengan panjang dan pendek dengan furing katun adem berbobot premium.
                        </p>
                    </div>
                    <span class="mt-4 text-xs font-bold text-[#0369A1] inline-flex items-center gap-1.5 group-hover:underline font-['Plus_Jakarta_Sans']">
                        Lihat 48+ Koleksi Pria <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </span>
                </div>
            </a>

            <!-- Kartu 2: Dress & Tunik Elegan → Katalog kategori Wanita -->
            <a href="/katalog?category=wanita"
               class="group cursor-pointer bg-white rounded-2xl overflow-hidden border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-lg hover:-translate-y-1 transition duration-200">
                <div class="relative h-64 bg-slate-100 overflow-hidden">
                    <img :src="covers.wanita"
                          x-on:error="gambarGagal($event, 'wanita')"
                          alt="Dress & Tunik Elegan"
                          loading="lazy" decoding="async"
                          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3 left-3 bg-[#0284C7] text-white text-[10px] font-semibold px-2.5 py-1 rounded-md uppercase tracking-wider font-['Plus_Jakarta_Sans']">
                        BUSANA WANITA
                    </span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-[#0F1A24] text-base mb-1 font-['Plus_Jakarta_Sans']">Dress & Tunik Elegan</h3>
                        <p class="text-xs text-slate-500 leading-relaxed font-['Plus_Jakarta_Sans']">
                            Siluet feminin anggun sutra alam dan katun halus untuk acara formal maupun harian.
                        </p>
                    </div>
                    <span class="mt-4 text-xs font-bold text-[#0369A1] inline-flex items-center gap-1.5 group-hover:underline font-['Plus_Jakarta_Sans']">
                        Lihat 62+ Koleksi Wanita <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </span>
                </div>
            </a>

            <!-- Kartu 3: Batik Anak-Anak & Remaja → Katalog kategori Anak -->
            <a href="/katalog?category=anak"
               class="group cursor-pointer bg-white rounded-2xl overflow-hidden border border-sky-200/80 shadow-sm flex flex-col justify-between bg-sky-50/20 hover:shadow-lg hover:-translate-y-1 transition duration-200">
                <div class="relative h-64 bg-sky-50 overflow-hidden">
                    <img :src="covers.anak"
                          x-on:error="gambarGagal($event, 'anak')"
                          alt="Batik Anak-Anak & Remaja"
                          loading="lazy" decoding="async"
                          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3 left-3 bg-[#0284C7] text-white text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider inline-flex items-center gap-1 font-['Plus_Jakarta_Sans']">
                        <i class="fa-solid fa-child text-[9px]"></i> KOLEKSI ANAK
                    </span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-[#0F1A24] text-base mb-1 font-['Plus_Jakarta_Sans']">Batik Anak-Anak & Remaja</h3>
                        <p class="text-xs text-slate-500 leading-relaxed font-['Plus_Jakarta_Sans']">
                            Kemeja cilik, dress anak, dan sarimbit keluarga mini dengan bahan katun lembut tanpa gatal.
                        </p>
                    </div>
                    <span class="mt-4 text-xs font-bold text-[#0369A1] inline-flex items-center gap-1.5 group-hover:underline font-['Plus_Jakarta_Sans']">
                        Lihat 24+ Koleksi Anak <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </span>
                </div>
            </a>

            <!-- Kartu 4: Kain Tradisional Lembaran → Katalog kategori Batik Cap (badge BATIK CAP) -->
            <a href="/katalog?category=cap"
               class="group cursor-pointer bg-white rounded-2xl overflow-hidden border border-slate-200/80 shadow-sm flex flex-col justify-between hover:shadow-lg hover:-translate-y-1 transition duration-200">
                <div class="relative h-64 bg-slate-100 overflow-hidden">
                    <img :src="covers.kain"
                          x-on:error="gambarGagal($event, 'kain')"
                          alt="Kain Tradisional Lembaran"
                          loading="lazy" decoding="async"
                          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    <span class="absolute top-3 left-3 bg-[#0284C7] text-white text-[10px] font-semibold px-2.5 py-1 rounded-md uppercase tracking-wider font-['Plus_Jakarta_Sans']">
                        BATIK CAP
                    </span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-[#0F1A24] text-base mb-1 font-['Plus_Jakarta_Sans']">Kain Tradisional Lembaran</h3>
                        <p class="text-xs text-slate-500 leading-relaxed font-['Plus_Jakarta_Sans']">
                            Kain panjang primisima, sutra baron, dan paris asli karya empu pembatik sentra Nusantara.
                        </p>
                    </div>
                    <span class="mt-4 text-xs font-bold text-[#0369A1] inline-flex items-center gap-1.5 group-hover:underline font-['Plus_Jakarta_Sans']">
                        Lihat 75+ Kain Tradisional <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </span>
                </div>
            </a>

        </div>
    </div>
</section>
