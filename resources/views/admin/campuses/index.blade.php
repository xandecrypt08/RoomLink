@extends('layouts.admin')

@section('content')

<div class="page-header">
    <div>
        <h1>Campus Management</h1>
        <p>Manage the campuses registered in RoomLink.</p>
    </div>

    <a href="{{ route('campuses.create') }}" class="btn-primary">
        + Add Campus
    </a>
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

    <form method="GET" action="{{ route('campuses.index') }}" class="search-bar">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search campus name or code..."
        >

        <button type="submit" class="btn-secondary">
            Search
        </button>

        @if($search)
            <a href="{{ route('campuses.index') }}" class="btn-light">
                Clear
            </a>
        @endif
    </form>

    <div class="table-wrapper">

        <table class="roomlink-table">

            <thead>
                <tr>
                    <th>Campus Name</th>
                    <th>Code</th>
                    <th>Address</th>
                    <th>Buildings</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                @forelse($campuses as $campus)

                    <tr>

                        <td>
                            <strong>{{ $campus->campus_name }}</strong>
                        </td>

                        <td>
                            {{ $campus->campus_code }}
                        </td>

                        <td>
                            {{ $campus->address }}
                        </td>

                        <td>
                            {{ $campus->buildings()->count() }}
                        </td>

                        <td class="action-buttons">

                            <a
                                href="{{ route('campuses.edit', $campus) }}"
                                class="btn-small btn-edit"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="{{ route('campuses.destroy', $campus) }}"
                                onsubmit="return confirm('Delete this campus?');"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn-small btn-delete"
                                >
                                    Delete
                                </button>
                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="empty-table">
                            No campuses found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="pagination-wrapper">
        {{ $campuses->links() }}
    </div>

</div>

@endsection