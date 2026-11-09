@extends('layouts.app')
@section('title', 'Form templates')
@section('content')
<div class="page-head">
    <div><h1>Form templates</h1><div class="muted">Only one template is live at a time. Every room QR code opens the active one.</div></div>
    <div class="actions">
        <a class="btn ghost" href="{{ route('admin.templates.sample') }}">Download sample .html</a>
        <a class="btn" href="{{ route('admin.templates.create') }}">Upload template</a>
    </div>
</div>

<div class="card flush">
    <div class="tablewrap"><table class="data">
        <tr><th>Name</th><th class="num">Version</th><th class="num">Questions</th><th class="num">Responses</th><th>Uploaded</th><th>Status</th><th></th></tr>
        @forelse($templates as $t)
            <tr>
                <td><a href="{{ route('admin.templates.show', $t) }}"><b>{{ $t->name }}</b></a></td>
                <td class="num">v{{ $t->version }}</td>
                <td class="num">{{ count($t->ratingFields()) }} rated · {{ count($t->fields()) }} fields</td>
                <td class="num">{{ $t->evaluations_count }}</td>
                <td class="small">{{ $t->created_at->format('M j, Y') }}<br><span class="muted">{{ $t->uploader?->name }}</span></td>
                <td><span class="badge {{ $t->is_active ? 'good' : 'muted' }}">{{ $t->is_active ? 'Live' : 'Inactive' }}</span></td>
                <td class="right nowrap">
                    @unless($t->is_active)
                        <form method="POST" action="{{ route('admin.templates.activate', $t) }}" style="display:inline" data-confirm="Make this the live form for all rooms?">@csrf <button class="btn sm" type="submit">Activate</button></form>
                    @endunless
                    <a class="btn sm ghost" href="{{ route('admin.templates.show', $t) }}">Preview</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">No templates yet.</td></tr>
        @endforelse
    </table></div>
</div>
@endsection
