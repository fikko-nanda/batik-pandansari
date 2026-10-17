<section class="bg-white py-14 border-b border-slate-100">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-2 h-7 bg-[#0369A1] rounded-full"></div>
                <div>
                    <span class="text-[10px] font-bold text-[#0369A1] tracking-wider uppercase block font-['Plus_Jakarta_Sans']">KOLEKSI BUSANA WANITA</span>
                    <h2 class="text-2xl font-bold text-[#0F1A24] tracking-wide font-['Plus_Jakarta_Sans']">WOMEN'S WEAR</h2>
                </div>
            </div>
            <a href="#women" class="text-xs font-bold text-slate-800 uppercase px-5 py-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 font-['Plus_Jakarta_Sans']">
                VIEW ALL
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-product-card 
                badge="TERBARU"
                subtitle="SOLO • ATELIER WANITA"
                title="Tunik Batik Cheongsam"
                description="Aksen kerah cheongsam klasik oriental dengan motif burung hong prada..."
                price="Rp 1.280.000"
                material="Katun Primis Super"
                :image="asset('images/katalog1.webp')"
            />
            <x-product-card 
                badge="BEST SELLER"
                subtitle="PEKALONGAN • CASUAL CHIC"
                title="Blouse Indigo Teduh"
                description="Blouse peplum modis dengan potongan tangan lonceng anggun..."
                price="Rp 1.190.000"
                material="Katun Halus"
                :image="asset('images/katalog2.webp')"
            />
            <x-product-card 
                badge="EKSKLUSIF"
                subtitle="YOGYAKARTA • PAKEM RESMI"
                title="Dress Primissima Navy"
                description="Siluet A-line elegan dengan furing katun hero yang menyerap keringat..."
                price="Rp 1.450.000"
                material="Primis Halus"
                :image="asset('images/hero-bg.jpg')"
            />
            <x-product-card 
                badge="HANDMADE"
                subtitle="CIREBON • SUTRA ALAM"
                title="Kutubaru Megamendung"
                description="Kombinasi klasik kutubaru dengan motif megamendung gradasi warna..."
                price="Rp 1.620.000"
                material="Sutra Alam"
                :image="asset('images/katalog1.webp')"
            />
        </div>
    </div>
</section>