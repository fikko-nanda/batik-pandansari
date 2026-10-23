@extends('layouts.teknisi')

@section('title', 'Audit Logs — Batik Pandansari')

@section('content')

<div class="space-y-6 font-sans">
    <x-teknisi.log-explorer
        kind="audit"
        title="Audit Logs & Teknis"
        icon="fa-code-commit"
        description="Jejak aktivitas teknis, perubahan konfigurasi, dan hak akses operator — GET /api/teknisi/audit-logs."
    />
</div>

@endsection
