@extends('layouts.admin')

@section('title', $type === 'issues' ? 'Room Issue Report' : 'Maintenance History')

@section('content')

<div class="page-header">
    <div>
        <span class="page-eyebrow">REPORTS</span>
        <h1>{{ $type === 'issues' ? 'Room Issue Report' : 'Maintenance History' }}</h1>
        <p>{{ $filters->from->format('M j, Y') }} to {{ $filters->to->format('M j, Y') }}</p>
    </div>
</div>

@include('admin.reports.partials.filters', ['route' => 'reports.maintenance'])

<div class="content-card">

    <div class="table-toolbar"><div><h2>{{ $type === 'issues' ? 'Issues' : 'Maintenance' }}</h2><span class="table-count">{{ $records->total() }} {{ Str::plural('record', $records->total()) }}</span></div></div>

    @if($records->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="wrench" /></div>
            <h3>No records in this period</h3>
            <p>Try a wider range of dates or another room.</p>
        </div>
    @elseif($type === 'issues')
        <div class="table-responsive">
            <table class="roomlink-table">
                <thead><tr><th>Classroom</th><th>Issue Category</th><th>Reported</th><th>Status</th><th>Resolution Date</th></tr></thead>
                <tbody>
                    @foreach($records as $issue)
                        <tr>
                            <td>
                                <div class="table-primary">{{ $issue->room->room_name }}</div>
                                <div class="table-secondary">{{ $issue->room->floor?->building?->building_name }} · {{ $issue->room->floor?->building?->campus?->campus_name }}</div>
                            </td>
                            <td>
                                <a href="{{ route('room-issues.show', $issue) }}" class="table-primary">{{ $issue->category_label }}</a>
                                <div class="table-secondary">{{ Str::limit($issue->description, 90) }}</div>
                            </td>
                            <td>
                                <div class="table-primary">{{ $issue->created_at->format('M j, Y') }}</div>
                                <div class="table-secondary">{{ $issue->reporter->full_name }}</div>
                            </td>
                            <td><span class="status-badge issue-{{ $issue->status }}">{{ $issue->status_label }}</span></td>
                            <td class="nowrap">{{ $issue->resolved_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="table-responsive">
            <table class="roomlink-table">
                <thead><tr><th>Classroom</th><th>Reason</th><th>Started</th><th>Status</th><th>Restored</th></tr></thead>
                <tbody>
                    @foreach($records as $maintenance)
                        <tr>
                            <td>
                                <div class="table-primary">{{ $maintenance->room->room_name }}</div>
                                <div class="table-secondary">{{ $maintenance->room->floor?->building?->building_name }} · {{ $maintenance->room->floor?->building?->campus?->campus_name }}</div>
                            </td>
                            <td>
                                {{ $maintenance->reason ?? '—' }}
                                @if($maintenance->roomIssue)
                                    <div class="table-secondary"><a href="{{ route('room-issues.show', $maintenance->roomIssue) }}">Issue: {{ $maintenance->roomIssue->category_label }}</a></div>
                                @endif
                            </td>
                            <td>
                                <div class="table-primary">{{ $maintenance->started_at->format('M j, Y g:i A') }}</div>
                                <div class="table-secondary">{{ $maintenance->starter?->full_name }}</div>
                            </td>
                            <td><span class="status-badge {{ $maintenance->ended_at ? 'issue-resolved' : 'status-maintenance' }}">{{ $maintenance->ended_at ? 'Restored' : 'Under maintenance' }}</span></td>
                            <td>
                                <div class="table-primary">{{ $maintenance->ended_at?->format('M j, Y g:i A') ?? '—' }}</div>
                                <div class="table-secondary">{{ $maintenance->ender?->full_name }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($records->hasPages())
        <div class="table-pagination">{{ $records->links() }}</div>
    @endif

</div>

@endsection
