<!DOCTYPE html>
<html lang="id" class="h-full bg-[#FAF8F5] overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Batik Pandansari - Mahakarya Wastra Nusantara' }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome & Alpine.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen text-[#0F1A24] antialiased font-['Plus_Jakarta_Sans',sans-serif] bg-[#FAF8F5] relative overflow-x-hidden">

    <x-announcement-bar />
    <x-navbar />

    <main class="flex-grow w-full overflow-hidden">
        {{ $slot }}
    </main>

    <x-footer />

    <!-- Sticky WhatsApp Button (Responsif dengan Tooltip Hover/Touch) -->
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

</body>
</html>