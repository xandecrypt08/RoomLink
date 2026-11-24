{{--
    A week as a timetable. On phones it becomes a list by day.
    $classes: class sessions (with room, faculty, subject, section).
    $hide: what every class shares and needn't be repeated (room, section or faculty).
--}}
@props(['classes', 'hide' => null])

@php
    $minutes = fn ($time): int => (int) $time->format('H') * 60 + (int) $time->format('i');
    $days = collect(\App\Models\ClassSession::DAY_NAMES)
        ->reject(fn ($name, $code) => $code === 'Su' && $classes->where('day', 'Su')->isEmpty());

    // Whole hours around the classes, at least 7 AM to 7 PM. One grid row is five minutes.
    $from = min(7, intdiv($classes->min(fn ($class) => $minutes($class->start_time)) ?? 420, 60));
    $to = max(19, (int) ceil(($classes->max(fn ($class) => $minutes($class->end_time)) ?? 1140) / 60));
    $row = fn (int $minute): int => (int) round(($minute - $from * 60) / 5) + 2;
    $columns = $days->keys()->flip()->map(fn ($index) => $index + 2);
@endphp

<div class="timetable" style="--days: {{ $days->count() }}; --rows: {{ ($to - $from) * 12 }}">

    @foreach($days as $code => $dayName)
        <div class="timetable-day" style="grid-column: {{ $columns[$code] }}" aria-hidden="true">{{ $dayName }}</div>
        <div class="timetable-column" style="grid-column: {{ $columns[$code] }}" aria-hidden="true"></div>
    @endforeach

    @for($hour = $from; $hour < $to; $hour++)
        <div class="timetable-hour" style="grid-row: {{ $row($hour * 60) }} / span 12" aria-hidden="true">{{ \Carbon\Carbon::createFromTime($hour)->format('g A') }}</div>
        <div class="timetable-line" style="grid-row: {{ $row($hour * 60) }}" aria-hidden="true"></div>
    @endfor

    @foreach($classes as $class)
        <article
            class="timetable-class"
            style="grid-column: {{ $columns[$class->day] }}; grid-row: {{ $row($minutes($class->start_time)) }} / {{ max($row($minutes($class->end_time)), $row($minutes($class->start_time)) + 3) }}"
            title="{{ $class->subject?->subject_name }}"
        >
            <span class="timetable-class-day">{{ $class->day_name }}</span>
            <strong>{{ $class->subject?->subject_code }}</strong>
            <span>{{ $class->start_time->format($class->start_time->format('A') === $class->end_time->format('A') ? 'g:i' : 'g:i A') }}–{{ $class->end_time->format('g:i A') }}</span>
            @if($hide !== 'section')
                <span>{{ $class->section?->section_name }}</span>
            @endif
            @if($hide !== 'room')
                <span>{{ $class->room?->room_name }}</span>
            @endif
            @if($hide !== 'faculty')
                <span>{{ $class->faculty?->full_name }}</span>
            @endif
        </article>
    @endforeach

</div>
