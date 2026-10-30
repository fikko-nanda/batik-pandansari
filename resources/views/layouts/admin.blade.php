<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard Admin - Pandansari Pusat Kurasi Wastra</title>
    
    <!-- Injeksi Dinamis Base API URL dari .env (ngrok backend) -->
    <script>
        window.__PANDANSARI_API_URL__ = @json(env('VITE_API_URL') ?: '/api');
    </script>
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Plus Jakarta Sans Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        /* Smooth thin dark scrollbar for admin sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); border-radius: 9999px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.4); }
        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: rgba(148, 163, 184, 0.2) transparent; }
    </style>
</head>
<body class="bg-[#FAF8F5] text-slate-800 font-['Plus_Jakarta_Sans'] antialiased">
    <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false" class="min-h-screen flex">
        <!-- Overlay -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-[1px] z-40 lg:hidden">
        </div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed lg:sticky lg:top-0 inset-y-0 left-0 z-50 w-64 h-screen transform transition-transform duration-300 ease-in-out lg:translate-x-0 shrink-0">
            <x-admin.sidebar class="h-full" />
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
            <!-- Navbar Component -->
            <x-admin.navbar />

            <!-- PAGE CONTENT -->
            <main class="flex-1 p-3 sm:p-4 md:p-6 lg:p-8 space-y-4 sm:space-y-5 lg:space-y-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>