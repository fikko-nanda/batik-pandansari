@extends('layouts.app')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen pt-6 sm:pt-8 pb-16 font-['Plus_Jakarta_Sans']"
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
                    2. Pengiriman & Logistik
                </span>
                <span class="text-slate-300">—</span>
                <span class="text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-credit-card"></i>3. Pembayaran Midtrans Snap
                </span>
            </div>
        </div>

        <!-- Skeleton Loader saat initial data sedang dimuat (mencegah layout shift / overlap) -->
        <div x-show="loading" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12 animate-pulse">
            <div class="lg:col-span-8 space-y-6">
                <div class="h-8 bg-slate-200/70 rounded-xl w-1/2"></div>
                <div class="h-4 bg-slate-200/50 rounded-lg w-3/4"></div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 space-y-4">
                    <div class="h-5 bg-slate-200 rounded w-1/3"></div>
                    <div class="h-20 bg-slate-100 rounded-xl"></div>
                    <div class="h-20 bg-slate-100 rounded-xl"></div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 space-y-4">
                    <div class="h-5 bg-slate-200 rounded w-1/4"></div>
                    <div class="h-24 bg-slate-100 rounded-xl"></div>
                </div>
            </div>
            <div class="lg:col-span-4 space-y-4">
                <div class="bg-white p-6 rounded-2xl border border-slate-200/60 space-y-4">
                    <div class="h-5 bg-slate-200 rounded w-1/2"></div>
                    <div class="h-14 bg-slate-100 rounded-xl"></div>
                    <div class="h-14 bg-slate-100 rounded-xl"></div>
                    <div class="h-20 bg-slate-100 rounded-xl"></div>
                    <div class="h-12 bg-slate-200 rounded-xl"></div>
                </div>
            </div>
        </div>

        <!-- Main Layout: Grid 12 Kolom (hanya tampil setelah loading selesai) -->
        <div x-show="!loading" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-12">

            <!-- LEFT SIDE: Form Alamat & Informasi (8 Kolom) -->
            <div class="lg:col-span-8 space-y-6">

                <div>
                    <span class="text-[10px] font-bold tracking-wider uppercase text-slate-500 block mb-1">PENGIRIMAN & ALAMAT PENERIMA</span>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#0F1A24]">Pengiriman & Alamat Penerima</h1>
                    <p class="text-xs text-slate-500 mt-1">Lengkapi rincian alamat pengiriman. Ongkos kirim dihitung otomatis berdasarkan koordinat rute OpenStreetMap & zona tarif, lalu pembayaran diproses langsung melalui Midtrans.</p>
                </div>

                <!-- Alert Lokasi Asal Toko Belum Dikonfigurasi Admin -->
                <div x-show="originNotConfigured" x-cloak class="bg-rose-50 border-2 border-rose-300 text-rose-800 p-4 sm:p-5 rounded-2xl flex items-start gap-3 shadow-sm">
                    <i class="fa-solid fa-triangle-exclamation text-xl text-rose-600 mt-0.5 shrink-0"></i>
                    <div class="flex-1">
                        <h4 class="font-extrabold text-xs sm:text-sm text-rose-900 uppercase tracking-wide">Pengiriman Belum Dapat Diproses</h4>
                        <p class="text-xs text-rose-700 leading-relaxed mt-1">
                            Lokasi asal pengiriman belum dikonfigurasi oleh admin. Mohon hubungi admin toko agar koordinat asal toko diset sebelum pesanan dapat dibuat.
                        </p>
                    </div>
                </div>

                <!-- Box 1: Form Alamat Penerima -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24] border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-map-location-dot text-sky-600 text-sm"></i>
                        <span>Alamat Pengiriman</span>
                    </div>

                    <div class="space-y-3">
                        <template x-if="!addresses.length">
                            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold rounded-xl px-4 py-3 flex items-center justify-between gap-3">
                                <span><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Anda belum memiliki alamat tersimpan.</span>
                                <a href="/address" class="bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg whitespace-nowrap">+ Tambah Alamat Baru</a>
                            </div>
                        </template>

                        <template x-for="a in addresses" :key="a.id_user_address || a.id">
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border-2 transition cursor-pointer"
                                   :class="(selectedAddressId === (a.id_user_address || a.id)) ? 'border-sky-500 bg-sky-50/40 ring-1 ring-sky-200' : 'border-slate-200 hover:border-slate-300 bg-white'">
                                <input type="radio" name="alamat" :value="a.id_user_address || a.id" :checked="selectedAddressId === (a.id_user_address || a.id)" @change="onAddressChanged(a)" class="mt-0.5 text-sky-600 focus:ring-sky-500">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-bold text-[#0F1A24]" x-text="a.label_alamat || 'Alamat'"></span>
                                        <span x-show="a.alamat_utama ?? a.is_primary" class="text-[9px] bg-sky-100 text-[#0369A1] font-bold px-1.5 py-0.5 rounded uppercase">UTAMA</span>
                                        <span x-show="a.latitude && a.longitude" class="text-[9px] bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2 py-0.5 rounded-md uppercase flex items-center gap-1">
                                            <i class="fa-solid fa-location-dot text-emerald-600"></i> Titik Peta Aktif
                                        </span>
                                        <span x-show="!a.latitude || !a.longitude" class="text-[9px] bg-rose-50 text-rose-700 border border-rose-200 font-bold px-2 py-0.5 rounded-md uppercase flex items-center gap-1">
                                            <i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Belum Ada Titik Peta
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 leading-relaxed mt-1">
                                        <strong x-text="a.nama_penerima"></strong> • <span x-text="a.nomor_telepon || a.telepon"></span><br>
                                        <span x-text="a.alamat_lengkap"></span>, <span x-text="a.nama_kecamatan"></span>, <span x-text="a.nama_kota"></span>, <span x-text="a.nama_provinsi"></span> <span x-text="a.kode_pos"></span>
                                    </p>
                                </div>
                            </label>
                        </template>

                        <!-- Alert & Tombol Aksi jika alamat terpilih belum memiliki titik koordinat peta -->
                        <div x-show="selectedAddress && !selectedAddressHasCoordinates" x-cloak class="bg-amber-50/90 border border-amber-200 text-amber-900 p-3.5 sm:p-4 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-location-crosshairs text-base"></i>
                                </div>
                                <div>
                                    <span class="font-bold block text-[#0F1A24]">Titik Peta Pengiriman Belum Disetel</span>
                                    <span class="text-[11px] text-amber-800">Tentukan titik lokasi pada peta agar ongkos kirim rute darat dapat dihitung otomatis.</span>
                                </div>
                            </div>
                            <a href="/address" class="inline-flex items-center justify-center gap-1.5 bg-[#0F1A24] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs whitespace-nowrap transition shadow-sm shrink-0">
                                <i class="fa-solid fa-map-location-dot text-sky-400"></i>
                                <span>Atur Titik Peta Alamat</span>
                            </a>
                        </div>

                        <div class="flex items-center justify-between pt-2 text-[11px] text-slate-500 border-t border-slate-100">
                            <span>Ingin menggunakan alamat lain?</span>
                            <a href="/address" class="text-sky-600 font-bold hover:underline flex items-center gap-1">
                                Kelola Buku Alamat <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Patokan / Catatan Alamat (opsional)</label>
                            <input type="text" x-model="catatan" placeholder="Patokan / catatan alamat..."
                                   class="w-full text-xs font-medium border border-slate-200 rounded-xl px-3 py-2.5 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>
                </div>

                <!-- Box 2: Panduan Alur Pemesanan & Pembayaran Midtrans Snap -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24] border-b border-slate-100 pb-3">
                        <i class="fa-solid fa-route text-sky-600 text-sm"></i>
                        <span>Alur Pemesanan & Pembayaran</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl space-y-1">
                            <span class="font-extrabold text-sky-700 block flex items-center gap-1.5">
                                <i class="fa-solid fa-map-location-dot"></i> 1. Rute & Ongkir Otomatis
                            </span>
                            <p class="text-[11px] text-slate-500 leading-normal">Kalkulasi rute jalan & zona tarif dihitung otomatis via OpenStreetMap & OSRM.</p>
                        </div>
                        <div class="p-3 bg-sky-50 border border-sky-200/80 rounded-xl space-y-1">
                            <span class="font-extrabold text-sky-800 block flex items-center gap-1.5">
                                <i class="fa-solid fa-wallet"></i> 2. Pop-Up Midtrans Snap
                            </span>
                            <p class="text-[11px] text-sky-900 leading-normal">Pilih metode pembayaran (QRIS, VA Bank, GoPay, Kartu) langsung di pop-up modal.</p>
                        </div>
                        <div class="p-3 bg-emerald-50 border border-emerald-200/80 rounded-xl space-y-1">
                            <span class="font-extrabold text-emerald-700 block flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check"></i> 3. Pesanan Diproses
                            </span>
                            <p class="text-[11px] text-emerald-800 leading-normal">Setelah transaksi berhasil, pesanan langsung masuk antrean pengemasan toko.</p>
                        </div>
                    </div>
                </div>

                <!-- Box 3: Kalkulasi Jarak Rute & Ongkos Kirim (OSRM & Zona) -->
                <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#0F1A24]">
                            <i class="fa-solid fa-truck-fast text-sky-600 text-sm"></i>
                            <span>Kalkulasi Pengiriman Otomatis (OpenStreetMap & OSRM)</span>
                        </div>
                        <button type="button"
                                @click="calculateShipping()"
                                :disabled="loadingShipping || !selectedAddress || !selectedAddressHasCoordinates"
                                class="text-[11px] text-sky-600 font-bold hover:underline flex items-center gap-1 disabled:opacity-40 cursor-pointer">
                            <i class="fa-solid fa-rotate-right" :class="loadingShipping && 'fa-spin'"></i>
                            <span>Hitung Ulang</span>
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div x-show="loadingShipping" class="py-5 text-center text-xs text-slate-500 font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-circle-notch fa-spin text-sky-600 text-base"></i>
                        <span>Menghitung rute darat OSRM dan tarif zona pengiriman...</span>
                    </div>

                    <!-- Hasil Kalkulasi Sukses -->
                    <div x-show="!loadingShipping && shippingQuote" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <!-- Jarak & Durasi -->
                            <div class="p-3 bg-sky-50/70 border border-sky-100 rounded-xl space-y-1">
                                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Jarak Rute Jalan (OSRM)</span>
                                <div class="text-sm font-extrabold text-slate-900 flex items-baseline gap-1.5">
                                    <span x-text="shippingQuote?.jarak_km ? Number(shippingQuote.jarak_km).toFixed(2) + ' km' : '-'"></span>
                                    <span class="text-[10px] text-slate-500 font-medium" x-text="shippingQuote?.durasi_menit ? '(± ' + shippingQuote.durasi_menit + ' menit)' : ''"></span>
                                </div>
                                <p class="text-[10px] text-slate-500">Dihitung berdasarkan rute jalan darat ke koordinat tujuan.</p>
                            </div>

                            <!-- Berat Paket & Pembulatan -->
                            <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl space-y-1">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Berat Paket & Pembulatan</span>
                                <div class="text-sm font-extrabold text-slate-900 flex items-baseline gap-1.5">
                                    <span x-text="(shippingQuote?.berat_dibulatkan_kg ?? 1) + ' kg'"></span>
                                    <span class="text-[10px] text-slate-500 font-medium" x-text="'(' + (shippingQuote?.berat_gram ?? 0) + ' gram, min. 1 kg)'"></span>
                                </div>
                                <p class="text-[10px] text-slate-500">Dibulatkan ke atas per kilogram penuh.</p>
                            </div>
                        </div>

                        <!-- Rincian Zona & Biaya -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fa-solid fa-layer-group text-sky-600"></i>
                                    <span x-text="shippingQuote?.zona?.nama_zona || 'Zona Pengiriman'"></span>
                                </span>
                                <span class="text-sm font-extrabold text-slate-900" x-text="formatRupiah(shippingQuote?.biaya_pengiriman ?? 0)"></span>
                            </div>
                            <div class="text-[11px] text-slate-500 flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 border-t border-slate-200/60">
                                <span>Biaya Dasar: <strong class="text-slate-700" x-text="formatRupiah(shippingQuote?.zona?.biaya_dasar ?? 0)"></strong></span>
                                <span>Tarif per kg: <strong class="text-slate-700" x-text="formatRupiah(shippingQuote?.zona?.biaya_per_kg ?? 0) + ' / kg'"></strong></span>
                                <span>Perhitungan: <span class="text-slate-600 font-mono text-[10px]">Dasar + (<span x-text="shippingQuote?.berat_dibulatkan_kg ?? 1"></span> kg × Tarif/kg)</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Peringatan jika error kalkulasi -->
                    <div x-show="!loadingShipping && shippingError && !originNotConfigured" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                            <span>Kalkulasi Ongkos Kirim Belum Berhasil</span>
                        </div>
                        <p x-text="shippingError"></p>
                    </div>

                    <!-- Peringatan jika belum ada alamat / belum ada koordinat -->
                    <div x-show="!loadingShipping && (!selectedAddress || !selectedAddressHasCoordinates)" class="p-3.5 bg-slate-50 border border-dashed border-slate-300 rounded-xl text-center text-xs text-slate-500">
                        <i class="fa-solid fa-map-pin text-slate-400 mb-1 text-sm block"></i>
                        <span x-text="!selectedAddress ? 'Pilih alamat pengiriman di atas untuk menghitung ongkir otomatis.' : 'Alamat belum memiliki koordinat peta. Perbarui alamat untuk menghitung ongkir.'"></span>
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
                                <span x-show="loadingShipping" class="text-slate-400 text-[11px]"><i class="fa-solid fa-circle-notch fa-spin text-sky-500"></i> Menghitung...</span>
                                <span x-show="!loadingShipping && shippingQuote" class="font-semibold text-slate-800" x-text="'+' + formatRupiah(shippingCost)"></span>
                                <span x-show="!loadingShipping && !shippingQuote" class="text-amber-600 text-[11px] font-semibold">Belum terhitung</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-[#0F1A24] pt-2 border-t border-slate-100">
                                <span>Total Pembayaran</span>
                                <span class="text-sky-600 font-extrabold" x-text="formatRupiah(totalPembayaran)"></span>
                            </div>
                        </div>

                        <!-- Pesan Submit Error -->
                        <div x-show="submitMsg" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl" x-text="submitMsg"></div>

                        <!-- Tombol Buat Pesanan & Bayar via Midtrans Snap (Embed Modal) -->
                        <button type="button" @click="handleCheckout()" :disabled="!canCheckout || isLoading"
                                class="w-full bg-[#0F1A24] hover:bg-slate-800 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg transition cursor-pointer flex items-center justify-center gap-2 text-xs uppercase tracking-wider disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="isLoading" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i> Menyiapkan Pembayaran...
                            </span>
                            <span x-show="!isLoading && loadingShipping" class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-notch fa-spin"></i> Menghitung Ongkir...
                            </span>
                            <span x-show="!isLoading && !loadingShipping && originNotConfigured" class="flex items-center gap-2 text-amber-300">
                                <i class="fa-solid fa-store-slash"></i> Lokasi Toko Belum Diset
                            </span>
                            <span x-show="!isLoading && !loadingShipping && !originNotConfigured && (!selectedAddress || !selectedAddressHasCoordinates)" class="flex items-center gap-2 text-amber-300">
                                <i class="fa-solid fa-location-dot"></i> Lengkapi Koordinat Alamat
                            </span>
                            <span x-show="!isLoading && !loadingShipping && !originNotConfigured && selectedAddress && selectedAddressHasCoordinates && !shippingQuote" class="flex items-center gap-2 text-amber-300">
                                <i class="fa-solid fa-triangle-exclamation"></i> Ongkir Belum Dihitung
                            </span>
                            <span x-show="!isLoading && !loadingShipping && canCheckout" class="flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-emerald-400"></i> Bayar Sekarang via Midtrans
                            </span>
                        </button>

                        <p class="text-[10px] text-slate-400 text-center leading-relaxed">
                            Pembayaran resmi dan aman langsung via Pop-Up Midtrans Snap (QRIS, Virtual Account, GoPay, dan Kartu Kredit).
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

            // === State Pengiriman & Ongkir ===
            shippingQuote: null,
            loadingShipping: false,
            shippingError: '',
            originNotConfigured: false,

            // Nomor WhatsApp admin untuk konfirmasi
            waNumber: '6283820380977',

            voucherList: [],
            voucherListPesan: '',

            // === Getters & Komputasi ===
            get selectedAddress() {
                return (this.addresses || []).find(a => (a.id_user_address || a.id) === this.selectedAddressId) ?? null;
            },

            get selectedAddressHasCoordinates() {
                const a = this.selectedAddress;
                if (!a) return false;
                const lat = a.latitude != null ? String(a.latitude).trim() : '';
                const lng = a.longitude != null ? String(a.longitude).trim() : '';
                return lat !== '' && lng !== '';
            },

            get canCheckout() {
                return !this.isLoading &&
                       !this.loadingShipping &&
                       !this.originNotConfigured &&
                       Boolean(this.selectedAddress) &&
                       this.selectedAddressHasCoordinates &&
                       Boolean(this.shippingQuote) &&
                       !this.shippingError &&
                       this.items.length > 0;
            },

            get shippingCost() {
                return Number(this.shippingQuote?.biaya_pengiriman ?? 0);
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
                // Subtotal − Diskon + PPN 11% + Ongkos Kirim
                return Math.max(0, (this.subtotal - this.discountAmount) + this.ppnAmount + this.shippingCost);
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

            onAddressChanged(a) {
                this.selectedAddressId = a.id_user_address || a.id;
                this.alamatAktif = a;
                this.calculateShipping();
            },

            async calculateShipping() {
                this.shippingError = '';
                this.originNotConfigured = false;

                if (!this.selectedAddressId) {
                    this.shippingQuote = null;
                    return false;
                }

                if (!this.selectedAddressHasCoordinates) {
                    this.shippingQuote = null;
                    return false;
                }

                this.loadingShipping = true;
                try {
                    const res = await PandansariAPI.shippingQuote(this.selectedAddressId);
                    if (res && res.ok && res.data) {
                        this.shippingQuote = res.data;
                        this.shippingError = '';
                        this.originNotConfigured = false;
                        return true;
                    } else {
                        this.shippingQuote = null;
                        const msg = res?.message || 'Gagal menghitung ongkos kirim.';
                        const code = res?.code || '';

                        if (code === 'SHIPPING_ORIGIN_NOT_CONFIGURED' || msg.includes('asal pengiriman belum dikonfigurasi')) {
                            this.originNotConfigured = true;
                            this.shippingError = 'Lokasi asal pengiriman belum dikonfigurasi oleh admin.';
                        } else if (code === 'SHIPPING_ZONE_NOT_FOUND' || msg.includes('Zona pengiriman')) {
                            this.shippingError = msg || 'Alamat tujuan berada di luar jangkauan zona pengiriman aktif.';
                        } else if (code === 'SHIPPING_ADDRESS_COORDINATES_REQUIRED' || msg.includes('koordinat')) {
                            this.shippingError = 'Alamat pengiriman belum memiliki koordinat peta OpenStreetMap.';
                        } else {
                            this.shippingError = msg;
                        }
                        return false;
                    }
                } catch (e) {
                    this.shippingQuote = null;
                    this.shippingError = 'Tidak dapat menghubungi layanan kalkulasi pengiriman.';
                    return false;
                } finally {
                    this.loadingShipping = false;
                }
            },

            hitungOngkir() {
                return this.calculateShipping();
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

            // === Submit Checkout & Langsung Buka Midtrans Snap Pop-Up (Embed Mode) ===
            async handleCheckout() {
                if (!window.PandansariAPI || !PandansariAPI.getToken()) {
                    window.location.href = '/login';
                    return;
                }
                this.submitMsg = '';
                this.submitOk = false;

                if (!this.canCheckout) {
                    if (this.originNotConfigured) {
                        this.submitMsg = 'Lokasi asal pengiriman belum dikonfigurasi oleh admin. Mohon hubungi admin toko.';
                    } else if (!this.selectedAddress) {
                        this.submitMsg = 'Pilih alamat pengiriman terlebih dahulu.';
                    } else if (!this.selectedAddressHasCoordinates) {
                        this.submitMsg = 'Alamat belum memiliki koordinat OpenStreetMap. Silakan perbarui alamat terlebih dahulu.';
                    } else if (!this.shippingQuote) {
                        this.submitMsg = this.shippingError || 'Kalkulasi ongkos kirim belum selesai atau belum berhasil.';
                    }
                    return;
                }

                if (!this.items.length) {
                    this.submitMsg = 'Keranjang Anda kosong.';
                    return;
                }

                // Validasi stok: hanya blokir jika kuantitas melebihi stok yang ada
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
                };

                this.isLoading = true;
                try {
                    // 1. Buat pesanan: POST /api/checkout
                    const res = await PandansariAPI.checkout(payload);
                    if (!res.ok) {
                        if (res.status === 401) {
                            this.submitMsg = 'Sesi Anda berakhir. Silakan login kembali.';
                            setTimeout(() => { window.location.href = '/login'; }, 1200);
                        } else if (res.code === 'SHIPPING_ORIGIN_NOT_CONFIGURED' || (res.message && res.message.includes('asal pengiriman belum dikonfigurasi'))) {
                            this.originNotConfigured = true;
                            this.submitMsg = 'Lokasi asal pengiriman belum dikonfigurasi oleh admin.';
                        } else {
                            this.submitMsg = res.message || 'Pesanan gagal dibuat. Silakan coba lagi.';
                        }
                        return;
                    }

                    const order = res.data ?? {};
                    const orderId = order.id_order ?? order.id ?? order.nomor_pesanan;

                    // 2. Minta Snap Token pembayaran Midtrans: POST /api/orders/{id}/payment
                    const payRes = await PandansariAPI.payOrder(orderId);
                    if (!payRes.ok || !payRes.data) {
                        this.submitMsg = payRes.message || 'Pesanan berhasil dibuat, namun gagal menyiapkan pembayaran Midtrans. Mengalihkan ke detail pesanan...';
                        setTimeout(() => { window.location.href = `/orders/${orderId}`; }, 1500);
                        return;
                    }

                    this.submitOk = true;

                    // 3. Tampilkan Pop-Up Modal Midtrans Snap (Embed Mode) langsung tanpa redirect
                    await PandansariAPI.bayarSnap(payRes.data, {
                        onSuccess: (result) => {
                            if (typeof PandansariAPI.toast === 'function') {
                                PandansariAPI.toast('Pembayaran berhasil! Mengalihkan ke Pesanan Saya...', true);
                            }
                            setTimeout(() => { window.location.href = '/orders'; }, 1000);
                        },
                        onPending: (result) => {
                            if (typeof PandansariAPI.toast === 'function') {
                                PandansariAPI.toast('Pesanan dicatat. Menunggu penyelesaian pembayaran...', true);
                            }
                            setTimeout(() => { window.location.href = `/orders/${orderId}`; }, 1200);
                        },
                        onError: (result) => {
                            this.submitMsg = result?.message || 'Pembayaran gagal atau dibatalkan.';
                            setTimeout(() => { window.location.href = `/orders/${orderId}`; }, 1500);
                        },
                        onClose: () => {
                            // Pengguna menutup pop-up modal sebelum menyelesaikan pembayaran
                            if (typeof PandansariAPI.toast === 'function') {
                                PandansariAPI.toast('Jendela pembayaran ditutup. Anda dapat melanjutkan pembayaran melalui Pesanan Saya.', false);
                            }
                            setTimeout(() => { window.location.href = `/orders/${orderId}`; }, 1000);
                        }
                    });
                } catch (err) {
                    console.error('[Checkout] Error checkout:', err);
                    this.submitMsg = err?.message || 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.';
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
                this.muatVoucher();
                if (this.selectedAddressId) {
                    await this.calculateShipping();
                }
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