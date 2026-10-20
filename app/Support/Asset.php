<?php

namespace App\Support;

/**
 * URLs for files in public/ that change whenever the file changes, so
 * browsers never keep using an old copy of a stylesheet or script.
 */
class Asset
{
    /**
     * The asset URL with the file's last-modified time as a version,
     * for example /css/ui.css?v=1760968123.
     */
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
