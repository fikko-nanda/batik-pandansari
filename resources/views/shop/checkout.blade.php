@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        // === State dari API ===
        addresses: [],
        selectedAddressId: null,
        items: [],
        voucher: null,           // hasil POST /vouchers/validate

        // === State UI ===
        loading: true,
        needLogin: false,
        errorMsg: '',
        selectedCourierId: 'jne',   // kurir manual statis terpilih (null bila pakai layanan RajaOngkir nyata)
        biayaPengiriman: 22000,
        catatan: '',
        submitting: false,
        submitMsg: '',
        submitOk: false,
        fallbackImage: '{{ asset('images/katalog1.webp') }}',

        couriers: [
            { id: 'jne', name: 'JNE Reguler (REG)', price: 22000, est: 'Estimasi 2 – 3 Hari Kerja' },
            { id: 'pos', name: 'POS Indonesia (Kilat Khusus)', price: 20000, est: 'Estimasi 2 – 4 Hari Kerja' },
            { id: 'tiki', name: 'TIKI Reguler (REG)', price: 24000, est: 'Estimasi 2 – 3 Hari Kerja' }
        ],

        voucherCode: '',
        voucherMsg: '',
        voucherOk: false,
        busyVoucher: false,

        // === RajaOngkir: cek ongkir nyata (GET /shipping/destinations + POST /shipping/cost) ===
        destQuery: '',
        destinations: [],
        destBusy: false,
        destPicked: null,        // hasil pilih dari daftar destinasi { id, label, ... }
        beratGram: 500,
        ongkirBusy: false,
        ongkirMsg: '',
        ongkirOk: true,
        ongkirLayanan: [],       // layanan dari POST /shipping/cost
        serviceTerpilih: null,   // { kurir, service, cost, etd } → dipakai sbg biaya pengiriman

        get selectedAddress() {
            return this.addresses.find(a => a.id_user_address === this.selectedAddressId) ?? null;
        },
        get subtotal() {
            return this.items.reduce((s, i) => s + (Number(i.variant?.harga ?? 0) * Number(i.jumlah ?? 1)), 0);
        },
        get discountAmount() {
            if (!this.voucher) return 0;
            let d = this.voucher.tipe_diskon === 'persentase'
                ? this.subtotal * Number(this.voucher.nilai_diskon) / 100
                : Number(this.voucher.nilai_diskon);
            if (this.voucher.maksimal_diskon) d = Math.min(d, Number(this.voucher.maksimal_diskon));
            return Math.round(Math.min(d, this.subtotal));
        },
        get totalPembayaran() {
            return Math.max(0, this.subtotal + Number(this.biayaPengiriman || 0) - this.discountAmount);
        },
        get itemCount() {
            return this.items.reduce((s, i) => s + Number(i.jumlah ?? 1), 0);
        },

        // === Helper render ===
        formatRupiah(number) { return PandansariAPI.formatRp(number); },
        titleOf(i) { return i.variant?.product?.nama_produk ?? 'Produk'; },
        variantLabel(i) {
            const v = i.variant;
            if (!v) return '';
            return [v.ukuran && ('Size ' + v.ukuran), v.warna].filter(Boolean).join(' • ');
        },
        priceOf(i) { return Number(i.variant?.harga ?? 0) * Number(i.jumlah ?? 1); },
        imageOf(i) {
            const img = (i.variant?.product?.images ?? []).find(x => x.url);
            return img?.url ?? this.fallbackImage;
        },

        get selectedCourier() {
            // prioritas: layanan RajaOngkir nyata → kurir manual statis
            if (this.serviceTerpilih) return (this.serviceTerpilih.kurir + ' ' + (this.serviceTerpilih.service ?? '')).toUpperCase();
            return this.couriers.find(c => c.id === this.selectedCourierId)?.name ?? '-';
        },
        selectCourier(courier) {
            this.selectedCourierId = courier.id;
            this.biayaPengiriman = courier.price;
            this.serviceTerpilih = null;
        },

        // === RajaOngkir: cari tujuan pengiriman ===
        async cariDestinasi() {
            const q = this.destQuery.trim();
            if (q.length < 3) { this.destBusy = false; return; }
            this.destBusy = true;
            const res = await PandansariAPI.shippingDestinations(q);
            this.destBusy = false;
            this.destinations = res.ok ? (res.data ?? []) : [];
        },
        pilihDestinasi(d) {
            this.destPicked = d;
            this.destQuery = d.label;
            this.destinations = [];
            this.ongkirLayanan = [];
            this.serviceTerpilih = null;
            this.ongkirMsg = '';
        },
        resetOngkir() {
            this.destPicked = null;
            this.destQuery = '';
            this.destinations = [];
            this.ongkirLayanan = [];
            this.serviceTerpilih = null;
            this.ongkirMsg = '';
            this.biayaPengiriman = 22000;   // kembali ke tarif manual default
        },

        // === RajaOngkir: hitung ongkir nyata (pilih kurir aktif) ===
        async hitungOngkir(kurir) {
            if (!this.destPicked) { this.ongkirOk = false; this.ongkirMsg = 'Pilih dulu tujuan pengiriman dari daftar.'; return; }
            this.ongkirBusy = true; this.ongkirMsg = '';
            const res = await PandansariAPI.shippingCost(this.destPicked.id, this.beratGram, kurir);
            this.ongkirBusy = false;
            if (res.ok) {
                const hasil = (res.data?.hasil ?? []).find(h => h.berhasil) ?? null;
                if (hasil?.layanan?.length) {
                    this.ongkirLayanan = hasil.layanan.map(l => ({
                        kurir, service: l.service ?? l.name ?? kurir.toUpperCase(),
                        cost: Number(l.cost ?? l.price ?? l.shipping_cost ?? 0),
                        etd: l.etd ?? l.description ?? '',
                    }));
                    this.ongkirOk = true;
                    this.ongkirMsg = 'Layanan ditemukan — pilih salah satu.';
                } else {
                    this.ongkirOk = false;
                    const err = hasil?.error;
                    this.ongkirMsg = err === 'RAJAONGKIR_DESTINATION_NOT_FOUND'
                        ? 'Tarif ' + kurir.toUpperCase() + ' tidak tersedia dari server ongkir. Silakan pakai tarif manual di bawah.'
                        : (err ?? 'Tarif tidak tersedia untuk kurir ini.');
                }
            } else {
                this.ongkirOk = false;
                this.ongkirMsg = res.message === 'RAJAONGKIR_DESTINATION_NOT_FOUND'
                    ? 'Layanan ongkir sedang tidak dapat dihubungi (kendala di backend). Pakai tarif manual di bawah.'
                    : res.message;
            }
        },
        pilihLayanan(l) {
            this.serviceTerpilih = l;
            this.biayaPengiriman = l.cost;
            this.selectedCourierId = null;
            this.ongkirMsg = 'Ongkir ' + (l.kurir + ' ' + l.service).toUpperCase() + ' (' + this.formatRupiah(l.cost) + ') dipakai.';
            this.ongkirOk = true;
        },

        // === Loader ===
        async loadCart() {
            const res = await PandansariAPI.cart();
            if (res.ok) {
                const cart = res.data ?? {};
                this.items = cart.items ?? (Array.isArray(cart) ? cart : []);
            } else if (res.status === 401) {
                this.needLogin = true;
            } else {
                this.errorMsg = res.message;
            }
            return res;
        },
        async loadAddresses() {
            const res = await PandansariAPI.addresses();
            if (res.ok) {
                this.addresses = res.data ?? [];
                const utama = this.addresses.find(a => a.alamat_utama) ?? this.addresses[0];
                if (utama) this.selectedAddressId = utama.id_user_address;
            }
            return res;
        },
        async applyVoucher() {
            if (!this.voucherCode.trim()) return;
            this.busyVoucher = true;
            const res = await PandansariAPI.validateVoucher(this.voucherCode.trim().toUpperCase(), this.subtotal);
            this.busyVoucher = false;
            if (res.ok) {
                this.voucher = res.data;
                this.voucherOk = true;
                this.voucherMsg = res.message;
            } else {
                this.voucher = null;
                this.voucherOk = false;
                this.voucherMsg = res.message;
            }
        },
        hapusVoucher() { this.voucher = null; this.voucherMsg = ''; this.voucherOk = false; this.voucherCode = ''; },

        // === Submit checkout → Midtrans Snap ===
        async submit() {
            if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
            this.submitMsg = ''; this.submitOk = false;

            if (!this.selectedAddressId) { this.submitMsg = 'Pilih alamat pengiriman terlebih dahulu.'; return; }
            if (!this.items.length) { this.submitMsg = 'Keranjang Anda kosong.'; return; }

            this.submitting = true;
            const res = await PandansariAPI.checkout({
                id_user_address: this.selectedAddressId,
                kode_voucher: this.voucherCode.trim() || undefined,
                biaya_pengiriman: Number(this.biayaPengiriman || 0) || undefined,
                catatan: this.catatan.trim() || undefined,
            });
            if (!res.ok) {
                this.submitting = false;
                this.submitMsg = res.message;
                return;
            }

            const order = res.data ?? {};
            const pay = await PandansariAPI.payOrder(order.id_order ?? order.id);
            this.submitting = false;

            if (pay.ok && pay.data?.redirect_url) {
                // Redirect ke halaman bayar Midtrans Snap (sandbox)
                window.location.href = pay.data.redirect_url;
            } else {
                this.submitMsg = 'Pesanan ' + (order.nomor_pesanan ?? '') + ' dibuat, tetapi halaman pembayaran gagal dimuat. ' + (pay.message ?? '');
                this.submitOk = false;
            }
        },

        async init() {
            await Promise.all([this.loadCart(), this.loadAddresses()]);
            this.loading = false;
        }
     }">

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
                    <i class="fa-solid fa-circle-check"></i> 1. Keranjang
                </span>
                <span class="text-slate-300">—</span>
                <span class="flex items-center gap-1.5 bg-[#BAE6FD] text-[#0369A1] px-3 py-1 rounded-full font-bold">
                    2. Pengiriman
                </span>
                <span class="text-slate-300">—</span>
                <span class="text-slate-400">3. Pembayaran</span>
            </div>
        </div>

        <!-- Main Layout: Grid 12 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDE: Form Alamat & Ekspedisi (8 Kolom) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Header Section -->
                <div>
                    <span class="text-[10px] font-bold tracking-wider uppercase text-slate-500 block mb-1">LOGISTIK & EKSPEDISI RAJAONGKIR</span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Pengiriman & Alamat Penerima</h1>
                    <p class="text-xs text-slate-500 mt-1">Lengkapi rincian alamat dan pilih kurir ekspedisi sebelum melanjutkan ke pembayaran Midtrans.</p>
                    
                </div>

                <!-- Box 1: Form Alamat Penerima -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24] border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-map-location-dot text-sky-600 text-sm"></i>
                        <span>Alamat</span>
                    </div>

                    <div class="space-y-3">
                        <!-- Status -->
                        <template x-if="!loading && !addresses.length">
                            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-3">
                                <span><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Anda belum memiliki alamat tersimpan.</span>
                                <a href="/address" class="bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg whitespace-nowrap">TAMBAH ALAMAT</a>
                            </div>
                        </template>

                        <!-- Saved Addresses (dari API, radio pilih) -->
                        <template x-for="a in addresses" :key="a.id_user_address">
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border-2 transition cursor-pointer"
                                   :class="selectedAddressId === a.id_user_address ? 'border-sky-500 bg-sky-50/40 ring-1 ring-sky-200' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                <input type="radio" name="alamat" :value="a.id_user_address" :checked="selectedAddressId === a.id_user_address" @change="selectedAddressId = a.id_user_address" class="mt-0.5 text-sky-600 focus:ring-sky-500">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#0F1A24]" x-text="a.label_alamat"></span>
                                        <span x-show="a.alamat_utama" class="text-[9px] bg-sky-100 text-[#0369A1] font-bold px-1.5 py-0.5 rounded uppercase">UTAMA</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed mt-0.5">
                                        <span x-text="a.nama_penerima"></span> • <span x-text="a.nomor_telepon"></span><br>
                                        <span x-text="a.alamat_lengkap"></span>, <span x-text="a.nama_kecamatan"></span>, <span x-text="a.nama_kota"></span>, <span x-text="a.nama_provinsi"></span> <span x-text="a.kode_pos"></span>
                                    </p>
                                </div>
                            </label>
                        </template>

                        <p class="text-[11px] text-slate-400 text-right">
                            Kelola daftar alamat di <a href="/address" class="text-sky-600 font-bold hover:underline">/address</a> →
                        </p>

                        <!-- Kondisi/Catatan (→ body checkout `catatan`) -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Patokan / Catatan Alamat (opsional)</label>
                            <input type="text" x-model="catatan" placeholder="Patokan / catatan alamat..."
                                   class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <!-- Box 1b: Cek Ongkir RajaOngkir (API nyata) -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24]">
                            <i class="fa-solid fa-calculator text-sky-600 text-sm"></i>
                            <span>Cek Ongkos Kirim (RajaOngkir)</span>
                        </div>
                        <button type="button" x-show="serviceTerpilih || destPicked" @click="resetOngkir()"
                                class="text-[10px] font-bold text-slate-400 hover:text-rose-500 transition cursor-pointer">
                            RESET
                        </button>
                    </div>

                    <!-- 1) Cari tujuan -->
                    <div class="space-y-2">
                        <label class="block text-[11px] font-semibold text-slate-600">1. Cari tujuan (kecamatan/kota)</label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                                <input type="text" x-model="destQuery" @keydown.enter="cariDestinasi()"
                                       placeholder="Contoh: Banjarsari, Surakarta..."
                                       class="w-full pl-7 pr-3 py-2.5 text-xs font-medium bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <button type="button" @click="cariDestinasi()" :disabled="destBusy"
                                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold px-4 py-2.5 rounded-xl cursor-pointer disabled:opacity-60">
                                <span x-show="!destBusy">CARI</span>
                                <span x-show="destBusy"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                            </button>
                        </div>

                        <!-- daftar hasil -->
                        <div x-show="destinations.length" class="max-h-44 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100">
                            <template x-for="d in destinations" :key="d.id">
                                <button type="button" @click="pilihDestinasi(d)"
                                        class="w-full text-left px-3.5 py-2.5 hover:bg-sky-50 transition cursor-pointer">
                                    <span class="block text-[11px] font-bold text-[#0F1A24]" x-text="d.label"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="destQuery.length >= 3 && !destBusy && !destinations.length" class="text-[10px] text-slate-400">
                            Tujuan tidak ditemukan — coba kata kunci lain (mis. nama kecamatan).
                        </p>
                    </div>

                    <!-- 2) Tujuan terpilih + berat + kurir -->
                    <div x-show="destPicked" class="space-y-3">
                        <div class="bg-sky-50 border border-sky-100 rounded-xl px-3.5 py-2.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-[#0369A1]">TUJUAN TERPILIH</span>
                                <span class="block text-[11px] font-semibold text-slate-700 truncate" x-text="destPicked?.label"></span>
                            </div>
                            <i class="fa-solid fa-circle-check text-emerald-500"></i>
                        </div>

                        <div class="flex items-end gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">2. Berat total (gram)</label>
                                <input type="number" min="1" x-model.number="beratGram"
                                       class="w-32 text-xs font-semibold border border-slate-200 rounded-xl px-3 py-2.5 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">3. Pilih kurir lalu tunggu tarif muncul</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="k in ['jne','tiki','sicepat','jnt','pos','anteraja']" :key="k">
                                        <button type="button" @click="hitungOngkir(k)" :disabled="ongkirBusy"
                                                class="text-[10px] font-bold uppercase bg-slate-100 hover:bg-sky-100 text-slate-700 px-3 py-2 rounded-lg transition cursor-pointer disabled:opacity-60">
                                            <span x-text="k"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- hasil layanan -->
                        <div x-show="ongkirBusy" class="text-[11px] font-semibold text-slate-500 py-2">
                            <i class="fa-solid fa-circle-notch fa-spin text-sky-500 mr-1"></i> Meminta tarif ke server ongkir…
                        </div>
                        <div x-show="ongkirMsg && !ongkirBusy" class="text-[11px] font-semibold rounded-xl px-3.5 py-2.5"
                             :class="ongkirOk ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'">
                            <i class="fa-solid mr-1" :class="ongkirOk ? 'fa-circle-check' : 'fa-triangle-exclamation'"></i>
                            <span x-text="ongkirMsg"></span>
                        </div>
                        <div x-show="ongkirLayanan.length" class="space-y-2">
                            <template x-for="(l, idx) in ongkirLayanan" :key="idx">
                                <label class="flex items-center justify-between p-3 rounded-xl border-2 transition cursor-pointer"
                                       :class="serviceTerpilih && serviceTerpilih.service === l.service && serviceTerpilih.kurir === l.kurir ? 'border-sky-500 bg-sky-50/40' : 'border-slate-200 hover:border-slate-300'">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="layanan" class="text-sky-600 focus:ring-sky-500"
                                               :checked="serviceTerpilih && serviceTerpilih.service === l.service && serviceTerpilih.kurir === l.kurir"
                                               @click="pilihLayanan(l)">
                                        <div>
                                            <span class="block text-xs font-bold text-[#0F1A24]" x-text="(l.kurir + ' — ' + l.service).toUpperCase()"></span>
                                            <span class="block text-[10px] text-slate-500" x-text="l.etd ? ('Estimasi ' + l.etd) : ''"></span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-[#0F1A24]" x-text="formatRupiah(l.cost)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-400">
                        <i class="fa-solid fa-circle-info text-sky-500 mr-1"></i>
                        Bila server ongkir sedang bermasalah, tetap bisa checkout dengan memilih tarif manual di bawah.
                    </p>
                </div>

                <!-- Box 2: Pilihan Layanan Ekspedisi -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24]">
                            <i class="fa-solid fa-truck-fast text-sky-600 text-sm"></i>
                            <span>Tarif Manual (Fallback)</span>
                        </div>
                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md font-semibold">
                            <span x-text="itemCount"></span> item
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-400">Dipakai bila tarif RajaOngkir tidak tersedia. Pilihan otomatis dilepas saat tarif nyata dipilih.</p>

                    <div class="space-y-3">
                        <template x-for="c in couriers" :key="c.id">
                            <label @click="selectCourier(c)"
                                   class="flex items-center justify-between p-4 rounded-xl border-2 transition cursor-pointer"
                                   :class="selectedCourierId === c.id ? 'border-sky-500 bg-sky-50/40 ring-1 ring-sky-200' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="courier" :value="c.id" :checked="selectedCourierId === c.id" class="mt-0.5 text-sky-600 focus:ring-sky-500">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-[#0F1A24]" x-text="c.name"></span>
                                        </div>
                                        <span class="text-[10px] text-slate-500 block mt-0.5" x-text="c.est"></span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-[#0F1A24]" x-text="formatRupiah(c.price)"></span>
                            </label>
                        </template>
                    </div>

                    <!-- Footnote Box -->
                    
                </div>

            </div>

            <!-- RIGHT SIDE: Ringkasan Pesanan (4 Kolom Sticky) -->
            <div class="lg:col-span-4 lg:sticky lg:top-6 space-y-4">
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <!-- Status loading -->
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
                        <a href="/cart" class="text-xs font-bold text-sky-600 hover:underline">Ubah Item</a>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-3 max-h-[260px] overflow-y-auto pr-1">
                        <template x-for="item in items" :key="item.id_cart_item">
                            <div class="flex items-center gap-3 text-xs">
                                <img :src="imageOf(item)" class="w-12 h-14 object-cover rounded-lg border border-slate-100 shrink-0 bg-slate-100" :onerror="`this.src='${fallbackImage}'`">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-[#0F1A24] truncate" x-text="titleOf(item)"></h4>
                                    <p class="text-[10px] text-slate-400 truncate" x-text="variantLabel(item)"></p>
                                    <span class="font-bold text-[#0F1A24] text-xs block mt-0.5" x-text="formatRupiah(priceOf(item))"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Voucher -->
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <i class="fa-solid fa-tag absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                                <input type="text" x-model="voucherCode" @keydown.enter="applyVoucher()" placeholder="Kode voucher..."
                                       class="w-full pl-7 pr-2 py-2 text-[11px] font-mono uppercase font-bold bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <button type="button" @click="applyVoucher()" :disabled="busyVoucher"
                                    class="bg-[#0F1A24] text-white text-[11px] font-bold px-3 py-2 rounded-xl cursor-pointer disabled:opacity-60">
                                <span x-show="!busyVoucher">CEK</span>
                                <span x-show="busyVoucher"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
                            </button>
                        </div>
                        <p x-show="voucherMsg" class="text-[10px] font-semibold flex items-center gap-1"
                           :class="voucherOk ? 'text-emerald-600' : 'text-rose-600'">
                            <i :class="voucherOk ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                            <span x-text="voucherMsg"></span>
                        </p>
                        <button type="button" x-show="voucher" @click="hapusVoucher()"
                                class="w-full bg-sky-50/80 border border-sky-100 p-2 rounded-xl flex items-center justify-between text-xs text-sky-800 font-medium cursor-pointer"></think>Perbaikan kecil — tag penutup `</button>` diakhiri:<tool_call>edit<arg_key>newString</arg_key><arg_value>                        <button type="button" x-show="voucher" @click="hapusVoucher()"
                                class="w-full bg-sky-50/80 border border-sky-100 p-2 rounded-xl flex items-center justify-between text-xs text-sky-800 font-medium cursor-pointer">
                            <span class="flex items-center gap-1.5 font-bold text-[11px]">
                                <i class="fa-solid fa-ticket text-sky-600"></i>
                                <span x-text="voucher?.nama_voucher ?? 'VOUCHER'"></span>
                            </span>
                            <span class="flex items-center gap-1 font-bold text-sky-600 text-xs">
                                <span x-text="'- ' + formatRupiah(discountAmount)"></span>
                                <i class="fa-solid fa-xmark text-slate-400"></i>
                            </span>
                        </button>
                    </div>

                    <!-- Breakdown Table -->
                    <div class="space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-3 mt-3">
                        <div class="flex justify-between">
                            <span>Subtotal (<span x-text="itemCount"></span> item)</span>
                            <span class="font-semibold text-[#0F1A24]" x-text="formatRupiah(subtotal)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Ongkos Kirim (<span x-text="selectedCourier"></span>)</span>
                            <span class="font-semibold text-[#0F1A24]" x-text="formatRupiah(biayaPengiriman)"></span>
                        </div>
                        <div class="flex justify-between" x-show="voucher" :class="voucher ? 'text-rose-500' : 'text-slate-400'">
                            <span>Diskon Voucher</span>
                            <span class="font-semibold" x-text="'- ' + formatRupiah(discountAmount)"></span>
                        </div>
                    </div>

                    <!-- Total Price & CTA Button -->
                    <div class="border-t border-slate-200/80 pt-3">
                        <div class="flex justify-between items-baseline mb-4">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">TOTAL PEMBAYARAN</span>
                                <span class="text-xs text-slate-400">Dihitung backend saat checkout</span>
                            </div>
                            <span class="text-xl font-bold text-[#0F1A24]" x-text="formatRupiah(totalPembayaran)"></span>
                        </div>

                        <!-- Pesan hasil submit -->
                        <p x-show="submitMsg && !submitOk" class="text-[11px] font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-3 py-2 mb-3">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i><span x-text="submitMsg"></span>
                        </p>

                        <!-- CTA Button: POST /checkout → POST /orders/{id}/payment → redirect Midtrans Snap -->
                        <button type="button" :disabled="submitting" @click="submit()"
                                class="w-full bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] font-bold text-xs py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-60">
                            <span x-show="!submitting">LANJUT KE PEMBAYARAN MIDTRANS →</span>
                            <span x-show="submitting"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> MEMBUAT PESANAN…</span>
                        </button>
                    </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>
@endsection