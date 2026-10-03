@extends('layouts.admin')

@section('title', 'Section Management')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">ACADEMIC DATA</span>

        <h1>Section Management</h1>

        <p>
            Manage student sections used in classroom schedules.
        </p>

    </div>

    <a
        href="{{ route('sections.create') }}"
        class="btn-primary"
    >
        + Add Section
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

            <h2>Sections</h2>

            <span class="table-count">

                {{ $sections->total() }}

                registered section{{ $sections->total() != 1 ? 's' : '' }}

            </span>

        </div>


        <form
            method="GET"
            action="{{ route('sections.index') }}"
            class="table-search"
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search sections..."
            >

            <button
                type="submit"
                class="btn-secondary"
            >
                Search
            </button>

        </form>

    </div>


    @if($sections->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>

                        <th>Section</th>
                        <th>Description</th>
                        <th>Class Sessions</th>
                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($sections as $section)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $section->section_name }}
                                </div>

                            </td>


                            <td>

                                <div class="table-secondary">

                                    {{ $section->description ?: 'No description' }}

                                </div>

                            </td>


                            <td>

                                <span class="count-badge">

                                    {{ $section->class_sessions_count }}

                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('sections.show', $section) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('sections.edit', $section) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('sections.destroy', $section) }}"
                                        onsubmit="return confirm('Delete this section?');"
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
            {{ $sections->links() }}
        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                ☷
            </div>

            <h3>No sections found</h3>

            <p>
                Add student sections to make them available for scheduling.
            </p>

            <a
                href="{{ route('sections.create') }}"
                class="btn-primary"
            >
                + Add Section
            </a>

        </div>

    @endif

</div>

@endsection