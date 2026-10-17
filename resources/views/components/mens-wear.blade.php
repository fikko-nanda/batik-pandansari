<section class="bg-white py-14 border-b border-slate-100">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-2 h-7 bg-[#0369A1] rounded-full"></div>
                <div>
                    <span class="text-[10px] font-bold text-[#0369A1] tracking-wider uppercase block font-['Plus_Jakarta_Sans']">KOLEKSI BUSANA PRIA</span>
                    <h2 class="text-2xl font-bold text-[#0F1A24] tracking-wide font-['Plus_Jakarta_Sans']">MEN'S WEAR</h2>
                </div>
            </div>
            <a href="#men" class="text-xs font-bold text-slate-800 uppercase px-5 py-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 font-['Plus_Jakarta_Sans']">
                VIEW ALL
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <x-product-card 
                badge="BEST SELLER"
                subtitle="SOLO • KEMEJA PRIA"
                title="Kemeja Parang Barong"
                description="Kemeja batik lengan panjang furing katun halus dengan potongan slimfit modern."
                price="Rp 850.000"
                material="Katun Primis"
                :image="asset('images/katalog2.webp')"
            />
            <x-product-card 
                badge="EKSKLUSIF"
                subtitle="YOGYAKARTA • HEM PRIA"
                title="Kemeja Sidomukti Klasik"
                description="Motif Sidomukti warna sogan tradisional cocok untuk agenda kerja dan acara resmi."
                price="Rp 920.000"
                material="Primisima Super"
                :image="asset('images/hero-bg.jpg')"
            />
            <x-product-card 
                badge="HANDMADE"
                subtitle="PEKALONGAN • KAIN"
                title="Kain Megamendung Indigo"
                description="Lembaran kain batik cap halus pekalongan warna indigo eksklusif."
                price="Rp 650.000"
                material="Katun Paris"
                :image="asset('images/katalog1.webp')"
            />
            <x-product-card 
                badge="POPULER"
                subtitle="SOLO • KEMEJA"
                title="Kemeja Kawung Elegant"
                description="Potongan regular fit dengan lapisan furing lembut anti gerah."
                price="Rp 780.000"
                material="Katun Halus"
                :image="asset('images/katalog2.webp')"
            />
        </div>
    </div>
</section>