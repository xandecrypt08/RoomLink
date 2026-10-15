@extends('layouts.app')

@section('title', 'Notifications')

@section('content')

<div class="page-header">

    <div>

        <span class="page-eyebrow">NOTIFICATIONS</span>

        <h1>Notifications</h1>

        <p>
            Class reminders and updates on temporary classroom requests.
        </p>

    </div>

    <div class="page-header-actions">

        <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
            Back to Dashboard
        </a>

    </div>

</div>


@if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif


<div class="content-card">

    <div class="table-container">

        <table class="data-table">

            <thead>

                <tr>
                    <th>Notification</th>
                    <th>Received</th>
                </tr>

            </thead>

            <tbody>

                @forelse($notifications as $notification)

                    <tr>

                        <td>

                            @if(in_array($notification->id, $unreadIds, true))
                                <span class="status-badge status-active">New</span>
                            @endif

                            {{ $notification->data['message'] ?? 'Notification' }}

                        </td>

                        <td>
                            {{ $notification->created_at->diffForHumans() }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="2" class="empty-state">
                            You have no notifications.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    {{ $notifications->links() }}

</div>

@endsection
