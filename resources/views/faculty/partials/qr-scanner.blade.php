{{--
    "Scan Room QR" button (public/js/qr-scanner.js). Opens the camera, reads the
    room code from the photo and opens the room on the address in use.
--}}
<div
    class="qr-scanner"
    data-qr-scanner
    data-scan-url="{{ route('faculty.sessions.scan', ['code' => '__CODE__'], false) }}"
>
    <label class="btn btn-primary qr-scanner-button">
        <span aria-hidden="true">▣</span>
        {{ $label ?? 'Scan Room QR' }}

        <input
            type="file"
            accept="image/*"
            capture="environment"
            class="qr-scanner-input"
            data-qr-input
        >
    </label>

    <p class="qr-scanner-status" data-qr-status role="status" aria-live="polite" hidden></p>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/vendor/jsQR-1.4.0.js') }}"></script>
        <script src="{{ asset('js/qr-scanner.js') }}"></script>
    @endpush
@endonce
