<?php

namespace Tests\Unit;

use App\Support\SubjectImport\SubjectListParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SubjectListParserTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function subjectRows(): array
    {
        return [
            'tab separated (Word / PDF)' => ["IT-110\tIntroduction to Computing\t2\t3\t3\tNone", 'IT-110', 'Introduction to Computing'],
            'wide gaps (OCR)' => ['IT 111    Computer Programming 1    2    3    3    None', 'IT-111', 'Computer Programming 1'],
            'pipes' => ['IT 112 | Computer Programming 2 | 2 | 3 | 3 | IT 111', 'IT-112', 'Computer Programming 2'],
            'single spaces with units and prerequisites' => ['GE 102 Readings in Philippine History 3 0 3 None', 'GE-102', 'Readings in Philippine History'],
            'no separator in the code' => ['GE109 Purposive Communication 3 0 3', 'GE-109', 'Purposive Communication'],
            'row number in front' => ['12. NSTP 2   National Service Training Program 2   3   0   3', 'NSTP-2', 'National Service Training Program 2'],
            'title in capitals' => ['PE 1   PHYSICAL ACTIVITIES TOWARDS HEALTH AND FITNESS I   2   0   2', 'PE-1', 'Physical Activities Towards Health and Fitness I'],
            'number in title kept, units dropped' => ['IT112 Computer Programming 2 2 3 3 IT 111', 'IT-112', 'Computer Programming 2'],
        ];
    }

    #[DataProvider('subjectRows')]
    public function test_it_reads_the_course_code_and_title(string $line, string $code, string $title): void
    {
        $this->assertSame(['code' => $code, 'title' => $title], (new SubjectListParser)->parseLine($line));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonSubjectRows(): array
    {
        return [
            'table header' => ["Course No.\tDescriptive Title\tLec\tLab\tUnits"],
            'total row' => ['TOTAL 23'],
            'year heading' => ['YEAR 2'],
            'page number' => ['Page 2 of 4'],
            'section name' => ['BSIT 1-A'],
            'school name' => ['LEYTE NORMAL UNIVERSITY'],
            'sentence' => ['Students must complete all subjects.'],
        ];
    }

    #[DataProvider('nonSubjectRows')]
    public function test_it_ignores_lines_that_are_not_subjects(string $line): void
    {
        $this->assertNull((new SubjectListParser)->parseLine($line));
    }

    public function test_duplicates_are_listed_once(): void
    {
        $subjects = (new SubjectListParser)->parse("IT-110\tIntroduction to Computing\t3\nIT 110  Introduction to Computing  3");

        $this->assertCount(1, $subjects);
    }

    public function test_misread_code_letters_in_photos_are_repaired_using_prefixes_seen_elsewhere(): void
    {
        $ocrText = "IT-110 Introduction to Computing 2 3 3 None\n"
            ."(7-111 Computer Programming 1 2 3 3 None\n"
            ."1T-112 Computer Programming 2 2 3 3 IT-111\n"
            ."15-2 Some heading that is not a subject\n";

        $codes = array_column((new SubjectListParser)->parse($ocrText, fromOcr: true), 'code');

        $this->assertSame(['IT-110', 'IT-111', 'IT-112'], $codes);
    }

    public function test_letters_are_not_repaired_in_documents_that_were_not_photos(): void
    {
        $codes = array_column((new SubjectListParser)->parse("IT-110 Introduction to Computing\n(7-111 Computer Programming 1"), 'code');

        $this->assertSame(['IT-110'], $codes);
    }
}
