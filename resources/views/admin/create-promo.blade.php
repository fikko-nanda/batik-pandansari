@extends('layouts.admin')

@section('content')

<div x-data="{
    // Form Inputs
    promoName: 'Pesona Wastra Oktober',
    promoCode: 'WASTRA15',
    promoDesc: 'Nikmati potongan 15% untuk koleksi batik pilihan Pandansari. Hadirkan pesona wastra dalam setiap momen istimewa.',
    
    discountType: 'percentage', // 'percentage' or 'nominal'
    discountValue: 15,
    maxDiscount: 250000,
    minPurchase: 1000000,
    quota: 100, // Input Kuota Penggunaan
    perUserLimit: 1,
    
    startDate: '2026-10-28T00:00',
    endDate: '2026-11-04T23:59',
    autoDisable: true,

    // Calculated Simulation Values
    get simulatedDiscount() {
        let subtotal = 1500000;
        if (this.discountType === 'percentage') {
            let calculated = (subtotal * this.discountValue) / 100;
            return Math.min(calculated, this.maxDiscount);
        } else {
            return Math.min(this.discountValue, subtotal);
        }
    },

    get simulatedTotal() {
        return 1500000 - this.simulatedDiscount;
    },

    copyCode() {
        navigator.clipboard.writeText(this.promoCode);
        alert('Kode promo ' + this.promoCode + ' berhasil disalin!');
    },

    publishPromo() {
        alert('Promo ' + this.promoName + ' (' + this.promoCode + ') berhasil dipublikasikan!');
    }
}" class="space-y-6 text-slate-800 font-sans pb-10">

    <!-- 1. HEADER TITLE -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
            Buat Promo
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            Rangkai penawaran istimewa untuk kolektor wastra. Atur diskon, periode, dan jangkauan promo toko.
        </p>
    </div>

    <!-- 2. INFO BANNER -->
    <div class="bg-sky-50 border border-sky-200/80 rounded-2xl p-3.5 flex items-center gap-3 text-xs text-sky-800 font-medium">
        <i class="fa-solid fa-circle-info text-sky-600 text-sm shrink-0"></i>
        <span>Promo akan tersedia sesuai jadwal setelah dipublikasikan. Kolom bertanda * wajib diisi.</span>
    </div>

    <!-- 3. MAIN GRID (FORM LEFT + PREVIEW RIGHT) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT COLUMN: FORM INPUTS (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- SECTION 1: DETAIL & KODE PROMO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Detail & kode promo</h2>
                        <p class="text-[10px] text-slate-400">Identitas penawaran yang ditampilkan kepada pelanggan.</p>
                    </div>
                </div>

                <div class="space-y-4 text-xs font-medium">
                    <!-- Nama Promo -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">NAMA PROMO *</label>
                        <input type="text" x-model="promoName" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400">
                    </div>

                    <!-- Kode Promo -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">KODE PROMO *</label>
                        <div class="relative">
                            <input type="text" x-model="promoCode" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono font-black text-slate-900 tracking-wider uppercase focus:outline-none focus:ring-2 focus:ring-sky-400 pr-10">
                            <button type="button" @click="copyCode()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 cursor-pointer" title="Salin Kode">
                                <i class="fa-regular fa-copy text-sm"></i>
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400 block mt-1">Kode unik, tanpa spasi. Pelanggan memasukkannya saat checkout.</span>
                    </div>

                    <!-- Deskripsi Promo -->
                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">DESKRIPSI PROMO</label>
                        <textarea x-model="promoDesc" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 leading-relaxed"></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: JENIS & NILAI DISKON (+ KUOTA) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Jenis & nilai diskon</h2>
                        <p class="text-[10px] text-slate-400">Tentukan manfaat promo, kuota, dan batas potongannya.</p>
                    </div>
                </div>

                <div class="space-y-4 text-xs font-medium">
                    <!-- Radio Choice Jenis Diskon -->
                    <div class="grid grid-cols-2 gap-3">
                        <label @click="discountType = 'percentage'" :class="discountType === 'percentage' ? 'bg-[#AEE2F0]/50 border-sky-400 ring-2 ring-sky-300' : 'bg-slate-50 border-slate-200'" class="p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3">
                            <input type="radio" name="disc_type" value="percentage" x-model="discountType" class="mt-0.5 accent-sky-600">
                            <div>
                                <strong class="text-slate-900 font-extrabold block text-xs">Persentase</strong>
                                <span class="text-[10px] text-slate-500 block">Potongan dalam persen</span>
                            </div>
                        </label>

                        <label @click="discountType = 'nominal'" :class="discountType === 'nominal' ? 'bg-[#AEE2F0]/50 border-sky-400 ring-2 ring-sky-300' : 'bg-slate-50 border-slate-200'" class="p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3">
                            <input type="radio" name="disc_type" value="nominal" x-model="discountType" class="mt-0.5 accent-sky-600">
                            <div>
                                <strong class="text-slate-900 font-extrabold block text-xs">Nominal</strong>
                                <span class="text-[10px] text-slate-500 block">Potongan rupiah tetap</span>
                            </div>
                        </label>
                    </div>

                    <!-- Input Nilai Diskon & Maksimum Potongan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">NILAI DISKON *</label>
                            <div class="relative">
                                <input type="number" x-model.number="discountValue" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 font-black text-slate-400 text-xs" x-text="discountType === 'percentage' ? '%' : 'Rp'"></span>
                            </div>
                            <span class="text-[9px] text-slate-400 block mt-1">Diskon dihitung dari subtotal produk, tidak termasuk ongkir.</span>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">MAKSIMUM POTONGAN</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-extrabold text-slate-400 text-xs">Rp</span>
                                <input type="number" x-model.number="maxDiscount" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 pl-9 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400">
                            </div>
                        </div>
                    </div>

                    <!-- Input Kuota Penggunaan & Batas Per Pelanggan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100">
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">KUOTA PENGGUNAAN *</label>
                            <div class="relative">
                                <input type="number" x-model.number="quota" placeholder="100" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400 pr-20">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 font-extrabold text-slate-400 text-[10px]">penggunaan</span>
                            </div>
                            <span class="text-[9px] text-slate-400 block mt-1">Batas total penggunaan promo oleh seluruh pelanggan.</span>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">BATAS PER PELANGGAN</label>
                            <div class="relative">
                                <input type="number" x-model.number="perUserLimit" placeholder="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-black text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-400 pr-12">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 font-extrabold text-slate-400 text-[10px]">kali</span>
                            </div>
                            <span class="text-[9px] text-slate-400 block mt-1">Maksimal pemakaian voucher per akun pelanggan.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: PERIODE PROMO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="w-8 h-8 bg-[#0A192F] text-sky-400 rounded-xl flex items-center justify-center text-xs shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Periode promo</h2>
                        <p class="text-[10px] text-slate-400">Jadwal mengikuti Waktu Indonesia Barat (WIB).</p>
                    </div>
                </div>

                <div class="space-y-4 text-xs font-medium">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">MULAI *</label>
                            <input type="text" readonly value="28 Okt 2026 - 00:00" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1">BERAKHIR *</label>
                            <input type="text" readonly value="04 Nov 2026 - 23:59" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-bold text-slate-800 focus:outline-none cursor-not-allowed">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer pt-1">
                        <input type="checkbox" x-model="autoDisable" class="rounded accent-sky-600 w-4 h-4">
                        <span class="text-xs font-extrabold text-slate-800">Nonaktifkan otomatis saat periode berakhir atau kuota habis</span>
                    </label>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: PREVIEW & RINGKASAN (5 COLS) -->
        <div class="lg:col-span-5 space-y-5">
            
            <!-- PRATINJAU PROMO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="font-extrabold text-slate-900 text-sm">Pratinjau promo</h3>
                    <i class="fa-solid fa-eye text-slate-400 text-xs"></i>
                </div>

                <!-- CARD VOUCHER DIGITAL DARK NAVY -->
                <div class="bg-[#0A192F] text-white rounded-2xl p-5 space-y-4 relative overflow-hidden shadow-md">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-300 block">Pandansari</span>
                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 block mt-0.5" x-text="promoName"></span>
                    </div>

                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white tracking-tight" x-text="'Diskon ' + discountValue + (discountType === 'percentage' ? '%' : ' Rp')"></div>
                        <p class="text-[10px] text-slate-300 leading-tight mt-1" x-text="'Untuk koleksi batik pilihan, hingga Rp ' + maxDiscount.toLocaleString('id-ID') + '.'"></p>
                    </div>

                    <!-- KODE PROMO BOX -->
                    <div class="bg-[#AEE2F0] text-slate-900 rounded-xl p-2.5 text-center font-mono font-black text-sm tracking-widest uppercase">
                        <span x-text="promoCode"></span>
                    </div>

                    <div class="text-[9px] text-slate-400 space-y-0.5 border-t border-white/10 pt-3">
                        <p x-text="'Min. belanja Rp ' + minPurchase.toLocaleString('id-ID')"></p>
                        <p>28 Okt - 04 Nov 2026</p>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400">Contoh tampilan voucher pelanggan. Promo belum tampil di toko.</p>
            </div>

            <!-- RINGKASAN PROMO -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3 text-xs">
                <h3 class="font-extrabold text-slate-900 text-sm pb-2 border-b border-slate-100">Ringkasan promo</h3>

                <div class="space-y-2 text-[11px] font-medium">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Jenis diskon</span>
                        <strong class="text-slate-900 font-bold" x-text="(discountType === 'percentage' ? 'Persentase - ' : 'Nominal - ') + discountValue + (discountType === 'percentage' ? '%' : '')"></strong>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Maks. potongan</span>
                        <strong class="text-slate-900 font-bold" x-text="'Rp ' + maxDiscount.toLocaleString('id-ID')"></strong>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Minimum belanja</span>
                        <strong class="text-slate-900 font-bold" x-text="'Rp ' + minPurchase.toLocaleString('id-ID')"></strong>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Kuota</span>
                        <strong class="text-slate-900 font-bold" x-text="(quota || 0) + ' penggunaan'"></strong>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Per pelanggan</span>
                        <strong class="text-slate-900 font-bold" x-text="(perUserLimit || 1) + ' kali'"></strong>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Produk</span>
                        <strong class="text-slate-900 font-bold">86 produk</strong>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Pelanggan</span>
                        <strong class="text-slate-900 font-bold">Semua pelanggan</strong>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 text-[10px] font-bold text-slate-500">
                    Terjadwal mulai 28 Oktober 2026 pukul 00:00 WIB.
                </div>
            </div>

            <!-- SIMULASI POTONGAN -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-5 space-y-3 text-xs">
                <h3 class="font-extrabold text-slate-900 text-sm">Simulasi potongan</h3>

                <div class="space-y-2 text-[11px] font-bold">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Subtotal produk</span>
                        <span class="text-slate-900">Rp 1.500.000</span>
                    </div>

                    <div class="flex items-center justify-between text-emerald-700">
                        <span x-text="'Diskon ' + discountValue + '%'"></span>
                        <span x-text="'- Rp ' + simulatedDiscount.toLocaleString('id-ID')"></span>
                    </div>

                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-slate-900 text-xs">
                        <span>Total setelah diskon</span>
                        <span class="font-black" x-text="'Rp ' + simulatedTotal.toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <p class="text-[9px] text-slate-400">Belum termasuk biaya pengiriman.</p>
            </div>

            <!-- ALERT PERIKSA SEBELUM TERBIT -->
            <div class="bg-amber-50/80 border border-amber-200 rounded-2xl p-4 flex items-start gap-3 text-xs text-amber-900">
                <i class="fa-regular fa-lightbulb text-amber-600 text-sm mt-0.5 shrink-0"></i>
                <div>
                    <strong class="font-bold block mb-0.5">Periksa sebelum terbit</strong>
                    <p class="text-[10px] text-amber-800 leading-tight">
                        Pastikan kode, periode, dan target promo sudah sesuai. Kode promo tidak dapat diubah setelah dipublikasikan.
                    </p>
                </div>
            </div>

        </div>

    </div>

    <!-- 4. BOTTOM ACTION BAR -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-bold mt-6">
        <div class="flex items-center gap-2 text-slate-400 text-[11px]">
            <i class="fa-regular fa-circle-check"></i>
            <span>Belum disimpan • Semua kolom wajib telah terisi</span>
        </div>

        <div class="flex items-center gap-2.5 self-end sm:self-auto">
            <button type="button" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition cursor-pointer">
                Batal
            </button>
            <button type="button" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl transition cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-box-archive text-xs"></i>
                <span>Simpan Draf</span>
            </button>
            <button type="button" @click="publishPromo()" class="px-6 py-2.5 bg-[#AEE2F0] hover:bg-[#94d7e8] text-slate-900 font-black rounded-xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Publikasikan Promo</span>
            </button>
        </div>
    </div>

</div>

@endsection