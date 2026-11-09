@extends('layouts.app')
@section('title', 'Response #'.$evaluation->id)
@section('print_title', 'Evaluation Response #'.$evaluation->id)
@section('content')
<div class="page-head">
    <div>
        <h1>Response #{{ $evaluation->id }}</h1>
        <div class="muted">{{ $evaluation->room->name }} · {{ $evaluation->created_at->format('F j, Y g:i A') }} · {{ $evaluation->template->name }} v{{ $evaluation->template->version }}</div>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('admin.evaluations.print-form', $evaluation) }}" target="_blank">Print on ISO form</a>
        <button class="btn ghost" type="button" data-print>Print summary</button>
        <a class="btn ghost" href="{{ route('admin.evaluations.index') }}">Back</a>
    </div>
</div>

<div class="card">
    <b>Average:</b> {{ $evaluation->average_score !== null ? number_format($evaluation->average_score, 2).' / 5 ('.\App\Models\Evaluation::describe($evaluation->average_score).')' : 'no ratings' }}
    · <b>Status:</b> {{ $evaluation->status }}
</div>

@foreach($groups as $title => $items)
<div class="card flush">
    <h2>{{ $title }}</h2>
    <div class="tablewrap"><table class="data">
        @foreach($items as $i)
            <tr>
                <td style="width:62%">{{ $i['label'] }}</td>
                <td>@if($i['long']) <div style="white-space:pre-wrap">{{ $i['value'] }}</div>
                    @elseif($i['rating']) <b>{{ strtoupper($i['value']) === 'NA' ? 'N/A' : $i['value'] }}</b>
                    @else {{ $i['value'] }} @endif</td>
            </tr>
        @endforeach
    </table></div>
</div>
@endforeach

<div class="card no-print">
    <h2>Moderation</h2>
    <div class="actions">
        @foreach(['visible' => 'Mark visible', 'flagged' => 'Flag for review', 'hidden' => 'Hide from reports'] as $s => $label)
            @if($evaluation->status !== $s)
                <form method="POST" action="{{ route('admin.evaluations.status', $evaluation) }}">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $s }}"><button class="btn ghost" type="submit">{{ $label }}</button></form>
            @endif
        @endforeach
        <form method="POST" action="{{ route('admin.evaluations.destroy', $evaluation) }}" data-confirm="Permanently delete this response?">@csrf @method('DELETE')
            <button class="btn danger" type="submit">Delete</button></form>
    </div>
    <p class="muted small" style="margin-bottom:0">Hidden responses are excluded from averages and reports but kept in the database. Use this for spam or test submissions.</p>
</div>
@endsection
