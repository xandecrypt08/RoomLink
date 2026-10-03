@extends('layouts.admin')

@section('title', 'Building Management')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Building Management</h1>

        <p>
            Manage buildings and their campus locations in RoomLink.
        </p>
    </div>

    <a href="{{ route('buildings.create') }}" class="btn-primary">
        + Add Building
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
            <h2>Buildings</h2>

            <span class="table-count">
                {{ $buildings->total() }}
                registered building{{ $buildings->total() == 1 ? '' : 's' }}
            </span>
        </div>


        <form
            method="GET"
            action="{{ route('buildings.index') }}"
            class="table-search"
        >

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search buildings..."
            >

            <button type="submit">
                Search
            </button>

        </form>

    </div>


    @if($buildings->count())

        <div class="table-wrapper">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Building</th>
                        <th>Campus</th>
                        <th>Code</th>
                        <th>Floors</th>
                        <th class="action-column">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($buildings as $building)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    {{ $building->building_name }}
                                </div>

                                @if($building->description)

                                    <div class="table-secondary">
                                        {{ $building->description }}
                                    </div>

                                @endif

                            </td>


                            <td>
                                <span class="location-text">
                                    {{ $building->campus->campus_name ?? '—' }}
                                </span>
                            </td>


                            <td>

                                <span class="code-badge">
                                    {{ $building->building_code ?: '—' }}
                                </span>

                            </td>


                            <td>

                                <span class="count-badge">
                                    {{ $building->floors_count }}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('buildings.show', $building) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('buildings.edit', $building) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('buildings.destroy', $building) }}"
                                        onsubmit="return confirm('Delete this building?')"
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
            {{ $buildings->links() }}
        </div>


    @else

        <div class="empty-state">

            <div class="empty-state-icon">
                ▤
            </div>

            <h3>
                No buildings found
            </h3>

            @if(request('search'))

                <p>
                    No building matches "{{ request('search') }}".
                </p>

                <a
                    href="{{ route('buildings.index') }}"
                    class="btn-secondary"
                >
                    Clear Search
                </a>

            @else

                <p>
                    Add your first building to organize rooms and floors.
                </p>

                <a
                    href="{{ route('buildings.create') }}"
                    class="btn-primary"
                >
                    + Add Building
                </a>

            @endif

        </div>

    @endif

</div>

@endsection