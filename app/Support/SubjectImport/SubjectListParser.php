<?php

namespace App\Support\SubjectImport;

use App\Models\Subject;

/**
 * Finds subjects (Course No. and Descriptive Title) in text extracted from a
 * curriculum or prospectus.
 *
 * A subject row starts with a course code such as "IT-110", "IT 110" or
 * "GE109", followed by the title and usually the units and prerequisite
 * columns, which are dropped. Every result should still be reviewed before
 * importing, because document layouts vary.
 */
class SubjectListParser
{
    /**
     * A course code: 2-6 capital letters, an optional space or dash, then a
     * number of up to 4 digits with an optional letter (IT-110, GE 109, PE1).
     */
    private const CODE = '(?<letters>[A-Z]{2,6})\s*[-–—.]?\s*(?<number>\d{1,4}[A-Z]?)(?=[\s|:\-–—]|$)';

    /**
     * Words that look like a code when followed by a number but start other rows.
     *
     * @var list<string>
     */
    private const NOT_CODES = ['YEAR', 'SEM', 'SEMESTER', 'UNIT', 'UNITS', 'TOTAL', 'PAGE', 'GRADE', 'ROOM', 'LEC', 'LAB', 'HRS', 'HOURS', 'SECTION', 'SUMMER', 'NO', 'NOS', 'CHAPTER', 'PART', 'TERM'];

    /**
     * Small words kept lower case when a title written in capitals is converted.
     *
     * @var list<string>
     */
    private const SMALL_WORDS = ['a', 'an', 'and', 'as', 'at', 'by', 'for', 'in', 'of', 'on', 'or', 'the', 'to', 'with'];

    /**
     * Characters OCR commonly reads in place of the letters of a course code.
     *
     * @var array<string, string>
     */
    private const OCR_LETTER_FIXES = ['1' => 'I', 'l' => 'I', '|' => 'I', '!' => 'I', '(' => 'I', '[' => 'I', '7' => 'T', '0' => 'O', '5' => 'S', '8' => 'B'];

    /**
     * @param  bool  $fromOcr  the text was read from a photo, so letters in a
     *                         course code may have been misread as digits or symbols
     * @return list<array{code: string, title: string}>
     */
    public function parse(string $text, bool $fromOcr = false): array
    {
        $lines = preg_split('/\R/u', $text);
        $subjects = [];

        // Code prefixes read correctly elsewhere in the document (IT, GE, ...),
        // used to accept repairs of misread ones such as "(7-111".
        $knownPrefixes = $fromOcr
            ? collect($lines)->map(fn (string $line) => $this->parseLine($line)['code'] ?? null)
                ->filter()->map(fn (string $code) => strtok($code, '-'))->unique()->all()
            : [];

        foreach ($lines as $line) {
            $subject = $this->parseLine($fromOcr ? $this->withOcrLettersFixed($line, $knownPrefixes) : $line);

            if ($subject !== null && ! isset($subjects[$subject['code']])) {
                $subjects[$subject['code']] = $subject;
            }
        }

        return array_values($subjects);
    }

    /**
     * @return array{code: string, title: string}|null
     */
    public function parseLine(string $line): ?array
    {
        $line = trim(str_replace("\u{00A0}", ' ', $line));

        // Row numbers and bullets in front of the code: "1.", "12)", "•", "-".
        $line = preg_replace('/^(?:\d{1,3}[.)]\s+|[•\-–*]\s+)/u', '', $line);

        if (! preg_match('/^'.self::CODE.'(?<rest>.*)$/u', $line, $match) || in_array($match['letters'], self::NOT_CODES, true)) {
            return null;
        }

        $title = $this->titleFrom($match['rest']);

        if ($title === null) {
            return null;
        }

        return [
            'code' => Subject::normalizeCode($match['letters'].'-'.$match['number']),
            'title' => $title,
        ];
    }

    /**
     * Repair the letters of a course code at the start of an OCR line, such as
     * "(7-111" or "1T-110" for "IT-111" / "IT-110". The code must have a dash
     * and become 2-6 capitals, and either keep a real capital or match a prefix
     * read correctly elsewhere in the document, so a line like "15-2 ..." is
     * not turned into a made-up "IS-2".
     *
     * @param  list<string>  $knownPrefixes
     */
    private function withOcrLettersFixed(string $line, array $knownPrefixes): string
    {
        return preg_replace_callback(
            '/^(\s*(?:\d{1,3}[.)]\s+)?)([A-Za-z0-9|!(\[]{2,6})(\s*[-–—]\s*\d{1,4}[A-Z]?\s)/u',
            function (array $match) use ($knownPrefixes): string {
                $letters = strtr($match[2], self::OCR_LETTER_FIXES);

                $trusted = preg_match('/[A-Z]/', $match[2]) || in_array($letters, $knownPrefixes, true);

                if ($letters === $match[2] || ! preg_match('/^[A-Z]{2,6}$/', $letters) || ! $trusted) {
                    return $match[0];
                }

                return $match[1].$letters.$match[3];
            },
            $line,
        );
    }

    /**
     * The title is the first cell after the code. Cells are separated by tabs,
     * "|" or a gap of two or more spaces; if the row has no such gaps, trailing
     * units and prerequisite columns are trimmed off instead.
     */
    private function titleFrom(string $rest): ?string
    {
        $cells = preg_split('/\t+|\s*\|\s*|\s{2,}/u', trim($rest, " \t:-–—|"), -1, PREG_SPLIT_NO_EMPTY);

        $title = collect($cells)
            ->map(fn (string $cell) => trim($cell))
            ->first(fn (string $cell) => preg_match_all('/\pL/u', $cell) >= 3);

        if ($title === null) {
            return null;
        }

        $title = $this->withoutTrailingColumns($title);
        $title = trim(preg_replace('/\s+/u', ' ', $title), " \t.,;:-–—");

        if (preg_match_all('/\pL/u', $title) < 3 || mb_strlen($title) > 150 || preg_match('/^descriptive\s+title$/iu', $title)) {
            return null;
        }

        return $this->readableCase($title);
    }

    /**
     * Drop prerequisites ("None", "IT-101, IT-102") and units from the end of a
     * title cell. Three or more trailing numbers are units (lecture, laboratory,
     * credit); a single trailing number is kept, since titles like "Computer
     * Programming 1" end in one.
     */
    private function withoutTrailingColumns(string $title): string
    {
        $code = '[A-Z]{2,6}\s*-?\s*\d{1,4}[A-Z]?';

        $title = preg_replace('/(?:\s+(?:none|n\/?a|-+|—|'.$code.')(?:\s*,\s*'.$code.')*)+\s*$/iu', '', $title);

        if (preg_match('/^(?<words>.*?\pL.*?)(?<numbers>(?:\s+\d+(?:\.\d+)?)+)\s*$/u', $title, $match)) {
            $numbers = preg_split('/\s+/', trim($match['numbers']));

            if (count($numbers) >= 4) {
                return $match['words'].' '.$numbers[0];
            }

            if (count($numbers) >= 2) {
                return $match['words'];
            }
        }

        return $title;
    }

    /**
     * Titles written in capitals ("INTRODUCTION TO COMPUTING") become title case,
     * keeping Roman numerals in capitals. Other titles are left as written.
     */
    private function readableCase(string $title): string
    {
        if (mb_strtoupper($title) !== $title) {
            return $title;
        }

        $words = explode(' ', mb_convert_case(mb_strtolower($title), MB_CASE_TITLE));

        foreach ($words as $index => $word) {
            if (preg_match('/^(?:I|Ii|Iii|Iv|V|Vi|Vii|Viii|Ix|X)$/u', $word)) {
                $words[$index] = mb_strtoupper($word);
            } elseif ($index > 0 && in_array(mb_strtolower($word), self::SMALL_WORDS, true)) {
                $words[$index] = mb_strtolower($word);
            }
        }

        return implode(' ', $words);
    }
}
