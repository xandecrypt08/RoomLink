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
            href="{{ route('student.dashboard') }}"
            class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="door" /></span>
            <span>Classrooms</span>
        </a>

        <a
            href="{{ route('faculty-locator.index') }}"
            class="{{ request()->routeIs('faculty-locator.*') ? 'active' : '' }}"
        >
            <span class="menu-icon"><x-icon name="map-pin" /></span>
            <span>Faculty Locator</span>
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
