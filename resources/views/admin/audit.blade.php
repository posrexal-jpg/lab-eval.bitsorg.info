@extends('layouts.app')
@section('title', 'Audit log')
@section('content')
<div class="page-head"><div><h1>Audit log</h1><div class="muted">Who did what, and from where.</div></div></div>
<div class="card flush">
    <div class="tablewrap"><table class="data">
        <tr><th>When</th><th>Admin</th><th>Action</th><th>Subject</th><th>IP</th></tr>
        @forelse($logs as $l)
            <tr>
                <td class="nowrap">{{ $l->created_at->format('M j, Y g:i A') }}</td>
                <td>{{ $l->user?->name ?? '—' }}</td>
                <td>{{ $l->action }}</td>
                <td>{{ $l->subject }}</td>
                <td class="small muted">{{ $l->ip }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Nothing logged yet.</td></tr>
        @endforelse
    </table></div>
</div>
{{ $logs->links() }}
@endsection
