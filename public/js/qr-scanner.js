/*
 * RoomLink "Scan Room QR" button.
 *
 * Opens the phone camera through a file input, reads the QR code in the photo
 * with jsQR, and opens that room's scan page on the address the faculty member
 * is already using. Only the room code is taken from the QR; the address
 * printed in it is ignored, so QR codes keep working when the server's address
 * changes (for example on a different Wi-Fi network).
 *
 * Markup: an element with [data-qr-scanner] containing
 *   input[type=file][data-qr-input], [data-qr-status],
 *   and data-scan-url="/faculty/scan/__CODE__" on the container.
 */
(function () {
    'use strict';

    var ROOM_CODE = /^ROOM-[A-Z0-9]{4,40}$/;
    var SCAN_PATH = /\/faculty\/scan\/([A-Za-z0-9-]+)/;

    // Sizes (longest side, in pixels) to try decoding at, smallest first.
    // Small copies are fast and read most photos; a busy photo with no
    // readable QR fails within a few seconds instead of freezing the phone.
    var DECODE_SIDES = [1000, 1600, 2400];

    /**
     * The room code in a QR's text: either a RoomLink scan link from any
     * address, or a bare ROOM-XXXXXXXXXX code. Anything else is rejected.
     */
    function extractRoomCode(text) {
        var value = String(text || '').trim();
        var match = value.match(SCAN_PATH);
        var code = match ? match[1] : value;

        code = code.toUpperCase();

        return ROOM_CODE.test(code) ? code : null;
    }

    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var image = new Image();

            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve(image);
            };

            image.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('unreadable image'));
            };

            image.src = url;
        });
    }

    /**
     * Scale factors for the photo: each DECODE_SIDES size smaller than the
     * photo, then the photo itself if it is no larger than the biggest size.
     */
    function decodeScales(largestSide) {
        var scales = DECODE_SIDES
            .filter(function (side) { return side < largestSide; })
            .map(function (side) { return side / largestSide; });

        if (largestSide <= DECODE_SIDES[DECODE_SIDES.length - 1]) {
            scales.push(1);
        }

        return scales;
    }

    function decode(image) {
        var scales = decodeScales(Math.max(image.naturalWidth, image.naturalHeight));
        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d', { willReadFrequently: true });

        for (var i = 0; i < scales.length; i++) {
            canvas.width = Math.round(image.naturalWidth * scales[i]);
            canvas.height = Math.round(image.naturalHeight * scales[i]);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);

            var pixels = context.getImageData(0, 0, canvas.width, canvas.height);
            var result = window.jsQR(pixels.data, canvas.width, canvas.height, {
                inversionAttempts: 'attemptBoth',
            });

            if (result) {
                return result.data;
            }
        }

        return null;
    }

    function setStatus(scanner, message, isError) {
        var status = scanner.querySelector('[data-qr-status]');

        status.textContent = message;
        status.classList.toggle('is-error', Boolean(isError));
        status.hidden = message === '';
    }

    function handlePhoto(scanner, input) {
        var file = input.files && input.files[0];

        if (!file) {
            return;
        }

        setStatus(scanner, 'Reading the QR code…', false);

        loadImage(file)
            .then(function (image) {
                var code = extractRoomCode(decode(image));

                if (code === null) {
                    setStatus(scanner, "Couldn't find a RoomLink QR code in that photo. Hold the phone steady, fill the frame with the QR code, and try again.", true);

                    return;
                }

                setStatus(scanner, 'Opening ' + code + '…', false);
                window.location.assign(scanner.dataset.scanUrl.replace('__CODE__', encodeURIComponent(code)));
            })
            .catch(function () {
                setStatus(scanner, "Couldn't open that photo. Please try again.", true);
            })
            .finally(function () {
                // Allow choosing the same photo again after an error.
                input.value = '';
            });
    }

    document.querySelectorAll('[data-qr-scanner]').forEach(function (scanner) {
        var input = scanner.querySelector('[data-qr-input]');

        input.addEventListener('change', function () {
            handlePhoto(scanner, input);
        });
    });

    window.RoomLinkQrScanner = { extractRoomCode: extractRoomCode };
})();
