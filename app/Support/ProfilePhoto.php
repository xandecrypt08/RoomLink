<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Saves profile photos as small square JPEGs on the private disk; they are
 * shown through a signed-in route, never a public folder.
 *
 * Phone photos are several megabytes and often stored sideways with a
 * rotation tag, so each upload is turned upright, cropped to a centred square
 * and re-encoded. Re-encoding also drops the photo's metadata, such as the GPS
 * location a phone camera records.
 */
class ProfilePhoto
{
    /** Folder on the local (private) disk. */
    public const FOLDER = 'profile-photos';

    /** Width and height of the saved photo, in pixels. */
    private const SIZE = 400;

    /**
     * Save the uploaded photo and return its path on the local disk.
     */
    public function store(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $source instanceof GdImage) {
            throw new RuntimeException("This photo couldn't be opened. Upload a JPG, PNG or WEBP image.");
        }

        $source = $this->upright($source, $file->getRealPath());

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $photo = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($photo, 0, 0, imagecolorallocate($photo, 255, 255, 255));
        imagecopyresampled($photo, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), self::SIZE, self::SIZE, $side, $side);

        ob_start();
        imagejpeg($photo, null, 85);
        $jpeg = (string) ob_get_clean();

        $path = self::FOLDER.'/'.Str::random(40).'.jpg';
        Storage::disk('local')->put($path, $jpeg);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Turn a phone photo upright using its EXIF rotation tag.
     */
    private function upright(GdImage $image, string $path): GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;

        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
