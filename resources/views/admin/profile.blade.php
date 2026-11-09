@extends('layouts.app')
@section('title', 'My account')
@section('content')
<div class="page-head"><div><h1>My account</h1><div class="muted">{{ auth()->user()->name }} · {{ auth()->user()->email }}</div></div></div>
<div class="card form-narrow">
    <h2>Change password</h2>
    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf @method('PUT')
        <div class="field"><label class="f" for="current_password">Current password</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password"></div>
        <div class="field"><label class="f" for="password">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <div class="muted small">At least 10 characters with upper and lower case letters and a number.</div></div>
        <div class="field"><label class="f" for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="btn" type="submit">Update password</button>
    </form>
</div>
@endsection
