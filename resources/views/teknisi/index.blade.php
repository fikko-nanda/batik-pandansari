@extends('layouts.teknisi')

@section('title', 'Dashboard Teknisi — Batik Pandansari')

@section('content')

<div class="space-y-6 font-sans">
    <div>
        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-0.5">ENGINEERING CONSOLE • TELEMETRY</span>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Dashboard Teknisi</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            Ringkasan kesehatan API, antrean, dan aktivitas sistem — data langsung dari
            <span class="font-mono text-slate-600">GET /api/teknisi/dashboard</span> dan
            <span class="font-mono text-slate-600">GET /api/teknisi/health</span>.
        </p>
    </div>

    <x-teknisi.dashboard-widgets />
</div>

@endsection
