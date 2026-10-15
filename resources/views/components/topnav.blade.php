@php
    $user = auth()->user();

    $roleLabel = match ($user?->role) {
        'admin' => 'System Administrator',
        'faculty' => 'Faculty',
        'student' => 'Student',
        default => '',
    };

    $initials = strtoupper(
        mb_substr($user?->first_name ?? '', 0, 1).mb_substr($user?->last_name ?? '', 0, 1)
    );

    $unreadCount = $user?->role === 'faculty' ? $user->unreadNotifications()->count() : 0;
@endphp

<header class="topbar">

    <div class="topbar-left">

        <button
            type="button"
            class="menu-toggle"
            data-sidebar-toggle
            aria-controls="sidebar"
            aria-expanded="false"
            aria-label="Open menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="topbar-date">
            <strong>{{ now()->format('l') }}</strong>
            <span>{{ now()->format('F j, Y') }}</span>
        </div>

    </div>


    <div class="topbar-right">

        @if($user?->role === 'faculty')
            <a
                href="{{ route('faculty.temporary-requests.index') }}"
                class="notification-btn"
                title="Room requests"
                aria-label="Room requests{{ $unreadCount ? " ({$unreadCount} new)" : '' }}"
            >
                <span aria-hidden="true">✉</span>

                @if($unreadCount > 0)
                    <span class="notification-count">{{ $unreadCount }}</span>
                @endif
            </a>
        @endif


        <div class="profile-box">

            @if($user?->profile_photo)
                <img
                    src="{{ asset('storage/'.$user->profile_photo) }}"
                    alt=""
                    class="profile-avatar"
                >
            @else
                <span class="profile-avatar profile-initials" aria-hidden="true">
                    {{ $initials ?: '?' }}
                </span>
            @endif

            <div class="profile-info">
                <strong>{{ $user?->full_name }}</strong>
                <span>{{ $roleLabel }}</span>
            </div>

        </div>

    </div>

</header>
