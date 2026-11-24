{{--
    Curriculum in the admin sidebar. Hovering (or tapping) it unfolds the
    colleges, and a college unfolds its programs. A program leads to its place
    in the full subject list. Behaviour is in public/js/sidebar-menu.js.
--}}
@php
    $menuColleges = \App\Models\Program::with('college')->inListOrder()->get()
        ->groupBy(fn ($program) => $program->college?->college_name ?? 'Other programs');
@endphp

<div class="nav-group" @if($menuColleges->isNotEmpty()) data-menu-item @endif>

    <a
        href="{{ route('curriculum.index') }}"
        class="{{ request()->routeIs('curriculum.*', 'subjects.*') ? 'active' : '' }}"
        @if($menuColleges->isNotEmpty()) data-menu-trigger aria-expanded="false" aria-controls="curriculum-menu" @endif
    >
        <span class="menu-icon"><x-icon name="graduation-cap" /></span>
        <span>Curriculum</span>
    </a>

    @if($menuColleges->isNotEmpty())

        <div class="nav-fold" id="curriculum-menu">

            <ul class="nav-sub">

                @foreach($menuColleges as $collegeName => $collegePrograms)

                    <li data-menu-item>

                        <button type="button" data-menu-trigger aria-expanded="false" aria-controls="curriculum-menu-{{ $loop->index }}" title="{{ $collegeName }}">
                            <span>{{ $collegeName }}</span>
                        </button>

                        <div class="nav-fold" id="curriculum-menu-{{ $loop->index }}">
                            <ul class="nav-sub nav-programs">
                                @foreach($collegePrograms->groupBy(fn ($program) => $program->program_group ?? '') as $group => $groupPrograms)
                                    @if($group !== '')
                                        <li class="nav-sub-label">{{ $group }}</li>
                                    @endif

                                    @foreach($groupPrograms as $program)
                                        <li>
                                            <a href="{{ route('curriculum.index') }}#program-{{ $program->id }}" title="{{ $program->program_name }}">{{ $program->program_code }}</a>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>

                    </li>

                @endforeach

            </ul>

        </div>

    @endif

</div>
