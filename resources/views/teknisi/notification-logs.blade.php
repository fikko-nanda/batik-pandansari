@extends('layouts.teknisi')

@section('title', 'Notification Logs — Batik Pandansari')

@section('content')

<div class="space-y-6 font-sans">
    <x-teknisi.log-explorer
        kind="notification"
        title="Log Notifikasi"
        icon="fa-envelope"
        description="Riwayat pengiriman email/WhatsApp, status pengiriman, dan penerima — GET /api/teknisi/notification-logs."
    />
</div>

@endsection
