@extends('layouts.admin')

@section('title', 'Program Management')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">ACADEMIC DATA</span>
        <h1>Program Management</h1>
        <p>Degree programs that subjects are grouped under.</p>
    </div>

    <div class="page-header-actions">
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


<div class="content-card">

    <div class="table-toolbar">
        <div>
            <h2>Programs</h2>
            <span class="table-count">
                {{ $programs->count() }} program{{ $programs->count() != 1 ? 's' : '' }}
            </span>
        </div>
    </div>

    @if($programs->count())

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

                </tbody>

            </table>

        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                <x-icon name="book" />
            </div>

            <h3>No programs yet</h3>

            <p>Add programs such as BSIT, BSED or BSBIO to group subjects under them.</p>

            <a href="{{ route('programs.create') }}" class="btn btn-primary">
                + Add Program
            </a>

        </div>

    @endif

</div>

@endsection
