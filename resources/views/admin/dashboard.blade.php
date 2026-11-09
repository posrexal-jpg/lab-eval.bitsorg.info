@extends('layouts.app')
@section('title', 'Dashboard')
@use('App\Models\Evaluation')
@section('content')
<div class="page-head">
    <div><h1>Dashboard</h1><div class="muted">Overview of laboratory satisfaction</div></div>
    <div class="actions">
        <a class="btn ghost" href="{{ route('admin.rooms.qr-print') }}" target="_blank">Print all QR codes</a>
        <a class="btn" href="{{ route('admin.reports') }}">Open reports</a>
    </div>
</div>

@unless($template)
    <div class="flash warn">No form template is active, so QR codes show a “not open” page. <a href="{{ route('admin.templates.index') }}">Activate a template</a>.</div>
@endunless

<div class="grid stats">
    <div class="stat key"><div class="n">{{ $stats['avg'] ? number_format($stats['avg'], 2) : '–' }}</div><div class="l">Overall average (out of 5)<br>{{ Evaluation::describe($stats['avg'] ? (float) $stats['avg'] : null) }}</div></div>
    <div class="stat"><div class="n">{{ $stats['total'] }}</div><div class="l">Responses</div></div>
    <div class="stat"><div class="n">{{ $stats['week'] }}</div><div class="l">Last 7 days</div></div>
    <div class="stat"><div class="n">{{ $stats['rooms'] }}</div><div class="l">Active rooms</div></div>
    <div class="stat"><div class="n">{{ $stats['flagged'] }}</div><div class="l">Flagged for review</div></div>
</div>

<div class="grid two">
    <div class="card flush">
        <h2>By room</h2>
        <div class="tablewrap"><table class="data">
            <tr><th>Room</th><th class="num">Responses</th><th class="num">Average</th><th>Result</th></tr>
            @forelse($byRoom as $r)
                @php $avg = $r->avg !== null ? (float) $r->avg : null; @endphp
                <tr>
                    <td>{{ $r->name }}@unless($r->is_active) <span class="badge muted">inactive</span>@endunless</td>
                    <td class="num">{{ $r->responses }}</td>
                    <td class="num">{{ $avg !== null ? number_format($avg, 2) : '–' }}</td>
                    <td><span class="badge {{ Evaluation::tone($avg) }}">{{ Evaluation::describe($avg) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No rooms yet.</td></tr>
            @endforelse
        </table></div>
    </div>

    <div class="card">
        <h2>Responses, last 14 days</h2>
        <div class="bars" role="img" aria-label="Bar chart of daily responses">
            @foreach($trend as $t)
                <div title="{{ $t['label'] }}: {{ $t['count'] }}"><span>{{ $t['count'] ?: '' }}</span><i style="height:{{ max(2, round($t['count'] / $maxTrend * 90)) }}%"></i><span>{{ $t['short'] }}</span></div>
            @endforeach
        </div>
    </div>
</div>

<div class="card flush">
    <h2>Latest responses</h2>
    <div class="tablewrap"><table class="data">
        <tr><th>When</th><th>Room</th><th class="num">Average</th><th>Status</th><th></th></tr>
        @forelse($recent as $e)
            <tr>
                <td class="nowrap">{{ $e->created_at->format('M j, g:i A') }}</td>
                <td>{{ $e->room->name }}</td>
                <td class="num">{{ $e->average_score !== null ? number_format($e->average_score, 2) : '–' }}</td>
                <td><span class="badge {{ $e->status === 'visible' ? 'muted' : ($e->status === 'flagged' ? 'warn' : 'bad') }}">{{ $e->status }}</span></td>
                <td class="right"><a href="{{ route('admin.evaluations.show', $e) }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Nothing yet. Print a room QR code and have someone scan it.</td></tr>
        @endforelse
    </table></div>
</div>
@endsection
