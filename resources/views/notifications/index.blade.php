@extends('layouts.app')

@section('title', 'Notifications')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">{{ auth()->user()->isAdmin() ? 'ROOMLINK ADMINISTRATION' : 'FACULTY PORTAL' }}</span>

        <h1>Notifications</h1>

        <p>
            {{ auth()->user()->isAdmin() ? 'Room issues reported by faculty.' : 'Class reminders and updates on your requests and reports.' }}
        </p>

    </div>

    <div class="page-header-actions">

        <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-secondary">
            ← Back to Dashboard
        </a>

    </div>

</div>


@if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif


<div class="content-card">

    <div class="table-toolbar">

        <div>
            <h2>All Notifications</h2>
            <span class="table-count">
                {{ $notifications->total() }} notification{{ $notifications->total() != 1 ? 's' : '' }}
            </span>
        </div>

        @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route(auth()->user()->isAdmin() ? 'admin.notifications.read' : 'faculty.notifications.read') }}">
                @csrf

                <button type="submit" class="btn btn-secondary">
                    Mark All as Read
                </button>
            </form>
        @endif

    </div>

    @if($notifications->isEmpty())

        <div class="empty-state">
            <div class="empty-state-icon"><x-icon name="bell" /></div>
            <h3>All caught up</h3>
            <p>You have no notifications.</p>
        </div>

    @else

        <div class="table-responsive">

            <table class="roomlink-table">

                <thead>
                    <tr>
                        <th>Notification</th>
                        <th>Received</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($notifications as $notification)

                        <tr>

                            <td>
                                @if(in_array($notification->id, $unreadIds, true))
                                    <span class="room-status status-occupied">New</span>
                                @endif

                                {{ $notification->data['message'] ?? 'Notification' }}

                                @isset($notification->data['room_issue_id'])
                                    <a href="{{ auth()->user()->isAdmin() ? route('room-issues.show', $notification->data['room_issue_id']) : route('faculty.issues.index') }}">View</a>
                                @endisset
                            </td>

                            <td class="nowrap">
                                <div class="table-primary">{{ $notification->created_at->diffForHumans() }}</div>
                                <div class="table-secondary">{{ $notification->created_at->format('M j, g:i A') }}</div>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if($notifications->hasPages())
            <div class="table-pagination">
                {{ $notifications->links() }}
            </div>
        @endif

    @endif

</div>

@endsection
