<aside class="sidebar" id="sidebar">

    <div class="brand">
        <h2>RoomLink</h2>
        <span>Faculty Portal</span>
    </div>


    <nav class="sidebar-menu">

        <div class="sidebar-section-label">
            OVERVIEW
        </div>

        <a
            href="{{ route('faculty.dashboard') }}"
            class="{{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}"
        >
            <span class="menu-icon">⌂</span>
            <span>Dashboard</span>
        </a>


        <div class="sidebar-section-label">
            CLASSROOM SESSIONS
        </div>

        <a
            href="{{ route('faculty.rooms.index') }}"
            class="{{ request()->routeIs('faculty.rooms.*', 'faculty.sessions.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">⌕</span>
            <span>Find a Room</span>
        </a>

        <a
            href="{{ route('faculty.transfer.create') }}"
            class="{{ request()->routeIs('faculty.transfer.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">⇄</span>
            <span>Transfer Class</span>
        </a>

        <a
            href="{{ route('faculty.temporary-requests.index') }}"
            class="{{ request()->routeIs('faculty.temporary-requests.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">✉</span>
            <span>Room Requests</span>

            @php
                $unreadRequests = auth()->user()
                    ->unreadNotifications()
                    ->where('type', \App\Notifications\TemporaryClassroomRequested::class)
                    ->count();
            @endphp

            @if($unreadRequests > 0)
                <span class="menu-badge">{{ $unreadRequests }}</span>
            @endif
        </a>

        <a
            href="{{ route('faculty-locator.index') }}"
            class="{{ request()->routeIs('faculty-locator.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">⌖</span>
            <span>Faculty Locator</span>
        </a>

        <a
            href="{{ route('faculty.notifications.index') }}"
            class="{{ request()->routeIs('faculty.notifications.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">🔔︎</span>
            <span>Notifications</span>

            @php
                $unreadNotifications = auth()->user()->unreadNotifications()->count();
            @endphp

            @if($unreadNotifications > 0)
                <span class="menu-badge">{{ $unreadNotifications }}</span>
            @endif
        </a>

    </nav>


    <div class="sidebar-bottom">

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="sidebar-logout">
                <span class="menu-icon">↪</span>
                <span>Logout</span>
            </button>

        </form>

    </div>

</aside>
