@extends('layouts.app')
@section('title', 'Admins')
@section('content')
<div class="page-head"><div><h1>Admins</h1><div class="muted">Super admins can manage admins, rotate templates and view the audit log.</div></div>
    <div class="actions"><a class="btn" href="{{ route('admin.admins.create') }}">Add admin</a></div></div>

<div class="card flush">
    <div class="tablewrap"><table class="data">
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr>
        @foreach($admins as $a)
            <tr>
                <td>{{ $a->name }}@if($a->id === auth()->id()) <span class="muted small">(you)</span>@endif</td>
                <td>{{ $a->email }}</td>
                <td>{{ $a->isSuperAdmin() ? 'Super admin' : 'Admin' }}</td>
                <td><span class="badge {{ $a->is_active ? 'good' : 'muted' }}">{{ $a->is_active ? 'Active' : 'Disabled' }}</span></td>
                <td class="small">{{ $a->last_login_at?->diffForHumans() ?? 'never' }}</td>
                <td class="right nowrap">
                    <a class="btn sm ghost" href="{{ route('admin.admins.edit', $a) }}">Edit</a>
                    @if($a->id !== auth()->id())
                        <button class="btn sm danger" type="submit" form="del-{{ $a->id }}">Delete</button>
                    @endif
                </td>
            </tr>
        @endforeach
    </table></div>
</div>
@foreach($admins as $a)
    <form id="del-{{ $a->id }}" method="POST" action="{{ route('admin.admins.destroy', $a) }}" data-confirm="Delete {{ $a->name }}? This cannot be undone.">@csrf @method('DELETE')</form>
@endforeach
@endsection
