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

    <!-- app.js (window.PandansariAPI) HARUS dimuat SEBELUM Alpine.js, agar saat Alpine eval x-data global sudah ada -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Google Identity Services (GIS) client for Google Sign-In -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="flex flex-col min-h-screen text-[#0F1A24] antialiased font-['Plus_Jakarta_Sans',sans-serif] bg-[#FAF8F5] relative overflow-x-hidden">

    <x-announcement-bar />
    <x-navbar />
        <main class="flex-grow w-full overflow-hidden">
            @yield('content')
        </main>

    <x-footer />

    <x-whatsapp-button />

</body>
</html>