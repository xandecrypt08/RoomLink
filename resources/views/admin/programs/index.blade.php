@extends('layouts.admin')

@section('title', 'Program Management')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Program Management</h1>
        <p>Degree programs by college. Subjects are grouped under these programs.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('colleges.index') }}" class="btn btn-secondary">
            Manage Colleges
        </a>

        <a href="{{ route('programs.create') }}" class="btn btn-primary">
            + Add Program
        </a>
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif


@php
    // One card per college, then programs without a college (key 0), if any.
    $sections = $colleges->map(fn ($college) => ['college' => $college, 'programs' => $programsByCollege->get($college->id, collect())]);

    if ($programsByCollege->has(0)) {
        $sections->push(['college' => null, 'programs' => $programsByCollege->get(0)]);
    }
@endphp


@forelse($sections as $section)

    <div class="content-card college-card">

        <div class="table-toolbar">

            <div>
                <h2>
                    @if($section['college'])
                        {{ $section['college']->college_name }}
                        <span class="code-badge">{{ $section['college']->college_code }}</span>
                    @else
                        Programs without a college
                    @endif
                </h2>

                <span class="table-count">
                    {{ $section['programs']->count() }} program{{ $section['programs']->count() != 1 ? 's' : '' }}
                </span>
            </div>

            @if($section['college'])
                <a href="{{ route('programs.create', ['college' => $section['college']->id]) }}" class="btn btn-secondary">
                    + Add Program
                </a>
            @endif

        </div>

        @if($section['programs']->count())

            <div class="table-responsive">

                <table class="roomlink-table">

                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Code</th>
                            <th>Subjects</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($section['programs']->groupBy(fn ($program) => $program->program_group ?? '') as $group => $programs)

                            @if($group !== '')
                                <tr class="program-group-row">
                                    <td colspan="4">{{ $group }}</td>
                                </tr>
                            @endif

                            @foreach($programs as $program)

                                <tr>

                                    <td>
                                        <div class="table-primary">{{ $program->program_name }}</div>
                                    </td>

                                    <td>
                                        <span class="code-badge">{{ $program->program_code }}</span>
                                    </td>

                                    <td>
                                        <a href="{{ route('subjects.index', ['program' => $program->id]) }}" class="count-badge" title="Show this program's subjects">
                                            {{ $program->subjects_count }}
                                        </a>
                                    </td>

                                    <td>
                                        <div class="table-actions">

                                            <a href="{{ route('programs.edit', $program) }}" class="action-edit">
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route('programs.destroy', $program) }}"
                                                onsubmit="return confirm('Delete this program?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="action-delete">
                                                    Delete
                                                </button>
                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            @endforeach

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="small-empty-state">
                <strong>No programs yet</strong>
                <span>Add this college's degree programs.</span>
            </div>

        @endif

    </div>

@empty

    <div class="content-card">
        <div class="empty-state">

            <div class="empty-state-icon">
                <x-icon name="graduation-cap" />
            </div>

            <h3>No colleges or programs yet</h3>

            <p>Add a college first, then its degree programs.</p>

            <a href="{{ route('colleges.create') }}" class="btn btn-primary">
                + Add College
            </a>

        </div>
    </div>

@endforelse

@endsection
