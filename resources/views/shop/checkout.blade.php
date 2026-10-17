@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-6 font-['Plus_Jakarta_Sans']"
     x-data="{
        selectedCourier: 'jne',
        courierFee: 22000,
        promoDiscount: 308800,
        subtotal: 3080800,

        couriers: [
            { id: 'jne', name: 'JNE Reguler (REG)', price: 22000, est: 'Estimasi 2 – 3 Hari Kerja • Asuransi Penuh', badge: 'Recomended' },
            { id: 'pos', name: 'POS Indonesia (Kilat Khusus)', price: 20000, est: 'Estimasi 2 – 4 Hari Kerja', badge: 'Termurah' },
            { id: 'tiki', name: 'TIKI Reguler (REG)', price: 24000, est: 'Estimasi 2 – 3 Hari Kerja', badge: 'Paling Cepat' }
        ],

        selectCourier(courier) {
            this.selectedCourier = courier.id;
            this.courierFee = courier.price;
        },

        formatRupiah(number) {
            return 'Rp ' + number.toLocaleString('id-ID');
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
                        <!-- Saved Address Dropdown -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Saved Addresses</label>
                            <select class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-slate-50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <option value="1">Utama: Home Address</option>
                                <option value="2">Kantor: Office Address</option>
                            </select>
                        </div>

                        <!-- Country/Region -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Country/Region</label>
                            <select class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-slate-50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <option>Indonesia</option>
                            </select>
                        </div>

                        <!-- First Name & Last Name -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <input type="text" value="Raden Arya" placeholder="Nama Depan" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <input type="text" value="Wijaya" placeholder="Nama Belakang" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                        </div>

                        <!-- Address Lines -->
                        <div>
                            <input type="text" value="Jl. Teuku Umar No. 18" placeholder="Alamat Jalan" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                        <div>
                            <input type="text" value="Menteng Pavilion, Blok B" placeholder="Patokan / Catatan Alamat" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>

                        <!-- City & Province & Postal Code -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <input type="text" value="Jakarta Pusat" placeholder="Kota/Kabupaten" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                            <div>
                                <select class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                                    <option>DKI Jakarta</option>
                                    <option>Jawa Barat</option>
                                    <option>Jawa Tengah</option>
                                </select>
                            </div>
                            <div>
                                <input type="text" value="10310" placeholder="Kode Pos" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            </div>
                        </div>

                        <!-- Phone -->
                        <div>
                            <input type="text" value="+62 812-3456-7890" placeholder="Nomor WhatsApp/HP" class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <!-- Box 2: Pilihan Layanan Ekspedisi (RajaOngkir) -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24]">
                            <i class="fa-solid fa-truck-fast text-sky-600 text-sm"></i>
                            <span>Pilihan Layanan Ekspedisi</span>
                        </div>
                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md font-semibold">
                            Berat: 685g (3 item)
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-400">Tarif otomatis terverifikasi sistem logistik kurir.</p>

                    <div class="space-y-3">
                        <template x-for="c in couriers" :key="c.id">
                            <label @click="selectCourier(c)"
                                   class="flex items-center justify-between p-4 rounded-xl border-2 transition cursor-pointer"
                                   :class="selectedCourier === c.id ? 'border-sky-500 bg-sky-50/40 ring-1 ring-sky-200' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="courier" :value="c.id" :checked="selectedCourier === c.id" class="mt-0.5 text-sky-600 focus:ring-sky-500">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-[#0F1A24]" x-text="c.name"></span>
                                            <span class="text-[9px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded font-bold uppercase" x-text="c.badge"></span>
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
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-[#0F1A24]">Ringkasan Pesanan <span class="text-xs text-slate-400 font-normal">(3 Item)</span></h2>
                        <a href="/cart" class="text-xs font-bold text-sky-600 hover:underline">Ubah Item</a>
                    </div>

                    <!-- Items List -->
                    <div class="space-y-3 max-h-[260px] overflow-y-auto pr-1">
                        <!-- Item 1 -->
                        <div class="flex items-center gap-3 text-xs">
                            <img src="{{ asset('images/m1.png') }}" class="w-12 h-14 object-cover rounded-lg border border-slate-100 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=200'">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-[#0F1A24] truncate">Kemeja Pria Parang Seling</h4>
                                <p class="text-[10px] text-slate-400">Size L • Indigobiro</p>
                                <span class="font-bold text-[#0F1A24] text-xs block mt-0.5">Rp 1.350.000</span>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="flex items-center gap-3 text-xs">
                            <img src="{{ asset('images/m2.png') }}" class="w-12 h-14 object-cover rounded-lg border border-slate-100 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1598033129183-c4f50c736f10?q=80&w=200'">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-[#0F1A24] truncate">Tunik Batik Cheongsam</h4>
                                <p class="text-[10px] text-slate-400">Size M • Shanghai</p>
                                <span class="font-bold text-[#0F1A24] text-xs block mt-0.5">Rp 1.280.000</span>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="flex items-center gap-3 text-xs">
                            <img src="{{ asset('images/tulis.jpg') }}" class="w-12 h-14 object-cover rounded-lg border border-slate-100 shrink-0" onerror="this.src='https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=200'">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-[#0F1A24] truncate">Dress Anak Bunga Sekar</h4>
                                <p class="text-[10px] text-slate-400">Size 4–5 Thn</p>
                                <span class="font-bold text-[#0F1A24] text-xs block mt-1">Rp 450.000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Promo Voucher Badge -->
                    <div class="bg-sky-50/80 border border-sky-100 p-2.5 rounded-xl flex items-center justify-between text-xs text-sky-800 font-medium">
                        <span class="flex items-center gap-1.5 font-bold text-[11px]">
                            <i class="fa-solid fa-ticket text-sky-600"></i> PANDANSARIPRO10
                        </span>
                        <span class="font-bold text-sky-600 text-xs">- Rp 308.800</span>
                    </div>

                    <!-- Breakdown Table -->
                    <div class="space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-3">
                        <div class="flex justify-between">
                            <span>Subtotal (3 item)</span>
                            <span class="font-semibold text-[#0F1A24]">Rp 3.080.800</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Ongkos Kirim (JNE Reguler)</span>
                            <span class="font-semibold text-[#0F1A24]" x-text="formatRupiah(courierFee)"></span>
                        </div>
                        <div class="flex justify-between text-red-500">
                            <span>Diskon HakPrivilege Wasim</span>
                            <span class="font-semibold">- Rp 308.800</span>
                        </div>
                    </div>

                    <!-- Total Price & CTA Button -->
                    <div class="border-t border-slate-200/80 pt-3">
                        <div class="flex justify-between items-baseline mb-4">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">TOTAL PEMBAYARAN</span>
                                <span class="text-xs text-slate-400">Termasuk Pajak & Asuransi</span>
                            </div>
                            <span class="text-xl font-bold text-[#0F1A24]" x-text="formatRupiah(subtotal + courierFee - promoDiscount)"></span>
                        </div>

                        <!-- CTA Button to Midtrans Payment -->
                        <a href="/checkout/payment" class="w-full bg-[#BAE6FD] hover:bg-sky-200 text-[#0F1A24] font-bold text-xs py-3.5 px-4 rounded-xl transition flex items-center justify-center gap-2 shadow-sm">
                            <span>LANJUT KE PEMBAYARAN MIDTRANS →</span>
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>
@endsection