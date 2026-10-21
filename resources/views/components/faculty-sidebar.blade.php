<aside class="sidebar" id="sidebar">

    <div class="brand">
        <h2>RoomLink</h2>
        <span>Smart Spaces. Seamless Classes.</span>
    </div>


    <nav class="sidebar-menu">

        <div class="sidebar-section-label">
            OVERVIEW
        </div>

        <a
            href="{{ route('faculty.dashboard') }}"
            class="{{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="home" /></span>
            <span>Dashboard</span>
        </a>

        <a
            href="{{ route('faculty.schedule.index') }}"
            class="{{ request()->routeIs('faculty.schedule.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="calendar" /></span>
            <span>My Schedule</span>
        </a>


        <div class="sidebar-section-label">
            CLASSROOM SESSIONS
        </div>

        <a
            href="{{ route('faculty.scanner') }}"
            class="{{ request()->routeIs('faculty.scanner') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="scan" /></span>
            <span>Scan Room QR</span>
        </a>

        <a
            href="{{ route('faculty.rooms.index') }}"
            class="{{ request()->routeIs('faculty.rooms.*', 'faculty.sessions.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="search" /></span>
            <span>Find a Room</span>
        </a>

        <a
            href="{{ route('faculty.transfer.create') }}"
            class="{{ request()->routeIs('faculty.transfer.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="transfer" /></span>
            <span>Transfer Class</span>
        </a>

        <a
            href="{{ route('faculty.temporary-requests.index') }}"
            class="{{ request()->routeIs('faculty.temporary-requests.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="inbox" /></span>
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
            <span class="menu-icon"><x-icon name="map-pin" /></span>
            <span>Faculty Locator</span>
        </a>

        <a
            href="{{ route('faculty.notifications.index') }}"
            class="{{ request()->routeIs('faculty.notifications.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="bell" /></span>
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

        <a
            href="{{ route('profile.edit') }}"
            class="sidebar-profile-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="user" /></span>
            <span>My Profile</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="sidebar-logout">
                <span class="menu-icon"><x-icon name="log-out" /></span>
                <span>Logout</span>
            </button>

        </form>

    </div>

</aside>
