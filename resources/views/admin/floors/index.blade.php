@extends('layouts.admin')

@section('title', 'Floor Management')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>

        <h1>Floor Management</h1>

        <p>
            Manage floors within each RoomLink building.
        </p>
    </div>

    <a href="{{ route('floors.create') }}" class="btn-primary">
        + Add Floor
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
            <h2>Floors</h2>

            <span class="table-count">
                {{ $floors->total() }}
                registered floor{{ $floors->total() != 1 ? 's' : '' }}
            </span>
        </div>


        <form
            method="GET"
            action="{{ route('floors.index') }}"
            class="table-search"
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search floors or buildings..."
            >

            <button type="submit" class="btn-secondary">
                Search
            </button>

        </form>

    </div>


    @if($floors->count())

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>

                    <tr>
                        <th>Floor</th>
                        <th>Building</th>
                        <th>Campus</th>
                        <th>Rooms</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($floors as $floor)

                        <tr>

                            <td>

                                <div class="table-primary">
                                    Floor {{ $floor->floor_number }}
                                </div>

                                <div class="table-secondary">
                                    {{ $floor->description ?: 'No description' }}
                                </div>

                            </td>


                            <td>

                                <div class="table-primary">
                                    {{ $floor->building->building_name ?? '—' }}
                                </div>

                                <div class="table-secondary">

                                    @if($floor->building?->building_code)
                                        {{ $floor->building->building_code }}
                                    @else
                                        No building code
                                    @endif

                                </div>

                            </td>


                            <td>

                                <span class="location-text">

                                    {{ $floor->building->campus->campus_name ?? '—' }}

                                </span>

                            </td>


                            <td>

                                <span class="count-badge">
                                    {{ $floor->rooms_count }}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <a
                                        href="{{ route('floors.show', $floor) }}"
                                        class="action-view"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="{{ route('floors.edit', $floor) }}"
                                        class="action-edit"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('floors.destroy', $floor) }}"
                                        onsubmit="return confirm('Delete this floor?');"
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
            {{ $floors->links() }}
        </div>

    @else

        <div class="empty-state">

            <div class="empty-state-icon">▥</div>

            <h3>No floors found</h3>

            <p>
                Add your first floor to start organizing rooms by building.
            </p>

            <a
                href="{{ route('floors.create') }}"
                class="btn-primary"
            >
                + Add Floor
            </a>

        </div>

    @endif

</div>

@endsection