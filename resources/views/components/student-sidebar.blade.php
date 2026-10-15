<aside class="sidebar">

    <div class="sidebar-logo">
        <strong>RoomLink</strong>
        <span>Student Portal</span>
    </div>

    <nav class="sidebar-nav">

        <a
            href="{{ route('student.dashboard') }}"
            class="sidebar-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
        >
            <span class="sidebar-icon">●</span>
            <span>Dashboard</span>
        </a>

        <a
            href="{{ route('faculty-locator.index') }}"
            class="sidebar-link {{ request()->routeIs('faculty-locator.*') ? 'active' : '' }}"
        >
            <span class="sidebar-icon">●</span>
            <span>Faculty Locator</span>
        </a>

    </nav>

    <div class="sidebar-footer">

        <form action="{{ route('logout') }}" method="POST">

            @csrf

            <button type="submit" class="sidebar-link logout-link">
                <span class="sidebar-icon">●</span>
                <span>Logout</span>
            </button>

        </form>

    </div>

</aside>