<header class="topbar">

    <div class="topbar-left">

        <div class="search-box">
            <span class="search-icon">⌕</span>

            <input
                type="text"
                placeholder="Search rooms, faculty, schedules..."
            >
        </div>

    </div>


    <div class="topbar-right">

        @if(auth()->user()?->isFaculty())

            @php($unreadNotificationCount = auth()->user()->unreadNotifications()->count())

            <a
                href="{{ route('faculty.notifications.index') }}"
                class="notification-btn"
                title="Notifications"
            >
                <span>♢</span>
                @if($unreadNotificationCount > 0)
                    <span class="status-badge status-active">{{ $unreadNotificationCount }}</span>
                @endif
            </a>

        @else

            <button
                type="button"
                class="notification-btn"
                title="Notifications"
            >
                <span>♢</span>
            </button>

        @endif


        <div class="profile-box">

            <img
                src="{{ asset('images/avatar.png') }}"
                alt="Administrator"
                class="profile-avatar"
            >

            <div class="profile-info">

                <strong>
                    {{ auth()->user()->first_name ?? 'Administrator' }}
                </strong>

                <span>
                    System Administrator
                </span>

            </div>

        </div>

    </div>

</header>