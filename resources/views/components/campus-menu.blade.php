{{--
    Campuses in the admin sidebar. Hovering (or tapping) it unfolds the
    campuses; one leads to its buildings, floors and rooms. Behaviour is in
    public/js/sidebar-menu.js.
--}}
@php
    $menuCampuses = \App\Models\Campus::orderBy('campus_name')->get();
@endphp

<div class="nav-group" @if($menuCampuses->isNotEmpty()) data-menu-item @endif>

    <a
        href="{{ route('classrooms.index') }}#all"
        class="{{ request()->routeIs('classrooms.*', 'rooms.*') ? 'active' : '' }}"
        @if($menuCampuses->isNotEmpty()) data-menu-trigger aria-expanded="false" aria-controls="campus-menu" @endif
    >
        <span class="menu-icon"><x-icon name="landmark" /></span>
        <span>Campuses</span>
    </a>

    @if($menuCampuses->isNotEmpty())
        <div class="nav-fold" id="campus-menu">
            <ul class="nav-sub">
                @foreach($menuCampuses as $campus)
                    <li>
                        <a href="{{ route('classrooms.index') }}#campus-{{ $campus->id }}" title="{{ $campus->campus_name }}">{{ $campus->campus_name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
