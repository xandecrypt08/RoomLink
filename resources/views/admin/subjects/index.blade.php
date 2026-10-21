@extends('layouts.admin')

@section('title', 'Subject Management')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Subject Management</h1>

        <p>
            Manage subjects used in classroom schedules.
        </p>

    </div>

    <div class="page-header-actions">

        <a
            href="{{ route('subjects.import.create') }}"
            class="btn btn-secondary"
        >
            Import from Document
        </a>

        <a
            href="{{ route('subjects.create') }}"
            class="btn btn-primary"
        >
            + Add Subject
        </a>

    </div>

</div>


@if(session('success'))

    <div class="alert-success">
        {{ session('success') }}
    </div>

@endif


@if(session('error'))

    <div class="alert-error">
        {{ session('error') }}
    </div>

@endif


<div class="content-card">

    <div class="table-toolbar">

        <div>

            <h2>Subjects</h2>

            <span class="table-count">
                {{ $subjects->total() }}
                registered subject{{ $subjects->total() != 1 ? 's' : '' }}
            </span>

        </div>


        <form
            method="GET"
            action="{{ route('subjects.index') }}"
            class="table-search"
        >

            <select name="program" aria-label="Filter by program">
                <option value="">All programs</option>

                @foreach($programs->groupBy(fn ($program) => $program->college?->college_code ?? 'Other') as $collegeCode => $collegePrograms)
                    <optgroup label="{{ $collegeCode }}">
                        @foreach($collegePrograms as $program)
                            <option value="{{ $program->id }}" {{ $programId === $program->id ? 'selected' : '' }}>
                                {{ $program->program_code }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search subjects..."
            >

            <button
                type="submit"
                class="btn-secondary"
            >
                Search
            </button>

        </form>

    </div>


    @if($subjects->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>

                        <th>Subject</th>
                        <th>Code</th>
                        <th>Programs</th>
                        <th>Class Sessions</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($subjects as $subject)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $subject->subject_name }}
                                </div>

                                <div class="table-secondary">
                                    {{ $subject->description ?: 'No description' }}
                                </div>

                            </td>


                            <td>

                                <span class="code-badge">
                                    {{ $subject->subject_code }}
                                </span>

                            </td>


                            <td>

                                <div class="program-tags">
                                    @forelse($subject->programs as $program)
                                        <span class="program-tag">{{ $program->program_code }}</span>
                                    @empty
                                        <span class="table-secondary">—</span>
                                    @endforelse
                                </div>

                            </td>


                            <td>

                                <span class="count-badge">
                                    {{ $subject->class_sessions_count }}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('subjects.show', $subject) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('subjects.edit', $subject) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('subjects.destroy', $subject) }}"
                                        onsubmit="return confirm('Delete this subject?');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="action-delete"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        <div class="table-pagination">
            {{ $subjects->links() }}
        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                <x-icon name="book" />
            </div>

            <h3>No subjects found</h3>

            <p>
                Add subjects to make them available for classroom scheduling.
            </p>

            <a
                href="{{ route('subjects.create') }}"
                class="btn-primary"
            >
                + Add Subject
            </a>

        </div>

    @endif

</div>

@endsection