<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Engineering Console — Batik Pandansari' }}</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Plus Jakarta Sans Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
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
@include('teknisi.components.sidebar')

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- TOP NAVBAR ENGINEERING CONSOLE -->
        <header class="bg-white border-b border-slate-200/80 px-6 py-3 flex items-center justify-between sticky top-0 z-20">
            <!-- BREADCRUMB -->
            <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <span>Batik Pandansari</span>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                <span>Engineering Console</span>
                <i class="fa-solid fa-chevron-right text-[9px] text-slate-400"></i>
                <span class="text-slate-900 font-extrabold">Gateway Telemetry</span>
            </div>

            <!-- STATUS BADGES & USER -->
            <div class="flex items-center gap-3 text-xs">
                <span class="bg-sky-50 text-sky-800 border border-sky-200 px-2.5 py-1 rounded-full font-bold text-[10px] flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                    <span>PRODUCTION (LIVE)</span>
                </span>

                <div class="relative">
                    <input type="text" placeholder="⌘K" readonly class="w-16 bg-slate-100 border border-slate-200 rounded-lg px-2 py-1 text-center font-mono text-[10px] font-bold text-slate-500 cursor-pointer">
                </div>

                <span class="bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-1 rounded-full font-mono text-[10px] font-bold flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Socket 12ms</span>
                </span>

                <!-- Profile User -->
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center font-black text-[10px]">
                        AS
                    </div>
                    <div>
                        <strong class="text-xs font-extrabold text-slate-900 block leading-none">Arya Seno</strong>
                        <span class="text-[9px] font-semibold text-slate-400 block mt-0.5">Lead Tech Operator</span>
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