@extends('layouts.admin')

@section('title', 'Facility Management')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Facility Management</h1>

        <p>
            Manage facilities available in RoomLink classrooms.
        </p>
    </div>

    <a href="{{ route('facilities.create') }}" class="btn-primary">
        + Add Facility
    </a>

</div>


@if(session('success'))

    <div class="alert-success">
        {{ session('success') }}
    </div>

@endif


<div class="content-card">

    <div class="table-toolbar">

        <div>

            <h2>Facilities</h2>

            <span class="table-count">
                {{ $facilities->total() }}
                registered facilit{{ $facilities->total() != 1 ? 'ies' : 'y' }}
            </span>

        </div>


        <form
            method="GET"
            action="{{ route('facilities.index') }}"
            class="table-search"
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search facilities..."
            >

            <button
                type="submit"
                class="btn-secondary"
            >
                Search
            </button>

        </form>

    </div>


    @if($facilities->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>
                        <th>Facility</th>
                        <th>Rooms Using Facility</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                    @foreach($facilities as $facility)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $facility->facility_name }}
                                </div>

                                <div class="table-secondary">
                                    Classroom facility
                                </div>

                            </td>


                            <td>

                                <span class="count-badge">
                                    {{ $facility->rooms_count }}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('facilities.show', $facility) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('facilities.edit', $facility) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('facilities.destroy', $facility) }}"
                                        onsubmit="return confirm('Delete this facility?');"
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
            {{ $facilities->links() }}
        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">◇</div>

            <h3>No facilities found</h3>

            <p>
                Add facilities such as projectors, air conditioning,
                computers, or whiteboards.
            </p>

            <a
                href="{{ route('facilities.create') }}"
                class="btn-primary"
            >
                + Add Facility
            </a>

        </div>

    @endif

</div>

@endsection