@extends('layouts.app')

@section('content')

    <!-- Container Utama Wishlist / Produk Favorit (data dari GET /api/wishlist) -->
    <div class="bg-[#FAF8F5] min-h-screen py-4 sm:py-8 font-['Plus_Jakarta_Sans']"
         x-data="{
            items: [],
            loading: true,
            needLogin: false,
            errorMsg: '',
            msg: '',
            msgOk: false,
            busy: false,
            selectedCategory: '',
            fallbackImage: '{{ asset('images/katalog1.webp') }}',

            // Kategori dibangun dinamis dari data wishlist
            get categories() {
                const cats = { '': { label: 'Semua Koleksi', count: this.items.length } };
                for (const it of this.items) {
                    const key = it.product?.category?.nama_kategori ?? 'Lainnya';
                    if (!cats[key]) cats[key] = { label: key, count: 0 };
                    cats[key].count++;
                }
                return cats;
            },
            get filteredItems() {
                if (!this.selectedCategory) return this.items;
                return this.items.filter(it => (it.product?.category?.nama_kategori ?? 'Lainnya') === this.selectedCategory);
            },

            selectCategory(key) { this.selectedCategory = key; },

            // === Helper render ===
            formatRupiah(number) { return PandansariAPI.formatRp(number); },
            titleOf(it) { return it.product?.nama_produk ?? 'Produk'; },
            descOf(it) { return it.product?.deskripsi ?? ''; },
            imageOf(it) {
                return PandansariAPI.coverUrl(it.product?.images) || this.fallbackImage;
            },
            catOf(it) { return (it.product?.category?.nama_kategori ?? 'Batik Pandansari').toUpperCase(); },
            ukuranOf(it) {
                const u = (it.product?.variants ?? []).map(v => v.ukuran).filter(Boolean);
                return u.length ? 'Ukuran: ' + u.join(', ') : '';
            },
            // Harga: varian termurah dari varian aktif (null jika tidak ada varian)
            hargaOf(it) {
                const harga = (it.product?.variants ?? [])
                    .map(v => Number(v.harga ?? 0))
                    .filter(Boolean);
                return harga.length ? Math.min(...harga) : null;
            },
            variantsOf(it) { return it.product?.variants ?? []; },

            // === Loader ===
            async load() {
                this.loading = true;
                const res = await PandansariAPI.wishlist();
                this.loading = false;
                if (res.ok) this.items = res.data ?? [];
                else if (res.status === 401) this.needLogin = true;
                else this.errorMsg = res.message;
                return res;
            },

            // === Aksi ===
            async hapusItem(index) {
                const it = this.filteredItems[index];
                const res = await PandansariAPI.removeWishlist(it.id_wishlist_item ?? it.id_wishlist ?? it.id);
                if (res.ok) {
                    this.items = this.items.filter(x => x !== it);
                    this.tampil('Produk dihapus dari wishlist.', true);
                } else this.tampil(res.message, false);
            },
            async hapusSemua() {
                if (!this.items.length) return;
                if (!confirm('Hapus semua produk dari wishlist?')) return;
                this.busy = true;
                for (const it of [...this.items]) {
                    await PandansariAPI.removeWishlist(it.id_wishlist_item ?? it.id_wishlist ?? it.id);
                }
                this.busy = false;
                await this.load();
                this.tampil('Wishlist berhasil dikosongkan.', true);
            },
            async keKeranjang(it) {
                if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
                const variant = this.variantsOf(it)[0];
                if (!variant) { window.location.href = '/detail/' + (it.product?.id_product ?? ''); return; }
                const res = await PandansariAPI.addCartItem(variant.id_variant, 1);
                if (res.ok) { this.tampil('Ditambahkan ke keranjang!', true); window.location.href = '/cart'; }
                else this.tampil(res.message, false);
            },
            async pindahkanSemua() {
                if (!this.items.length) return;
                if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
                this.busy = true;
                let ok = 0;
                for (const it of this.items) {
                    const variant = this.variantsOf(it)[0];
                    if (!variant) continue;
                    const res = await PandansariAPI.addCartItem(variant.id_variant, 1);
                    if (res.ok) ok++;
                }
                this.busy = false;
                if (ok) window.location.href = '/cart';
                else this.tampil('Tidak ada produk dengan varian aktif.', false);
            },

            tampil(msg, ok) {
                this.msg = msg; this.msgOk = ok;
                setTimeout(() => { this.msg = ''; }, 4000);
            },

            async init() {
                if (!PandansariAPI.getToken()) { this.needLogin = true; this.loading = false; return; }
                await this.load();
            }
         }">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

            <!-- 1. Breadcrumb Navigation -->
            <nav class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-medium text-slate-500 mb-3 sm:mb-4 overflow-x-auto whitespace-nowrap">
                <a href="/" class="hover:text-slate-900 transition">Beranda</a>
                <span>/</span>
                <a href="/katalog" class="hover:text-slate-900 transition">Katalog</a>
                <span>/</span>
                <span class="text-slate-900 font-bold">Produk yang Disukai (Favorit)</span>
            </nav>

            <!-- 2. Page Header & Action Bar -->
            <div class="mb-6">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-slate-200/60 text-[#0369A1] text-[9px] sm:text-[10px] font-bold uppercase tracking-wider mb-2">
                    ✦ KOLEKSI PRIBADI
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-3xl font-bold text-[#0F1A24] tracking-tight">
                            Mahakarya yang Anda Sukai
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mt-1 leading-relaxed">
                            Daftar busana dan kain batik pilihan yang telah Anda beri tanda hati (favorit). Simpan untuk nanti atau pindahkan langsung ke keranjang belanja Anda.
                        </p>
                    </div>

                    <!-- Right Quick Actions -->
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
                        <!-- Indikator Jumlah Tersimpan -->
                        <div class="inline-flex items-center gap-1.5 px-3 py-2 bg-sky-50 text-[#0369A1] border border-sky-200 rounded-xl text-xs font-bold">
                            <i class="fa-solid fa-heart text-sky-500"></i>
                            <span><span x-text="items.length"></span> Mahakarya Tersimpan</span>
                        </div>

                        <!-- Tombol Hapus Semua -->
                        <button type="button" :disabled="busy || !items.length" @click="hapusSemua()"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer disabled:opacity-40">
                            <i class="fa-regular fa-trash-can text-xs"></i>
                            <span>Hapus Semua</span>
                        </button>

                        <!-- Tombol Pindahkan Semua ke Keranjang -->
                        <button type="button" :disabled="busy || !items.length" @click="pindahkanSemua()"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#0F1A24] hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer disabled:opacity-40">
                            <i class="fa-solid fa-bag-shopping text-xs"></i>
                            <span>Pindahkan Semua ke Keranjang</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Banner Status -->
            <template x-if="needLogin">
                <div class="bg-sky-50 border border-sky-200 text-[#0369A1] text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-3 mb-6">
                    <span><i class="fa-solid fa-lock mr-1.5"></i> Masuk untuk melihat wishlist Anda.</span>
                    <a href="/login" class="bg-[#0F1A24] text-white text-xs font-bold px-4 py-2 rounded-xl">MASUK →</a>
                </div>
            </template>
            <template x-if="errorMsg">
                <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3 flex items-center gap-2 mb-6">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span x-text="errorMsg"></span>
                </div>
            </template>
            <div x-show="msg" class="rounded-xl text-xs font-semibold px-4 py-3 flex items-center gap-2 mb-4"
                 :class="msgOk ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'">
                <i :class="msgOk ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                <span x-text="msg"></span>
            </div>

            <!-- 3. Filter Category Pills (dinamis) -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-6 text-[11px] sm:text-xs font-semibold font-['Plus_Jakarta_Sans']" x-show="!needLogin && items.length">
                <template x-for="(cat, key) in categories" :key="key">
                    <button @click="selectCategory(key)"
                            type="button"
                            class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl border transition whitespace-nowrap shadow-sm font-['Plus_Jakarta_Sans'] cursor-pointer"
                            :class="selectedCategory === key ? 'bg-[#BAE6FD] text-[#0F1A24] font-bold border-sky-300' : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'">
                        <span x-text="cat.label + ' (' + cat.count + ')'"></span>
                    </button>
                </template>
            </div>

            <!-- 4. Loading / Kosong -->
            <template x-if="loading && !needLogin">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                    <i class="fa-solid fa-circle-notch fa-spin text-xl text-sky-500"></i>
                    <p class="text-xs text-slate-500 mt-3 font-semibold">Memuat wishlist…</p>
                </div>
            </template>
            <template x-if="!loading && !needLogin && !items.length">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm py-16 text-center">
                    <i class="fa-regular fa-heart text-3xl text-slate-300 block mb-3"></i>
                    <p class="text-sm font-bold text-[#0F1A24]">Wishlist Anda masih kosong</p>
                    <p class="text-xs text-slate-500 mt-1">Tandai produk favorit Anda dari katalog.</p>
                    <a href="/katalog" class="inline-block mt-4 bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition">JELAJAHI KATALOG →</a>
                </div>
            </template>

            <!-- 5. Grid Wishlist Cards (3 Kolom Desktop / 2 Kolom Smartphone) -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-12" x-show="!needLogin && !loading">
                <template x-for="(it, index) in filteredItems" :key="it.id_wishlist_item ?? it.id_wishlist ?? it.id">
                    <div class="group bg-white border border-slate-200/80 rounded-xl sm:rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                        <div class="relative h-52 sm:h-80 bg-slate-100 overflow-hidden">
                            <a :href="'/detail/' + (it.product?.id_product ?? '')">
                                <img :src="imageOf(it)" :alt="titleOf(it)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out">
                            </a>

                            <!-- Badge Ukuran Tersedia -->
                            <span x-show="ukuranOf(it)" class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-md text-[#0F1A24] text-[9px] sm:text-[10px] font-bold px-2.5 py-1 rounded-md shadow-sm border border-slate-200 z-10 truncate max-w-[85%]" x-text="ukuranOf(it)"></span>

                            <!-- Icon Heart Merah Terisi (hapus dari wishlist) -->
                            <button type="button" @click="hapusItem(index)"
                                    class="absolute top-3 right-3 w-8 h-8 bg-white/90 backdrop-blur-md rounded-full flex items-center justify-center text-rose-500 shadow-md hover:scale-110 active:scale-95 transition-all duration-200 z-10 cursor-pointer"
                                    title="Hapus dari wishlist">
                                <i class="fa-solid fa-heart text-xs"></i>
                            </button>
                        </div>

                        <div class="p-3 sm:p-5 flex-1 flex flex-col justify-between">
                            <div>
                                <span class="text-[8px] sm:text-[9px] font-bold text-[#0369A1] uppercase tracking-wider block mb-1 line-clamp-1" x-text="catOf(it)"></span>
                                <a :href="'/detail/' + (it.product?.id_product ?? '')">
                                    <h3 class="font-bold text-[#0F1A24] text-xs sm:text-base mb-1 leading-snug group-hover:text-[#0369A1] transition-colors line-clamp-2" x-text="titleOf(it)"></h3>
                                </a>
                                <p class="text-[11px] sm:text-xs text-slate-500 line-clamp-2 mt-1" x-text="descOf(it)"></p>
                            </div>

                            <div class="mt-3 sm:mt-4 pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-2 mb-3">
                                    <!-- Harga varian termurah, atau ajak ke detail -->
                                    <template x-if="hargaOf(it) !== null">
                                        <span class="font-bold text-[#0F1A24] text-xs sm:text-lg" x-text="formatRupiah(hargaOf(it))"></span>
                                    </template>
                                    <template x-if="hargaOf(it) === null">
                                        <span class="text-xs text-slate-400 italic">Lihat Detail untuk harga</span>
                                    </template>
                                </div>

                                <!-- Tombol Aksi Keranjang & Trash -->
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="keKeranjang(it)"
                                            class="flex-1 bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] text-[11px] sm:text-xs font-bold py-2 sm:py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm cursor-pointer">
                                        <i class="fa-solid fa-cart-plus text-xs"></i>
                                        <span>+ Ke Keranjang</span>
                                    </button>
                                    <button type="button" @click="hapusItem(index)"
                                            class="w-9 h-9 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center transition shrink-0 cursor-pointer">
                                        <i class="fa-regular fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

@endsection
