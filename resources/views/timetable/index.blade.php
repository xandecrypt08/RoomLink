@extends('layouts.app')

@section('title', $name ? "Timetable: {$name}" : 'Timetables')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">WEEKLY TIMETABLE</span>
        <h1>{{ $name ?? 'Timetables' }}</h1>
        <p>{{ $term->label }}</p>
    </div>

    <div class="page-header-actions no-print">
        @unless(auth()->user()->isStudent())
            <x-term-picker />
        @endunless

        @if($name)
            <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
        @endif
    </div>

</div>

@unless(auth()->user()->isStudent())
    <x-term-notice />
@endunless


<nav class="page-tabs no-print" aria-label="Timetable of a">
    @foreach($types as $value => $label)
        <a href="{{ route('timetable', ['type' => $value]) }}" class="{{ $type === $value ? 'active' : '' }}" @if($type === $value) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>


<form method="GET" action="{{ route('timetable') }}" class="content-card report-filters no-print" data-timetable-form>
    <input type="hidden" name="type" value="{{ $type }}">

    <label>{{ $types[$type] }}
        <select name="id" onchange="this.form.submit()">
            <option value="">Choose a {{ mb_strtolower($type === 'faculty' ? 'faculty member' : $types[$type]) }}</option>
            @foreach($options as $optionId => $optionName)
                <option value="{{ $optionId }}" {{ $id === $optionId ? 'selected' : '' }}>{{ $optionName }}</option>
            @endforeach
        </select>
    </label>

    <noscript><button type="submit" class="btn btn-primary">Show</button></noscript>
</form>


<div class="content-card timetable-card">

    @if($name === null)
        <div class="small-empty-state">
            <strong>Choose a {{ mb_strtolower($type === 'faculty' ? 'faculty member' : $types[$type]) }}</strong>
            <span>Its classes for the week appear here, and the gaps between them are free.</span>
        </div>
    @elseif($classes->isEmpty())
        <div class="small-empty-state">
            <strong>No classes this semester</strong>
            <span>{{ $name }} has no classes in {{ $term->label }}.</span>
        </div>
    @else
        <x-timetable :classes="$classes" :hide="$type" />
    @endif

</div>

@endsection
