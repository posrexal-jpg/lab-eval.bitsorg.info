@extends('layouts.public')
@section('title', 'Admin sign in')
@section('content')
<div class="login">
    <div class="card">
        <h1>Admin sign in</h1>
        <p class="muted small" style="margin:0 0 16px">{{ config('evaluation.institution.name') }} · Laboratory Evaluation</p>

        @if($errors->any())<div class="flash error" role="alert">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label class="f" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div class="field">
                <label class="f" for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <label class="check field"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
            <button class="btn" type="submit" style="width:100%">Sign in</button>
        </form>
    </div>
</div>
@endsection
