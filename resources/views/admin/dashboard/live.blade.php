{{-- The live part of the dashboard, replaced every few seconds by public/js/dashboard.js. --}}
@php
    $byStatus = $rooms->groupBy('status');
    $available = $byStatus->get('available', collect());
    $occupied = $byStatus->get('occupied', collect());
    $maintenance = $byStatus->get('maintenance', collect());
    $roomTotal = max($rooms->count(), 1);
@endphp

<p class="live-updated"><span class="status-dot available"></span> Live · updated {{ $updatedAt->format('g:i:s A') }}</p>

<div class="dashboard-grid">

    {{-- CLASSROOM STATISTICS --}}
    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="panel-eyebrow">CLASSROOMS</span>
                <h2>Room Status</h2>
            </div>
            <a href="{{ route('classrooms.index') }}" class="panel-link">View Rooms →</a>
        </div>

        <div class="room-status-list">
            @foreach([['available', 'Available', 'Ready for use', $available], ['occupied', 'Occupied', 'Currently in use', $occupied], ['maintenance', 'Maintenance', 'Unavailable', $maintenance]] as [$key, $label, $help, $group])
                <div class="room-status-row">
                    <div class="room-status-label">
                        <span class="status-dot {{ $key }}"></span>
                        <div>
                            <strong>{{ $label }}</strong>
                            <small>{{ $help }}</small>
                        </div>
                    </div>
                    <strong class="room-status-number">{{ $group->count() }}</strong>
                </div>
            @endforeach
        </div>

        <div class="room-status-bar">
            @foreach(['available' => $available, 'occupied' => $occupied, 'maintenance' => $maintenance] as $key => $group)
                @if($group->isNotEmpty())
                    <div class="status-bar-{{ $key }}" style="width: {{ $group->count() / $roomTotal * 100 }}%"></div>
                @endif
            @endforeach
        </div>

        <div class="live-figures">
            <div><strong>{{ $sessionsToday }}</strong><span>{{ Str::plural('session', $sessionsToday) }} today</span></div>
            <div><strong>{{ intdiv($minutesToday, 60) }}h {{ $minutesToday % 60 }}m</strong><span>used today</span></div>
            <div><strong>{{ $roomsUsedToday }}</strong><span>{{ Str::plural('room', $roomsUsedToday) }} used today</span></div>
        </div>

    </div>


    {{-- LIVE OCCUPIED CLASSROOMS --}}
    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="panel-eyebrow">LIVE</span>
                <h2>Occupied Classrooms</h2>
            </div>
            <span class="count-badge">{{ $activeSessions->count() }}</span>
        </div>

        @forelse($activeSessions as $activeSession)
            <div class="live-session-row">
                <span class="status-dot occupied"></span>
                <div>
                    <strong>
                        {{ $activeSession->room->room_name }}
                        @if($activeSession->wasStartedManually())
                            <span class="manual-start-tag" title="Started without scanning: {{ $activeSession->manual_start_reason }}">Manual</span>
                        @endif
                    </strong>
                    <span>
                        {{ $activeSession->faculty->full_name }}
                        @if($activeSession->classSession)
                            · {{ $activeSession->classSession->subject->subject_code }}
                            · {{ $activeSession->classSession->section->section_name }}
                        @else
                            · Temporary session
                        @endif
                    </span>
                    @if($activeSession->wasStartedManually())
                        <span class="manual-start-reason">Not scanned: {{ $activeSession->manual_start_reason }}</span>
                    @endif
                </div>
                <small>since {{ $activeSession->started_at->format('g:i A') }}</small>
            </div>
        @empty
            <div class="small-empty-state">
                <strong>No classes in session</strong>
                <span>Rooms appear here when a faculty member starts a class.</span>
            </div>
        @endforelse

    </div>


    {{-- AVAILABLE CLASSROOMS --}}
    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="panel-eyebrow">READY</span>
                <h2>Available Classrooms</h2>
            </div>
            <span class="count-badge">{{ $available->count() }}</span>
        </div>

        @if($available->isEmpty())
            <div class="small-empty-state"><strong>No available rooms</strong></div>
        @else
            <div class="program-tags">
                @foreach($available->take(30) as $room)
                    <a href="{{ route('rooms.show', $room) }}" class="program-tag" title="{{ $room->floor?->building?->building_name }}">{{ $room->room_name }}</a>
                @endforeach
                @if($available->count() > 30)
                    <a href="{{ route('classrooms.index') }}" class="program-tag">+{{ $available->count() - 30 }} more</a>
                @endif
            </div>
        @endif

    </div>


    {{-- FACULTY LOCATOR --}}
    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="panel-eyebrow">FACULTY LOCATOR</span>
                <h2>Teaching Now</h2>
            </div>
            <a href="{{ route('faculty-locator.index') }}" class="panel-link">{{ $locatedSessions->count() }} located →</a>
        </div>

        @forelse($locatedSessions->take(6) as $located)
            <div class="live-session-row">
                <span class="status-dot available"></span>
                <div>
                    <strong>{{ $located->faculty->full_name }}</strong>
                    <span>{{ $located->room->room_name }} · {{ $located->room->floor?->building?->building_name }}</span>
                </div>
            </div>
        @empty
            <div class="small-empty-state"><strong>No one is teaching right now</strong></div>
        @endforelse

    </div>


    {{-- MAINTENANCE --}}
    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="panel-eyebrow">MAINTENANCE</span>
                <h2>Issues and Maintenance</h2>
            </div>
            <a href="{{ route('room-issues.index') }}" class="panel-link">View Issues →</a>
        </div>

        <div class="live-figures">
            @foreach(['pending', 'acknowledged', 'in_progress'] as $issueStatus)
                <a href="{{ route('room-issues.index', ['status' => $issueStatus]) }}">
                    <strong>{{ $issueCounts[$issueStatus] ?? 0 }}</strong>
                    <span>{{ \App\Models\RoomIssue::STATUSES[$issueStatus] }}</span>
                </a>
            @endforeach
        </div>

        @forelse($openMaintenances->take(5) as $open)
            <div class="live-session-row">
                <span class="status-dot maintenance"></span>
                <div>
                    <strong>{{ $open->room->room_name }}</strong>
                    <span>{{ $open->reason ?? $open->roomIssue?->category_label ?? 'Under maintenance' }}</span>
                </div>
                <small>since {{ $open->started_at->format('M j') }}</small>
            </div>
        @empty
            <div class="small-empty-state"><strong>No rooms under maintenance</strong></div>
        @endforelse

    </div>


    {{-- CAMPUSES --}}
    <div class="dashboard-panel">

        <div class="panel-header">
            <div>
                <span class="panel-eyebrow">STATISTICS</span>
                <h2>By Campus</h2>
            </div>
            <a href="{{ route('reports.utilization') }}" class="panel-link">Reports →</a>
        </div>

        @if($roomsByCampus->isEmpty())
            <div class="small-empty-state"><strong>No rooms yet</strong></div>
        @else
            <div class="table-responsive">
                <table class="roomlink-table campus-table">
                    <thead><tr><th>Campus</th><th>Rooms</th><th>Available</th><th>Occupied</th><th>Maint.</th></tr></thead>
                    <tbody>
                        @foreach($roomsByCampus as $campusName => $campusRooms)
                            <tr>
                                <td class="table-primary">{{ $campusName }}</td>
                                <td>{{ $campusRooms->count() }}</td>
                                <td>{{ $campusRooms->where('status', 'available')->count() }}</td>
                                <td>{{ $campusRooms->where('status', 'occupied')->count() }}</td>
                                <td>{{ $campusRooms->where('status', 'maintenance')->count() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

</div>
