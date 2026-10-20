<?php

namespace App\Support\SubjectImport;

/**
 * Text read from an uploaded document.
 */
final readonly class ExtractedText
{
    /**
     * @param  bool  $usedOcr  some of it was read from images with OCR, so
     *                         characters may have been misread
     * @param  int  $pages  pages read (1 for files without pages)
     */
    public function __construct(
        public string $text,
        public bool $usedOcr,
        public int $pages = 1,
    ) {}
}
