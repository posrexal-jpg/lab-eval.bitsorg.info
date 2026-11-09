@extends('layouts.app')
@section('title', 'Responses')
@section('content')
<div class="page-head"><div><h1>Responses</h1><div class="muted">{{ $evaluations->total() }} found</div></div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.evaluations.print-many', $filters) }}" target="_blank">Print these on ISO form</a>
        <a class="btn ghost" href="{{ route('admin.reports.export', $filters) }}">Export CSV</a>
    </div>
</div>

<form class="card filters" method="GET">
    <div class="field"><label class="f" for="room_id">Room</label>
        <select id="room_id" name="room_id"><option value="">All</option>
            @foreach($rooms as $r)<option value="{{ $r->id }}" @selected(($filters['room_id'] ?? '') == $r->id)>{{ $r->name }}</option>@endforeach
        </select></div>
    <div class="field"><label class="f" for="from">From</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
    <div class="field"><label class="f" for="to">To</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
    <div class="field"><label class="f" for="max_score">Average at most</label><input id="max_score" type="number" step="0.1" min="1" max="5" name="max_score" value="{{ $filters['max_score'] ?? '' }}" placeholder="e.g. 2.5"></div>
    <div class="field"><label class="f" for="status">Status</label>
        <select id="status" name="status"><option value="">Any</option>
            @foreach(['visible','flagged','hidden'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select></div>
    <div class="actions"><button class="btn" type="submit">Filter</button><a class="btn ghost" href="{{ route('admin.evaluations.index') }}">Reset</a></div>
</form>

<div class="card flush">
    <div class="tablewrap"><table class="data">
        <tr><th>#</th><th>When</th><th>Room</th><th class="num">Average</th><th>Status</th><th></th></tr>
        @forelse($evaluations as $e)
            <tr>
                <td>{{ $e->id }}</td>
                <td class="nowrap">{{ $e->created_at->format('M j, Y g:i A') }}</td>
                <td>{{ $e->room->name }}</td>
                <td class="num">{{ $e->average_score !== null ? number_format($e->average_score, 2) : '–' }}</td>
                <td><span class="badge {{ $e->status === 'visible' ? 'muted' : ($e->status === 'flagged' ? 'warn' : 'bad') }}">{{ $e->status }}</span></td>
                <td class="right nowrap"><a class="btn sm ghost" href="{{ route('admin.evaluations.show', $e) }}">View</a> <a class="btn sm ghost" href="{{ route('admin.evaluations.print-form', $e) }}" target="_blank">Print form</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No responses match.</td></tr>
        @endforelse
    </table></div>
</div>
{{ $evaluations->links() }}
@endsection
