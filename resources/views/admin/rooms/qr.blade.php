@extends('layouts.app')
@section('title', 'QR code · '.$room->name)
@section('content')
<div class="page-head">
    <div><h1>{{ $room->name }}</h1><div class="muted">Scan to evaluate this room</div></div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.rooms.qr-print', ['ids' => [$room->id]]) }}" target="_blank">Print QR</a>
        <a class="btn ghost" href="{{ route('admin.rooms.qr.download', $room) }}">Download SVG</a>
        <a class="btn ghost" href="{{ route('admin.rooms.index') }}">Back</a>
    </div>
</div>

<div class="card">
    <div class="qrbox">{!! $svg !!}</div>
    <p class="small muted" style="margin:12px 0 4px">Link encoded in the QR code</p>
    <div class="urlbox">{{ $room->evaluationUrl() }}</div>
    <div class="actions" style="margin-top:10px">
        <button class="btn sm ghost" type="button" data-copy="{{ $room->evaluationUrl() }}">Copy link</button>
        <a class="btn sm ghost" href="{{ $room->evaluationUrl() }}" target="_blank" rel="noopener">Open form</a>
    </div>
    @unless($room->is_active)<p class="flash warn" style="margin-top:14px">This room is inactive. The QR code shows a “not found” page until you re-enable it.</p>@endunless
</div>

<div class="card">
    <h2>Replace QR code</h2>
    <p class="muted small" style="margin-top:0">If a printed QR was copied or abused, generate a new one. The old printed code stops working immediately.</p>
    <form method="POST" action="{{ route('admin.rooms.regenerate', $room) }}" data-confirm="Generate a new QR code? The printed one will stop working.">
        @csrf
        <button class="btn danger" type="submit">Generate new QR code</button>
    </form>
</div>
@endsection
