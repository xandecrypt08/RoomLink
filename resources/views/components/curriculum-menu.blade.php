{{--
    Curriculum in the admin sidebar. Hovering (or tapping) it shows the
    colleges, and a college shows its programs. A program leads to its place in
    the full subject list. Behaviour is in public/js/sidebar-menu.js.
--}}
@php
    $menuColleges = \App\Models\Program::with('college')->inListOrder()->get()
        ->groupBy(fn ($program) => $program->college?->college_name ?? 'Other programs');
@endphp

<div class="curriculum-menu" data-sidebar-menu>

    <a
        href="{{ route('curriculum.index') }}"
        class="{{ request()->routeIs('curriculum.*', 'subjects.*') ? 'active' : '' }}"
        data-menu-button
        @if($menuColleges->isNotEmpty()) aria-haspopup="true" aria-expanded="false" aria-controls="curriculum-flyout" @endif
    >
        <span class="menu-icon"><x-icon name="graduation-cap" /></span>
        <span>Curriculum</span>
    </a>

    @if($menuColleges->isNotEmpty())

        <div class="sidebar-flyout" id="curriculum-flyout" data-menu-flyout hidden>

            <ul class="sidebar-flyout-colleges">

                @foreach($menuColleges as $collegeName => $collegePrograms)

                    <li data-menu-college>

                        <button type="button" data-menu-college-button aria-expanded="false" aria-controls="sidebar-flyout-{{ $loop->index }}">
                            <span>
                                <strong>{{ $collegePrograms->first()->college?->college_code ?? 'Other' }}</strong>
                                {{ $collegeName }}
                            </span>
                        </button>

                        <ul class="sidebar-flyout-programs" id="sidebar-flyout-{{ $loop->index }}" hidden>
                            @foreach($collegePrograms->groupBy(fn ($program) => $program->program_group ?? '') as $group => $groupPrograms)
                                @if($group !== '')
                                    <li class="sidebar-flyout-group">{{ $group }}</li>
                                @endif

                                @foreach($groupPrograms as $program)
                                    <li>
                                        <a href="{{ route('curriculum.index') }}#program-{{ $program->id }}">
                                            <strong>{{ $program->program_code }}</strong>
                                            {{ $program->program_name }}
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>

                    </li>

                @endforeach

            </ul>

        </div>

    @endif

</div>
