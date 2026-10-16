{{--
    "Scan Room QR" (public/js/qr-scanner.js). Reads the room code from the QR
    and opens the room on the address in use.

    On HTTPS (or localhost) the button opens a live camera view that scans
    continuously. Browsers only allow live camera access there, so on plain
    HTTP it falls back to taking a photo of the QR code.
--}}
<div
    class="qr-scanner"
    data-qr-scanner
    data-scan-url="{{ route('faculty.sessions.scan', ['code' => '__CODE__'], false) }}"
>
    <button type="button" class="btn btn-primary qr-scanner-button" data-qr-live-button hidden>
        <span aria-hidden="true">▣</span>
        {{ $label ?? 'Scan Room QR' }}
    </button>

    <label class="btn btn-primary qr-scanner-button" data-qr-photo-button>
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

    <div class="qr-live" data-qr-live-view role="dialog" aria-modal="true" aria-label="Scan a room QR code" hidden>

        <video class="qr-live-video" data-qr-video playsinline muted></video>

        <div class="qr-live-frame" aria-hidden="true"></div>

        <div class="qr-live-panel">
            <p class="qr-live-status" data-qr-live-status role="status" aria-live="polite">
                Point the camera at the room's QR code.
            </p>

            <div class="qr-live-actions">
                <label class="btn btn-secondary">
                    Take a photo instead

                    <input
                        type="file"
                        accept="image/*"
                        capture="environment"
                        class="qr-scanner-input"
                        data-qr-input
                    >
                </label>

                <button type="button" class="btn btn-secondary" data-qr-close>
                    Cancel
                </button>
            </div>
        </div>

    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/vendor/jsQR-1.4.0.js') }}"></script>
        <script src="{{ asset('js/qr-scanner.js') }}"></script>
    @endpush
@endonce
