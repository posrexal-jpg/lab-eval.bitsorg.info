@extends('layouts.public')
@section('title', 'Thank you')
@section('content')
<div class="center-card">
    <div class="card">
        <h1>{{ $repeat ? 'Already received' : 'Thank you!' }}</h1>
        <p>
            @if($repeat)
                We already have your evaluation for <b>{{ $room->name }}</b> from this device. You can submit another one later.
            @else
                Your feedback for <b>{{ $room->name }}</b> was submitted. It helps us improve the laboratory.
            @endif
        </p>
        <p class="muted small">{{ config('evaluation.institution.name') }}</p>
    </div>
</div>
@endsection
