@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        // === State dari API ===
        addresses: [],
        selectedAddressId: null,
        alamatAktif: null,
        items: [],
        voucher: null,

        // === State UI ===
        loading: true,
        needLogin: false,
        errorMsg: '',
        selectedCourierId: 'jne',
        biayaPengiriman: 22000,
        catatan: '',
        isLoading: false,
        submitMsg: '',
        submitOk: false,
        fallbackImage: '{{ asset('images/katalog1.webp') }}',

        couriers: [],

        voucherCode: '',
        voucherMsg: '',
        voucherOk: false,
        busyVoucher: false,

        // === RajaOngkir ===
        destQuery: '',
        destinations: [],
        destBusy: false,
        destPicked: null,
        beratGram: null,
        layananOngkir: [],
        ongkirBusy: false,
        ongkirMsg: '',
        ongkirOk: true,
        serviceTerpilih: null,

        get selectedAddress() {
            return this.addresses.find(a => a.id_user_address === this.selectedAddressId) ?? null;
        },
        get subtotal() {
            return this.items.reduce((s, i) => s + (Number(i.variant?.harga ?? 0) * Number(i.jumlah ?? 1)), 0);
        },
        get totalBerat() {
            return this.items.reduce((sum, item) => sum + ((item.produk?.berat || item.variant?.berat || 1000) * Number(item.jumlah ?? 1)), 0);
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
            return PandansariAPI.coverUrl(i.variant?.product?.images) || this.fallbackImage;
        },

        get selectedCourier() {
            if (this.serviceTerpilih) return (this.serviceTerpilih.kurir + ' ' + (this.serviceTerpilih.service ?? '')).toUpperCase();
            return '-';
        },
        getNamaKurir(code) {
            const mapped = {
                jne: 'JNE',
                jnt: 'J&T',
                pos: 'POS',
                'jne-reguler': 'JNE',
                'jnt-express': 'J&T',
                'pos-jNE': 'POS',
            };
            return mapped[code?.toLowerCase()] || code || '-';
        },
        getNamaLayanan(service) {
            const mapped = {
                ctc: 'Reguler (Dalam Kota)',
                ctcyes: 'YES (Yakin Esok Sampai - Dalam Kota)',
                ctcps: 'Super Speed (Sameday)',
                jtr: 'JTR (JNE Trucking / Kargo)',
            };
            return mapped[service?.toLowerCase()] || service || '-';
        },
        formatEtd(etd) {
            if (!etd) return '';
            return etd.replace(/ day/i, '') + ' hari';
        },

        // === RajaOngkir ===
        async cariDestinasi() {
            const q = this.destQuery.trim();
            if (q.length < 3) { this.destBusy = false; return; }
            this.destBusy = true;
            const res = await PandansariAPI.getDestinations(q);
            this.destBusy = false;
            this.destinations = res.ok ? (res.data ?? []) : [];
        },
        pilihDestinasi(d) {
            this.destPicked = d;
            this.destQuery = d.label;
            this.destinations = [];
            this.layananOngkir = [];
            this.serviceTerpilih = null;
            this.ongkirMsg = '';
            this.cekOngkir();
        },
        resetOngkir() {
            this.destPicked = null;
            this.destQuery = '';
            this.destinations = [];
            this.layananOngkir = [];
            this.serviceTerpilih = null;
            this.ongkirMsg = '';
            this.biayaPengiriman = 0;
        },
        useSavedTujuan() {
            const a = this.alamatAktif;
            if (a?.id_tujuan_rajaongkir) {
                this.destPicked = { id: Number(a.id_tujuan_rajaongkir), label: 'Tujuan tersimpan dari alamat ' + (a.label_alamat ?? '') };
                this.destQuery = '';
                this.destinations = [];
                this.layananOngkir = [];
                this.serviceTerpilih = null;
                this.ongkirMsg = '';
                this.cekOngkir();
            }
        },

        async cekOngkir() {
            if (!this.destPicked) { this.ongkirOk = false; this.ongkirMsg = 'Pilih dulu tujuan pengiriman dari daftar.'; return; }
            this.ongkirBusy = true; this.ongkirMsg = '';
            const beratFix = Math.max(1, Number(this.totalBerat) || 1000);
            const res = await PandansariAPI.rajaongkirCheckAll(this.destPicked.id, beratFix);
            this.ongkirBusy = false;
            const di = Array.isArray(res.data) ? res.data : [];
            if (res.ok && di.length) {
                this.layananOngkir = di.map(l => ({
                    kurir: l.kurir ?? l.courier ?? '',
                    nama: l.nama ?? l.kurir ?? l.courier ?? '',
                    service: l.service ?? l.service_name ?? '',
                    deskripsi: l.deskripsi ?? l.description ?? '',
                    cost: Number(l.cost ?? l.price ?? l.shipping_cost ?? 0),
                    etd: l.etd ?? '',
                }));
                this.ongkirOk = true;
                this.ongkirMsg = 'Layanan ditemukan — pilih salah satu.';
            } else if (res.ok && !di.length) {
                this.layananOngkir = [];
                this.ongkirOk = false;
                this.ongkirMsg = 'Tidak ada layanan ongkir tersedia untuk tujuan dan berat ini.';
            } else {
                this.layananOngkir = [];
                this.ongkirOk = false;
                this.ongkirMsg = res.message ?? 'Gagal memuat layanan ongkir.';
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
                if (utama) {
                    this.alamatAktif = utama;
                    this.selectedAddressId = utama.id_user_address;
                    this.useSavedTujuan();
                }
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

        voucherList: [], voucherListPesan: '',
        async muatVoucher() {
            const r = await PandansariAPI.availableVouchers(this.subtotal);
            if (r.ok) {
                this.voucherList = (r.data ?? []).filter(v => {
                    const st = String(v.status_voucher ?? v.status ?? '').toLowerCase();
                    return !st || ['aktif', 'dipublikasikan', 'published', 'active'].includes(st);
                });
                this.voucherListPesan = this.voucherList.length ? '' : 'Belum ada voucher tersedia.';
            } else {
                this.voucherList = [];
                this.voucherListPesan = r.message;
            }
        },
        pakaiVoucher(v) {
            this.voucherCode = String(v.kode_voucher ?? v.kode ?? '');
            this.applyVoucher();
            window.scrollTo({ top: window.scrollY, behavior: 'smooth' });
        },

        // === Checkout ===
        async handleCheckout() {
            if (!PandansariAPI.getToken()) { window.location.href = '/login'; return; }
            this.submitMsg = ''; this.submitOk = false;

            const manual = !this.serviceTerpilih;
            const kurirManual = this.couriers.find(c => c.id === this.selectedCourierId);
            const kurirDipilih = this.serviceTerpilih?.kurir || kurirManual?.id || 'jne';
            const courierDipilih = this.serviceTerpilih?.kurir || kurirManual?.name || 'jne';
            const layananDipilih = this.serviceTerpilih?.service ?? this.getNamaLayanan(kurirDipilih);
            const payload = {
                id_user_address: this.selectedAddressId,
                kode_voucher: this.voucherCode.trim() || undefined,
                biaya_pengiriman: Number(this.biayaPengiriman || 0) || undefined,
                catatan: this.catatan.trim() || undefined,
                kurir: kurirDipilih,
                courier: courierDipilih,
                layanan: layananDipilih,
            };
            if (manual) {
                payload.is_manual_shipping = true;
                payload.shipping_cost = Number(this.biayaPengiriman || 0);
                payload.courier_name = kurirManual?.name ?? 'JNE Reguler';
            }

            if (!this.selectedAddressId) { this.submitMsg = 'Pilih alamat pengiriman terlebih dahulu.'; return; }
            if (!this.serviceTerpilih) { this.submitMsg = 'Pilih layanan pengiriman terlebih dahulu.'; return; }
            if (!this.items.length) { this.submitMsg = 'Keranjang Anda kosong.'; return; }

            const alamatPilih = this.alamatAktif;
            if (alamatPilih && !alamatPilih.id_tujuan_rajaongkir && !manual) {
                this.submitMsg = 'Alamat ' + (alamatPilih.label_alamat ?? 'yang dipilih') + ' belum memiliki tujuan ongkir (RajaOngkir). Perbarui alamat di Buku Alamat: edit alamat tersebut, pilih di kolom Cari Tujuan Ongkir, lalu simpan.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            this.isLoading = true;
            const res = await PandansariAPI.checkout(payload);
            if (!res.ok) {
                this.isLoading = false;
                if (res.message === 'ALAMAT_TANPA_TUJUAN_RAJAONGKIR') {
                    this.submitMsg = 'Alamat pengiriman belum terhubung dengan lokasi RajaOngkir. Silakan perbarui alamat Anda di Buku Alamat.'
                        + (manual ? ' (Tarif manual dipakai, tetapi backend masih mengharuskan alamat punya tujuan ongkir.)' : '');
                } else {
                    this.submitMsg = res.message === 'RAJAONGKIR_DESTINATION_NOT_FOUND' || res.message === 'RAJAONGKIR_INVALID_RESPONSE'
                        ? 'Perhitungan ongkir sedang bermasalah di server. Coba lagi sebentar, atau pakai tarif manual lalu ulangi.'
                        : res.message;
                }
                return;
            }

            const order = res.data ?? {};
            const pay = await PandansariAPI.payOrder(order.id_order ?? order.id);
            this.isLoading = false;
            const pembayaran = pay.data ?? {};

            if (pembayaran.snap_token && pembayaran.client_key) {
                await PandansariAPI.bayarSnap(pembayaran, {
                    onSuccess: () => { window.location.href = '/orders'; },
                    onPending: () => { window.location.href = '/orders'; },
                    onError: (r) => {
                        this.submitMsg = r?.message ?? 'Pembayaran gagal. Coba lanjutkan pembayaran di menu Pesanan Saya.';
                        this.submitOk = false;
                    },
                    onClose: () => { this.isLoading = false; },
                });
            } else if (pay.ok && (pembayaran.redirect_url ?? pembayaran.payment_url)) {
                window.location.href = pembayaran.redirect_url ?? pembayaran.payment_url;
            } else {
                this.submitMsg = 'Pesanan ' + (order.nomor_pesanan ?? '') + ' dibuat, tetapi halaman pembayaran gagal dimuat. ' + (pay.message ?? '');
                this.submitOk = false;
            }
        },

        async init() {
            await Promise.all([this.loadCart(), this.loadAddresses()]);
            this.muatVoucher();
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

                        <!-- Saved Addresses -->
                        <template x-for="a in addresses" :key="a.id_user_address">
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border-2 transition cursor-pointer"
                                   :class="selectedAddressId === a.id_user_address ? 'border-sky-500 bg-sky-50/40 ring-1 ring-sky-200' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                <input type="radio" name="alamat" :value="a.id_user_address" :checked="selectedAddressId === a.id_user_address" @change="selectedAddressId = a.id_user_address; alamatAktif = a; useSavedTujuan()" class="mt-0.5 text-sky-600 focus:ring-sky-500">
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

                        <!-- Catatan Alamat -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Patokan / Catatan Alamat (opsional)</label>
                            <input type="text" x-model="catatan" placeholder="Patokan / catatan alamat..."
                                   class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <!-- Box 1b: Cek Ongkir RajaOngkir -->
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

                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-500">TOTAL BERAT PAKET</span>
                                <span class="block text-[11px] font-semibold text-slate-700" x-text="totalBerat.toLocaleString('id-ID') + ' gram (' + (totalBerat / 1000).toFixed(1) + ' kg)'"></span>
                            </div>
                            <span class="text-xs font-bold text-sky-600" x-text="items.length + ' item'"></span>
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
                        <div x-show="layananOngkir.length" class="space-y-2">
                            <template x-for="(l, idx) in layananOngkir" :key="idx">
                                <label class="flex items-center justify-between p-3 rounded-xl border-2 transition cursor-pointer"
                                       :class="serviceTerpilih && serviceTerpilih.service === l.service && serviceTerpilih.kurir === l.kurir ? 'border-sky-500 bg-sky-50/40' : 'border-slate-200 hover:border-slate-300'">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="layanan" class="text-sky-600 focus:ring-sky-500"
                                               :checked="serviceTerpilih && serviceTerpilih.service === l.service && serviceTerpilih.kurir === l.kurir"
                                               @click="pilihLayanan(l)">
                                        <div class="min-w-0">
                                            <span class="block text-xs font-bold text-[#0F1A24]" x-text="getNamaKurir(l.kurir) + ' - ' + getNamaLayanan(l.service)"></span>
                                            <span class="block text-[10px] leading-snug text-slate-500" x-text="l.deskripsi ? l.deskripsi : 'Layanan ' + (l.kurir ?? '').toUpperCase()"></span>
                                            <span class="block text-[10px] text-sky-600" x-text="l.etd ? formatEtd(l.etd) : ''"></span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-[#0F1A24]" x-text="formatRupiah(l.cost)"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-400">
                        <i class="fa-solid fa-circle-info text-sky-500 mr-1"></i>
                        Tarif dikirim otomatis saat tujuan dipilih. Pilih salah satu layanan di atas untuk melanjutkan.
                    </p>
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
                            <a href="/cart" class="text-xs text-sky-600 font-semibold hover:underline">Ubah</a>
                        </div>

                        <!-- Daftar Produk -->
                        <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto pr-1">
                            <template x-for="item in items" :key="item.id_cart_item || item.id">
                                <div class="py-3 flex gap-3 items-center">
                                    <img :src="imageOf(item)" :alt="titleOf(item)" class="w-12 h-12 rounded-lg object-cover border border-slate-100 flex-shrink-0">
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
                            <div class="flex justify-between text-slate-600">
                                <span>Biaya Pengiriman</span>
                                <span class="font-semibold text-slate-800" x-text="formatRupiah(biayaPengiriman)"></span>
                            </div>
                            <div x-show="discountAmount > 0" class="flex justify-between text-emerald-600 font-medium">
                                <span>Diskon Voucher</span>
                                <span>-<span x-text="formatRupiah(discountAmount)"></span></span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-[#0F1A24] pt-2 border-t border-slate-100">
                                <span>Total Pembayaran</span>
                                <span class="text-sky-600" x-text="formatRupiah(totalPembayaran)"></span>
                            </div>
                        </div>

                        <!-- Pesan Submit Error -->
                        <div x-show="submitMsg" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl" x-text="submitMsg"></div>

                        <!-- Tombol Buat Pesanan / Bayar -->
                        <button type="button" @click="handleCheckout()" :disabled="isLoading" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-sky-600/20 transition cursor-pointer flex items-center justify-center gap-2 text-xs uppercase tracking-wider disabled:opacity-60">
                            <span x-show="!isLoading"><i class="fa-solid fa-credit-card"></i> Bayar Sekarang</span>
                            <span x-show="isLoading"><i class="fa-solid fa-circle-notch fa-spin"></i> Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection