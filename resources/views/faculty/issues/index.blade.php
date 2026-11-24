@extends('layouts.app')

@section('title', 'Report an Issue')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM SESSIONS</span>
        <h1>My Reports</h1>
        <p>Room issues and damaged QR codes you reported, and what is being done about them.</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.issues.create', ['category' => 'qr_code']) }}" class="btn btn-secondary">Report Damaged QR Code</a>
        <a href="{{ route('faculty.issues.create') }}" class="btn btn-primary">+ Report an Issue</a>
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif


<div class="content-card">

    @if($issues->isEmpty())

        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="wrench" /></div>
            <h3>No reports yet</h3>
            <p>Tell the administrator when something in a classroom needs fixing.</p>
        </div>

    @else

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Issue</th>
                        <th>Reported</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($issues as $issue)

                        @php
                            $latestNote = $issue->updates->first(fn ($update) => $update->note !== null)?->note;
                        @endphp

                        <tr>
                            <td>
                                <div class="table-primary">{{ $issue->room->room_name }}</div>
                                <div class="table-secondary">{{ $issue->room->floor?->building?->building_name }}</div>
                            </td>
                            <td>
                                <div class="table-primary">{{ $issue->category_label }}</div>
                                <div class="table-secondary">{{ $issue->description ?: ($issue->qr_replacement_requested ? 'Replacement QR code requested' : '') }}</div>
                            </td>
                            <td class="nowrap">{{ $issue->created_at->format('M j, Y g:i A') }}</td>
                            <td>
                                <span class="status-badge issue-{{ $issue->status }}">{{ $issue->status_label }}</span>
                                @if($latestNote)
                                    <div class="table-secondary">{{ $latestNote }}</div>
                                @endif
                                @if($issue->resolved_at)
                                    <div class="table-secondary">{{ $issue->resolved_at->format('M j, Y') }}</div>
                                @endif
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
