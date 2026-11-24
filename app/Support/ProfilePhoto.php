<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Saves profile photos as small squares, cropped from the centre.
 */
class ProfilePhoto
{
    /** Folder on the local (private) disk. */
    public const FOLDER = 'profile-photos';

    /** Width and height of the saved photo, in pixels. */
    private const SIZE = 400;

    /**
     * Save the uploaded photo and return its path on the local disk.
     *
     * @throws RuntimeException when it isn't a photo
     */
    public function store(UploadedFile $file): string
    {
        $source = UploadedPhoto::open($file);
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        return UploadedPhoto::save($source, [
            'x' => intdiv($width - $side, 2),
            'y' => intdiv($height - $side, 2),
            'width' => $side,
            'height' => $side,
        ], self::SIZE, self::SIZE, self::FOLDER);
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('local')->delete($path);
        }
    }
}
