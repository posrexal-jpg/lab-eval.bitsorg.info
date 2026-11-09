@extends('layouts.app')
@section('title', 'Print paper form')
@section('content')
<div class="page-head"><div><h1>Print paper form</h1><div class="muted">The A4 Computer Laboratory User Satisfaction Form, ready for printing and photocopying.</div></div></div>

<div class="card form-narrow">
    <form method="GET" action="{{ route('admin.paper-form.print') }}" target="_blank">
        <div class="field">
            <label class="f" for="room_id">Pre-fill for</label>
            <select id="room_id" name="room_id">
                <option value="">Blank form (leave the room empty)</option>
                <option value="all">Every active room, one set each</option>
                @foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
            </select>
            <div class="muted small">Fills the “Laboratory / Room No.” cell and ticks “Computer Laboratory 1/2/3” when the room name matches.</div>
        </div>
        <div class="field">
            <label class="f" for="copies">Copies {{ '(per room)' }}</label>
            <input id="copies" type="number" name="copies" value="{{ old('copies', 1) }}" min="1" max="200" required>
        </div>
        <label class="check field"><input type="checkbox" name="fill_date" value="1"> Pre-fill today’s date ({{ now()->format('F j, Y') }})</label>
        <button class="btn" type="submit">Open print preview</button>
    </form>
</div>

<div class="card form-narrow">
    <h2>Printing tips</h2>
    <ul class="small" style="margin:0;padding-left:18px">
        <li>In the print dialog choose paper size <b>A4</b>, margins <b>None</b>, scale <b>100%</b>, and turn on <b>Background graphics</b> so the logos print.</li>
        <li>The letterhead uses the Poppins font from Google Fonts. Offline computers fall back to Arial automatically.</li>
        <li>The design lives in <code>resources/print/paper-form.html</code>. Replace that file to update the paper version.</li>
    </ul>
</div>
@endsection
