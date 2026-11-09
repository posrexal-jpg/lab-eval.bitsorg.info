@extends('layouts.app')
@section('title', 'Monitor')
@section('print_title', 'Laboratory Monitoring Sheet — '.now()->format('F j, Y g:i A'))
@section('body_attrs', 'data-refresh="60"')
@use('App\Models\Evaluation')
@section('content')
<div class="page-head">
    <div><h1>Monitor</h1><div class="muted">Refreshes every minute · {{ now()->format('l, F j, Y g:i A') }}</div></div>
    <div class="actions"><button class="btn" type="button" data-print>Print monitoring sheet</button></div>
</div>

<div class="card flush">
    <h2>Rooms</h2>
    <div class="tablewrap"><table class="data">
        <tr><th>Room</th><th class="num">Today</th><th class="num">Last 7 days</th><th class="num">7-day average</th><th>Status</th><th>Last response</th></tr>
        @foreach($rooms as $r)
            @php $avg = $r->week_avg !== null ? (float) $r->week_avg : null; @endphp
            <tr>
                <td>{{ $r->name }}@unless($r->is_active) <span class="badge muted">inactive</span>@endunless</td>
                <td class="num">{{ $r->today }}</td>
                <td class="num">{{ $r->week }}</td>
                <td class="num">{{ $avg !== null ? number_format($avg, 2) : '–' }}</td>
                <td>
                    @if($avg !== null && $avg < config('evaluation.low_score_threshold'))
                        <span class="badge bad">Needs attention</span>
                    @elseif($avg === null)
                        <span class="badge muted">No data</span>
                    @else
                        <span class="badge {{ Evaluation::tone($avg) }}">{{ Evaluation::describe($avg) }}</span>
                    @endif
                </td>
                <td class="nowrap">{{ $r->last_at ? \Carbon\Carbon::parse($r->last_at)->diffForHumans() : '–' }}</td>
            </tr>
        @endforeach
    </table></div>
</div>

<div class="card flush">
    <h2>Latest 25 responses</h2>
    <div class="tablewrap"><table class="data">
        <tr><th>When</th><th>Room</th><th class="num">Average</th><th>Status</th></tr>
        @forelse($recent as $e)
            <tr>
                <td class="nowrap">{{ $e->created_at->format('M j, g:i A') }}</td>
                <td>{{ $e->room->name }}</td>
                <td class="num">{{ $e->average_score !== null ? number_format($e->average_score, 2) : '–' }}</td>
                <td>{{ $e->status }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">No responses yet.</td></tr>
        @endforelse
    </table></div>
</div>

<div class="sign"><div>Monitored by</div><div>Noted by</div></div>
@endsection
