@extends('layouts.public')
@section('title', 'Evaluate ' . $room->name)
@section('content')
<div class="public">
    <div class="banner">
        <div><b>{{ $room->name }}</b>@if($room->building)<br><span class="small">{{ $room->building }}</span>@endif</div>
        <div class="small" style="align-self:center">Your answers are anonymous unless you enter your name.</div>
    </div>

    @if($errors->any())
        <div class="flash error" role="alert">
            @if($errors->has('form'))
                {{ $errors->first('form') }}
            @else
                Please check the highlighted items: {{ $errors->count() }} {{ \Illuminate\Support\Str::plural('item', $errors->count()) }} need attention.
                <ul style="margin:6px 0 0 18px;padding:0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            @endif
        </div>
    @endif

    <form id="evalform" method="POST" action="{{ route('evaluate.store', $room->token) }}"
          data-room="{{ $room->name }}" data-old="{{ json_encode(old()) }}">
        @csrf
        <input type="hidden" name="_tpl" value="{{ $template->id }}">
        <input type="hidden" name="_ts" value="{{ old('_ts', \Illuminate\Support\Facades\Crypt::encryptString((string) time())) }}">
        {{-- Honeypot: invisible to people, tempting to bots --}}
        <div class="hp" aria-hidden="true">
            <label>Leave this field empty <input type="text" name="contact_url_hp" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="paper">
            {{-- Sanitized by TemplateParser when the template was uploaded --}}
            {!! $template->html !!}
        </div>

        <div class="submit">
            <button class="btn" type="submit" data-once>Submit evaluation</button>
        </div>
    </form>
</div>
@endsection
