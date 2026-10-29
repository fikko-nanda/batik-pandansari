<footer class="bg-white pt-14 pb-8 border-t border-slate-100 text-xs font-['Plus_Jakarta_Sans']">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-12 border-b border-slate-100">
            <div class="md:col-span-4 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-sky-50 border border-sky-200 rounded-xl flex items-center justify-center text-[#0369A1]">
                        <i class="fa-solid fa-gem text-base"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-[#0F1A24] text-base leading-none">BATIK PANDANSARI</h4>
                        <span class="text-[9px] font-bold text-[#0284C7] tracking-[2.25px] uppercase">BATIK AUTENTIK</span>
                    </div>
                </div>
                <p class="text-slate-500 leading-relaxed pr-4">
                    Menghidupkan kemegahan wastra Nusantara melalui mahakarya batik tulis dan cap dengan mutu dan autentik.
                </p>
                <div class="flex gap-2 pt-1">
                    <a href="https://www.instagram.com/_pandansari_/" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@batikpandansari" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="https://wa.me/6283820380977" target="_blank" rel="noopener noreferrer" class="w-8 h-8 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <div class="md:col-span-3 space-y-3">
                <h5 class="font-bold text-[#0F1A24] uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> KOLEKSI WASTRA
                </h5>
                <ul class="space-y-2.5 text-slate-600">
                    <li class="flex justify-between items-center"><a href="/katalog?category=pria" class="hover:text-slate-900 transition">Kemeja & Hem Pria</a></li>
                    <li class="flex justify-between items-center"><a href="/katalog?category=wanita" class="hover:text-slate-900 transition">Dress & Tunik Wanita</a></li>
                    <li class="flex justify-between items-center"><a href="/katalog?category=anak" class="hover:text-slate-900 transition">Batik Anak-Anak & Remaja</a></li>
                </ul>
            </div>

            <div class="md:col-span-2 space-y-3">
                <h5 class="font-bold text-[#0F1A24] uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> TOKO RESMI
                </h5>
                <ul class="space-y-2.5 text-slate-600">
                    <li><a href="https://shopee.co.id/batik_pandansari_" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 flex items-center gap-2"><i class="fa-solid fa-bag-shopping text-[#EE4D2D]"></i> Shopee Mall</a></li>
                    <li><a href="https://www.tiktok.com/@batikpandansari" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 flex items-center gap-2"><i class="fa-brands fa-tiktok text-slate-900"></i> TikTok Shop</a></li>
                    <li><a href="https://tk.tokopedia.com/ZSgDUVn5g/" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 flex items-center gap-2"><i class="fa-solid fa-store text-[#059669]"></i> Tokopedia</a></li>
                    <li><a href="https://www.instagram.com/_pandansari_/" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 flex items-center gap-2"><i class="fa-brands fa-instagram text-[#F43F5E]"></i> Instagram</a></li>
                </ul>
            </div>

            <div class="md:col-span-3 space-y-3">
                <h5 class="font-bold text-[#0F1A24] uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> LAYANAN & KURASI
                </h5>
                <div>
                    <div class="flex items-center gap-1.5 font-medium text-slate-800 mb-1">
                        <i class="fa-solid fa-clock text-sky-600"></i> Layanan  Batik:
                    </div>
                    <p class="text-slate-500 text-[11px] leading-relaxed pl-5">
                        Senin – Sabtu: 08.30 – 21.00 WIB<br>
                        Minggu & Hari Besar: 09.00 – 17.00 WIB
                    </p>
                </div>
            </div>
        </div>

        <div class="pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-slate-400 text-[11px]">
            <p>© 2026 BATIK PANDANSARI. SELURUH HAK CIPTA DILINDUNGI.</p>
            <div class="flex flex-wrap gap-6">
                <a href="{{ route('home') }}" class="hover:text-slate-600">Beranda</a>
                <a href="/katalog" class="hover:text-slate-600">Katalog Koleksi</a>
                <a href="{{ route('terms') }}" class="hover:text-slate-600">Ketentuan Pemesanan</a>
                <a href="{{ route('privacy') }}" class="hover:text-slate-600">Kebijakan Privasi</a>
            </div>
        </div>
    </div>
</footer>