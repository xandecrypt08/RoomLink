<?php

namespace App\Support\ScheduleImport;

use App\Models\Subject;

/**
 * Finds the classes in a faculty member's teaching load (a class list or
 * timetable) from text extracted from a PDF, spreadsheet, Word file or photo.
 *
 * A class row holds the subject code (Course No.), the section, usually the
 * descriptive title, and a schedule such as "10:30 am-12:30 pm S COMLAB2A"
 * (time, days, room) or "MTh 5:30-7 pm COMLAB1A" (days first). A row taught
 * on several days ("MTh") becomes one class per day, as RoomLink stores them.
 * Every result is reviewed before it is saved, because layouts vary.
 */
class TeachingLoadParser
{
    /**
     * A clock time: "10", "10:30" or "10.30" (12- or 24-hour).
     */
    private const TIME = '(?<![\d.:])\d{1,2}(?:[:.]\d{2})?(?![\d:])';

    /**
     * am / pm, also written "AM", "a.m." or "p m".
     */
    private const MERIDIEM = '[AaPp]\.?\s?[Mm]\.?(?![A-Za-z])';

    /**
     * Between the start and end time: a dash, "to", or a tab (separate
     * Start and End columns in a spreadsheet).
     */
    private const SEPARATOR = '\s*(?:[-–—~]|to\b|\t)\s*';

    /**
     * Day codes as written in class lists: M T W Th F S Su, combined ("MTh",
     * "TTH", "MWF"), or names ("Mon", "Sat"). "Ih", "lh" and "1h" are how OCR
     * often reads "Th", and "$" an "S".
     */
    private const DAYS = '(?:(?i:mon|tues?|wed|thu(?:rs?)?|fri|sat|sun)|Th|TH|[Il1|]h|Su|SU|Sa|SA|M|T|W|F|S|\$)+';

    /**
     * A subject or section code: letters then a number ("IT-109", "IT 109L",
     * "AI21", "PATHFIT 1", "NSTP-CWTS 1"), or one letter and a dashed number
     * ("T-109", an OCR misreading of "IT-109").
     */
    private const CODE = '(?<![\w-])(?:[A-Za-z]{2,8}\s?-?\s?\d{1,4}[A-Za-z]?|[A-Z]{2,8}[ -][A-Za-z]{2,6}\s?-?\s?\d{1,4}[A-Za-z]?|[A-Za-z]-\d{2,4}[A-Za-z]?)(?![\w.-])';

    /**
     * Words that look like a code when followed by a number but are not
     * subjects or sections (compared in capitals).
     *
     * @var list<string>
     */
    private const NOT_CODES = ['ROOM', 'RM', 'UNIT', 'UNITS', 'YEAR', 'SEM', 'PERIOD', 'PAGE', 'TOTAL', 'LEC', 'LAB', 'HRS'];

    /**
     * @param  bool  $fromOcr  the text was read from a photo or scan, so some
     *                         letters may be misread
     * @return list<array{subject_code: string, title: string, section: string, day: string, start_time: string, end_time: string, room: string}>
     */
    public function parse(string $text, bool $fromOcr = false): array
    {
        $lines = preg_split('/\R/u', str_replace("\u{00A0}", ' ', $text));
        $sectionFirst = $this->sectionComesFirst($lines);
        $classes = [];

        foreach ($lines as $line) {
            foreach ($this->parseLine($line, $fromOcr, $sectionFirst) as $class) {
                $classes[implode('|', $class)] = $class;
            }
        }

        $classes = array_values($classes);

        return $fromOcr ? $this->withRepairedPrefixes($classes) : $classes;
    }

    /**
     * The classes on one row of the load: one per day of each schedule on it.
     *
     * @return list<array{subject_code: string, title: string, section: string, day: string, start_time: string, end_time: string, room: string}>
     */
    public function parseLine(string $line, bool $fromOcr = false, bool $sectionFirst = true): array
    {
        $schedules = $this->schedules($line);

        if ($schedules === []) {
            return [];
        }

        $course = $this->course(substr($line, 0, $schedules[0]['offset']), $fromOcr, $sectionFirst);

        if ($course === null) {
            return [];
        }

        $classes = [];

        foreach ($schedules as $schedule) {
            foreach ($schedule['days'] as $day) {
                $classes[] = [
                    ...$course,
                    'day' => $day,
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'room' => $schedule['room'],
                ];
            }
        }

        return $classes;
    }

    /**
     * Every schedule on the line, with where it starts. Time first
     * ("5-7 pm W CISCOLAB") is tried before days first ("W 5-7 pm CISCOLAB").
     *
     * @return list<array{offset: int, days: list<string>, start_time: string, end_time: string, room: string}>
     */
    private function schedules(string $line): array
    {
        $time = self::TIME;
        $meridiem = self::MERIDIEM;
        $separator = self::SEPARATOR;
        $days = self::DAYS;

        $range = "(?<start>{$time})\s*(?<startMeridiem>{$meridiem})?(?<separator>{$separator})(?<end>{$time})\s*(?<endMeridiem>{$meridiem})?";

        // The room runs to the line's end, a "/" or ";", or the next schedule
        // on the same row; room() then keeps its first cell.
        $roomEnd = "(?=$|[\/;]|\s+(?:{$days}[\s,|]+)?{$time}\s*(?:{$meridiem})?{$separator}{$time})";

        $patterns = [
            "/{$range}[\s,|]+(?<days>{$days})(?=[\s,|]|$)(?<room>.*?){$roomEnd}/u",
            "/(?<![A-Za-z])(?<days>{$days})[\s,|]+{$range}(?<room>.*?){$roomEnd}/u",
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $line, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

            $schedules = [];

            foreach ($matches as $match) {
                $schedule = $this->schedule($match);

                if ($schedule !== null) {
                    $schedules[] = $schedule;
                }
            }

            if ($schedules !== []) {
                return $schedules;
            }
        }

        return [];
    }

    /**
     * @param  array<string, array{0: ?string, 1: int}>  $match
     * @return array{offset: int, days: list<string>, start_time: string, end_time: string, room: string}|null
     */
    private function schedule(array $match): ?array
    {
        $startMeridiem = $match['startMeridiem'][0];
        $endMeridiem = $match['endMeridiem'][0];

        // Two bare numbers in neighbouring spreadsheet columns ("3<tab>0") are
        // units, not a time range.
        if (str_contains($match['separator'][0], "\t") && $startMeridiem === null && $endMeridiem === null
            && ! str_contains($match['start'][0].$match['end'][0], ':')) {
            return null;
        }

        $times = $this->times($match['start'][0], $startMeridiem, $match['end'][0], $endMeridiem);
        $days = $this->days($match['days'][0]);

        if ($times === null || $days === []) {
            return null;
        }

        return [
            'offset' => $match[0][1],
            'days' => $days,
            'start_time' => $times[0],
            'end_time' => $times[1],
            'room' => $this->room((string) $match['room'][0]),
        ];
    }

    /**
     * Start and end as "H:i". A time without am/pm takes the other one's
     * ("1:30-4:30 pm"), unless that would start the class after it ends
     * ("10:30-12:30 pm" is 10:30 am). With neither, 7 to 11 is morning and
     * 12 to 6 afternoon. Hours above 12 are a 24-hour clock.
     *
     * @return array{0: string, 1: string}|null null when the range isn't a real class time
     */
    private function times(string $start, ?string $startMeridiem, string $end, ?string $endMeridiem): ?array
    {
        [$startHour, $startMinute] = array_map('intval', preg_split('/[:.]/', $start) + [1 => 0]);
        [$endHour, $endMinute] = array_map('intval', preg_split('/[:.]/', $end) + [1 => 0]);

        if ($startHour > 23 || $endHour > 24 || $startMinute > 59 || $endMinute > 59) {
            return null;
        }

        $startMeridiem = $this->meridiem($startMeridiem);
        $endMeridiem = $this->meridiem($endMeridiem);
        $guessedStart = $startMeridiem === null;
        $guessedEnd = $endMeridiem === null;

        $endMeridiem ??= $startMeridiem ?? (($endHour >= 7 && $endHour <= 11) ? 'am' : 'pm');
        $startMeridiem ??= $endMeridiem;

        $minutes = function (int $hour, int $minute, string $meridiem): int {
            if ($hour <= 12 && $hour > 0) {
                $hour = match (true) {
                    $meridiem === 'pm' && $hour < 12 => $hour + 12,
                    $meridiem === 'am' && $hour === 12 => 0,
                    default => $hour,
                };
            }

            return $hour * 60 + $minute;
        };

        $from = $minutes($startHour, $startMinute, $startMeridiem);
        $to = $minutes($endHour, $endMinute, $endMeridiem);

        if ($from >= $to && $guessedStart && $startMeridiem === 'pm') {
            $from = $minutes($startHour, $startMinute, 'am');
        }

        if ($from >= $to && $guessedEnd && $endMeridiem === 'am') {
            $to = $minutes($endHour, $endMinute, 'pm');
        }

        if ($from >= $to || $to > 24 * 60) {
            return null;
        }

        return [sprintf('%02d:%02d', intdiv($from, 60), $from % 60), sprintf('%02d:%02d', intdiv($to, 60), $to % 60)];
    }

    private function meridiem(?string $meridiem): ?string
    {
        if ($meridiem === null) {
            return null;
        }

        return in_array(strtolower($meridiem[0]), ['p'], true) ? 'pm' : 'am';
    }

    /**
     * Day codes in RoomLink's form (M, T, W, Th, F, S, Su), in order written.
     *
     * @return list<string>
     */
    private function days(string $days): array
    {
        preg_match_all('/(?i:mon|tues?|wed|thu(?:rs?)?|fri|sat|sun)|Th|TH|[Il1|]h|Su|SU|Sa|SA|M|T|W|F|S|\$/', $days, $matches);

        $codes = array_map(fn (string $day): string => match (strtolower($day)) {
            'mon', 'm' => 'M',
            'tue', 'tues', 't' => 'T',
            'wed', 'w' => 'W',
            'thu', 'thur', 'thurs', 'th', 'ih', 'lh', '1h', '|h' => 'Th',
            'fri', 'f' => 'F',
            'sat', 'sa', 's', '$' => 'S',
            'sun', 'su' => 'Su',
        }, $matches[0]);

        return array_values(array_unique($codes));
    }

    /**
     * The room as written, without table borders or punctuation around it.
     */
    private function room(string $room): string
    {
        $room = preg_split('/\t|\s{2,}/u', trim($room, " \t,|"))[0];

        return mb_substr(trim($room, ' |[](){}_,.;:—–-'), 0, 100);
    }

    /**
     * The subject code, section and title from the part of the row before the
     * schedule, e.g. "A86  AI21  IT-109  IT Elective I - Platform Tech  2.0  Memoracion, Janine Salve".
     *
     * Codes standing next to each other are the section and subject (in the
     * order of the header row, section first by default); a class code with a
     * single letter ("A86") is not a code. The title follows them, up to the
     * units or the instructor's name.
     *
     * @return array{subject_code: string, title: string, section: string}|null
     */
    private function course(string $prefix, bool $fromOcr, bool $sectionFirst): ?array
    {
        // OCR reads the "I" of "Elective I" as a border; borders themselves were removed.
        if ($fromOcr) {
            $prefix = preg_replace('/(?<=\s)\|(?=\s)/u', 'I', $prefix);
        }

        $prefix = preg_replace('/[|\[\]{}_]+/u', ' ', $prefix);

        preg_match_all('/'.self::CODE.'/u', $prefix, $matches, PREG_OFFSET_CAPTURE);

        $codes = array_values(array_filter(
            $matches[0],
            fn (array $code): bool => ! in_array(strtoupper((string) preg_replace('/[\s\-\d].*$/', '', $code[0])), self::NOT_CODES, true),
        ));

        if ($codes === []) {
            return null;
        }

        // The first run of codes separated only by spaces or tabs.
        $run = [$codes[0]];

        foreach (array_slice($codes, 1) as $code) {
            $previous = end($run);
            $gap = substr($prefix, $previous[1] + strlen($previous[0]), $code[1] - $previous[1] - strlen($previous[0]));

            if (trim($gap) !== '') {
                break;
            }

            $run[] = $code;
        }

        $subject = $sectionFirst ? end($run) : $run[0];
        $runEnd = end($run)[1] + strlen(end($run)[0]);
        $after = substr($prefix, $runEnd);
        $section = '';

        if (count($run) > 1) {
            $section = $sectionFirst ? $run[count($run) - 2][0] : $run[1][0];
        } elseif ($sectionFirst) {
            $section = $this->sectionBefore(substr($prefix, 0, $subject[1]));
        } elseif (preg_match('/^ *(\S*\d\S*)(.*)$/su', $after, $next)) {
            // A section written right after the subject, such as "1A", has a digit.
            [$section, $after] = [$next[1], $next[2]];
        }

        // Otherwise a section code in capitals later on the row, after the
        // title: "GE-109 Purposive Communication BSED-2B".
        if ($section === '') {
            foreach ($codes as $code) {
                if ($code[1] >= $runEnd && preg_match('/^[A-Z0-9 \-]+$/', $code[0])) {
                    $section = $code[0];
                    $after = substr($prefix, $runEnd, $code[1] - $runEnd);

                    break;
                }
            }
        }

        return [
            'subject_code' => Subject::normalizeCode($subject[0]),
            'title' => $this->title($after),
            'section' => $this->section($section, $fromOcr),
        ];
    }

    /**
     * The section written just before the subject code: the last word, or the
     * last two for names like "BSIT 2A".
     */
    private function sectionBefore(string $before): string
    {
        $words = preg_split('/\s+/u', trim($before), -1, PREG_SPLIT_NO_EMPTY);
        $section = array_pop($words) ?? '';

        if (preg_match('/^\d[A-Za-z]?$/', $section) && $words !== [] && preg_match('/^[A-Za-z]{2,}$/', end($words))) {
            $section = array_pop($words).' '.$section;
        }

        return $section;
    }

    private function section(string $section, bool $fromOcr): string
    {
        $section = trim($section, " \t.,;:");

        // "Al21" is how OCR reads "AI21": a lone small "l" among capitals is an "I".
        if ($fromOcr && preg_match('/^[A-Z0-9 \-]*l[A-Z0-9l \-]*$/', $section)) {
            $section = str_replace('l', 'I', $section);
        }

        return mb_substr($section, 0, 100);
    }

    /**
     * The descriptive title: the next cell when cells are tab-separated,
     * otherwise the words up to the units ("2.0") or the instructor's name
     * ("3 Dela Cruz, Juan").
     */
    private function title(string $after): string
    {
        if (str_contains($after, "\t")) {
            $cells = array_values(array_filter(array_map('trim', explode("\t", $after)), fn (string $cell) => $cell !== ''));
            $title = (isset($cells[0]) && preg_match('/\pL{2,}/u', $cells[0])) ? $cells[0] : '';
        } else {
            $title = preg_split('/(?<!\S)\d{1,2}\.\d{1,2}(?!\S)/u', $after)[0];
            $title = preg_split('/(?<!\S)\d{1,2}(?=\s+[\pL .\'-]+,|\s*$)/u', $title)[0];
        }

        $title = trim(preg_replace('/\s+/u', ' ', $title), " \t.,;:-–—");

        return preg_match('/\pL{2,}/u', $title) ? mb_substr($title, 0, 150) : '';
    }

    /**
     * The section is in the first column when the header row names it before
     * the subject (Code, Section, Subject, ...), as most class lists do.
     *
     * @param  list<string>  $lines
     */
    private function sectionComesFirst(array $lines): bool
    {
        foreach ($lines as $line) {
            if (preg_match('/\bsection\b/i', $line, $section, PREG_OFFSET_CAPTURE)
                && preg_match('/\b(?:subject|course)\b/i', $line, $subject, PREG_OFFSET_CAPTURE)) {
                return $section[0][1] < $subject[0][1];
            }
        }

        return true;
    }

    /**
     * OCR sometimes drops the first letter of a code ("T-119L" for "IT-119L").
     * When exactly one prefix read elsewhere in the load ends with the letter
     * that is left, use it.
     *
     * @param  list<array{subject_code: string, title: string, section: string, day: string, start_time: string, end_time: string, room: string}>  $classes
     * @return list<array{subject_code: string, title: string, section: string, day: string, start_time: string, end_time: string, room: string}>
     */
    private function withRepairedPrefixes(array $classes): array
    {
        $prefixes = collect($classes)
            ->map(fn (array $class) => preg_match('/^([A-Z]{2,})-/', $class['subject_code'], $match) ? $match[1] : null)
            ->filter()
            ->unique();

        return array_map(function (array $class) use ($prefixes): array {
            if (preg_match('/^([A-Z])-(.+)$/', $class['subject_code'], $match)) {
                $candidates = $prefixes->filter(fn (string $prefix) => str_ends_with($prefix, $match[1]));

                if ($candidates->count() === 1) {
                    $class['subject_code'] = $candidates->first().'-'.$match[2];
                }
            }

            return $class;
        }, $classes);
    }
}
