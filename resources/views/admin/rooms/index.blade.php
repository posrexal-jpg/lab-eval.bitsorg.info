@extends('layouts.app')
@section('title', 'Rooms & QR codes')
@use('App\Models\Evaluation')
@section('content')
<div class="page-head">
    <div><h1>Rooms &amp; QR codes</h1><div class="muted">Each room has its own QR code that opens the active evaluation form.</div></div>
    <div class="actions">
        <a class="btn ghost" href="{{ route('admin.rooms.qr-print') }}" target="_blank">Print all QR codes</a>
        <a class="btn ghost" href="{{ route('admin.paper-form') }}">Print paper form</a>
        <a class="btn" href="{{ route('admin.rooms.create') }}">Add room</a>
    </div>
</div>

<form method="GET" action="{{ route('admin.rooms.qr-print') }}" target="_blank">
<div class="card flush">
    <div class="tablewrap"><table class="data">
        <tr><th></th><th>Room</th><th>Building</th><th class="num">Capacity</th><th class="num">Responses</th><th class="num">Average</th><th>Status</th><th></th></tr>
        @forelse($rooms as $r)
            @php $avg = $r->avg !== null ? (float) $r->avg : null; @endphp
            <tr>
                <td><input type="checkbox" name="ids[]" value="{{ $r->id }}" aria-label="Select {{ $r->name }}"></td>
                <td><b>{{ $r->name }}</b></td>
                <td>{{ $r->building ?: '–' }}</td>
                <td class="num">{{ $r->capacity ?: '–' }}</td>
                <td class="num">{{ $r->responses }}</td>
                <td class="num">{{ $avg !== null ? number_format($avg, 2) : '–' }}</td>
                <td><span class="badge {{ $r->is_active ? 'good' : 'muted' }}">{{ $r->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="right nowrap">
                    <a class="btn sm ghost" href="{{ route('admin.rooms.qr', $r) }}">QR code</a>
                    <a class="btn sm ghost" href="{{ route('admin.rooms.edit', $r) }}">Edit</a>
                    <button class="btn sm danger" type="submit" form="del-{{ $r->id }}">Delete</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">No rooms yet.</td></tr>
        @endforelse
    </table></div>
</div>
@if($rooms->count())<button class="btn ghost" type="submit">Print selected QR codes</button>@endif
</form>

@foreach($rooms as $r)
    <form id="del-{{ $r->id }}" method="POST" action="{{ route('admin.rooms.destroy', $r) }}" data-confirm="Delete {{ $r->name }}? Its past responses stay in reports, but its QR code stops working.">
        @csrf @method('DELETE')
    </form>
@endforeach
@endsection
