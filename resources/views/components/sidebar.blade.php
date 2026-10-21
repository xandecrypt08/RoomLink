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
            CLASSROOM MANAGEMENT
        </div>

        <a
            href="{{ route('campuses.index') }}"
            class="{{ request()->routeIs('campuses.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="landmark" /></span>
            <span>Campus</span>
        </a>

        <a
            href="{{ route('buildings.index') }}"
            class="{{ request()->routeIs('buildings.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="building" /></span>
            <span>Buildings</span>
        </a>

        <a
            href="{{ route('floors.index') }}"
            class="{{ request()->routeIs('floors.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="layers" /></span>
            <span>Floors</span>
        </a>

        <a
            href="{{ route('rooms.index') }}"
            class="{{ request()->routeIs('rooms.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="door" /></span>
            <span>Rooms</span>
        </a>

        <a
            href="{{ route('facilities.index') }}"
            class="{{ request()->routeIs('facilities.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="plug" /></span>
            <span>Facilities</span>
        </a>


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

        <a
            href="{{ route('colleges.index') }}"
            class="{{ request()->routeIs('colleges.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="library" /></span>
            <span>Colleges</span>
        </a>

        <a
            href="{{ route('programs.index') }}"
            class="{{ request()->routeIs('programs.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="graduation-cap" /></span>
            <span>Programs</span>
        </a>

        <a
            href="{{ route('subjects.index') }}"
            class="{{ request()->routeIs('subjects.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="book" /></span>
            <span>Subjects</span>
        </a>

        <a
            href="{{ route('sections.index') }}"
            class="{{ request()->routeIs('sections.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="users" /></span>
            <span>Sections</span>
        </a>

        <a
            href="{{ route('schedules.index') }}"
            class="{{ request()->routeIs('schedules.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="clock" /></span>
            <span>Class Sessions</span>
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

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="sidebar-logout">
                <span class="menu-icon"><x-icon name="log-out" /></span>
                <span>Logout</span>
            </button>

        </form>

    </div>

</aside>