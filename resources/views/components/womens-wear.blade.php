{{-- KOLEKSI BUSANA WANITA — kartu produk dari GET /api/products.
     Strategi: ambil halaman 2 lebih dulu; bila kosong (katalog masih kecil),
     fallback ke halaman 1 supaya section ini TETAP tampil isi produk. --}}
<section class="bg-white py-14 border-b border-slate-100"
         x-data="{
            produk: [],
            loading: true,
            wishlisted: {},
            fallbackImage: '{{ asset('images/katalog1.webp') }}',

            // --- Helper kartu (sama seperti katalog & section pria) ---
            coverOf(p) {
                const img = (p.images ?? []).find(i => i.url);
                return img?.url ?? this.fallbackImage;
            },
            priceOf(p) {
                const v = (p.variants ?? []).filter(x => x.status_varian !== 'nonaktif' && x.harga != null);
                if (!v.length) return 'Lihat Detail';
                return PandansariAPI.formatRp(Math.min(...v.map(x => Number(x.harga))));
            },
            sizesOf(p) {
                const v = (p.variants ?? []).map(x => x.ukuran).filter(Boolean);
                return v.length ? [...new Set(v)].join(', ') : '';
            },
            async toggleWishlist(p) {
                if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
                if (this.wishlisted[p.id_product]) return;
                const res = await PandansariAPI.addWishlist(p.id_product);
                if (res.ok || res.status === 422) this.wishlisted[p.id_product] = true; // 422 = sudah ada
            },
            async keKeranjang(p) {
                const res = await PandansariAPI.keKeranjangProduk(p.id_product);
                if (res.login) { window.location.href = '/login'; return; }
                PandansariAPI.toast(res.ok ? 'Ditambahkan ke keranjang!' : (res.message ?? 'Gagal menambahkan.'), res.ok);
            },

            async init() {
                // Utamakan halaman 2 (produk lanjutan); bila kosong → fallback halaman 1
                let res = await PandansariAPI.products({ page: 2, per_page: 4 });
                let data = res.ok ? (res.data ?? []) : [];
                if (!data.length) {
                    res = await PandansariAPI.products({ page: 1, per_page: 4 });
                    data = res.ok ? (res.data ?? []) : [];
                }
                this.loading = false;
                this.produk = data;
            }
         }">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="flex justify-between items-center border-b border-slate-200 pb-3 mb-8">
            <div class="flex items-center gap-3">
                <div class="w-2 h-7 bg-[#0369A1] rounded-full"></div>
                <div>
                    <span class="text-[10px] font-bold text-[#0369A1] tracking-wider uppercase block font-['Plus_Jakarta_Sans']">KOLEKSI BUSANA WANITA</span>
                    <h2 class="text-2xl font-bold text-[#0F1A24] tracking-wide font-['Plus_Jakarta_Sans']">WOMEN'S WEAR</h2>
                </div>
            </div>
            <a href="/katalog" class="text-xs font-bold text-slate-800 uppercase px-5 py-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 font-['Plus_Jakarta_Sans']">
                VIEW ALL
            </a>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <template x-for="i in 4" :key="i">
                <div class="bg-slate-50 rounded-2xl h-80 animate-pulse"></div>
            </template>
        </div>

        <!-- Kosong (backend belum punya produk sama sekali) -->
        <div x-show="!loading && !produk.length" class="bg-slate-50 rounded-2xl py-10 text-center">
            <p class="text-sm font-bold text-[#0F1A24]">Belum ada produk tersedia</p>
            <a href="/katalog" class="inline-block mt-3 bg-[#0F1A24] text-white text-xs font-bold px-5 py-2.5 rounded-xl hover:bg-slate-800 transition">LIHAT KATALOG →</a>
        </div>

        <!-- Grid Produk (kartu sama dengan katalog & section pria) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" x-show="!loading && produk.length">
            <template x-for="p in produk" :key="p.id_product">
                <x-shop.product-item />
            </template>
        </div>
    </div>
</section>
