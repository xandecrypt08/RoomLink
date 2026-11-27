<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Saves the photo of a room issue whole, shrunk to a size that still shows
 * the problem.
 */
class IssuePhoto
{
    /** Folder on the local (private) disk. */
    public const FOLDER = 'issue-photos';

    /** Longest side of the saved photo, in pixels. */
    private const LONGEST_SIDE = 1600;

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
        $scale = min(1, self::LONGEST_SIDE / max($width, $height));

        return UploadedPhoto::save(
            $source,
            ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height],
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
            self::FOLDER,
        );
    }
}
