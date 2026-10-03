<aside class="sidebar">

    <div class="brand">
        <h2>RoomLink</h2>
        <span>Smart Spaces. Seamless Classes.</span>
    </div>


    <nav class="sidebar-menu">

        <div class="sidebar-section-label">
            OVERVIEW
        </div>

        <a
            href="{{ route('admin.dashboard') }}"
            class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
        >
            <span class="menu-icon">⌂</span>
            <span>Dashboard</span>
        </a>


        <div class="sidebar-section-label">
            CLASSROOM MANAGEMENT
        </div>

        <a
            href="{{ route('campuses.index') }}"
            class="{{ request()->routeIs('campuses.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">▣</span>
            <span>Campus</span>
        </a>

        <a
            href="{{ route('buildings.index') }}"
            class="{{ request()->routeIs('buildings.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">▤</span>
            <span>Buildings</span>
        </a>

        <a
            href="{{ route('floors.index') }}"
            class="{{ request()->routeIs('floors.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">▥</span>
            <span>Floors</span>
        </a>

        <a
            href="{{ route('rooms.index') }}"
            class="{{ request()->routeIs('rooms.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">□</span>
            <span>Rooms</span>
        </a>

        <a
            href="{{ route('facilities.index') }}"
            class="{{ request()->routeIs('facilities.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">◇</span>
            <span>Facilities</span>
        </a>


        <div class="sidebar-section-label">
            ACADEMIC DATA
        </div>

        <a
            href="{{ route('faculties.index') }}"
            class="{{ request()->routeIs('faculties.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">♙</span>
            <span>Faculty</span>
        </a>

        <a
            href="{{ route('subjects.index') }}"
            class="{{ request()->routeIs('subjects.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">▤</span>
            <span>Subjects</span>
        </a>

        <a
            href="{{ route('sections.index') }}"
            class="{{ request()->routeIs('sections.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">☷</span>
            <span>Sections</span>
        </a>

        <a
            href="{{ route('schedules.index') }}"
            class="{{ request()->routeIs('schedules.*') ? 'active' : '' }}"
        >
            <span class="menu-icon">◷</span>
            <span>Class Sessions</span>
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