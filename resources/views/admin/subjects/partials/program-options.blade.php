{{--
    Program checkboxes for a subject (or for subjects being imported),
    grouped by college. Expects $programs (with their college loaded) and
    $selected (list of program IDs).
--}}
<div class="form-group">

    <label>
        Programs
    </label>

    @if($programs->count())

        @foreach($programs->groupBy(fn ($program) => $program->college ? $program->college->college_name.' ('.$program->college->college_code.')' : 'Other programs') as $collegeLabel => $collegePrograms)

            <div class="program-options-college">

                <span class="program-options-heading">{{ $collegeLabel }}</span>

                <div class="facility-options">

                    @foreach($collegePrograms as $program)

                        <label class="facility-option">

                            <input
                                type="checkbox"
                                name="programs[]"
                                value="{{ $program->id }}"
                                {{ in_array($program->id, old('programs', $selected)) ? 'checked' : '' }}
                            >

                            <span>
                                <strong>{{ $program->program_code }}</strong>
                                · {{ $program->program_name }}
                            </span>

                        </label>

                    @endforeach

                </div>

            </div>

        @endforeach

        <span class="form-help">
            {{ $help ?? 'Select every program that takes this subject. General Education subjects usually belong to all of them.' }}
        </span>

    @else

        <div class="form-note">
            No programs have been added yet.
            <a href="{{ route('programs.create') }}">Add a program</a> first.
        </div>

    @endif

    @error('programs')
        <span class="form-error">{{ $message }}</span>
    @enderror

    @error('programs.*')
        <span class="form-error">{{ $message }}</span>
    @enderror

</div>
