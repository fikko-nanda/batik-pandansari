<!-- TOP NAVBAR ADMIN -->
<header class="bg-white border-b border-slate-200/80 py-2 sm:py-2.5 md:py-3.5 px-3 sm:px-4 md:px-6 sticky top-0 z-30 flex items-center justify-between gap-2 sm:gap-3 md:gap-4">
    
    <div class="flex items-center gap-2 sm:gap-2.5 md:gap-3 flex-1 min-w-0">
        <!-- Hamburger Menu -->
        <button type="button" @click="sidebarOpen = true" class="lg:hidden w-9 h-9 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 active:scale-[0.98] transition flex items-center justify-center shrink-0" aria-label="Buka menu">
            <i class="fa-solid fa-bars text-sm"></i>
        </button>

        <!-- Search Input Header -->
        <div class="relative w-full max-w-[220px] xs:max-w-[260px] sm:max-w-xs md:max-w-sm lg:max-w-md">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px] sm:text-xs"></i>
            <input type="text" 
                   placeholder="Cari wastra, no. resi, ID pesanan..." 
                   class="w-full text-[11px] sm:text-xs font-medium border border-slate-200 rounded-xl pl-8 sm:pl-9 pr-3 py-1.5 sm:py-2 bg-slate-50/80 text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
    </div>

    <!-- Right Action Items -->
    <div class="flex items-center gap-1.5 sm:gap-2 md:gap-2.5 lg:gap-3 shrink-0 text-[11px] sm:text-xs">
        
        <!-- Month Selector -->
        <div class="hidden md:flex bg-slate-100 text-slate-700 px-2.5 lg:px-3 py-1 lg:py-1.5 rounded-xl font-bold items-center gap-1.5">
            <i class="fa-regular fa-calendar text-slate-500"></i>
            <span>{{ now()->locale('id')->translatedFormat('M Y') }}</span>
        </div>

        <!-- Live Midtrans Status Badge -->
        <div class="hidden xl:flex bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl font-bold items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="text-[11px]">Midtrans Live</span>
        </div>

        <!-- Notification Bell (aktif) — data dari GET /admin/{orders,payments}/summary + /admin/orders -->
        <div class="relative"
             x-data="{
                buka: false,
                memuat: true,
                items: [],
                dibaca: {},
                KUNCI_SIMPAN: 'pandansari_admin_notif_dibaca',

                get belumDibaca() {
                    return this.items.filter(i => !this.dibaca[i.kunci]).length;
                },

                siapkan() {
                    try { this.dibaca = JSON.parse(localStorage.getItem(this.KUNCI_SIMPAN)) || {}; }
                    catch { this.dibaca = {}; }
                    this.muat();
                    // Segarkan diam-diam tiap 60 detik selama panel tidak dibuka.
                    setInterval(() => { if (!this.buka) this.muat(); }, 60000);
                },

                simpanDibaca() {
                    try {
                        // Simpan hanya kunci yang masih relevan agar localStorage tidak menumpuk.
                        const aktif = {};
                        this.items.forEach(i => { if (this.dibaca[i.kunci]) aktif[i.kunci] = this.dibaca[i.kunci]; });
                        this.dibaca = aktif;
                        localStorage.setItem(this.KUNCI_SIMPAN, JSON.stringify(aktif));
                    } catch {}
                },

                tandai(item) {
                    if (!this.dibaca[item.kunci]) { this.dibaca[item.kunci] = Date.now(); this.simpanDibaca(); }
                },

                tandaiSemua() {
                    this.items.forEach(i => { this.dibaca[i.kunci] = Date.now(); });
                    this.simpanDibaca();
                },

                async muat() {
                    if (!PandansariAPI.getToken()) { this.items = []; this.memuat = false; return; }
                    this.memuat = true;

                    const [os, ps, od] = await Promise.all([
                        PandansariAPI.admin.ordersSummary(),
                        PandansariAPI.admin.paymentsSummary(),
                        PandansariAPI.admin.orders({ page: 1, per_page: 3 }),
                    ]);

                    const daftar = [];
                    const statusOrder = os.ok ? (os.data?.per_status ?? {}) : {};
                    const statusBayar = ps.ok ? (ps.data?.per_status ?? {}) : {};

                    const nMenunggu = Number(statusOrder.menunggu_pembayaran ?? 0);
                    if (nMenunggu > 0) {
                        daftar.push({
                            kunci: 'order:menunggu_pembayaran:' + nMenunggu,
                            ikon: 'fa-hourglass-half',
                            warna: 'bg-amber-100 text-amber-700',
                            judul: nMenunggu + ' pesanan menunggu pembayaran',
                            keterangan: 'Kirim pengingat atau periksa status transaksi kolektor.',
                            tautan: '/admin/orders?status=menunggu_pembayaran',
                        });
                    }

                    const nDiproses = Number(statusOrder.diproses ?? 0);
                    if (nDiproses > 0) {
                        daftar.push({
                            kunci: 'order:diproses:' + nDiproses,
                            ikon: 'fa-box-open',
                            warna: 'bg-sky-100 text-sky-700',
                            judul: nDiproses + ' pesanan siap diproses',
                            keterangan: 'Pesanan sudah dibayar dan menunggu dikemas.',
                            tautan: '/admin/orders?status=diproses',
                        });
                    }

                    const nBayarMenunggu = Number(statusBayar.menunggu ?? 0);
                    if (nBayarMenunggu > 0) {
                        daftar.push({
                            kunci: 'bayar:menunggu:' + nBayarMenunggu,
                            ikon: 'fa-credit-card',
                            warna: 'bg-indigo-100 text-indigo-700',
                            judul: nBayarMenunggu + ' pembayaran belum dikonfirmasi',
                            keterangan: 'Periksa gerbang Midtrans untuk status terbaru.',
                            tautan: '/admin/midtrans',
                        });
                    }

                    if (od.ok) {
                        (od.data ?? []).slice(0, 3).forEach(o => {
                            daftar.push({
                                kunci: 'pesanan:' + o.id_order,
                                ikon: 'fa-receipt',
                                warna: 'bg-emerald-100 text-emerald-700',
                                judul: 'Pesanan ' + (o.nomor_pesanan ?? '-'),
                                keterangan: (o.nama_penerima ?? 'Pelanggan') + ' • ' + PandansariAPI.formatRp(o.total_pembayaran)
                                    + ' • ' + String(o.status_pesanan ?? '').replaceAll('_', ' '),
                                tautan: '/admin/orders?detail=' + o.id_order,
                            });
                        });
                    }

                    this.items = daftar;
                    this.memuat = false;
                },
             }"
             x-init="siapkan()"
             @click.outside="buka = false">

            <button type="button"
                    @click="buka = !buka; if (buka) muat()"
                    :aria-expanded="buka"
                    title="Notifikasi"
                    class="w-9 h-9 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center hover:bg-slate-200 transition relative cursor-pointer">
                <i class="fa-regular fa-bell text-sm"></i>

                <!-- Jumlah notifikasi belum dibaca -->
                <span x-show="belumDibaca > 0" x-cloak
                      class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 bg-rose-500 text-white text-[9px] font-extrabold rounded-full flex items-center justify-center leading-none"
                      x-text="belumDibaca > 99 ? '99+' : belumDibaca"></span>

                <!-- Indikator hijau: notifikasi aktif, belum ada yang baru -->
                <span x-show="!memuat && belumDibaca === 0" x-cloak
                      class="absolute top-2 right-2 w-2 h-2 bg-emerald-500 rounded-full"></span>
            </button>

            <!-- Panel Dropdown Notifikasi -->
            <div x-show="buka" x-cloak x-transition
                 class="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 overflow-hidden">

                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold text-[#0F1A24]">Notifikasi</span>
                        <span x-show="belumDibaca > 0" x-cloak
                              class="text-[10px] font-bold text-white bg-rose-500 px-1.5 py-0.5 rounded-full"
                              x-text="belumDibaca"></span>
                    </div>
                    <button type="button" x-show="items.length > 0" @click="tandaiSemua()"
                            class="text-[10px] font-bold text-sky-600 hover:text-sky-800 cursor-pointer">
                        Tandai dibaca
                    </button>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <template x-if="memuat">
                        <div class="px-4 py-8 text-center text-[11px] text-slate-400 font-semibold">
                            <i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Memuat notifikasi…
                        </div>
                    </template>

                    <template x-if="!memuat && items.length === 0">
                        <div class="px-4 py-8 text-center text-[11px] text-slate-400 font-semibold">
                            <i class="fa-regular fa-bell-slash text-lg block mb-2 text-slate-300"></i>
                            Tidak ada notifikasi saat ini.
                        </div>
                    </template>

                    <template x-for="item in items" :key="item.kunci">
                        <a :href="item.tautan" @click="tandai(item)"
                           class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 transition"
                           :class="!dibaca[item.kunci] ? 'bg-sky-50/50' : ''">
                            <span class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" :class="item.warna">
                                <i class="fa-solid text-[11px]" :class="item.ikon"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <strong class="block text-[11px] font-bold text-[#0F1A24] leading-snug" x-text="item.judul"></strong>
                                <span class="block text-[10px] text-slate-500 leading-snug mt-0.5" x-text="item.keterangan"></span>
                            </span>
                            <span x-show="!dibaca[item.kunci]" class="w-1.5 h-1.5 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>
                        </a>
                    </template>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('admin.orders') }}" class="text-[10px] font-bold text-slate-600 hover:text-[#0F1A24]">Semua pesanan</a>
                    <a href="{{ route('admin.midtrans') }}" class="text-[10px] font-bold text-slate-600 hover:text-[#0F1A24]">Gerbang Midtrans</a>
                </div>
            </div>
        </div>

        <!-- Tombol Logout -->
        <button type="button"
                onclick="window.PandansariAPI ? PandansariAPI.keluarPanel() : (window.location.href = '/login')"
                title="Keluar dari panel admin"
                class="h-8 sm:h-9 px-2 sm:px-2.5 lg:px-3 bg-rose-50 text-rose-600 border border-rose-200 rounded-xl font-bold flex items-center gap-1.5 sm:gap-2 hover:bg-rose-100 hover:text-rose-700 active:scale-[0.98] transition cursor-pointer">
            <i class="fa-solid fa-arrow-right-from-bracket text-[11px] sm:text-xs"></i>
            <span class="text-[10px] sm:text-[11px] hidden sm:inline">Keluar</span>
        </button>

        <!-- Profile Avatar -->
        <div class="w-8 h-8 sm:w-9 sm:h-9 bg-black text-white rounded-full flex items-center justify-center font-bold text-[10px] sm:text-xs" title="Admin Pandansari">
            <i class="fa-solid fa-user"></i>
        </div>
    </div>

</header>
