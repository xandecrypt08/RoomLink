<?php

namespace App\Support\SubjectImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser as PdfParser;
use Smalot\PdfParser\XObject\Image;
use Throwable;
use ZipArchive;

/**
 * Extracts plain text from an uploaded curriculum or prospectus so the
 * subjects in it can be found. Table cells are separated by tabs or wide
 * gaps, so the course code, title and units columns stay apart.
 *
 * Supported: PDF (pages with text are read directly; scanned pages that are
 * only pictures are read with OCR, page by page), Word (.docx), Excel (.xlsx)
 * and CSV, plain text, and photos or scans (JPG, PNG, WEBP) read with the
 * Tesseract OCR program.
 */
class DocumentText
{
    /**
     * File extensions accepted for upload.
     *
     * @var list<string>
     */
    public const EXTENSIONS = ['pdf', 'docx', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png', 'webp'];

    /**
     * Largest upload, in kilobytes (multi-page scans are big).
     */
    public const MAX_KILOBYTES = 25600;

    /**
     * Most PDF pages read in one upload; OCR takes several seconds a page.
     */
    public const MAX_PAGES = 30;

    /**
     * A PDF page with fewer letters than this is treated as a picture.
     */
    private const MIN_PAGE_LETTERS = 20;

    public function __construct(private OcrImage $ocrImage) {}

    /**
     * @throws RuntimeException with a message for the administrator when the
     *                          file can't be read.
     */
    public function extract(UploadedFile $file): ExtractedText
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $extracted = match ($extension) {
            'pdf' => $this->fromPdf($path),
            'docx' => new ExtractedText($this->fromDocx($path), usedOcr: false),
            'xlsx' => new ExtractedText($this->fromXlsx($path), usedOcr: false),
            'csv' => new ExtractedText($this->fromCsv($path), usedOcr: false),
            'txt' => new ExtractedText((string) file_get_contents($path), usedOcr: false),
            'jpg', 'jpeg', 'png', 'webp' => new ExtractedText($this->fromImage($path), usedOcr: true),
            default => throw new RuntimeException('This file type is not supported. Upload a PDF, Word (.docx), Excel (.xlsx or .csv), text file or a photo.'),
        };

        if (trim($extracted->text) === '') {
            throw new RuntimeException('No text was found in this file.');
        }

        return $extracted;
    }

    /**
     * Each page is read on its own: text pages directly, picture pages
     * (scans) by OCR of the page's image.
     */
    private function fromPdf(string $path): ExtractedText
    {
        try {
            $pages = (new PdfParser)->parseFile($path)->getPages();
        } catch (Throwable) {
            throw new RuntimeException("This PDF couldn't be read. It may be damaged or password-protected.");
        }

        if (count($pages) > self::MAX_PAGES) {
            throw new RuntimeException('This PDF has '.count($pages).' pages. Upload up to '.self::MAX_PAGES.' pages at a time, for example one year level per file.');
        }

        $parts = [];
        $usedOcr = false;
        $unreadablePages = [];

        foreach ($pages as $number => $page) {
            $text = $this->pageText($page);

            if (preg_match_all('/\pL/u', $text) >= self::MIN_PAGE_LETTERS) {
                $parts[] = $text;

                continue;
            }

            $images = $this->pageImages($page);

            if ($images === []) {
                $unreadablePages[] = $number + 1;

                continue;
            }

            foreach ($images as $image) {
                try {
                    $parts[] = $this->fromImage($image);
                    $usedOcr = true;
                } finally {
                    @unlink($image);
                }
            }
        }

        if ($parts === []) {
            throw new RuntimeException($unreadablePages === []
                ? 'No text was found in this PDF.'
                : "The pages of this PDF are pictures in a format RoomLink can't read. Save the pages as JPG or PNG images and upload those instead.");
        }

        return new ExtractedText(implode("\n", $parts), $usedOcr, count($pages));
    }

    private function pageText(Page $page): string
    {
        try {
            return $page->getText();
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * The page's scanned images saved as files, largest first. Small images
     * (logos, signatures) are skipped; a page split into tiles keeps them all.
     *
     * @return list<string> file paths, to be deleted after use
     */
    private function pageImages(Page $page): array
    {
        $images = [];

        try {
            $objects = $page->getXObjects();
        } catch (Throwable) {
            return [];
        }

        foreach ($objects as $object) {
            if (! $object instanceof Image) {
                continue;
            }

            $details = $object->getDetails();
            $width = (int) ($details['Width'] ?? 0);
            $height = (int) ($details['Height'] ?? 0);
            $content = $object->getContent();

            // Each image is often listed twice under different names.
            $key = md5($content);

            if ($width > 0 && $height > 0 && ! isset($images[$key])) {
                $images[$key] = ['area' => $width * $height, 'object' => $object, 'width' => $width, 'height' => $height];
            }
        }

        if ($images === []) {
            return [];
        }

        $largest = max(array_column($images, 'area'));
        $paths = [];

        foreach ($images as $image) {
            if ($image['area'] >= $largest / 4 && ($path = $this->imageFile($image['object'], $image['width'], $image['height'])) !== null) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Save a PDF image as a file GD can open. JPEG scans are stored as-is;
     * uncompressed bitmaps (grey or colour, 8-bit, or 1-bit black and white)
     * are wrapped in a PNG. Other formats (JPEG 2000, fax-style CCITT/JBIG2)
     * can't be read without extra software and are skipped.
     */
    private function imageFile(Image $image, int $width, int $height): ?string
    {
        $details = $image->getDetails();
        $filters = (array) ($details['Filter'] ?? []);
        $content = $image->getContent();

        if (in_array('DCTDecode', $filters, true) || str_starts_with($content, "\xFF\xD8\xFF")) {
            return $this->tempFile($content, 'jpg');
        }

        if (array_intersect($filters, ['JPXDecode', 'JBIG2Decode', 'CCITTFaxDecode']) !== []) {
            return null;
        }

        $bits = (int) ($details['BitsPerComponent'] ?? 8);
        $rowBytes = $bits === 1 ? intdiv($width + 7, 8) : null;

        if ($bits === 1 && strlen($content) >= $rowBytes * $height) {
            return $this->tempFile($this->png($content, $width, $height, $rowBytes, colourType: 0, bitDepth: 1), 'png');
        }

        if ($bits === 8) {
            $channels = intdiv(strlen($content), $width * $height);

            if ($channels === 1 || $channels === 3) {
                return $this->tempFile($this->png($content, $width, $height, $width * $channels, colourType: $channels === 3 ? 2 : 0, bitDepth: 8), 'png');
            }
        }

        return null;
    }

    /**
     * A minimal PNG around raw pixel rows (no filtering, zlib-compressed).
     */
    private function png(string $pixels, int $width, int $height, int $rowBytes, int $colourType, int $bitDepth): string
    {
        $rows = substr($pixels, 0, $rowBytes * $height);
        $data = "\0".implode("\0", str_split($rows, $rowBytes));

        $chunk = fn (string $type, string $body) => pack('N', strlen($body)).$type.$body.pack('N', crc32($type.$body));

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, $bitDepth, $colourType, 0, 0, 0))
            .$chunk('IDAT', (string) gzcompress($data, 6))
            .$chunk('IEND', '');
    }

    private function tempFile(string $contents, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pdfpage').'.'.$extension;
        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * A .docx file is a zip of XML. Table cells become tabs and rows and
     * paragraphs become new lines.
     */
    private function fromDocx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true || ($xml = $zip->getFromName('word/document.xml')) === false) {
            throw new RuntimeException("This Word file couldn't be read. Save it as .docx and try again.");
        }

        $zip->close();

        // Inside a table cell, paragraph breaks are just spaces; otherwise
        // every cell would land on its own line, away from its row.
        $xml = preg_replace_callback('#<w:tc\b.*?</w:tc>#s', fn (array $cell) => str_replace(['</w:p>', '<w:br/>'], ' ', $cell[0]), $xml);

        $xml = preg_replace(['#</w:tc>#', '#</w:p>#', '#</w:tr>#', '#<w:tab/>#', '#<w:br/>#'], ["\t", "\n", "\n", "\t", "\n"], $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * An .xlsx file is a zip of XML: the sheets, plus the text of their cells
     * stored once in sharedStrings.xml. Each row becomes a line with its cells
     * separated by tabs; empty cells are kept so the columns stay in place.
     * Cells formatted as times (stored as a fraction of a day, 0.4375) are
     * written as clock times ("10:30 AM").
     */
    private function fromXlsx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException("This Excel file couldn't be read. Save it as .xlsx and try again.");
        }

        $strings = [];

        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            preg_match_all('#<si>(.*?)</si>#s', $xml, $items);

            // Phonetic guides (<rPh>) repeat the text, so they are skipped.
            $strings = array_map(fn (string $item) => $this->xmlText(preg_replace('#<rPh\b.*?</rPh>#s', '', $item)), $items[1]);
        }

        $timeStyles = $this->xlsxTimeStyles((string) $zip->getFromName('xl/styles.xml'));

        $sheets = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            if (preg_match('#^xl/worksheets/sheet(\d+)\.xml$#', (string) $zip->getNameIndex($index), $match)) {
                $sheets[(int) $match[1]] = $match[0];
            }
        }

        ksort($sheets);
        $lines = [];

        foreach ($sheets as $sheet) {
            preg_match_all('#<row\b[^>]*?(?:/>|>(.*?)</row>)#s', (string) $zip->getFromName($sheet), $rows);

            foreach ($rows[1] as $row) {
                preg_match_all('#<c\b([^>]*?)(?:/>|>(.*?)</c>)#s', $row, $cells, PREG_SET_ORDER);

                $values = [];

                foreach ($cells as $position => [, $attributes, $content]) {
                    $column = preg_match('/\br="([A-Z]+)\d+"/', $attributes, $reference) ? $this->columnIndex($reference[1]) : $position;
                    $values[$column] = $this->xlsxCellValue($attributes, $content ?? '', $strings, $timeStyles);
                }

                if (array_filter($values, fn (string $value) => trim($value) !== '') !== []) {
                    $lines[] = implode("\t", array_replace(array_fill(0, max(array_keys($values)) + 1, ''), $values));
                }
            }
        }

        $zip->close();

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $strings  the workbook's shared strings
     * @param  list<int>  $timeStyles  cell style numbers that show a time
     */
    private function xlsxCellValue(string $attributes, string $content, array $strings, array $timeStyles): string
    {
        $type = preg_match('/\bt="(\w+)"/', $attributes, $match) ? $match[1] : 'n';
        $value = preg_match('#<v>(.*?)</v>#s', $content, $match) ? html_entity_decode($match[1], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';

        return match ($type) {
            's' => $strings[(int) $value] ?? '',
            'inlineStr' => $this->xmlText($content),
            'b' => $value === '1' ? 'TRUE' : 'FALSE',
            'e' => '',
            'n' => is_numeric($value) && preg_match('/\bs="(\d+)"/', $attributes, $style) && in_array((int) $style[1], $timeStyles, true)
                ? $this->clockTime((float) $value)
                : $value,
            default => $value,
        };
    }

    /**
     * Cell styles (positions in cellXfs) whose number format shows a time:
     * Excel's built-in time formats, or a custom format with hours in it.
     *
     * @return list<int>
     */
    private function xlsxTimeStyles(string $stylesXml): array
    {
        $timeFormats = [18, 19, 20, 21, 45, 46, 47];

        preg_match_all('#<numFmt\b[^>]*numFmtId="(\d+)"[^>]*formatCode="([^"]*)"#', $stylesXml, $formats, PREG_SET_ORDER);

        foreach ($formats as [, $id, $code]) {
            // Text in quotes or brackets ("h" in "[Red]" or "\"hrs\"") isn't a format letter.
            if (preg_match('/h/i', preg_replace('/"[^"]*"|\[[^\]]*\]/', '', html_entity_decode($code)))) {
                $timeFormats[] = (int) $id;
            }
        }

        if (! preg_match('#<cellXfs\b[^>]*>(.*?)</cellXfs>#s', $stylesXml, $cellXfs)) {
            return [];
        }

        preg_match_all('#<xf\b[^>]*?numFmtId="(\d+)"#', $cellXfs[1], $styles);

        return array_keys(array_filter($styles[1], fn (string $id) => in_array((int) $id, $timeFormats, true)));
    }

    /**
     * The time of day in an Excel date-time number, e.g. 0.4375 is "10:30 AM".
     */
    private function clockTime(float $value): string
    {
        $minutes = (int) round(($value - floor($value)) * 24 * 60) % (24 * 60);

        return date('g:i A', mktime(intdiv($minutes, 60), $minutes % 60));
    }

    /**
     * Column letters as a position: A is 0, Z is 25, AA is 26.
     */
    private function columnIndex(string $letters): int
    {
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }

    /**
     * The text of every <t> element inside a piece of spreadsheet XML.
     */
    private function xmlText(string $xml): string
    {
        preg_match_all('#<t\b[^>]*>(.*?)</t>#s', $xml, $texts);

        return html_entity_decode(implode('', $texts[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Rows of a CSV file as tab-separated lines. Excel saves CSV with
     * semicolons in some regions, so the separator is detected.
     */
    private function fromCsv(string $path): string
    {
        $contents = (string) file_get_contents($path);
        $contents = str_starts_with($contents, "\xEF\xBB\xBF") ? substr($contents, 3) : $contents;

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($contents, "\n");
        $separator = substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',') ? ';' : ',';

        $lines = [];

        foreach (preg_split('/\R/u', $contents) as $line) {
            if (trim($line) !== '') {
                $lines[] = implode("\t", str_getcsv($line, $separator, '"', ''));
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Read a photo or scan with Tesseract, after levelling it and removing the
     * table borders (see OcrImage). --psm 6 treats the page as one block of
     * text, which keeps table rows on one line; preserve_interword_spaces
     * keeps the gaps between columns.
     */
    private function fromImage(string $path): string
    {
        $tesseract = (string) config('services.tesseract.path', 'tesseract');

        // A full path that doesn't exist: say so plainly instead of a generic failure.
        if (preg_match('#[/\\\\]#', $tesseract) && ! is_file($tesseract)) {
            throw new RuntimeException(self::tesseractMissingMessage());
        }

        $cleaned = $this->ocrImage->clean($path);

        try {
            $result = Process::timeout(120)->run([
                $tesseract, $cleaned, 'stdout', '--psm', '6', '-c', 'preserve_interword_spaces=1',
            ]);
        } catch (Throwable) {
            throw new RuntimeException(self::tesseractMissingMessage());
        } finally {
            @unlink($cleaned);
        }

        if (! $result->successful()) {
            throw new RuntimeException(str_contains(strtolower($result->errorOutput()), 'not recognized') || $result->exitCode() === 127
                ? self::tesseractMissingMessage()
                : "This photo couldn't be read. Try a clearer, straight-on photo of the page.");
        }

        return $result->output();
    }

    public static function tesseractMissingMessage(): string
    {
        return 'Reading photos and scanned PDFs needs the free Tesseract OCR program, which is not installed on this server. '
            .'Install it, or set TESSERACT_PATH in .env to its location. PDFs with selectable text and Word files work without it.';
    }
}
