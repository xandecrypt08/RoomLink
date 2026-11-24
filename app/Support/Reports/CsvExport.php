<?php

namespace App\Support\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExport
{
    /**
     * Download rows as a CSV file Excel opens correctly (UTF-8 with a BOM).
     *
     * @param  list<string>  $headings
     * @param  iterable<int, list<string|int|null>>  $rows
     */
    public static function download(string $fileName, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headings, ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($output, array_map(self::cell(...), $row), ',', '"', '');
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Text people typed must not run as a formula when the file is opened.
     */
    private static function cell(string|int|null $value): string|int|null
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
