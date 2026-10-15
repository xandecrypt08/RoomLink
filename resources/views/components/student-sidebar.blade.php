<aside class="sidebar" id="sidebar">

    <div class="brand">
        <h2>RoomLink</h2>
        <span>Student Portal</span>
    </div>


    <nav class="sidebar-menu">

        <div class="sidebar-section-label">
            OVERVIEW
        </div>

        <a
            href="{{ route('student.dashboard') }}"
            class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
        >
            <span class="menu-icon">⌂</span>
            <span>Classrooms</span>
        </a>

        <a
            href="{{ route('faculty-locator.index') }}"
            class="sidebar-link {{ request()->routeIs('faculty-locator.*') ? 'active' : '' }}"
        >
            <span class="sidebar-icon">●</span>
            <span>Faculty Locator</span>
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
