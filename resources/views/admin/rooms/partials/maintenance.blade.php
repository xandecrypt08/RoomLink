{{-- Take a room out of use or restore it. Expects $room (with currentMaintenance) and $issue (or null). --}}
<div class="content-card room-maintenance {{ $room->isUnderMaintenance() ? 'is-closed' : '' }}">

    @if($room->isUnderMaintenance())

        <div>
            <h2>{{ $room->room_name }} is under maintenance</h2>
            <p>
                Sessions can't be started in it{{ $room->currentMaintenance ? ', since '.$room->currentMaintenance->started_at->format('M j, Y g:i A') : '' }}.
                {{ $room->currentMaintenance?->reason }}
            </p>
        </div>

        <form method="POST" action="{{ route('rooms.maintenance.destroy', $room) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-primary">Restore Room</button>
        </form>

    @else

        <div>
            <h2>Room availability</h2>
            <p>Sessions can be started in {{ $room->room_name }}. Put it under maintenance to prevent that while it is being repaired.</p>
        </div>

        <form method="POST" action="{{ route('rooms.maintenance.store', $room) }}">
            @csrf
            @if($issue)
                <input type="hidden" name="room_issue_id" value="{{ $issue->id }}">
            @endif
            <input type="text" name="reason" maxlength="500" placeholder="Reason" aria-label="Reason for maintenance" value="{{ $issue?->category_label }}">
            <button type="submit" class="btn btn-secondary">Mark Under Maintenance</button>
        </form>

    @endif

</div>
