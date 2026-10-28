@extends('layouts.teknisi')

@section('title', 'Webhook Logs — Batik Pandansari')

@section('content')

<div class="space-y-6 font-sans">
    <x-teknisi.log-explorer
        kind="webhook"
        title="Log Webhook & Event Dispatcher"
        icon="fa-bolt"
        description="Pengawasan relai event Midtrans, RajaOngkir, dan dispatcher internal — GET /api/teknisi/webhook-logs."
    />
</div>

@endsection
