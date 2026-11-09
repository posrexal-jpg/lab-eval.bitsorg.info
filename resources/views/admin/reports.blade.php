@extends('layouts.app')
@section('title', 'Reports')
@section('print_title', 'Computer Laboratory User Satisfaction Report')
@use('App\Models\Evaluation')
@php $tone = fn ($a) => Evaluation::tone($a); @endphp
@section('content')
<div class="page-head">
    <div><h1>Reports</h1><div class="muted">Hidden responses are excluded.</div></div>
    <div class="actions">
        <a class="btn ghost" href="{{ route('admin.reports.export', request()->query()) }}">Export CSV</a>
        <button class="btn" type="button" data-print>Print report</button>
    </div>
</div>

<form class="card filters" method="GET" action="{{ route('admin.reports') }}">
    <div class="field" style="min-width:220px"><label class="f" for="room_id">Rooms (hold Ctrl/Cmd for several)</label>
        <select id="room_id" name="room_id[]" multiple size="4">
            @foreach($rooms as $r)<option value="{{ $r->id }}" @selected(in_array($r->id, (array) ($filters['room_id'] ?? [])))>{{ $r->name }}</option>@endforeach
        </select></div>
    <div class="field"><label class="f" for="from">From</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
    <div class="field"><label class="f" for="to">To</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
    <div class="field"><label class="f" for="template_id">Form version</label>
        <select id="template_id" name="template_id"><option value="">All</option>
            @foreach($templates as $t)<option value="{{ $t->id }}" @selected(($filters['template_id'] ?? '') == $t->id)>{{ $t->name }} v{{ $t->version }}</option>@endforeach
        </select></div>
    <div class="actions"><button class="btn" type="submit">Apply</button><a class="btn ghost" href="{{ route('admin.reports') }}">Reset</a></div>
</form>

<p class="small" style="margin:0 0 12px">
    <b>Scope:</b>
    {{ $selected->isNotEmpty() ? $selected->join(', ') : 'All rooms' }} ·
    {{ !empty($filters['from']) ? \Carbon\Carbon::parse($filters['from'])->format('M j, Y') : 'Beginning' }} –
    {{ !empty($filters['to']) ? \Carbon\Carbon::parse($filters['to'])->format('M j, Y') : 'today' }}
</p>

@if($report['total'] === 0)
    <div class="card muted">No responses match these filters.</div>
@else
<div class="grid stats">
    <div class="stat key"><div class="n">{{ $report['avg'] !== null ? number_format($report['avg'], 2) : '–' }}</div><div class="l">Overall average (out of 5)<br>{{ Evaluation::describe($report['avg']) }}</div></div>
    <div class="stat"><div class="n">{{ $report['total'] }}</div><div class="l">Respondents</div></div>
    <div class="stat"><div class="n">{{ count($report['rooms']) }}</div><div class="l">Rooms evaluated</div></div>
</div>

<div class="card">
    <h2>Rating distribution</h2>
    @php $dtotal = max(1, array_sum($report['dist'])); @endphp
    <div class="dist" style="height:16px" role="img" aria-label="Distribution of all ratings">
        @foreach([5,4,3,2,1] as $k)<span class="d{{ $k }}" style="width:{{ $report['dist'][$k] / $dtotal * 100 }}%" title="{{ $k }}: {{ $report['dist'][$k] }}"></span>@endforeach
    </div>
    <div class="legend" style="margin-top:8px">
        @foreach([5 => 'Very Satisfied', 4 => 'Satisfied', 3 => 'Neutral', 2 => 'Dissatisfied', 1 => 'Very Dissatisfied'] as $k => $l)
            <span><i class="d{{ $k }}"></i>{{ $k }} – {{ $l }}: {{ $report['dist'][$k] }} ({{ round($report['dist'][$k] / $dtotal * 100) }}%)</span>
        @endforeach
    </div>
</div>

<div class="card flush">
    <h2>By room</h2>
    <div class="tablewrap"><table class="data">
        <tr><th>Room</th><th class="num">Respondents</th><th class="num">Average</th><th>Interpretation</th></tr>
        @foreach($report['rooms'] as $r)
            <tr><td>{{ $r['name'] }}</td><td class="num">{{ $r['count'] }}</td>
                <td class="num">{{ $r['avg'] !== null ? number_format($r['avg'], 2) : '–' }}</td>
                <td><span class="badge {{ $tone($r['avg']) }}">{{ Evaluation::describe($r['avg']) }}</span></td></tr>
        @endforeach
    </table></div>
</div>

@if(!empty($report['respondents']))
<div class="card flush">
    <h2>Respondent type</h2>
    <div class="tablewrap"><table class="data">
        @foreach($report['respondents'] as $type => $n)
            <tr><td>{{ $type }}</td><td class="num">{{ $n }}</td><td class="num">{{ round($n / $report['total'] * 100) }}%</td></tr>
        @endforeach
    </table></div>
</div>
@endif

<div class="card flush">
    <h2>By criterion</h2>
    <div class="tablewrap"><table class="data">
        @foreach($report['criteria'] as $section => $items)
            <tr><th colspan="4" style="background:#eef1f6;color:var(--ink)">{{ $section }}</th></tr>
            @foreach($items as $c)
                @php $ct = max(1, array_sum($c['dist'])); @endphp
                <tr>
                    <td style="width:46%">{{ $c['label'] }}</td>
                    <td class="num"><b>{{ number_format($c['avg'], 2) }}</b></td>
                    <td><span class="badge {{ $tone($c['avg']) }}">{{ Evaluation::describe($c['avg']) }}</span></td>
                    <td style="min-width:140px"><div class="dist">@foreach([5,4,3,2,1] as $k)<span class="d{{ $k }}" style="width:{{ $c['dist'][$k] / $ct * 100 }}%" title="{{ $k }}: {{ $c['dist'][$k] }}"></span>@endforeach</div></td>
                </tr>
            @endforeach
        @endforeach
    </table></div>
</div>

@if(count($report['lowest']))
<div class="card">
    <h2>Areas to improve first</h2>
    <p class="muted small" style="margin-top:0">The three lowest-rated criteria in this selection.</p>
    <ol style="margin:0;padding-left:20px">
        @foreach($report['lowest'] as $c)<li>{{ $c['label'] }} <b>({{ number_format($c['avg'], 2) }})</b></li>@endforeach
    </ol>
</div>
@endif

<div class="card">
    <h2>Comments and suggestions ({{ count($report['comments']) }})</h2>
    @forelse($report['comments'] as $c)
        <div class="comment">
            <div class="meta">{{ $c['date'] }} · {{ $c['room'] }} · {{ \Illuminate\Support\Str::limit($c['label'], 70) }}</div>
            <div style="white-space:pre-wrap">{{ $c['text'] }}</div>
        </div>
    @empty
        <span class="muted">No written comments.</span>
    @endforelse
</div>

<div class="sign"><div>Prepared by</div><div>Noted by</div></div>
@endif
@endsection
