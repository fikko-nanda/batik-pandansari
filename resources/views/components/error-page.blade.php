@props([
    'code' => '500',
    'title' => 'Terjadi Kesalahan',
    'message' => 'Maaf, ada gangguan pada sistem. Silakan coba beberapa saat lagi.',
    'icon' => 'fa-triangle-exclamation',
    'shortcuts' => false,
])

{{-- Halaman error mandiri — TIDAK memakai @vite/API agar tetap tampil saat server bermasalah.
     Dipanggil dari resources/views/errors/{403,404,500,503}.blade.php
     `shortcuts` = tampilkan "Pintas Navigasi Populer" (khusus 404). --}}
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $code }} — {{ $title }} | Batik Pandansari</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-[#FAF8F5] text-slate-800 font-['Plus_Jakarta_Sans'] antialiased flex items-center justify-center p-4 sm:p-6">
    <main class="w-full max-w-lg">

        <!-- CARD KONTER -->
        <section class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-7 sm:p-10 text-center">

            <!-- 1. Logo Brand -->
            <a href="/" class="inline-flex items-center gap-2.5">
                <span class="w-9 h-9 bg-[#0F1A24] rounded-xl flex items-center justify-center text-white shrink-0">
                    <i class="fa-regular fa-gem" aria-hidden="true"></i>
                </span>
                <span class="text-left">
                    <span class="block text-sm font-extrabold text-slate-900 leading-none tracking-tight">Batik Pandansari</span>
                    <span class="block text-[9px] font-bold text-sky-600 tracking-[1.5px] uppercase mt-1">Pusat Kurasi Wastra</span>
                </span>
            </a>

            <!-- 2. Kode error -->
            <p class="mt-6 font-['Playfair_Display'] font-bold text-[#0F1A24] leading-none text-6xl sm:text-7xl">{{ $code }}</p>

            <!-- 3. Ikon -->
        

            <h1 class="mt-4 text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">{{ $title }}</h1>
            <p class="mt-2 text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">{{ $message }}</p>

            <!-- 4. Tombol aksi -->
            <div class="mt-7 flex flex-col sm:flex-row gap-3">
                <a href="/"
                   class="flex-1 inline-flex items-center justify-center gap-2 bg-[#0F1A24] hover:bg-slate-800 text-white text-xs font-bold px-5 py-3.5 rounded-xl transition shadow-sm">
                    <i class="fa-solid fa-house" aria-hidden="true"></i> Kembali ke Beranda
                </a>
               
            </div>

    
        </section>

        <p class="mt-6 text-center text-[10px] text-slate-400">&copy; {{ date('Y') }} Batik Pandansari — Mahakarya Wastra Nusantara</p>
    </main>
</body>
</html>
