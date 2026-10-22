<section class="bg-white py-16 border-b border-slate-100"
    x-data="{
        galeriFotos: [],
        fotoIdx: 0,
        fotoUtama: '',
        namaUsaha: '',
        deskripsiSingkat: '',
        deskripsiLengkap: '',
        alamatText: '',
        mapsLink: '',
        jamRows: [],
        fasList: [],
        async init() {
            if (typeof PandansariAPI === 'undefined') {
                console.warn('PandansariAPI belum termuat — galeri butik pakai data statis');
                return;
            }
            try {
                const [gRes, pRes, fRes] = await Promise.all([
                    PandansariAPI.cmsGaleriPublik(),
                    PandansariAPI.cmsProfilUsahaPublik(),
                    PandansariAPI.cmsFasilitasPublik(),
                ]);

                if (gRes.ok && gRes.data) {
                    const d = gRes.data;
                    const infoNonObj = Array.isArray(d) ? {} : (d ?? {});
                    let daftar = Array.isArray(d) ? d : (infoNonObj.gambar ?? infoNonObj.foto ?? infoNonObj.photos ?? infoNonObj.daftar_gambar ?? []);
                    if (!daftar.length && d?.informasi) daftar = d.informasi.gambar ?? d.informasi.foto ?? [];
                    this.galeriFotos = daftar
                        .map(x => typeof x === 'string' ? { _url: PandansariAPI.imgUrl(x), alt: '' } : {
                            _url: PandansariAPI.imgUrl(x.url_gambar ?? x.gambar ?? x.url ?? ''),
                            alt: x.teks_alternatif ?? x.alt ?? '',
                            _id: x.id_gambar_galeri ?? x.id_gambar ?? x.id ?? null,
                        })
                        .filter(f => f._url);
                    if (this.galeriFotos.length) {
                        this.fotoIdx = 0;
                        this.fotoUtama = this.galeriFotos[0]._url;
                    }
                }

                if (pRes.ok && pRes.data) {
                    const d = pRes.data;
                    this.namaUsaha = d.nama_usaha ?? '';
                    this.deskripsiSingkat = d.deskripsi_singkat ?? '';
                    this.deskripsiLengkap = d.deskripsi_lengkap ?? '';
                    this.alamatText = d.alamat_lengkap ?? d.alamat ?? '';
                    this.mapsLink = d.tautan_google_maps ?? d.link_google_maps ?? '';
                    const jam = Array.isArray(d.jam_operasional) ? d.jam_operasional : [];
                    const tanda = t => String(t ?? '').replace(':', '.');
                    this.jamRows = jam
                        .filter(x => x && (x.tutup || x.jam_buka || x.jam_tutup))
                        .map(x => ({
                            hari: String(x.hari ?? '').trim(),
                            buka: Array.isArray(x.jam_buka) ? tanda(x.jam_buka[0]) : tanda(x.jam_buka),
                            tutupJam: Array.isArray(x.jam_tutup) ? tanda(x.jam_tutup[0]) : tanda(x.jam_tutup),
                            tutupHari: x.tutup === true,
                        }));
                }

                if (fRes.ok && Array.isArray(fRes.data)) {
                    this.fasList = fRes.data
                        .filter(f => f && (f.status_aktif ?? true))
                        .sort((a, b) => (a.urutan_tampilan ?? 0) - (b.urutan_tampilan ?? 0))
                        .map(f => String(f.nama_fasilitas ?? '')).filter(Boolean);
                }
            } catch (e) {
                console.warn('Galeri butik: gagal memuat CMS, pakai data statis', e);
            }
        },
        pilihFoto(i) {
            if (!this.galeriFotos[i]) return;
            this.fotoIdx = i;
            this.fotoUtama = this.galeriFotos[i]._url;
        },
        ikonFas(n) {
            const s = String(n).toLowerCase();
            if (s.includes('parkir')) return 'fa-solid fa-square-parking';
            if (s.includes('fitting') || s.includes('serimbit') || s.includes('busana')) return 'fa-solid fa-shirt';
            if (s.includes('wifi')) return 'fa-solid fa-wifi';
            if (s.includes('toilet')) return 'fa-solid fa-restroom';
            return 'fa-solid fa-circle-check';
        },
    }">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-6 md:p-8 border-b border-slate-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <h2 class="text-2xl font-bold text-[#0F1A24] font-['Plus_Jakarta_Sans']">Galeri Butik & Batik Pandansari</h2>
                <a :href="mapsLink || 'https://share.google/EFRSYAMaqQoDhYvND'" target="_blank" class="bg-[#0369A1] text-white text-xs font-bold px-5 py-2.5 rounded-xl flex items-center gap-2 uppercase hover:bg-sky-700 transition font-['Plus_Jakarta_Sans']">
                    <i class="fa-solid fa-location-dot"></i> PETUNJUK LOKASI GALERI
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[380px]">
                <div class="lg:col-span-7 relative min-h-[280px] bg-[#0F1A24]">
                    <img x-show="fotoUtama" :src="fotoUtama" :alt="galeriFotos[fotoIdx]?.alt || 'Galeri Butik Pandansari'" class="w-full h-full object-cover">
                    <img x-show="!fotoUtama" src="{{ asset('images/hero-bg.jpg') }}" alt="Galeri Butik Pandansari" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0F1A24]/90 via-transparent to-transparent flex items-end p-6">
                        <div>
                            <span class="text-[11px] font-semibold text-sky-300 uppercase tracking-wider block font-['Plus_Jakarta_Sans']"
                                  x-text="deskripsiSingkat || 'Pengrajin & galeri utama Batik Pandansari'"></span>
                            <h3 class="text-lg font-semibold text-white font-['Plus_Jakarta_Sans']" x-text="namaUsaha || 'Melayani pemesanan seragam custom serta pembuatan desain motif dan batik berlogo'"></h3>
                        </div>
                    </div>
                    <div class="absolute bottom-6 right-6 flex gap-2 z-10" x-show="galeriFotos.length > 1">
                        <template x-for="(f, i) in galeriFotos.slice(0, 5)" :key="f._id ?? i">
                            <button type="button" @click="pilihFoto(i)"
                                    class="w-16 h-12 rounded-lg overflow-hidden border-2 transition cursor-pointer"
                                    :class="i === fotoIdx ? 'border-sky-400' : 'border-white/60 opacity-70 hover:opacity-100'">
                                <img :src="f._url" :alt="f.alt || 'Foto galeri ' + (i + 1)" class="w-full h-full object-cover">
                            </button>
                        </template>
                        <span class="w-16 h-12 rounded-lg bg-[#0F1A24]/70 border border-white/40 text-white text-[10px] font-bold flex items-center justify-center"
                              x-show="galeriFotos.length > 5"
                              x-text="'+' + (galeriFotos.length - 5)"></span>
                    </div>
                </div>

                <div class="lg:col-span-5 p-8 bg-white flex flex-col justify-between font-['Plus_Jakarta_Sans']">
                    <div>
                        <h3 class="text-xl font-bold text-[#0F1A24] mb-2">Kunjungi Butik & Galeri Resmi Kami</h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4 line-clamp-5" x-show="deskripsiLengkap" x-text="deskripsiLengkap"></p>
                        <p class="text-xs text-slate-500 leading-relaxed mb-6"
                           x-text="alamatText || 'Batik pandansari, Wonokerto, Jembangan, Kec. Plupuh, Kabupaten Sragen, Jawa Tengah 57283.'"></p>

                        <div class="space-y-3 pt-4 border-t border-slate-100 text-xs text-slate-700">
                            <div class="flex items-start gap-2.5">
                                <i class="fa-regular fa-clock text-[#0369A1] mt-0.5"></i>
                                <template x-if="jamRows.length">
                                    <ul class="space-y-1">
                                        <template x-for="(j, i) in jamRows" :key="i">
                                            <li class="flex items-baseline gap-3">
                                                <span class="w-16 shrink-0 font-semibold text-slate-800" x-text="j.hari"></span>
                                                <span :class="j.tutupHari ? 'text-slate-400' : ''" x-text="j.tutupHari ? 'Tutup' : (j.buka + ' – ' + j.tutupJam + ' WIB')"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                                <span x-show="!jamRows.length">Jam Buka: Setiap Hari 08.30 – 21.00 WIB</span>
                            </div>
                            <template x-if="fasList.length">
                                <div>
                                    <template x-for="(f, i) in fasList" :key="i">
                                        <div class="flex items-center gap-2.5 py-1.5">
                                            <i :class="ikonFas(f) + ' text-[#0369A1]'"></i>
                                            <span x-text="f"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!fasList.length">
                                <div>
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid fa-shirt text-[#0369A1]"></i>
                                        <span>Layanan Fitting Busana & Sarimbit Keluarga</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 mt-3">
                                        <i class="fa-solid fa-square-parking text-[#0369A1]"></i>
                                        <span>Area Parkir Luas & Tempat Nyaman</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
