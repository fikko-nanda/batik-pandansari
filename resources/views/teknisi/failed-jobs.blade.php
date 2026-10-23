@extends('layouts.teknisi')

@section('title', 'Failed Jobs — Batik Pandansari')

@section('content')

<div class="space-y-6 font-sans">
    <x-teknisi.log-explorer
        kind="failed"
        title="Failed Jobs"
        icon="fa-triangle-exclamation"
        description="Antrean job yang gagal diproses beserta exception dan jumlah percobaan — GET /api/teknisi/failed-jobs."
    />
</div>

@endsection
