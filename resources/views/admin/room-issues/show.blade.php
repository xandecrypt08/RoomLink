@extends('layouts.admin')

@section('title', 'Room Issue')

@section('content')

@php
    $next = $issue->nextStatus();
    $order = array_keys(\App\Models\RoomIssue::STATUSES);
    $step = array_search($issue->status, $order, true);
@endphp

<div class="page-header">

    <div>
        <span class="page-eyebrow">ROOM ISSUE #{{ $issue->id }}</span>
        <h1>{{ $issue->category_label }}</h1>
        <p>{{ $issue->room->room_name }} · {{ $issue->room->floor?->building?->building_name }} · {{ $issue->room->floor?->building?->campus?->campus_name }}</p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('rooms.show', $issue->room) }}" class="btn btn-secondary">View Room</a>
        <a href="{{ route('room-issues.index', ['status' => $issue->status]) }}" class="btn btn-secondary">← Room Issues</a>
    </div>

</div>


@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif


<ol class="issue-steps">
    @foreach(\App\Models\RoomIssue::STATUSES as $value => $label)
        <li class="{{ $loop->index < $step ? 'done' : ($loop->index === $step ? 'current' : '') }}" @if($loop->index === $step) aria-current="step" @endif>{{ $label }}</li>
    @endforeach
</ol>


<div class="details-grid">

    <div class="content-card detail-card">

        <div class="detail-card-header">
            <div>
                <span class="panel-eyebrow">REPORT</span>
                <h2>Details</h2>
            </div>
            <span class="status-badge issue-{{ $issue->status }}">{{ $issue->status_label }}</span>
        </div>

        <div class="detail-list">
            <div class="detail-item"><span>Reported by</span><strong>{{ $issue->reporter->full_name }}</strong></div>
            <div class="detail-item"><span>Date reported</span><strong>{{ $issue->created_at->format('M j, Y g:i A') }}</strong></div>
            <div class="detail-item"><span>Description</span><strong>{{ $issue->description ?: '—' }}</strong></div>
            <div class="detail-item"><span>Assigned to</span><strong>{{ $issue->assigned_to ?? '—' }}</strong></div>
            @if($issue->resolved_at)
                <div class="detail-item"><span>Resolved</span><strong>{{ $issue->resolved_at->format('M j, Y g:i A') }}</strong></div>
            @endif
        </div>

        @if($issue->photo)
            <a href="{{ route('room-issues.photo', $issue) }}" target="_blank" class="issue-photo">
                <img src="{{ route('room-issues.photo', $issue) }}" alt="Photo of the issue in {{ $issue->room->room_name }}, sent by {{ $issue->reporter->full_name }}">
            </a>
        @endif

        @if($issue->isQrIssue() && ! $issue->isResolved())
            <div class="issue-qr">
                <p>{{ $issue->qr_replacement_requested ? 'A replacement QR code was requested.' : 'The QR code was reported as damaged.' }}</p>

                <form method="POST" action="{{ route('room-issues.replace-qr', $issue) }}" onsubmit="return confirm('Generate a new QR code? The current QR code will stop working right away.');">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Generate New QR Code</button>
                </form>

                <a href="{{ route('rooms.qr.print', $issue->room) }}" target="_blank" class="btn btn-secondary">Print QR Code</a>
            </div>
        @endif

    </div>


    <div class="content-card detail-card">

        <div class="detail-card-header">
            <div>
                <span class="panel-eyebrow">PROGRESS</span>
                <h2>{{ $next ? 'Update Status' : 'Resolved' }}</h2>
            </div>
        </div>

        @if($next)

            <form method="POST" action="{{ route('room-issues.update', $issue) }}">

                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $next }}">

                <div class="form-group">
                    <label for="assigned_to">Assigned to</label>
                    <input type="text" id="assigned_to" name="assigned_to" value="{{ old('assigned_to', $issue->assigned_to) }}" maxlength="150" placeholder="Who is repairing it">
                </div>

                <div class="form-group">
                    <label for="note">Note for the reporter</label>
                    <textarea id="note" name="note" rows="3" maxlength="500">{{ old('note') }}</textarea>
                </div>

                @if($next === \App\Models\RoomIssue::STATUS_RESOLVED && $issue->room->isUnderMaintenance())
                    <div class="form-group">
                        <label class="facility-option">
                            <input type="checkbox" name="restore_room" value="1" {{ $issue->room->currentMaintenance?->room_issue_id === $issue->id ? 'checked' : '' }}>
                            <span>Restore {{ $issue->room->room_name }} so it can be used again</span>
                        </label>
                    </div>
                @endif

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        {{ ['acknowledged' => 'Acknowledge', 'in_progress' => 'Start Repair (In Progress)', 'resolved' => 'Mark Resolved'][$next] }}
                    </button>
                </div>

            </form>

        @else
            <p class="table-secondary">This issue was resolved on {{ $issue->resolved_at?->format('M j, Y') }}.</p>
        @endif

    </div>

</div>


@include('admin.rooms.partials.maintenance', ['room' => $issue->room])


<div class="content-card detail-card">

    <div class="detail-card-header">
        <div>
            <span class="panel-eyebrow">HISTORY</span>
            <h2>What happened</h2>
        </div>
    </div>

    <div class="detail-list">
        @foreach($issue->updates as $update)
            <div class="detail-item">
                <span>
                    <strong>{{ $loop->first ? 'Reported' : (\App\Models\RoomIssue::STATUSES[$update->status] ?? $update->status) }}</strong>
                    by {{ $update->user?->full_name ?? 'a removed account' }}
                    @if($update->note)
                        <br>{{ $update->note }}
                    @endif
                </span>
                <span class="nowrap">{{ $update->created_at->format('M j, Y g:i A') }}</span>
            </div>
        @endforeach
    </div>

</div>

@endsection
