@extends('layouts.app')

@section('title', 'Scan Room QR')

@section('content')

<div class="page-header">

    <div>
        <span class="page-eyebrow">CLASSROOM SESSIONS</span>

        <h1>Scan Room QR</h1>

        <p>
            Take a photo of the QR code posted in the room to start or request it.
        </p>
    </div>

    <div class="page-header-actions">
        <a href="{{ route('faculty.dashboard') }}" class="btn btn-secondary">
            ← Back to Dashboard
        </a>
    </div>

</div>

<div class="content-card scanner-card">

    <div class="scanner-card-icon" aria-hidden="true"><x-icon name="scan" /></div>

    <h2>Point your camera at the room's QR code</h2>

    <p>
        Tap the button, take a clear photo of the QR code, then confirm the photo.
        RoomLink opens the room so you can start your class.
    </p>

    @include('faculty.partials.qr-scanner')

    <p class="scanner-card-help">
        QR code missing or damaged? <a href="{{ route('faculty.rooms.index') }}">Find the room manually</a>.
    </p>

</div>

@endsection
