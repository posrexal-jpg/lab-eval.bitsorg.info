<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Dashboard') · Lab Evaluation</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body @yield('body_attrs')>
<div class="shell">
    <aside class="side">
        <div class="brand">
            <b>Lab Evaluation</b>
            <span>{{ config('evaluation.institution.name') }}</span>
        </div>
        <nav>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.monitor') }}" class="{{ request()->routeIs('admin.monitor') ? 'active' : '' }}">Monitor</a>
            <a href="{{ route('admin.rooms.index') }}" class="{{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}">Rooms &amp; QR codes</a>
            <a href="{{ route('admin.evaluations.index') }}" class="{{ request()->routeIs('admin.evaluations.*') ? 'active' : '' }}">Responses</a>
            <a href="{{ route('admin.reports') }}" class="{{ request()->routeIs('admin.reports') ? 'active' : '' }}">Reports</a>
            <a href="{{ route('admin.templates.index') }}" class="{{ request()->routeIs('admin.templates.*') ? 'active' : '' }}">Form templates</a>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.admins.index') }}" class="{{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">Admins</a>
                <a href="{{ route('admin.audit') }}" class="{{ request()->routeIs('admin.audit') ? 'active' : '' }}">Audit log</a>
            @endif
            <a href="{{ route('admin.profile') }}" class="{{ request()->routeIs('admin.profile') ? 'active' : '' }}">My account</a>
        </nav>
        <div class="who">
            {{ auth()->user()->name }}<br>
            <form method="POST" action="{{ route('logout') }}">@csrf <button type="submit">Sign out</button></form>
        </div>
    </aside>

    <main class="main">
        <div class="print-head">
            <b>{{ config('evaluation.institution.name') }}</b><br>
            <span>{{ config('evaluation.institution.campus') }}</span><br>
            <span>@yield('print_title', 'Laboratory Evaluation')</span>
        </div>

        @if(session('status'))<div class="flash ok" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())
            <div class="flash error" role="alert">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
