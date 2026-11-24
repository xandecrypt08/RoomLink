@extends('layouts.admin')

@section('title', 'Room Issues')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">MAINTENANCE AND REPORTS</span>
        <h1>Room Issues</h1>
        <p>Problems reported by faculty, from report to resolution.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('reports.maintenance') }}" class="btn btn-secondary">Issue and Maintenance Report</a>
    </div>

</div>


<nav class="page-tabs" aria-label="Issue status">
    @foreach(\App\Models\RoomIssue::STATUSES as $value => $label)
        <a
            href="{{ route('room-issues.index', array_filter(['status' => $value, 'category' => $category, 'search' => $search])) }}"
            class="{{ $status === $value ? 'active' : '' }}"
            @if($status === $value) aria-current="page" @endif
        >
            {{ $label }}
            <span class="page-tab-count">{{ $counts[$value] ?? 0 }}</span>
        </a>
    @endforeach
</nav>


<div class="content-card">

    <div class="table-toolbar">

        <div>
            <h2>{{ \App\Models\RoomIssue::STATUSES[$status] }}</h2>
            <span class="table-count">{{ $issues->total() }} {{ Str::plural('issue', $issues->total()) }}</span>
        </div>

        <form method="GET" action="{{ route('room-issues.index') }}" class="table-search">
            <input type="hidden" name="status" value="{{ $status }}">

            <select name="category" aria-label="Category">
                <option value="">All categories</option>
                @foreach(\App\Models\RoomIssue::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" {{ $category === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <input type="text" name="search" value="{{ $search }}" placeholder="Search room or description...">
            <button type="submit" class="btn-secondary">Search</button>
        </form>

    </div>

    @if($issues->isEmpty())

        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="check-circle" /></div>
            <h3>Nothing here</h3>
            <p>No {{ mb_strtolower(\App\Models\RoomIssue::STATUSES[$status]) }} issues{{ $search || $category ? ' match your search' : '' }}.</p>
        </div>

    @else

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Issue</th>
                        <th>Reported</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($issues as $issue)

                        <tr>
                            <td>
                                <div class="table-primary">{{ $issue->room->room_name }}</div>
                                <div class="table-secondary">{{ $issue->room->floor?->building?->building_name }}</div>
                                @if($issue->room->isUnderMaintenance())
                                    <span class="room-status status-maintenance">Under maintenance</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-primary">{{ $issue->category_label }}</div>
                                <div class="table-secondary">{{ Str::limit($issue->description, 90) }}</div>
                                @if($issue->qr_replacement_requested)
                                    <span class="status-badge status-pending">Replacement requested</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-primary">{{ $issue->created_at->format('M j, Y') }}</div>
                                <div class="table-secondary">{{ $issue->reporter->full_name }}</div>
                            </td>
                            <td>{{ $issue->assigned_to ?? '—' }}</td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('room-issues.show', $issue) }}" class="action-view">Open</a>

                                    @if($issue->status === \App\Models\RoomIssue::STATUS_PENDING)
                                        <form method="POST" action="{{ route('room-issues.update', $issue) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $issue->nextStatus() }}">
                                            <button type="submit" class="action-edit">Acknowledge</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if($issues->hasPages())
            <div class="table-pagination">{{ $issues->links() }}</div>
        @endif

    @endif

</div>

@endsection
