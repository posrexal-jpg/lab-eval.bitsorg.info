@extends('layouts.app')
@section('title', $room->exists ? 'Edit room' : 'Add room')
@section('content')
<div class="page-head"><h1>{{ $room->exists ? 'Edit room' : 'Add room' }}</h1></div>
<div class="card form-narrow">
    <form method="POST" action="{{ $room->exists ? route('admin.rooms.update', $room) : route('admin.rooms.store') }}">
        @csrf
        @if($room->exists) @method('PUT') @endif
        <div class="field">
            <label class="f" for="name">Room name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $room->name) }}" required maxlength="120">
            <div class="muted small">If a form has a “Computer Laboratory 1/2/3” choice, a matching name pre-selects it.</div>
        </div>
        <div class="field">
            <label class="f" for="building">Building / location</label>
            <input id="building" type="text" name="building" value="{{ old('building', $room->building) }}" maxlength="120">
        </div>
        <div class="field">
            <label class="f" for="capacity">Capacity (workstations)</label>
            <input id="capacity" type="number" name="capacity" value="{{ old('capacity', $room->capacity) }}" min="1" max="1000">
        </div>
        <label class="check field"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $room->is_active))> Accepting evaluations</label>
        <div class="actions">
            <button class="btn" type="submit">Save</button>
            <a class="btn ghost" href="{{ route('admin.rooms.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
