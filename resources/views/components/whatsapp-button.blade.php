{{-- Tombol WhatsApp mengambang (sticky) — dipakai global di semua halaman publik --}}
{{--
    Penggunaan: <x-whatsapp-button />
    Ubah nomor/Pesan default di sini jika perlu.
--}}
<div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex items-center group">
    <span class="hidden sm:inline-block mr-3 px-3.5 py-2 bg-[#0F1A24] text-white text-xs font-semibold rounded-xl shadow-xl opacity-0 translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 pointer-events-none whitespace-nowrap font-['Plus_Jakarta_Sans'] border border-sky-400/20">
        Chat WhatsApp Pandansari
    </span>

    <a href="https://wa.me/6281234567890?text=Halo%20Batik%20Pandansari,%20saya%20ingin%20konsultasi%20produk"
       target="_blank"
       rel="noopener noreferrer"
       class="w-12 h-12 sm:w-14 sm:h-14 bg-[#25D366] text-white rounded-full flex items-center justify-center shadow-2xl hover:bg-[#20ba5a] hover:scale-110 active:scale-95 transition-all duration-300 shrink-0">
        <i class="fa-brands fa-whatsapp text-2xl sm:text-3xl"></i>
    </a>
</div>
