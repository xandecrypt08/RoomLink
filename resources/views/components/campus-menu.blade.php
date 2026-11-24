{{--
    Campuses in the admin sidebar. Hovering (or tapping) it shows the campuses;
    one leads to its buildings, floors and rooms. Behaviour is in
    public/js/sidebar-menu.js.
--}}
@php
    $menuCampuses = \App\Models\Campus::withCount('buildings')->orderBy('campus_name')->get();
@endphp

<div data-sidebar-menu>

    <a
        href="{{ route('classrooms.index') }}#all"
        class="{{ request()->routeIs('classrooms.*', 'rooms.*') ? 'active' : '' }}"
        data-menu-button
        @if($menuCampuses->isNotEmpty()) aria-haspopup="true" aria-expanded="false" aria-controls="campus-flyout" @endif
    >
        <span class="menu-icon"><x-icon name="landmark" /></span>
        <span>Campuses</span>
    </a>

    @if($menuCampuses->isNotEmpty())
        <div class="sidebar-flyout" id="campus-flyout" data-menu-flyout hidden>
            <ul>
                @foreach($menuCampuses as $campus)
                    <li>
                        <a href="{{ route('classrooms.index') }}#campus-{{ $campus->id }}">
                            <strong>{{ $campus->campus_name }}</strong>
                            {{ $campus->buildings_count }} {{ Str::plural('building', $campus->buildings_count) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
