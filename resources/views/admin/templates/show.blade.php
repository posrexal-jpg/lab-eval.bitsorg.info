@extends('layouts.app')
@section('title', $template->name)
@section('content')
<div class="page-head">
    <div>
        <h1>{{ $template->name }} <span class="muted" style="font-weight:400">v{{ $template->version }}</span></h1>
        <span class="badge {{ $template->is_active ? 'good' : 'muted' }}">{{ $template->is_active ? 'Live' : 'Inactive' }}</span>
    </div>
    <div class="actions">
        @unless($template->is_active)
            <form method="POST" action="{{ route('admin.templates.activate', $template) }}" data-confirm="Make this the live form for all rooms?">@csrf <button class="btn" type="submit">Activate</button></form>
            <form method="POST" action="{{ route('admin.templates.destroy', $template) }}" data-confirm="Delete this template?">@csrf @method('DELETE') <button class="btn danger" type="submit">Delete</button></form>
        @endunless
        <a class="btn ghost" href="{{ route('admin.templates.index') }}">Back</a>
    </div>
</div>

<div class="grid two">
    <div class="card">
        <h2>Detected fields ({{ count($template->fields()) }})</h2>
        <p class="muted small" style="margin-top:0">Check that labels and sections look right. These drive validation and reports.</p>
        @php $last = null; @endphp
        <ul class="q-list">
            @foreach($template->fields() as $f)
                @if($f['rating'] && $f['section'] !== $last)
                    @php $last = $f['section']; @endphp
                    <li class="sect" style="display:block;border:0">{{ $f['section'] ?? 'Ratings' }}</li>
                @endif
                <li>
                    <span>{{ $f['label'] }}</span>
                    <span class="nowrap">
                        <span class="badge {{ $f['rating'] ? 'good' : 'muted' }}">{{ $f['rating'] ? 'rating 1–5' : $f['type'] }}</span>
                        @if($f['required'])<span class="badge warn">required</span>@endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card">
        <h2>Preview</h2>
        <p class="muted small" style="margin-top:0">Inputs are disabled here. This is exactly the sanitized markup respondents see.</p>
        <div style="pointer-events:none;max-height:640px;overflow:auto;border:1px solid var(--line);padding:10px;border-radius:6px" inert>
            {!! $template->html !!}
        </div>
    </div>
</div>
@endsection
