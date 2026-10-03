@extends('layouts.admin')

@section('title', 'Room QR Code')

@section('content')

<div class="page-header">

    <div>
        <h1>Room QR Code</h1>
        <p>QR identification code for this classroom.</p>
    </div>

    <a
        href="{{ route('rooms.show', $room) }}"
        class="btn btn-light"
    >
        Back to Room
    </a>

</div>

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

@endsection