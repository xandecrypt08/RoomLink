<?php

namespace Tests\Unit;

use App\Support\ScheduleImport\TeachingLoadParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TeachingLoadParserTest extends TestCase
{
    /**
     * The class list from the registrar's system (Code, Section, Subject,
     * Description, Units, Instructor, Schedule), as text from a PDF.
     */
    private const CLASS_LIST = "Class List (First Semester 2026-2027)\n"
        ."Period 26-1   Instructor 250702   Memoracion, Janine Salve\n"
        ."Code  Section  Subject  Description  Units  Instructor  Schedule\n"
        ."A86  AI21  IT-109  IT Elective I - Platform Tech  2.0  Memoracion, Janine Salve  10:30 am-12:30 pm S COMLAB2A\n"
        ."A78  AI21  IT-109L  IT Elective I - Platform Tech  1.0  Memoracion, Janine Salve  1:30-4:30 pm S COMLAB2A\n"
        ."A189  AI34  IT-119  IT Elective IV - Web System  2.0  Memoracion, Janine Salve  5-7 pm W CISCOLAB\n"
        ."A188  AI34  IT-119L  IT Elective IV - Web System  1.0  Memoracion, Janine Salve  5:30-7 pm MTh COMLAB1A\n";

    public function test_it_reads_every_class_of_a_class_list_one_per_day(): void
    {
        $classes = (new TeachingLoadParser)->parse(self::CLASS_LIST);

        $this->assertSame([
            ['subject_code' => 'IT-109', 'title' => 'IT Elective I - Platform Tech', 'section' => 'AI21', 'day' => 'S', 'start_time' => '10:30', 'end_time' => '12:30', 'room' => 'COMLAB2A'],
            ['subject_code' => 'IT-109L', 'title' => 'IT Elective I - Platform Tech', 'section' => 'AI21', 'day' => 'S', 'start_time' => '13:30', 'end_time' => '16:30', 'room' => 'COMLAB2A'],
            ['subject_code' => 'IT-119', 'title' => 'IT Elective IV - Web System', 'section' => 'AI34', 'day' => 'W', 'start_time' => '17:00', 'end_time' => '19:00', 'room' => 'CISCOLAB'],
            ['subject_code' => 'IT-119L', 'title' => 'IT Elective IV - Web System', 'section' => 'AI34', 'day' => 'M', 'start_time' => '17:30', 'end_time' => '19:00', 'room' => 'COMLAB1A'],
            ['subject_code' => 'IT-119L', 'title' => 'IT Elective IV - Web System', 'section' => 'AI34', 'day' => 'Th', 'start_time' => '17:30', 'end_time' => '19:00', 'room' => 'COMLAB1A'],
        ], $classes);
    }

    public function test_spreadsheet_rows_with_tabs_and_an_empty_description_are_read(): void
    {
        $classes = (new TeachingLoadParser)->parse("A188\tAI34\tIT-119L\t\t1.0\t\t5:30-7 pm MTh COMLAB1A");

        $this->assertSame(['IT-119L', '', 'AI34'], [$classes[0]['subject_code'], $classes[0]['title'], $classes[0]['section']]);
        $this->assertSame(['M', 'Th'], array_column($classes, 'day'));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function times(): array
    {
        return [
            'both with am / pm' => ['10:30 am-12:30 pm', '10:30', '12:30'],
            'start takes the end\'s pm' => ['1:30-4:30 pm', '13:30', '16:30'],
            'hours only' => ['5-7 pm', '17:00', '19:00'],
            'start would be after the end in pm, so am' => ['10:30-12:30 pm', '10:30', '12:30'],
            'across noon' => ['11-1 pm', '11:00', '13:00'],
            'end takes the start\'s am, then pm across noon' => ['11:00 am-1:00', '11:00', '13:00'],
            'no am / pm in the morning' => ['7:30-9:00', '07:30', '09:00'],
            'no am / pm in the afternoon' => ['1-2:30', '13:00', '14:30'],
            '24-hour clock' => ['13:00-14:30', '13:00', '14:30'],
            'capitals with dots' => ['8:00 A.M. - 9:30 A.M.', '08:00', '09:30'],
            'the word to' => ['3 to 5 PM', '15:00', '17:00'],
        ];
    }

    #[DataProvider('times')]
    public function test_it_reads_the_class_times(string $written, string $start, string $end): void
    {
        $classes = (new TeachingLoadParser)->parse("AI21 IT-109 Platform Tech {$written} M COMLAB2A");

        $this->assertSame([$start, $end], [$classes[0]['start_time'], $classes[0]['end_time']]);
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function days(): array
    {
        return [
            'Monday and Thursday' => ['MTh', ['M', 'Th']],
            'Tuesday and Thursday in capitals' => ['TTH', ['T', 'Th']],
            'Monday, Wednesday, Friday' => ['MWF', ['M', 'W', 'F']],
            'Saturday' => ['S', ['S']],
            'Sunday' => ['Su', ['Su']],
            'day names' => ['Sat', ['S']],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('days')]
    public function test_it_reads_the_days(string $written, array $expected): void
    {
        $classes = (new TeachingLoadParser)->parse("AI21 IT-109 Platform Tech 8-10 am {$written} COMLAB2A");

        $this->assertSame($expected, array_column($classes, 'day'));
    }

    public function test_days_may_come_before_the_time_and_a_row_may_have_two_schedules(): void
    {
        $classes = (new TeachingLoadParser)->parse('BSED-2B GE-109 Purposive Communication TTh 1:00-2:30 pm Rm 204 / F 3-5 pm Rm 205');

        $this->assertSame(
            [['T', '13:00', 'Rm 204'], ['Th', '13:00', 'Rm 204'], ['F', '15:00', 'Rm 205']],
            array_map(fn (array $class) => [$class['day'], $class['start_time'], $class['room']], $classes),
        );
        $this->assertSame('Purposive Communication', $classes[0]['title']);
        $this->assertSame('BSED-2B', $classes[0]['section']);
    }

    public function test_the_section_follows_the_subject_when_the_header_says_so(): void
    {
        $text = "Course\tSection\tDays\tStart\tEnd\tRoom\n"
            ."IT 110 Introduction to Computing\tBSIT 1A\tMW\t8:00 AM\t9:30 AM\tRoom 101\n";

        $classes = (new TeachingLoadParser)->parse($text);

        $this->assertCount(2, $classes);
        $this->assertSame(
            ['subject_code' => 'IT-110', 'title' => 'Introduction to Computing', 'section' => 'BSIT 1A', 'day' => 'M', 'start_time' => '08:00', 'end_time' => '09:30', 'room' => 'Room 101'],
            $classes[0],
        );
    }

    public function test_unit_columns_are_not_read_as_a_time(): void
    {
        $classes = (new TeachingLoadParser)->parse("AI21\tIT-109\tPlatform Tech\t3\t0\tM\t8:00 AM\t10:00 AM\tCOMLAB2A");

        $this->assertSame([['M', '08:00', '10:00']], array_map(fn (array $class) => [$class['day'], $class['start_time'], $class['end_time']], $classes));
    }

    public function test_misread_letters_in_photos_are_repaired(): void
    {
        $ocrText = "|A86 Al21 IT-109 IT Elective | - Platform Tech 2.0 Memoracion, Janine Salve 10:30 am-12:30 pm S COMLAB2A\n"
            ." |A188 Al34 T-119L IT Elective IV - Web System 1.0 Memoracion, Janine Salve 5:30-7 pm MIh COMLABIA\n";

        $classes = (new TeachingLoadParser)->parse($ocrText, fromOcr: true);

        $this->assertSame('IT Elective I - Platform Tech', $classes[0]['title']);
        $this->assertSame(['AI21', 'AI34', 'AI34'], array_column($classes, 'section'));
        $this->assertSame(['IT-109', 'IT-119L', 'IT-119L'], array_column($classes, 'subject_code'));
        $this->assertSame(['S', 'M', 'Th'], array_column($classes, 'day'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonClassLines(): array
    {
        return [
            'header row' => ['Code  Section  Subject  Description  Units  Instructor  Schedule'],
            'period and instructor' => ['Period 26-1   Instructor 250702   Memoracion, Janine Salve'],
            'school year' => ['Class List (First Semester 2026-2027)'],
            'time without a subject' => ['Consultation hours 1-3 pm F Faculty Room'],
            'impossible time' => ['AI21 IT-109 Platform Tech 25-26 M COMLAB2A'],
        ];
    }

    #[DataProvider('nonClassLines')]
    public function test_lines_that_are_not_classes_are_ignored(string $line): void
    {
        $this->assertSame([], (new TeachingLoadParser)->parse($line));
    }
}
