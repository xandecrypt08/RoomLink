<aside class="sidebar" id="sidebar">

    <div class="brand">
        <img src="{{ \App\Support\Asset::url('images/logo.svg') }}" alt="" class="brand-logo">

        <div>
            <h2>RoomLink</h2>
            <span>Smart Spaces. Seamless Classes.</span>
        </div>
    </div>


    <nav class="sidebar-menu" aria-label="Main menu">

        <div class="sidebar-section-label">
            OVERVIEW
        </div>

        <a
            href="{{ route('admin.dashboard') }}"
            class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="home" /></span>
            <span>Dashboard</span>
        </a>

        <a
            href="{{ route('faculty-locator.index') }}"
            class="{{ request()->routeIs('faculty-locator.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="map-pin" /></span>
            <span>Faculty Locator</span>
        </a>


        <div class="sidebar-section-label">
            CAMPUS MANAGEMENT
        </div>

        <x-campus-menu />


        <div class="sidebar-section-label">
            ACADEMIC DATA
        </div>

        <a
            href="{{ route('faculties.index') }}"
            class="{{ request()->routeIs('faculties.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="user" /></span>
            <span>Faculty</span>
        </a>

        <x-curriculum-menu />

        <a
            href="{{ route('schedules.index') }}"
            class="{{ request()->routeIs('schedules.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="clock" /></span>
            <span>Class Sessions</span>
        </a>

        <a
            href="{{ route('timetable') }}"
            class="{{ request()->routeIs('timetable') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="calendar" /></span>
            <span>Timetables</span>
        </a>


        <div class="sidebar-section-label">
            MAINTENANCE AND REPORTS
        </div>

        <a
            href="{{ route('room-issues.index') }}"
            class="{{ request()->routeIs('room-issues.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="wrench" /></span>
            <span>Room Issues</span>

            @php
                $pendingIssues = \App\Models\RoomIssue::where('status', \App\Models\RoomIssue::STATUS_PENDING)->count();
            @endphp

            @if($pendingIssues > 0)
                <span class="menu-badge">{{ $pendingIssues }}</span>
            @endif
        </a>

        <a
            href="{{ route('reports.utilization') }}"
            class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="chart" /></span>
            <span>Reports</span>
        </a>


        <div class="sidebar-section-label">
            SYSTEM
        </div>

        <a
            href="{{ route('users.index') }}"
            class="{{ request()->routeIs('users.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="shield" /></span>
            <span>User Accounts</span>
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

@push('scripts')
<script src="{{ \App\Support\Asset::url('js/sidebar-menu.js') }}"></script>
@endpush
