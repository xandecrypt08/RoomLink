<?php

namespace App\Support\SubjectImport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

/**
 * Extracts plain text from an uploaded curriculum or prospectus so the
 * subjects in it can be found. Table cells are separated by tabs or wide
 * gaps, so the course code, title and units columns stay apart.
 *
 * Supported: PDF with selectable text, Word (.docx), plain text, and photos
 * or scans (JPG, PNG, WEBP) read with the Tesseract OCR program.
 */
class DocumentText
{
    /**
     * File extensions accepted for upload.
     *
     * @var list<string>
     */
    public const EXTENSIONS = ['pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'webp'];

    /**
     * Extensions read with OCR, whose text can contain misread characters.
     *
     * @var list<string>
     */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(private OcrImage $ocrImage) {}

    public static function isImage(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), self::IMAGE_EXTENSIONS, true);
    }

    /**
     * @throws RuntimeException with a message for the administrator when the
     *                          file can't be read.
     */
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $text = match ($extension) {
            'pdf' => $this->fromPdf($file->getRealPath()),
            'docx' => $this->fromDocx($file->getRealPath()),
            'txt' => (string) file_get_contents($file->getRealPath()),
            'jpg', 'jpeg', 'png', 'webp' => $this->fromImage($file->getRealPath()),
            default => throw new RuntimeException('This file type is not supported. Upload a PDF, Word (.docx), text file or a photo.'),
        };

        if (trim($text) === '') {
            throw new RuntimeException($extension === 'pdf'
                ? 'No text was found in this PDF. If it is a scan, upload photos or screenshots of its pages instead.'
                : 'No text was found in this file.');
        }

        return $text;
    }

    private function fromPdf(string $path): string
    {
        try {
            return (new PdfParser)->parseFile($path)->getText();
        } catch (Throwable) {
            throw new RuntimeException("This PDF couldn't be read. It may be damaged or password-protected.");
        }
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
        return 'Reading photos needs the free Tesseract OCR program, which is not installed on this server. '
            .'Install it, or set TESSERACT_PATH in .env to its location. PDF and Word files work without it.';
    }
}
