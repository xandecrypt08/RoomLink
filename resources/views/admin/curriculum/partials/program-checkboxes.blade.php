{{-- Programs a subject belongs to, grouped by college. Expects $programs (with their college). --}}
<div class="form-group">
    <label>Programs</label>

    @if($programs->isEmpty())
        <span class="form-help">No programs have been added yet.</span>
    @else
        <div class="facility-options">
            @foreach($programs->groupBy(fn ($program) => $program->college ? $program->college->college_name.' ('.$program->college->college_code.')' : 'Other programs') as $collegeLabel => $collegePrograms)
                <span class="program-options-heading">{{ $collegeLabel }}</span>

                @foreach($collegePrograms as $program)
                    <label class="facility-option" title="{{ $program->program_name }}">
                        <input type="checkbox" name="programs[]" value="{{ $program->id }}">
                        <span>{{ $program->program_code }}</span>
                    </label>
                @endforeach
            @endforeach
        </div>
    @endif
</div>
