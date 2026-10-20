<?php

namespace App\Support\SubjectImport;

use GdImage;
use RuntimeException;

/**
 * Cleans a photo or scan of a subject table before OCR. Tesseract reads the
 * borders of a table as letters ("|", "f", "[") and garbles the text that
 * touches them, so the image is:
 *
 *  1. scaled so text is large enough to read (small screenshots are enlarged);
 *  2. turned black and white with an adaptive threshold: each pixel is compared
 *     with the average brightness around it, which copes with shadows and
 *     uneven light;
 *  3. stripped of long horizontal and vertical lines. Letters are short, table
 *     borders are long; a little tolerance keeps slightly tilted photos working.
 *
 * Pixels are kept in byte strings (one byte each) so a phone photo fits in memory.
 */
class OcrImage
{
    /** Width the image is scaled to before cleaning (pixels). */
    private const TARGET_WIDTH = 2000;

    /** Largest enlargement for small images. */
    private const MAX_UPSCALE = 2.5;

    /** A pixel is ink if darker than its surroundings by this fraction. */
    private const THRESHOLD = 0.15;

    /**
     * Clean the image at $path and save it as a PNG. Returns the new path.
     */
    public function clean(string $path): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($path));

        if (! $source instanceof GdImage) {
            throw new RuntimeException("This image couldn't be opened. Upload a JPG, PNG or WEBP photo.");
        }

        $image = $this->scaled($source);
        $width = imagesx($image);
        $height = imagesy($image);
        $ink = $this->withoutLines($this->blackAndWhite($image, $width, $height), $width, $height);

        // Measure the tilt on the text alone (table borders, page edges and a
        // dark desk would mislead it), then level the photo and clean it again.
        $angle = $this->tiltAngle($ink, $width, $height);

        if (abs($angle) >= 0.25) {
            $rotated = imagerotate($image, $angle, imagecolorallocate($image, 255, 255, 255));

            if ($rotated instanceof GdImage) {
                $image = $rotated;
                $width = imagesx($image);
                $height = imagesy($image);
                $ink = $this->withoutLines($this->blackAndWhite($image, $width, $height), $width, $height);
            }
        }

        $cleaned = tempnam(sys_get_temp_dir(), 'ocr').'.png';
        imagepng($this->toImage($ink, $width, $height), $cleaned);

        return $cleaned;
    }

    private function scaled(GdImage $source): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(self::MAX_UPSCALE, self::TARGET_WIDTH / $width);

        $image = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
        imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), $width, $height);
        imagefilter($image, IMG_FILTER_GRAYSCALE);

        // Light sharpening helps OCR tell thin letters apart in soft phone
        // photos (which are shrunk). Enlarged screenshots are already crisp,
        // and sharpening them adds artefacts.
        if ($scale < 1) {
            imageconvolution($image, [[0, -1, 0], [-1, 5, -1], [0, -1, 0]], 1, 0);
        }

        return $image;
    }

    /**
     * The tilt of the text lines in degrees, as GD's imagerotate() expects to
     * level them (Even 2 degrees moves the right end of a wide table by more
     * than a row, so OCR would pair titles with the wrong units.)
     *
     * Each candidate angle from -5 to 5 degrees is scored by how sharply the
     * ink piles up into rows when projected along it; level text gives tall,
     * narrow peaks (projection-profile method). Every third pixel is sampled.
     */
    private function tiltAngle(string $ink, int $width, int $height): float
    {
        $step = 3;
        $points = [];

        for ($y = 0; $y < $height; $y += $step) {
            $row = $y * $width;

            for ($x = 0; $x < $width; $x += $step) {
                if ($ink[$row + $x] === "\1") {
                    $points[] = [$x, $y];
                }
            }
        }

        $bestAngle = 0.0;
        $bestScore = -1;

        for ($angle = -5.0; $angle <= 5.0; $angle += 0.25) {
            $slope = tan(deg2rad($angle));
            $rows = [];

            foreach ($points as [$x, $y]) {
                // Bins as wide as the sampling step, so no angle is favoured
                // just because sampled rows land in every third bin.
                $row = (int) round(($y - $x * $slope) / $step);
                $rows[$row] = ($rows[$row] ?? 0) + 1;
            }

            $score = 0;

            foreach ($rows as $count) {
                $score += $count * $count;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAngle = $angle;
            }
        }

        return $bestAngle;
    }

    /**
     * Each pixel against the local average brightness. The average comes from
     * shrinking the image to a coarse grid and enlarging it again, which GD
     * does quickly.
     *
     * @return string one byte per pixel, "\1" for ink
     */
    private function blackAndWhite(GdImage $image, int $width, int $height): string
    {
        $cell = max(16, intdiv($width, 40));
        $small = imagecreatetruecolor(max(1, intdiv($width, $cell)), max(1, intdiv($height, $cell)));
        imagecopyresampled($small, $image, 0, 0, 0, 0, imagesx($small), imagesy($small), $width, $height);

        $average = imagecreatetruecolor($width, $height);
        imagecopyresampled($average, $small, 0, 0, 0, 0, $width, $height, imagesx($small), imagesy($small));

        $ink = str_repeat("\0", $width * $height);
        $limit = 1 - self::THRESHOLD;

        for ($y = 0; $y < $height; $y++) {
            $row = $y * $width;

            for ($x = 0; $x < $width; $x++) {
                if ((imagecolorat($image, $x, $y) & 0xFF) < (imagecolorat($average, $x, $y) & 0xFF) * $limit) {
                    $ink[$row + $x] = "\1";
                }
            }
        }

        return $ink;
    }

    /**
     * Erase runs of ink much longer than a letter. Neighbouring rows (and
     * columns) are merged first, so a border tilted a degree or two still
     * forms one long run.
     */
    private function withoutLines(string $ink, int $width, int $height): string
    {
        $erase = str_repeat("\0", $width * $height);
        $spread = 2;

        // Horizontal borders.
        $minRun = intdiv($width, 18);

        for ($y = 0; $y < $height; $y++) {
            $run = 0;

            for ($x = 0; $x <= $width; $x++) {
                $dark = false;

                for ($dy = -$spread; $x < $width && $dy <= $spread && ! $dark; $dy++) {
                    $yy = $y + $dy;
                    $dark = $yy >= 0 && $yy < $height && $ink[$yy * $width + $x] === "\1";
                }

                if ($dark) {
                    $run++;
                } else {
                    if ($run >= $minRun) {
                        for ($xx = $x - $run; $xx < $x; $xx++) {
                            $erase[$y * $width + $xx] = "\1";
                        }
                    }

                    $run = 0;
                }
            }
        }

        // Vertical borders.
        $minRun = intdiv($height, 30);

        for ($x = 0; $x < $width; $x++) {
            $run = 0;

            for ($y = 0; $y <= $height; $y++) {
                $dark = false;

                for ($dx = -$spread; $y < $height && $dx <= $spread && ! $dark; $dx++) {
                    $xx = $x + $dx;
                    $dark = $xx >= 0 && $xx < $width && $ink[$y * $width + $xx] === "\1";
                }

                if ($dark) {
                    $run++;
                } else {
                    if ($run >= $minRun) {
                        for ($yy = $y - $run; $yy < $y; $yy++) {
                            $erase[$yy * $width + $x] = "\1";
                        }
                    }

                    $run = 0;
                }
            }
        }

        // Keep ink that isn't part of a line: ink AND NOT erase, byte by byte.
        return $ink & ($erase ^ str_repeat("\1", $width * $height));
    }

    private function toImage(string $ink, int $width, int $height): GdImage
    {
        $out = imagecreate($width, $height);
        $white = imagecolorallocate($out, 255, 255, 255);
        $black = imagecolorallocate($out, 0, 0, 0);
        imagefill($out, 0, 0, $white);

        for ($y = 0; $y < $height; $y++) {
            $row = $y * $width;

            for ($x = 0; $x < $width; $x++) {
                if ($ink[$row + $x] === "\1") {
                    imagesetpixel($out, $x, $y, $black);
                }
            }
        }

        return $out;
    }
}
