@extends('layouts.admin')

@section('title', 'Faculty Management')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Faculty Management</h1>

        <p>
            Manage faculty members and their academic information.
        </p>

    </div>

    <a
        href="{{ route('faculties.create') }}"
        class="btn-primary"
    >
        + Add Faculty
    </a>

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

            <h2>Faculty</h2>

            <span class="table-count">
                {{ $faculties->total() }}
                registered faculty member{{ $faculties->total() != 1 ? 's' : '' }}
            </span>

        </div>


        <form
            method="GET"
            action="{{ route('faculties.index') }}"
            class="table-search"
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search faculty..."
            >

            <button
                type="submit"
                class="btn-secondary"
            >
                Search
            </button>

        </form>

    </div>


    @if($faculties->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>

                        <th>Faculty</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Class Sessions</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($faculties as $faculty)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $faculty->full_name }}
                                </div>

                                <div class="table-secondary">
                                    {{ $faculty->email ?: 'No email provided' }}
                                </div>

                            </td>


                            <td>

                                <span class="code-badge">
                                    {{ $faculty->employee_id }}
                                </span>

                            </td>


                            <td>

                                <span class="location-text">
                                    {{ $faculty->department ?: '—' }}
                                </span>

                            </td>


                            <td>

                                <span class="count-badge">
                                    {{ $faculty->class_sessions_count }}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('faculties.show', $faculty) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('faculties.edit', $faculty) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('faculties.destroy', $faculty) }}"
                                        onsubmit="return confirm('Delete this faculty member?');"
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
            {{ $faculties->links() }}
        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                ♙
            </div>

            <h3>No faculty members found</h3>

            <p>
                Add faculty members to use them in classroom schedules.
            </p>

            <a
                href="{{ route('faculties.create') }}"
                class="btn-primary"
            >
                + Add Faculty
            </a>

        </div>

    @endif

</div>

@endsection