/*
 * RoomLink "Scan Room QR".
 *
 * Live mode (HTTPS or localhost): opens the rear camera and scans continuously
 * until a room QR code is found. Photo mode (plain HTTP, or if the camera
 * can't be used): the phone takes a photo and the QR is read from it.
 *
 * Only the room code is taken from the QR; the address printed in it is
 * ignored and the room opens on the address in use, so printed QR codes keep
 * working when the server's address changes.
 *
 * QR codes shown on screens (phones, laptops, OLED panels) are harder to read
 * than printed ones: the pixel grid causes moiré, glare and low brightness wash
 * out contrast, and dark mode inverts the colours. So every image is also tried
 * slightly blurred (removes the pixel grid) and with local contrast
 * normalisation (each area brightened against its own surroundings), with
 * inverted colours allowed. Tested on simulated camera footage of an OLED
 * screen in dark mode, a dim washed-out screen and a printed sheet.
 */
(function () {
    'use strict';

    var ROOM_CODE = /^ROOM-[A-Z0-9]{4,40}$/;
    var SCAN_PATH = /\/faculty\/scan\/([A-Za-z0-9-]+)/;

    // Photo mode: sizes (longest side, px) to decode at, smallest first.
    var PHOTO_SIDES = [1000, 1600, 2400];

    // Live mode: the middle of the frame, decoded at a rotating set of variants.
    var LIVE_REGION = 0.85;
    var LIVE_VARIANTS = [
        { side: 400, blur: true, local: 16 },
        { side: 400, blur: true, local: 0 },
        { side: 560, blur: true, local: 8 },
    ];
    var LIVE_INTERVAL_MS = 120;

    /**
     * The room code in a QR's text: a RoomLink scan link from any address, or
     * a bare ROOM-XXXXXXXXXX code. Anything else (other websites) is rejected.
     */
    function extractRoomCode(text) {
        var value = String(text || '').trim();
        var match = value.match(SCAN_PATH);
        var code = (match ? match[1] : value).toUpperCase();

        return ROOM_CODE.test(code) ? code : null;
    }

    function newContext() {
        return document.createElement('canvas').getContext('2d', { willReadFrequently: true });
    }

    // Older browsers ignore canvas filters; the blurred variants are skipped there.
    var FILTERS_WORK = typeof newContext().filter === 'string';

    /**
     * jsQR occasionally throws on unusual images instead of returning null;
     * treat that as "no QR found" so the remaining attempts still run.
     */
    function jsQrText(pixels, width, height) {
        try {
            var result = window.jsQR(pixels, width, height, { inversionAttempts: 'attemptBoth' });

            return result ? result.data : null;
        } catch (error) {
            return null;
        }
    }

    function draw(context, source, region, width, height, filter) {
        context.filter = filter || 'none';
        context.drawImage(source, region.x, region.y, region.width, region.height, 0, 0, width, height);
        context.filter = 'none';

        return context.getImageData(0, 0, width, height).data;
    }

    /**
     * Pixels for one decoding variant of a region of an image or video frame:
     *   blur  - a 1px blur that removes a screen's pixel grid (moiré);
     *   local - local contrast normalisation: each pixel compared with the
     *           average of its surroundings (radius = shorter side / local),
     *           which restores contrast lost to glare or a dim screen.
     */
    function variantPixels(context, source, region, width, height, variant) {
        var useFilters = FILTERS_WORK && (variant.blur || variant.local);

        context.canvas.width = width;
        context.canvas.height = height;
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';

        var pixels = draw(context, source, region, width, height, useFilters && variant.blur ? 'blur(1px)' : null);

        if (!useFilters || !variant.local) {
            return pixels;
        }

        var radius = Math.max(4, Math.round(Math.min(width, height) / variant.local));
        var surroundings = draw(context, source, region, width, height, 'blur(' + radius + 'px)');
        var out = new Uint8ClampedArray(pixels.length);

        for (var i = 0; i < pixels.length; i += 4) {
            var grey = (pixels[i] * 77 + pixels[i + 1] * 150 + pixels[i + 2] * 29) >> 8;
            var average = (surroundings[i] * 77 + surroundings[i + 1] * 150 + surroundings[i + 2] * 29) >> 8;

            out[i] = out[i + 1] = out[i + 2] = 128 + 2.5 * (grey - average);
            out[i + 3] = 255;
        }

        return out;
    }

    function decodeVariant(context, source, region, width, height, variant) {
        return jsQrText(variantPixels(context, source, region, width, height, variant), width, height);
    }

    /* ------------------------------------------------------------------ */
    /* Photo mode                                                          */
    /* ------------------------------------------------------------------ */

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
     * Each PHOTO_SIDES size smaller than the photo, then the photo itself if
     * it is no larger than the biggest size. Small copies are fast and read
     * most photos; a photo with no readable QR fails in seconds.
     */
    function photoScales(largestSide) {
        var scales = PHOTO_SIDES
            .filter(function (side) { return side < largestSide; })
            .map(function (side) { return side / largestSide; });

        if (largestSide <= PHOTO_SIDES[PHOTO_SIDES.length - 1]) {
            scales.push(1);
        }

        return scales;
    }

    /**
     * At each size: as photographed, then the screen-friendly variant. Screens
     * read at the small sizes, so the slow screen variant is skipped above
     * 1600px to keep a photo with no QR from taking too long.
     */
    function decodePhoto(image) {
        var region = { x: 0, y: 0, width: image.naturalWidth, height: image.naturalHeight };
        var scales = photoScales(Math.max(region.width, region.height));
        var context = newContext();

        for (var i = 0; i < scales.length; i++) {
            var width = Math.round(region.width * scales[i]);
            var height = Math.round(region.height * scales[i]);
            var variants = [{ blur: false, local: 0 }];

            if (Math.max(width, height) <= 1600) {
                variants.push({ blur: true, local: 16 });
            }

            for (var v = 0; v < variants.length; v++) {
                var text = decodeVariant(context, image, region, width, height, variants[v]);

                if (text) {
                    return text;
                }
            }
        }

        return null;
    }

    /* ------------------------------------------------------------------ */
    /* Shared UI                                                           */
    /* ------------------------------------------------------------------ */

    function setStatus(element, message, isError) {
        element.textContent = message;
        element.classList.toggle('is-error', Boolean(isError));
        element.hidden = message === '';
    }

    function openRoom(scanner, code) {
        if (navigator.vibrate) {
            navigator.vibrate(80);
        }

        window.location.assign(scanner.dataset.scanUrl.replace('__CODE__', encodeURIComponent(code)));
    }

    function handlePhoto(scanner, input, statusElement) {
        var file = input.files && input.files[0];

        if (!file) {
            return;
        }

        setStatus(statusElement, 'Reading the QR code…', false);

        loadImage(file)
            .then(function (image) {
                var code = extractRoomCode(decodePhoto(image));

                if (code === null) {
                    setStatus(statusElement, "Couldn't find a RoomLink QR code in that photo. Fill the frame with the QR code and try again. On a screen, turn the brightness up and avoid reflections.", true);

                    return;
                }

                setStatus(statusElement, 'Opening ' + code + '…', false);
                openRoom(scanner, code);
            })
            .catch(function () {
                setStatus(statusElement, "Couldn't open that photo. Please try again.", true);
            })
            .finally(function () {
                // Allow choosing the same photo again after an error.
                input.value = '';
            });
    }

    /* ------------------------------------------------------------------ */
    /* Live mode                                                           */
    /* ------------------------------------------------------------------ */

    function liveScanningIsAvailable() {
        return window.isSecureContext
            && Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    /**
     * The phone's built-in QR detector (Android Chrome) is faster and copes
     * better with screens; jsQR is used everywhere else and as a backup.
     */
    function nativeDetector() {
        if (!('BarcodeDetector' in window)) {
            return Promise.resolve(null);
        }

        return window.BarcodeDetector.getSupportedFormats()
            .then(function (formats) {
                return formats.indexOf('qr_code') === -1 ? null : new window.BarcodeDetector({ formats: ['qr_code'] });
            })
            .catch(function () {
                return null;
            });
    }

    function LiveScanner(scanner) {
        this.scanner = scanner;
        this.view = scanner.querySelector('[data-qr-live-view]');
        this.video = scanner.querySelector('[data-qr-video]');
        this.status = scanner.querySelector('[data-qr-live-status]');
        this.context = newContext();
        this.stream = null;
        this.timer = null;
        this.frame = 0;
        this.detector = null;
        this.busy = false;
    }

    LiveScanner.prototype.open = function () {
        var self = this;

        this.view.hidden = false;
        document.body.classList.add('qr-live-open');
        setStatus(this.status, 'Starting the camera…', false);

        nativeDetector().then(function (detector) {
            self.detector = detector;
        });

        navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
        }).then(function (stream) {
            if (self.view.hidden) {
                stream.getTracks().forEach(function (track) { track.stop(); });

                return;
            }

            self.stream = stream;
            self.useContinuousFocus(stream);
            self.video.srcObject = stream;

            return self.video.play().then(function () {
                setStatus(self.status, "Point the camera at the room's QR code.", false);
                self.timer = window.setInterval(function () { self.tick(); }, LIVE_INTERVAL_MS);
            });
        }).catch(function (error) {
            var denied = error && (error.name === 'NotAllowedError' || error.name === 'SecurityError');

            setStatus(self.status, denied
                ? 'Camera access was blocked. Allow the camera for this site, or take a photo instead.'
                : "Couldn't start the camera. Take a photo instead.", true);
        });
    };

    LiveScanner.prototype.useContinuousFocus = function (stream) {
        var track = stream.getVideoTracks()[0];
        var capabilities = track && track.getCapabilities ? track.getCapabilities() : {};

        if (capabilities.focusMode && capabilities.focusMode.indexOf('continuous') !== -1) {
            track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(function () {});
        }
    };

    LiveScanner.prototype.close = function () {
        window.clearInterval(this.timer);
        this.timer = null;

        if (this.stream) {
            this.stream.getTracks().forEach(function (track) { track.stop(); });
            this.stream = null;
        }

        this.video.srcObject = null;
        this.view.hidden = true;
        document.body.classList.remove('qr-live-open');
    };

    LiveScanner.prototype.found = function (text) {
        var code = extractRoomCode(text);

        if (code === null) {
            setStatus(this.status, "That isn't a RoomLink room QR code. Point the camera at the code posted in the room.", true);

            return false;
        }

        setStatus(this.status, 'Opening ' + code + '…', false);
        window.clearInterval(this.timer);
        openRoom(this.scanner, code);

        return true;
    };

    LiveScanner.prototype.tick = function () {
        var self = this;
        var video = this.video;

        if (this.busy || video.readyState < 2 || !video.videoWidth) {
            return;
        }

        this.frame++;

        if (this.detector) {
            this.busy = true;
            this.detector.detect(video)
                .then(function (codes) {
                    if (codes.length) {
                        self.found(codes[0].rawValue);
                    } else if (self.frame % 3 === 0) {
                        // Backup for codes the built-in detector misses.
                        self.scanWithJsQr();
                    }
                })
                .catch(function () {
                    self.detector = null;
                })
                .finally(function () {
                    self.busy = false;
                });

            return;
        }

        this.scanWithJsQr();
    };

    /**
     * Decode the middle of the frame with the next variant in LIVE_VARIANTS,
     * so each frame stays quick and every variant is tried within a second.
     */
    LiveScanner.prototype.scanWithJsQr = function () {
        var video = this.video;
        var size = Math.round(Math.min(video.videoWidth, video.videoHeight) * LIVE_REGION);
        var region = {
            x: Math.round((video.videoWidth - size) / 2),
            y: Math.round((video.videoHeight - size) / 2),
            width: size,
            height: size,
        };
        var variant = LIVE_VARIANTS[this.frame % LIVE_VARIANTS.length];
        var text = decodeVariant(this.context, video, region, variant.side, variant.side, variant);

        if (text) {
            this.found(text);
        }
    };

    /* ------------------------------------------------------------------ */
    /* Wiring                                                              */
    /* ------------------------------------------------------------------ */

    document.querySelectorAll('[data-qr-scanner]').forEach(function (scanner) {
        var status = scanner.querySelector('[data-qr-status]');
        var liveButton = scanner.querySelector('[data-qr-live-button]');
        var photoButton = scanner.querySelector('[data-qr-photo-button]');
        var live = null;

        if (liveScanningIsAvailable()) {
            live = new LiveScanner(scanner);
            liveButton.hidden = false;
            photoButton.hidden = true;

            liveButton.addEventListener('click', function () {
                setStatus(status, '', false);
                live.open();
            });

            scanner.querySelector('[data-qr-close]').addEventListener('click', function () {
                live.close();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !live.view.hidden) {
                    live.close();
                }
            });

            // Release the camera if the faculty member leaves or locks the phone.
            document.addEventListener('visibilitychange', function () {
                if (document.hidden && !live.view.hidden) {
                    live.close();
                }
            });
        }

        scanner.querySelectorAll('[data-qr-input]').forEach(function (input) {
            input.addEventListener('change', function () {
                if (live) {
                    live.close();
                }

                handlePhoto(scanner, input, status);
            });
        });
    });

    window.RoomLinkQrScanner = { extractRoomCode: extractRoomCode };
})();
