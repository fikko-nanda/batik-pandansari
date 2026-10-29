@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="checkoutPage">

    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">

        <!-- Breadcrumb & Step Progress Indicator -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <a href="/" class="hover:text-black">Beranda</a>
                <span>/</span>
                <a href="/cart" class="hover:text-black">Keranjang</a>
                <span>/</span>
                <span class="text-black font-bold">Pengiriman & Logistik</span>
            </nav>

            <!-- Stepper -->
            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="flex items-center gap-1.5 text-emerald-600">
                    <i class="fa-solid fa-circle-check"></i>1. Keranjang
                </span>
                <span class="text-slate-300">—</span>
                <span class="flex items-center gap-1.5 bg-[#BAE6FD] text-[#0369A1] px-3 py-1 rounded-full font-bold">
                    2. Pengiriman & Konfirmasi
                </span>
                <span class="text-slate-300">—</span>
                <span class="text-slate-400">3. Pembayaran Midtrans</span>
            </div>
        </div>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDE: Form Alamat & Informasi (8 Kolom) -->
            <div class="lg:col-span-8 space-y-6">

                <div>
                    <span class="text-[10px] font-bold tracking-wider uppercase text-slate-500 block mb-1">PENGIRIMAN & ALAMAT PENERIMA</span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Pengiriman & Alamat Penerima</h1>
                    <p class="text-xs text-slate-500 mt-1">Lengkapi rincian alamat penerima. Setelah konfirmasi via WhatsApp, Anda akan diarahkan ke pembayaran digital Midtrans.</p>
                </div>

                <!-- Box 1: Form Alamat Penerima -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24] border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-map-location-dot text-sky-600 text-sm"></i>
                        <span>Alamat Pengiriman</span>
                    </div>

                    <div class="space-y-3">
                        <template x-if="!loading && !addresses.length">
                            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-3">
                                <span><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Anda belum memiliki alamat tersimpan.</span>
                                <a href="/address" class="bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg whitespace-nowrap">+ Tambah Alamat Baru</a>
                            </div>
                        </template>

                        <template x-for="a in addresses" :key="a.id_user_address || a.id">
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border-2 transition cursor-pointer"
                                   :class="(selectedAddressId === (a.id_user_address || a.id)) ? 'border-sky-500 bg-sky-50/40 ring-1 ring-sky-200' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                <input type="radio" name="alamat" :value="a.id_user_address || a.id" :checked="selectedAddressId === (a.id_user_address || a.id)" @change="selectedAddressId = (a.id_user_address || a.id); alamatAktif = a; hitungOngkir()" class="mt-0.5 text-sky-600 focus:ring-sky-500">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#0F1A24]" x-text="a.label_alamat || 'Alamat'"></span>
                                        <span x-show="a.alamat_utama ?? a.is_primary" class="text-[9px] bg-sky-100 text-[#0369A1] font-bold px-1.5 py-0.5 rounded uppercase">UTAMA</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed mt-0.5">
                                        <strong x-text="a.nama_penerima"></strong> • <span x-text="a.nomor_telepon || a.telepon"></span><br>
                                        <span x-text="a.alamat_lengkap"></span>, <span x-text="a.nama_kecamatan"></span>, <span x-text="a.nama_kota"></span>, <span x-text="a.nama_provinsi"></span> <span x-text="a.kode_pos"></span>
                                    </p>
                                    <p x-show="!a.latitude || !a.longitude" class="mt-1 text-[10px] font-semibold text-amber-700">Koordinat belum dipilih. Lengkapi lokasi di <a href="/address" class="underline">buku alamat</a> untuk cek ongkir.</p>
                                </div>
                            </label>
                        </template>

                        <p class="text-[11px] text-slate-400 text-right">
                            Kelola daftar alamat di <a href="/address" class="text-sky-600 font-bold hover:underline">/address</a> →
                        </p>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Patokan / Catatan Alamat (opsional)</label>
                            <input type="text" x-model="catatan" placeholder="Patokan / catatan alamat..."
                                   class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                        <div class="rounded-xl border border-sky-100 bg-sky-50/70 p-3 space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold text-slate-700">Ongkos Kirim OpenStreetMap</p>
                                    <p class="text-[10px] text-slate-500" x-show="shippingQuote">
                                        <span x-text="Number(shippingQuote?.jarak_km ?? 0).toFixed(2)"></span> km ·
                                        <span x-text="shippingQuote?.zona?.nama_zona"></span>
                                    </p>
                                </div>
                                <button type="button" @click="hitungOngkir()" :disabled="shippingBusy || !selectedAddressId"
                                        class="rounded-lg bg-sky-700 px-3 py-2 text-[10px] font-bold text-white disabled:opacity-50">
                                    <span x-show="!shippingBusy">Cek Ongkir</span>
                                    <span x-show="shippingBusy">Menghitung...</span>
                                </button>
                            </div>
                            <p x-show="shippingError" class="text-[11px] text-rose-700" x-text="shippingError"></p>
                            <p x-show="shippingQuote" class="text-[11px] text-slate-600">
                                Berat <span x-text="shippingQuote?.berat_dibulatkan_kg"></span> kg ·
                                Ongkir <strong class="text-slate-900" x-text="formatRupiah(shippingQuote?.biaya_pengiriman)"></strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Box 2: Panduan Alur Konfirmasi & Pembayaran Midtrans -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24] border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-route text-sky-600 text-sm"></i>
                        <span>Alur Pemesanan & Pembayaran</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl space-y-1">
                            <span class="font-extrabold text-sky-700 block">1. Buat Pesanan</span>
                            <p class="text-[11px] text-slate-500 leading-normal">Klik tombol di samping untuk membuat pesanan resmi di website.</p>
                        </div>
                        <div class="p-3 bg-emerald-50 border border-emerald-200/80 rounded-xl space-y-1">
                            <span class="font-extrabold text-emerald-700 block"><i class="fa-brands fa-whatsapp"></i> 2. Konfirmasi WA</span>
                            <p class="text-[11px] text-emerald-800 leading-normal">Kirim detail pesanan & alamat ke Admin WhatsApp untuk konfirmasi stok & ongkir.</p>
                        </div>
                        <div class="p-3 bg-sky-50 border border-sky-200/80 rounded-xl space-y-1">
                            <span class="font-extrabold text-sky-700 block"><i class="fa-solid fa-credit-card"></i> 3. Bayar Midtrans</span>
                            <p class="text-[11px] text-sky-800 leading-normal">Kembali ke halaman website untuk menyelesaikan pembayaran via Midtrans (QRIS/VA).</p>
                        </div>
                    </div>
                </div>

                <!-- Alert Info Ongkos Kirim -->
                <div class="bg-amber-50/80 border border-amber-200/90 rounded-2xl p-5 sm:p-6 flex items-start gap-3 shadow-sm">
                    <i class="fa-solid fa-truck-ramp-box text-amber-600 text-base mt-0.5"></i>
                    <div>
                        <h3 class="text-xs font-extrabold text-amber-800">Informasi Ongkos Kirim</h3>
                        <p class="text-[11px] text-amber-800/90 leading-relaxed mt-1">
                            Seluruh biaya pengiriman (ongkir) sepenuhnya ditanggung oleh pembeli. Tarif ongkir akan dihitung
                            secara akurat dan dikonfirmasikan langsung oleh Admin kami via WhatsApp sesuai alamat tujuan
                            serta ekspedisi terpilih.
                        </p>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDE: Ringkasan Pesanan (4 Kolom Sticky) -->
            <div class="lg:col-span-4 lg:sticky lg:top-6 space-y-4">
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    
                    <template x-if="loading && !errorMsg && !needLogin">
                        <div class="py-8 text-center"><i class="fa-solid fa-circle-notch fa-spin text-xl text-sky-500"></i><p class="text-xs text-slate-500 mt-3 font-semibold">Memuat data checkout…</p></div>
                    </template>
                    <template x-if="needLogin">
                        <div class="bg-sky-50 border border-sky-200 text-[#0369A1] text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-2">
                            <span><i class="fa-solid fa-lock mr-1"></i> Silakan masuk terlebih dahulu.</span>
                            <a href="/login" class="bg-[#0F1A24] text-white px-3 py-1.5 rounded-lg text-[11px] font-bold">MASUK</a>
                        </div>
                    </template>
                    <template x-if="errorMsg">
                        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold rounded-xl px-4 py-3">
                            <span x-text="errorMsg"></span>
                        </div>
                    </template>

                    <div x-show="!loading && !needLogin" class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-bold text-[#0F1A24]">Ringkasan Pesanan <span class="text-xs text-slate-400 font-normal">(<span x-text="itemCount"></span> Item)</span></h2>
                            <a href="/cart" class="text-xs text-sky-600 font-semibold hover:underline">Ubah</a>
                        </div>

                        <!-- Daftar Produk -->
                        <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto pr-1">
                            <template x-for="item in items" :key="item.id_cart_item || item.id">
                                <div class="py-3 flex gap-3 items-center">
                                    <img :src="imageOf(item)" :alt="titleOf(item)" class="w-12 h-12 rounded-lg object-cover border border-slate-100 flex-shrink-0 bg-slate-50" :onerror="`this.src='${fallbackImage}'`">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-xs font-semibold text-[#0F1A24] truncate" x-text="titleOf(item)"></h3>
                                        <p class="text-[10px] text-slate-500" x-text="variantLabel(item)"></p>
                                        <p class="text-xs font-bold text-slate-800 mt-0.5">
                                            <span x-text="item.jumlah"></span> x <span x-text="formatRupiah(item.variant?.harga)"></span>
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Voucher Input -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <label class="block text-[11px] font-semibold text-slate-600">Kode Voucher</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="voucherCode" placeholder="Masukkan kode..." class="uppercase flex-1 text-xs border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <button type="button" @click="applyVoucher()" :disabled="busyVoucher" class="bg-[#0F1A24] text-white text-xs font-bold px-3 py-2 rounded-xl hover:bg-slate-800 transition disabled:opacity-50">
                                    <span x-show="!busyVoucher">Gunakan</span>
                                    <span x-show="busyVoucher"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                                </button>
                            </div>
                            <div x-show="voucherMsg" class="text-[10px] font-medium mt-1" :class="voucherOk ? 'text-emerald-600' : 'text-rose-500'" x-text="voucherMsg"></div>
                        </div>

                        <!-- Rincian Biaya -->
                        <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal Produk</span>
                                <span class="font-semibold text-slate-800" x-text="formatRupiah(subtotal)"></span>
                            </div>
                            <div x-show="discountAmount > 0" class="flex justify-between text-emerald-600 font-medium">
                                <span>Diskon Voucher</span>
                                <span>-<span x-text="formatRupiah(discountAmount)"></span></span>
                            </div>
                            <div class="flex justify-between text-slate-600 font-medium">
                                <span>Pajak PPN (11%)</span>
                                <span>+<span x-text="formatRupiah(ppnAmount)"></span></span>
                            </div>
                            <div class="flex justify-between text-slate-600 font-medium">
                                <span>Ongkos Kirim</span>
                                <span>+<span x-text="formatRupiah(shippingQuote?.biaya_pengiriman ?? 0)"></span></span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-[#0F1A24] pt-2 border-t border-slate-100">
                                <span>Total Sementara</span>
                                <span class="text-sky-600" x-text="formatRupiah(totalPembayaran)"></span>
                            </div>
                        </div>

                        <!-- Pesan Submit Error -->
                        <div x-show="submitMsg" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl" x-text="submitMsg"></div>

                        <!-- Tombol Buat Pesanan & Konfirmasi WA -->
                        <button type="button" @click="handleCheckout()" :disabled="isLoading"
                                class="w-full bg-[#25D366] hover:bg-[#20ba5a] text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-emerald-500/20 transition cursor-pointer flex items-center justify-center gap-2 text-xs uppercase tracking-wider disabled:opacity-60">
                            <span x-show="!isLoading" class="flex items-center gap-2">
                                <i class="fa-brands fa-whatsapp text-base"></i> Buat Pesanan &amp; Konfirmasi WA
                            </span>
                            <span x-show="isLoading" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i> Memproses Pesanan...
                            </span>
                        </button>

                        <p class="text-[10px] text-slate-400 text-center leading-relaxed">
                            Sistem akan membuka WhatsApp Admin untuk konfirmasi, lalu mengarahkan Anda kembali ke halaman website untuk pembayaran via Midtrans.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    /**
     * Komponen Alpine.js Halaman Checkout
     * Mengelola pemilihan alamat, ringkasan keranjang, voucher promo,
     * dan checkout pesanan (WhatsApp konfirmasi & pembayaran Midtrans).
     */
    function checkoutPage() {
        return {
            // === State dari API ===
            addresses: [],
            selectedAddressId: null,
            alamatAktif: null,
            items: [],
            voucher: null,
            shippingQuote: null,
            shippingError: '',
            shippingErrorCode: '',
            shippingBusy: false,

            // === State UI ===
            loading: true,
            needLogin: false,
            errorMsg: '',
            catatan: '',
            isLoading: false,
            submitMsg: '',
            submitOk: false,
            fallbackImage: '{{ asset('images/katalog1.webp') }}',

            voucherCode: '',
            voucherMsg: '',
            voucherOk: false,
            busyVoucher: false,

            // Nomor WhatsApp admin untuk konfirmasi
            waNumber: '6283820380977',

            voucherList: [],
            voucherListPesan: '',

            // === Getters & Komputasi ===
            get selectedAddress() {
                return (this.addresses || []).find(a => (a.id_user_address || a.id) === this.selectedAddressId) ?? null;
            },

            get subtotal() {
                return (this.items || []).reduce((s, i) => s + (Number(i.variant?.harga ?? 0) * Number(i.jumlah ?? 1)), 0);
            },

            get discountAmount() {
                if (!this.voucher) return 0;
                let d = this.voucher.tipe_diskon === 'persentase'
                    ? (this.subtotal * Number(this.voucher.nilai_diskon)) / 100
                    : Number(this.voucher.nilai_diskon);
                if (this.voucher.maksimal_diskon) {
                    d = Math.min(d, Number(this.voucher.maksimal_diskon));
                }
                return Math.round(Math.min(d, this.subtotal));
            },

            // Pajak Penjualan (PPN) 11% — dihitung dari dasar setelah diskon (subtotal − diskon)
            get ppnAmount() {
                return Math.round((this.subtotal - this.discountAmount) * 0.11);
            },

            get totalPembayaran() {
                // Subtotal − Diskon + PPN 11%
                return Math.max(0, (this.subtotal - this.discountAmount) + this.ppnAmount
                    + Number(this.shippingQuote?.biaya_pengiriman ?? 0));
            },

            get itemCount() {
                return (this.items || []).reduce((s, i) => s + Number(i.jumlah ?? 1), 0);
            },

            // === Helper Render ===
            formatRupiah(number) {
                try {
                    return typeof PandansariAPI?.formatRp === 'function'
                        ? PandansariAPI.formatRp(number)
                        : ('Rp ' + Number(number ?? 0).toLocaleString('id-ID'));
                } catch (e) {
                    return 'Rp ' + Number(number ?? 0).toLocaleString('id-ID');
                }
            },

            titleOf(i) {
                return i?.variant?.product?.nama_produk ?? 'Produk';
            },

            variantLabel(i) {
                const v = i?.variant;
                if (!v) return '';
                return [v.ukuran && ('Size ' + v.ukuran), v.warna].filter(Boolean).join(' • ');
            },

            priceOf(i) {
                return Number(i?.variant?.harga ?? 0) * Number(i?.jumlah ?? 1);
            },

            imageOf(i) {
                try {
                    if (typeof PandansariAPI?.getProductImage === 'function') {
                        const img = PandansariAPI.getProductImage(i);
                        if (img) return img;
                    }
                    if (typeof PandansariAPI?.coverUrl === 'function') {
                        const cover = PandansariAPI.coverUrl(i?.variant?.product?.images);
                        if (cover) return cover;
                    }
                } catch (e) {
                    console.warn('[Checkout] Error imageOf:', e);
                }
                return this.fallbackImage;
            },

            // === Loader ===
            async loadCart() {
                try {
                    if (!window.PandansariAPI || typeof PandansariAPI.cart !== 'function') {
                        throw new Error('PandansariAPI.cart tidak tersedia.');
                    }
                    const res = await PandansariAPI.cart();
                    if (res && res.ok) {
                        const cart = res.data ?? {};
                        this.items = Array.isArray(cart.items) ? cart.items : (Array.isArray(cart) ? cart : []);
                    } else if (res && res.status === 401) {
                        this.needLogin = true;
                        this.items = [];
                    } else {
                        this.errorMsg = res?.message || 'Gagal memuat keranjang belanja.';
                    }
                    return res;
                } catch (err) {
                    console.error('[Checkout] Error loadCart:', err);
                    this.errorMsg = 'Gagal memuat keranjang belanja.';
                    return { ok: false, message: this.errorMsg };
                }
            },

            async loadAddresses() {
                try {
                    if (!window.PandansariAPI || typeof PandansariAPI.addresses !== 'function') {
                        throw new Error('PandansariAPI.addresses tidak tersedia.');
                    }
                    const res = await PandansariAPI.addresses();
                    if (res && res.ok) {
                        this.addresses = res.data?.data ?? res.data ?? [];
                        const utama = this.addresses.find(a => a.alamat_utama ?? a.is_primary) ?? this.addresses[0];
                        if (utama) {
                            this.alamatAktif = utama;
                            this.selectedAddressId = utama.id_user_address || utama.id;
                        }
                    }
                    return res;
                } catch (err) {
                    console.error('[Checkout] Error loadAddresses:', err);
                    return { ok: false };
                }
            },

            async hitungOngkir() {
                this.shippingQuote = null;
                this.shippingError = '';
                this.shippingErrorCode = '';
                if (!this.selectedAddressId) {
                    return false;
                }

                this.shippingBusy = true;
                try {
                    const res = await PandansariAPI.shippingQuote(this.selectedAddressId);
                    if (!res?.ok) {
                        this.shippingError = res?.message || 'Ongkos kirim belum dapat dihitung.';
                        this.shippingErrorCode = res?.code || '';
                        return false;
                    }

                    this.shippingQuote = res.data;
                    return true;
                } catch (error) {
                    this.shippingError = 'Layanan cek ongkir tidak dapat dihubungi.';
                    return false;
                } finally {
                    this.shippingBusy = false;
                }
            },

            async applyVoucher() {
                if (!this.voucherCode.trim()) return;
                this.busyVoucher = true;
                try {
                    const res = await PandansariAPI.validateVoucher(this.voucherCode.trim().toUpperCase(), this.subtotal);
                    if (res && res.ok) {
                        this.voucher = res.data;
                        this.voucherOk = true;
                        this.voucherMsg = res.message;
                    } else {
                        this.voucher = null;
                        this.voucherOk = false;
                        this.voucherMsg = res?.message || 'Kode voucher tidak valid.';
                    }
                } catch (err) {
                    console.error('[Checkout] Error applyVoucher:', err);
                    this.voucher = null;
                    this.voucherOk = false;
                    this.voucherMsg = 'Gagal memvalidasi voucher.';
                } finally {
                    this.busyVoucher = false;
                }
            },

            hapusVoucher() {
                this.voucher = null;
                this.voucherMsg = '';
                this.voucherOk = false;
                this.voucherCode = '';
            },

            async muatVoucher() {
                try {
                    if (!window.PandansariAPI || typeof PandansariAPI.availableVouchers !== 'function') return;
                    const r = await PandansariAPI.availableVouchers(this.subtotal);
                    if (r && r.ok) {
                        this.voucherList = (r.data ?? []).filter(v => {
                            const st = String(v.status_voucher ?? v.status ?? '').toLowerCase();
                            return !st || ['aktif', 'dipublikasikan', 'published', 'active'].includes(st);
                        });
                        this.voucherListPesan = this.voucherList.length ? '' : 'Belum ada voucher tersedia.';
                    } else {
                        this.voucherList = [];
                        this.voucherListPesan = r?.message || '';
                    }
                } catch (err) {
                    console.error('[Checkout] Error muatVoucher:', err);
                }
            },

            // === Format Pesan WhatsApp ===
            waMessage(order) {
                const alamat = this.alamatAktif;
                const user = (window.PandansariAPI && PandansariAPI.getUser && PandansariAPI.getUser()) || {};
                const orderNumber = order?.nomor_pesanan ?? order?.id_order ?? order?.id ?? '-';
                const orderId = order?.id_order ?? order?.id ?? orderNumber;

                const lines = [];
                lines.push('Halo Batik Pandansari, saya telah membuat pesanan di website.');
                lines.push('');
                lines.push('Nomor Pesanan: ' + orderNumber);
                lines.push('Nama: ' + (alamat?.nama_penerima || user.nama_pengguna || user.nama || '-'));
                lines.push('No. HP: ' + (alamat?.nomor_telepon || user.nomor_telepon || '-'));
                if (alamat) {
                    const alamatLengkap = [
                        alamat.alamat_lengkap,
                        alamat.nama_kecamatan,
                        alamat.nama_kota,
                        alamat.nama_provinsi,
                        alamat.kode_pos,
                    ].filter(Boolean).join(', ');
                    lines.push('Alamat: ' + (alamatLengkap || '-'));
                }
                lines.push('');
                lines.push('Item Pesanan:');
                (this.items || []).forEach((item, index) => {
                    const nama = this.titleOf(item);
                    const varian = this.variantLabel(item);
                    const jumlah = Number(item.jumlah ?? 1);
                    const harga = this.formatRupiah(this.priceOf(item));
                    lines.push(`${index + 1}. ${nama}${varian ? ' (' + varian + ')' : ''} x${jumlah} — ${harga}`);
                });
                lines.push('');
                lines.push('Subtotal: ' + this.formatRupiah(this.subtotal));
                if (this.discountAmount > 0) {
                    lines.push('Diskon Voucher: -' + this.formatRupiah(this.discountAmount));
                }
                lines.push('PPN 11%: +' + this.formatRupiah(this.ppnAmount));
                lines.push('Ongkos Kirim: +' + this.formatRupiah(this.shippingQuote?.biaya_pengiriman ?? 0));
                if (this.shippingQuote) {
                    lines.push('Jarak Pengiriman: ' + Number(this.shippingQuote.jarak_km).toFixed(2) + ' km');
                    lines.push('Zona Pengiriman: ' + this.shippingQuote.zona.nama_zona);
                }
                lines.push('Total Sementara: ' + this.formatRupiah(this.totalPembayaran));
                if (this.catatan.trim()) {
                    lines.push('Catatan: ' + this.catatan.trim());
                }
                lines.push('');
                lines.push('Tautan Pembayaran Midtrans Anda:');
                lines.push(window.location.origin + '/orders/' + orderId);
                lines.push('');
                lines.push('Mohon konfirmasi pesanan & ongkirnya. Terima kasih!');
                return lines.join('\n');
            },

            // === Buka WA & Redirect Kembali ke Website untuk Pembayaran Midtrans ===
            openWhatsAppAndRedirect(order) {
                const orderId = order?.id_order ?? order?.id ?? order?.nomor_pesanan;
                const waUrl = 'https://wa.me/' + this.waNumber + '?text=' + encodeURIComponent(this.waMessage(order));

                // 1. Buka Chat WhatsApp Admin di Tab Baru
                window.open(waUrl, '_blank', 'noopener');

                // 2. Redirect Halaman Lokal ke Detail Pesanan / Midtrans Payment
                const targetUrl = orderId ? `/orders/${orderId}` : '/orders';
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 1000);
            },

            // === Submit Checkout ===
            async handleCheckout() {
                if (!window.PandansariAPI || !PandansariAPI.getToken()) {
                    window.location.href = '/login';
                    return;
                }
                this.submitMsg = '';
                this.submitOk = false;

                if (!this.selectedAddressId) {
                    this.submitMsg = 'Pilih alamat pengiriman terlebih dahulu.';
                    return;
                }
                if (!this.items.length) {
                    this.submitMsg = 'Keranjang Anda kosong.';
                    return;
                }

                if (!await this.hitungOngkir()) {
                    this.submitMsg = this.shippingError || 'Ongkos kirim belum dapat dihitung.';
                    if (this.shippingErrorCode === 'SHIPPING_ORIGIN_NOT_CONFIGURED') {
                        window.alert('Lokasi asal pengiriman belum dikonfigurasi oleh admin.');
                    }
                    return;
                }

                // Validasi stok: hanya blokir jika kuantitas melebihi stok yang ada (quantity > stock)
                const overStock = this.items.find(i => i.variant?.stok != null && Number(i.jumlah) > Number(i.variant.stok));
                if (overStock) {
                    this.submitMsg = 'Jumlah pesanan untuk "' + this.titleOf(overStock) + '" melebihi stok yang tersedia (' + overStock.variant.stok + '). Mohon sesuaikan kuantitas di keranjang.';
                    return;
                }
                const zeroStock = this.items.find(i => i.variant?.stok != null && Number(i.variant.stok) <= 0);
                if (zeroStock) {
                    this.submitMsg = 'Produk "' + this.titleOf(zeroStock) + '" telah habis. Silakan hapus item tersebut dari keranjang sebelum memesan.';
                    return;
                }

                const payload = {
                    id_user_address: this.selectedAddressId,
                    kode_voucher: this.voucherCode.trim() || undefined,
                    catatan: this.catatan.trim() || undefined,
                    is_manual_shipping: true,
                    shipping_cost: 0,
                };

                this.isLoading = true;
                try {
                    const res = await PandansariAPI.checkout(payload);
                    if (!res.ok) {
                        if (res.status === 401) {
                            this.submitMsg = 'Sesi Anda berakhir. Silakan login kembali.';
                            setTimeout(() => { window.location.href = '/login'; }, 1200);
                        } else {
                            this.submitMsg = res.message || 'Pesanan gagal dibuat. Silakan coba lagi.';
                        }
                        return;
                    }

                    this.submitOk = true;
                    this.openWhatsAppAndRedirect(res.data ?? {});
                } catch (err) {
                    console.error('[Checkout] Error checkout:', err);
                    this.submitMsg = 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.';
                } finally {
                    this.isLoading = false;
                }
            },

            // Alias method untuk submit/checkout
            checkout() {
                return this.handleCheckout();
            },

            submit() {
                return this.handleCheckout();
            },

            async init() {
                try {
                    if (typeof PandansariAPI?.loadCatalogImages === 'function') {
                        const p = PandansariAPI.loadCatalogImages();
                        if (p && typeof p.then === 'function') {
                            p.then(() => { this.items = [...this.items]; }).catch(() => {});
                        }
                    }
                } catch (_) {}
                await Promise.all([this.loadCart(), this.loadAddresses()]);
                if (this.selectedAddressId) {
                    await this.hitungOngkir();
                }
                this.muatVoucher();
                this.loading = false;
            }
        };
    }

    // Registrasi Alpine Component
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkoutPage', () => checkoutPage());
    });

    if (window.Alpine) {
        Alpine.data('checkoutPage', () => checkoutPage());
    }
</script>
@endsection