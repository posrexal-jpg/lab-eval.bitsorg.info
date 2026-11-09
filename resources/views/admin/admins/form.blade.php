@extends('layouts.app')
@section('title', $admin->exists ? 'Edit admin' : 'Add admin')
@section('content')
<div class="page-head"><h1>{{ $admin->exists ? 'Edit admin' : 'Add admin' }}</h1></div>
<div class="card form-narrow">
    <form method="POST" action="{{ $admin->exists ? route('admin.admins.update', $admin) : route('admin.admins.store') }}" autocomplete="off">
        @csrf
        @if($admin->exists) @method('PUT') @endif
        <div class="field"><label class="f" for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $admin->name) }}" required maxlength="120"></div>
        <div class="field"><label class="f" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $admin->email) }}" required maxlength="190"></div>
        <div class="field"><label class="f" for="role">Role</label>
            <select id="role" name="role">
                <option value="admin" @selected(old('role', $admin->role) === 'admin')>Admin</option>
                <option value="super_admin" @selected(old('role', $admin->role) === 'super_admin')>Super admin</option>
            </select></div>
        <div class="field"><label class="f" for="password">{{ $admin->exists ? 'New password (leave blank to keep)' : 'Password' }}</label>
            <input id="password" type="password" name="password" autocomplete="new-password" @required(!$admin->exists)>
            <div class="muted small">At least 10 characters with upper and lower case letters and a number.</div></div>
        <div class="field"><label class="f" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
        @if($admin->exists)
            <label class="check field"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $admin->is_active))> Account active</label>
        @endif
        <div class="actions"><button class="btn" type="submit">Save</button><a class="btn ghost" href="{{ route('admin.admins.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
