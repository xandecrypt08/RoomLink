{{--
    The semester and school year being viewed, as a button. Clicking it opens
    the list of semesters to switch to.
--}}
@php
    $selected = \App\Support\SelectedTerm::get();
    $current = \App\Models\AcademicTerm::current();
    $known = \App\Models\AcademicTerm::has('classSessions')->pluck('school_year');
    $years = range(
        min($current->school_year, $selected->school_year, $known->min() ?? $current->school_year),
        max($current->school_year + 3, $selected->school_year, $known->max() ?? 0),
    );
@endphp

<details class="term-picker" data-term-picker>

    <summary class="btn btn-secondary term-picker-button" aria-label="Semester: {{ $selected->label }}. Change semester">
        <x-icon name="calendar" />
        <span>{{ $selected->label }}</span>
    </summary>

    <div class="term-picker-panel">

        <form method="POST" action="{{ route('academic-term.select') }}">

            @csrf

            @foreach($years as $year)

                <div class="term-picker-year">

                    <strong>S.Y. {{ \App\Models\AcademicTerm::schoolYearLabel($year) }}</strong>

                    <div class="term-picker-semesters">
                        @foreach(\App\Models\AcademicTerm::SEMESTERS as $semester => $name)

                            @php
                                $isSelected = $selected->school_year === $year && $selected->semester === $semester;
                                $isCurrent = $current->school_year === $year && $current->semester === $semester;
                            @endphp

                            <button
                                type="submit"
                                name="term"
                                value="{{ $year }}-{{ $semester }}"
                                class="{{ $isSelected ? 'active' : '' }}"
                                @if($isSelected) aria-current="true" @endif
                            >{{ $name }}@if($isCurrent)<small>Current</small>@endif</button>

                        @endforeach
                    </div>

                </div>

            @endforeach

        </form>

        @if(! $selected->is_current && auth()->user()->isAdmin())

            <form
                method="POST"
                action="{{ route('academic-terms.current', $selected) }}"
                class="term-picker-current"
                onsubmit="return confirm('Make {{ $selected->label }} the current semester? QR scanning, reminders and faculty dashboards will follow its schedule.');"
            >
                @csrf
                <button type="submit" class="btn btn-primary">Make {{ $selected->label }} current</button>
            </form>

        @endif

    </div>

</details>

@once
    @push('scripts')
    <script>
        (function () {
            var pickers = document.querySelectorAll('[data-term-picker]');

            document.addEventListener('click', function (event) {
                pickers.forEach(function (picker) {
                    if (!picker.contains(event.target)) {
                        picker.open = false;
                    }
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    pickers.forEach(function (picker) { picker.open = false; });
                }
            });
        })();
    </script>
    @endpush
@endonce
