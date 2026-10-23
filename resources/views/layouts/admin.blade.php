<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pandansari Pusat Kurasi Wastra</title>
    
    <!-- Tailwind CSS via Vite -->
    <!-- app.js (window.PandansariAPI) HARUS dimuat SEBELUM Alpine.js, agar saat Alpine eval x-data global sudah ada -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Plus Jakarta Sans Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-[#FAF8F5] text-slate-800 font-['Plus_Jakarta_Sans'] antialiased">

    <div class="min-h-screen flex">

        <!-- Sidebar Component -->
        <x-admin.sidebar />

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            
            <!-- Navbar Component -->
            <x-admin.navbar />

            <!-- PAGE CONTENT -->
            <main class="p-6 sm:p-8 space-y-6">
                @yield('content')
            </main>

        </div>

    </div>

</body>
</html>