@extends('layouts.app')
@section('title', 'Upload template')
@section('content')
<div class="page-head"><h1>Upload form template</h1></div>

<div class="grid two">
    <div class="card">
        <form method="POST" action="{{ route('admin.templates.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="field">
                <label class="f" for="file">HTML file (.html, max {{ config('evaluation.template_max_kb') }} KB, UTF-8)</label>
                <input id="file" type="file" name="file" accept=".html,.htm,text/html" required>
            </div>
            <div class="field">
                <label class="f" for="name">Template name (optional)</label>
                <input id="name" type="text" name="name" maxlength="150" value="{{ old('name') }}" placeholder="Defaults to the file name">
                <div class="muted small">Uploading the same name again creates a new version; old responses keep the version they were answered on.</div>
            </div>
            <label class="check field"><input type="checkbox" name="activate" value="1" checked> Make it live immediately</label>
            <button class="btn" type="submit">Upload and check</button>
        </form>
    </div>

    <div class="card">
        <h2>How your HTML is read</h2>
        <ul class="small" style="padding-left:18px;margin:0">
            <li>Scripts, iframes, buttons, external CSS/images and event handlers are removed for safety. Logos can be embedded as <code>data:image/png;base64</code>.</li>
            <li>Every <code>&lt;input&gt;</code>, <code>&lt;select&gt;</code> and <code>&lt;textarea&gt;</code> needs a <code>name</code>. Do not add your own <code>&lt;form&gt;</code> or submit button.</li>
            <li>Radio groups with values <code>5, 4, 3, 2, 1</code> (and optionally <code>NA</code>) count as rated questions and feed the averages.</li>
            <li>Give a question its text with <code>data-label</code> on the row, or put it in the row’s first cell.</li>
            <li>A row with <code>class="section"</code> groups the questions below it in reports.</li>
            <li>Add <code>required</code> to make an item mandatory.</li>
        </ul>
        <p style="margin-bottom:0"><a href="{{ route('admin.templates.sample') }}">Download the sample</a> (your Computer Laboratory User Satisfaction Form) to see the markup.</p>
    </div>
</div>
@endsection
