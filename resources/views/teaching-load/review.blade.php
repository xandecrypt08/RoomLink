@extends('layouts.app')

@section('title', 'Review Teaching Load')

@section('content')

@php
    $readyCount = $rows->filter(fn ($row) => ! $row['duplicate'] && $row['problems'] === [])->count();
    $problemCount = $rows->filter(fn ($row) => ! $row['duplicate'] && $row['problems'] !== [])->count();
    $duplicateCount = $rows->where('duplicate', true)->count();
@endphp

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASS SCHEDULE</span>
        <h1>Review Classes</h1>
        <p>
            Found {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('class', $rows->count()) }} in {{ $fileName }}
            for <strong>{{ $faculty->full_name }}</strong>.
            Check each one, fix anything that was misread, then save.
        </p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route($routes['create'], $isAdmin ? ['faculty_id' => $faculty->id] : []) }}" class="btn btn-secondary">
            ← Upload a Different File
        </a>
    </div>

</div>


@if($errors->any())
    <div class="alert alert-error">
        {{ $errors->first() }}
    </div>
@endif

@if($usedOcr)
    <div class="alert alert-warning">
        This load was read from a picture, so a letter or digit may be misread.
        Compare the course numbers, sections and rooms with the original before saving.
    </div>
@endif

@if($mode === 'replace')
    <div class="alert alert-warning">
        Saving replaces the current schedule: {{ $isAdmin ? 'their' : 'your' }} current classes will be set to inactive.
    </div>
@endif


<form method="POST" action="{{ route($routes['store']) }}">

    @csrf

    <input type="hidden" name="mode" value="{{ $mode }}">

    @if($isAdmin)
        <input type="hidden" name="faculty_id" value="{{ $faculty->id }}">
    @endif

    <div class="content-card">

        <div class="table-toolbar">

            <div>
                <h2>2. Review and save</h2>
                <span class="table-count">
                    {{ $readyCount }} ready
                    @if($problemCount) · {{ $problemCount }} need{{ $problemCount === 1 ? 's' : '' }} attention @endif
                    @if($duplicateCount) · {{ $duplicateCount }} already in the schedule @endif
                </span>
            </div>

            <label class="import-select-all">
                <input type="checkbox" data-select-all {{ $rows->every(fn ($row) => $row['import']) ? 'checked' : '' }}>
                Select all
            </label>

        </div>

        <div class="table-responsive">

            <table class="roomlink-table import-table load-table">

                <colgroup>
                    <col class="import-col-check">
                    <col class="load-col-code">
                    <col>
                    <col class="load-col-section">
                    <col class="load-col-when">
                    <col class="load-col-room">
                    <col class="load-col-status">
                </colgroup>

                <thead>
                    <tr>
                        <th class="import-check-cell">Save</th>
                        <th>Course No.</th>
                        <th>Descriptive Title</th>
                        <th>Section</th>
                        <th>Day and Time</th>
                        <th>Room</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($rows as $index => $row)

                        <tr class="{{ ! $row['duplicate'] && $row['problems'] !== [] ? 'load-row-problem' : '' }}">

                            <td class="import-check-cell">
                                <input
                                    type="checkbox"
                                    name="rows[{{ $index }}][import]"
                                    value="1"
                                    aria-label="Save {{ $row['subject_code'] }} on {{ $row['day'] }}"
                                    data-row-check
                                    {{ $row['import'] ? 'checked' : '' }}
                                >
                                <input type="hidden" name="rows[{{ $index }}][room]" value="{{ $row['room'] }}">
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="rows[{{ $index }}][subject_code]"
                                    value="{{ $row['subject_code'] }}"
                                    maxlength="50"
                                    class="import-input import-code"
                                    aria-label="Course No."
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="rows[{{ $index }}][title]"
                                    value="{{ $row['title'] }}"
                                    maxlength="150"
                                    class="import-input"
                                    aria-label="Descriptive Title"
                                    @if($row['subject']) readonly title="Already in RoomLink." @endif
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="rows[{{ $index }}][section]"
                                    value="{{ $row['section'] }}"
                                    maxlength="100"
                                    class="import-input"
                                    aria-label="Section"
                                >
                            </td>

                            <td>
                                <div class="load-when">
                                    <select name="rows[{{ $index }}][day]" class="import-input" aria-label="Day">
                                        @foreach(\App\Models\ClassSession::DAY_NAMES as $code => $name)
                                            <option value="{{ $code }}" {{ $row['day'] === $code ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="time" name="rows[{{ $index }}][start_time]" value="{{ $row['start_time'] }}" class="import-input" aria-label="Start time">
                                    <input type="time" name="rows[{{ $index }}][end_time]" value="{{ $row['end_time'] }}" class="import-input" aria-label="End time">
                                </div>
                            </td>

                            <td>
                                <select name="rows[{{ $index }}][room_id]" class="import-input" aria-label="Room">
                                    <option value="">Choose a room</option>

                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}" {{ $row['room_id'] === $room->id ? 'selected' : '' }}>
                                            {{ $room->room_name }}{{ $room->floor?->building ? ' · '.($room->floor->building->building_code ?: $room->floor->building->building_name) : '' }}
                                        </option>
                                    @endforeach
                                </select>

                                @if($row['room'] !== '')
                                    <div class="table-secondary">Load says: {{ $row['room'] }}</div>
                                @endif
                            </td>

                            <td>
                                @if($row['duplicate'])
                                    <span class="status-badge status-neutral">Already in schedule</span>
                                    <div class="table-secondary">Skipped if ticked</div>
                                @elseif($row['problems'] !== [])
                                    <span class="status-badge status-maintenance">Needs attention</span>
                                    <ul class="load-problems">
                                        @foreach($row['problems'] as $problem)
                                            <li>{{ $problem }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="status-badge status-available">Ready</span>
                                @endif

                                @if(! $row['duplicate'] && ($row['subject'] === null || $row['new_section']))
                                    <div class="table-secondary">
                                        {{ collect([$row['subject'] === null ? 'New subject' : null, $row['new_section'] ? 'New section' : null])->filter()->implode(' · ') }}
                                    </div>
                                @endif
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="import-footer">

            @if($text !== '')
                <details class="import-raw-text">
                    <summary>Show the text read from the file</summary>
                    <pre>{{ \Illuminate\Support\Str::limit($text, 20000) }}</pre>
                </details>
            @endif

            <div class="form-actions">
                <a href="{{ route($routes['back']) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Selected Classes</button>
            </div>

        </div>

    </div>

</form>

@endsection


@push('scripts')
<script>
    (function () {
        var selectAll = document.querySelector('[data-select-all]');
        var checks = document.querySelectorAll('[data-row-check]');

        if (!selectAll) {
            return;
        }

        selectAll.addEventListener('change', function () {
            checks.forEach(function (check) { check.checked = selectAll.checked; });
        });

        checks.forEach(function (check) {
            check.addEventListener('change', function () {
                selectAll.checked = Array.prototype.every.call(checks, function (c) { return c.checked; });
            });
        });
    })();
</script>
@endpush
