@extends('layouts.print')
@section('title', 'Print QR codes')
@section('content')
<div class="screen-bar">
    <div><b>QR code sheets</b> <span class="muted small">({{ $rooms->count() }} {{ \Illuminate\Support\Str::plural('room', $rooms->count()) }})</span></div>
    <div class="actions"><button class="btn" type="button" data-print>Print</button></div>
</div>
<div class="qr-sheet">
    @forelse($rooms as $item)
        <div class="qr-card">
            <p class="small">{{ config('evaluation.institution.name') }}</p>
            <h2>{{ $item['room']->name }}</h2>
            <p class="muted small">{{ $item['room']->building }}</p>
            <div class="qrbox">{!! $item['svg'] !!}</div>
            <p><b>Scan to evaluate this laboratory</b></p>
            <p class="small muted">Open your phone camera and point it at the code. It takes about 2 minutes.</p>
        </div>
    @empty
        <p class="muted">No active rooms to print.</p>
    @endforelse
</div>
@endsection
