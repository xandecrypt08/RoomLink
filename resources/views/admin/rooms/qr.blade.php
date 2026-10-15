@extends('layouts.admin')

@section('title', 'Room QR Code')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM MANAGEMENT</span>
        <h1>Room QR Code</h1>
        <p>QR identification code for this classroom.</p>
    </div>

    <div class="page-header-actions">
        <a
            href="{{ route('rooms.show', $room) }}"
            class="btn btn-secondary"
        >
            ← Back to Room
        </a>
    </div>

</div>

@if(session('success'))

    <div class="alert alert-success">
        {{ session('success') }}
    </div>

@endif

<div class="content-card qr-page">

    <div class="qr-room-info">

        <h2>{{ $room->room_name }}</h2>

        <p>
            {{ $room->floor->building->building_name }}
            —
            Floor {{ $room->floor->floor_number }}
        </p>

        <span class="qr-identifier">
            {{ $room->qr_code }}
        </span>

    </div>

    <div class="qr-code-container">

        {!! QrCode::size(300)->generate($qrData) !!}

    </div>

    <div class="qr-instructions">

        <p>
            Scan this QR code to identify the classroom.
        </p>

        <a
            href="{{ route('rooms.qr.print', $room) }}"
            target="_blank"
            class="btn btn-primary"
        >
            Print QR Code
        </a>

    </div>

</div>

<div class="content-card detail-card qr-replace-card">

    <div class="detail-card-header">

        <div>
            <span class="panel-eyebrow">DAMAGED OR COPIED CODE</span>
            <h2>Replace QR Code</h2>
        </div>

    </div>

    <p class="qr-replace-text">
        If the posted QR code is damaged, unreadable, or was copied, generate a new one.
        The current code stops working immediately, so print the new code and post it in the room.
    </p>

    <form
        method="POST"
        action="{{ route('rooms.qr.regenerate', $room) }}"
        onsubmit="return confirm('Generate a new QR code? The current QR code will stop working right away.');"
    >

        @csrf

        <button
            type="submit"
            class="btn btn-delete"
        >
            Regenerate QR Code
        </button>

    </form>

    @if($room->retiredQrCodes->isNotEmpty())

        <div class="detail-list qr-retired-list">

            @foreach($room->retiredQrCodes as $retiredQrCode)
                <div class="detail-item">
                    <span class="code-badge">{{ $retiredQrCode->qr_code }}</span>
                    <strong>Replaced {{ $retiredQrCode->retired_at->format('M j, Y g:i A') }}</strong>
                </div>
            @endforeach

        </div>

    @endif

</div>

@endsection