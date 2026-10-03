<aside class="sidebar">

    <div class="sidebar-logo">
        <strong>RoomLink</strong>
        <span>Faculty Portal</span>
    </div>

    <nav class="sidebar-nav">

        <a
            href="{{ route('faculty.dashboard') }}"
            class="sidebar-link {{ request()->routeIs('faculty.dashboard') ? 'active' : '' }}"
        >
            <span class="sidebar-icon">●</span>
            <span>Dashboard</span>
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