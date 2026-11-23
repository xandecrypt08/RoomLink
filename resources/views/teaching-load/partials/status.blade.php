{{-- Status of one class on the review screen. Expects $row. Also sent while it is being edited. --}}
@if($row['duplicate'])
    <span class="status-badge status-neutral">Already in schedule</span>
    <div class="table-secondary">Skipped if ticked</div>
@elseif($row['problems'] !== [])
    <span class="status-badge status-maintenance">Needs attention</span>
    <ul class="load-problems">
        @foreach($row['problems'] as $problem)
            <li>{{ $problem }}</li>
        @endforeach
    </ul>
@else
    <span class="status-badge status-available">Ready</span>
@endif

@if(! $row['duplicate'] && ($row['subject'] === null || $row['new_section']))
    <div class="table-secondary">
        {{ collect([$row['subject'] === null ? 'New subject' : null, $row['new_section'] ? 'New section' : null])->filter()->implode(' · ') }}
    </div>
@endif
