<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Photos people upload, kept on the private disk and shown through signed-in
 * routes. Each is turned upright and re-encoded as JPEG, which also drops the
 * metadata phones add, such as the GPS location.
 */
class UploadedPhoto
{
    /**
     * Open an upload the right way up. Phone photos are often stored sideways
     * with a rotation tag.
     *
     * @throws RuntimeException with a message for the user when it isn't a photo
     */
    public static function open(UploadedFile $file): GdImage
    {
        $path = $file->getRealPath();
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if (! $image instanceof GdImage) {
            throw new RuntimeException("This photo couldn't be opened. Upload a JPG, PNG or WEBP image.");
        }

        $exif = function_exists('exif_read_data') ? @exif_read_data($path) : false;

        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        $rotated = $angle === 0 ? $image : imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    /**
     * Save part of a photo at the given size and return its path on the local disk.
     *
     * @param  array{x: int, y: int, width: int, height: int}  $part
     */
    public static function save(GdImage $source, array $part, int $width, int $height, string $folder): string
    {
        $photo = imagecreatetruecolor($width, $height);
        imagefill($photo, 0, 0, imagecolorallocate($photo, 255, 255, 255));
        imagecopyresampled($photo, $source, 0, 0, $part['x'], $part['y'], $width, $height, $part['width'], $part['height']);

        ob_start();
        imagejpeg($photo, null, 85);
        $jpeg = (string) ob_get_clean();

        $path = $folder.'/'.Str::random(40).'.jpg';
        Storage::disk('local')->put($path, $jpeg);

        return $path;
    }
}
