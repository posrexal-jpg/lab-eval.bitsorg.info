@extends('layouts.public')
@section('title', 'Form unavailable')
@section('content')
<div class="center-card">
    <div class="card">
        <h1>Evaluation is not open</h1>
        <p>There is no active evaluation form for <b>{{ $room->name }}</b> right now. Please try again later or tell the laboratory staff.</p>
    </div>
</div>
@endsection
