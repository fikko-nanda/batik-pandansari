<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', $title ?? 'Engineering Console — Batik Pandansari')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Injeksi Dinamis Base API URL dari .env (ngrok backend) -->
    <script>
        window.__PANDANSARI_API_URL__ = @json(env('VITE_API_URL') ?: '/api');
    </script>

    <!-- app.js (window.PandansariAPI) sebelum Alpine -->
    @vite(['resources/js/app.js', 'resources/js/teknisi.js'])

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Plus Jakarta Sans Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        font-mono, code, pre {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex">


   <!-- SIDEBAR TEKNISI -->
    <x-teknisi.sidebar />

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- TOP NAVBAR ENGINEERING CONSOLE -->
        <header class="bg-white border-b border-slate-200/80 px-6 py-3 flex items-center justify-between sticky top-0 z-20">
            <!-- BREADCRUMB -->
            <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <a href="{{ route('home') }}" class="hover:underline hover:text-blue-600 text-slate-500 transition">Batik Pandansari</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                <a href="{{ route('teknisi.dashboard') }}" class="hover:underline hover:text-blue-600 text-slate-500 transition">Engineering Console</a>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                <span class="text-slate-900 font-extrabold">Gateway Telemetry</span>
            </div>

            <!-- STATUS BADGES & USER -->
            <div class="flex items-center gap-3 text-xs">
                <!-- Profile User (dinamis dari user terotentikasi) -->
                <div x-data="teknisiTopbar" class="flex items-center gap-2">
                    <div class="w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center font-black text-[10px]"
                         :title="displayName"
                         x-text="getInitials(displayName)"></div>
                    <div>
                        <strong class="text-xs font-extrabold text-slate-900 block leading-none" x-text="displayName"></strong>
                        <span class="text-[9px] font-semibold text-slate-400 block mt-0.5" x-text="roleLabel"></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT PAGE -->
        <main class="p-6">
            @yield('content')
        </main>

    </div>

</body>
</html>