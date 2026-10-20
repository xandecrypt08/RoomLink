@extends('layouts.admin')

@section('title', 'Review Subjects')

@section('content')

@php
    $newCount = $rows->whereNull('existing')->count();
    $existingCount = $rows->count() - $newCount;
@endphp

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Review Subjects</h1>
        <p>
            Found {{ $rows->count() }} subject{{ $rows->count() !== 1 ? 's' : '' }} in {{ $fileName }}{{ $pages > 1 ? " ({$pages} pages)" : '' }}.
            Check the codes and titles, untick anything that isn't a subject, then import.
        </p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('subjects.import.create') }}" class="btn btn-secondary">
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
        Some of this document was read from pictures, so a letter or digit may be misread.
        Compare the codes and titles with the document before importing.
    </div>
@endif


<form method="POST" action="{{ route('subjects.import.store') }}">

    @csrf

    @foreach($programs as $program)
        <input type="hidden" name="programs[]" value="{{ $program->id }}">
    @endforeach

    <div class="content-card">

        <div class="table-toolbar">

            <div>
                <h2>2. Review and import</h2>
                <span class="table-count">
                    {{ $newCount }} new · {{ $existingCount }} already in RoomLink
                    · for
                    @foreach($programs as $program)
                        <span class="program-tag">{{ $program->program_code }}</span>
                    @endforeach
                </span>
            </div>

            <label class="import-select-all">
                <input type="checkbox" data-select-all {{ $rows->every(fn ($row) => $row['import'] ?? true) ? 'checked' : '' }}>
                Select all
            </label>

        </div>

        <div class="table-responsive">

            <table class="roomlink-table import-table">

                <thead>
                    <tr>
                        <th class="import-check-cell">Import</th>
                        <th>Course No.</th>
                        <th>Descriptive Title</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($rows as $index => $row)

                        <tr>

                            <td class="import-check-cell">
                                <input
                                    type="checkbox"
                                    name="rows[{{ $index }}][import]"
                                    value="1"
                                    aria-label="Import {{ $row['code'] }}"
                                    data-row-check
                                    {{ ($row['import'] ?? true) ? 'checked' : '' }}
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="rows[{{ $index }}][code]"
                                    value="{{ $row['code'] }}"
                                    maxlength="50"
                                    class="import-input import-code"
                                    aria-label="Course No."
                                >
                            </td>

                            <td>
                                <input
                                    type="text"
                                    name="rows[{{ $index }}][title]"
                                    value="{{ $row['existing']?->subject_name ?? $row['title'] }}"
                                    maxlength="150"
                                    class="import-input"
                                    aria-label="Descriptive Title"
                                    @if($row['existing']) readonly title="Already in RoomLink; edit it from Subjects." @endif
                                >
                            </td>

                            <td class="nowrap">
                                @if($row['existing'])
                                    <span class="status-badge status-neutral">Already exists</span>
                                    <div class="table-secondary">Programs will be added</div>
                                @else
                                    <span class="status-badge status-available">New</span>
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
                    <summary>Show the text read from the document</summary>
                    <pre>{{ \Illuminate\Support\Str::limit($text, 20000) }}</pre>
                </details>
            @endif

            <div class="form-actions">
                <a href="{{ route('subjects.import.create') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Import Selected Subjects</button>
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
