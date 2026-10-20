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
            'PATHFIT (7 letters)' => ["PATHFIT 1\tMovement Competency Training\t2\t0\t2\tNone", 'PATHFIT-1', 'Movement Competency Training'],
            'PATHFit (capitals then small letters)' => ["PATHFit 1\tMovement Competency Training\t2\t0\t2\tNone", 'PATHFIT-1', 'Movement Competency Training'],
            'PATHFit with the number attached' => ['PATHFit1 Movement Competency Training 2 0 2', 'PATHFIT-1', 'Movement Competency Training'],
            'PathFIT (small letters then capitals)' => ["PathFIT 3\tDance and Recreational Activities\t2\t0\t2", 'PATHFIT-3', 'Dance and Recreational Activities'],
            'PATH Fit written as two words' => ['PATH Fit 2   Exercise-based Fitness Activities   2   0   2', 'PATHFIT-2', 'Exercise-based Fitness Activities'],
            'PATHFit prerequisite dropped' => ['PATHFit 4 Sports and Outdoor Activities 2 0 2 PATHFit 3', 'PATHFIT-4', 'Sports and Outdoor Activities'],
            'PathFit in camel case' => ['PathFit 2   Exercise-based Fitness Activities   2   0   2   PATHFIT 1', 'PATHFIT-2', 'Exercise-based Fitness Activities'],
            'NSTP with a component' => ['NSTP-CWTS 1   Civic Welfare Training Service 1   3   0   3', 'NSTP-CWTS-1', 'Civic Welfare Training Service 1'],
            'NSTP with a space before the component' => ['NSTP ROTC 2 Reserve Officers Training Corps 2 3 0 3 NSTP ROTC 1', 'NSTP-ROTC-2', 'Reserve Officers Training Corps 2'],
            'plain NSTP' => ['NSTP 1 National Service Training Program 1 3 0 3', 'NSTP-1', 'National Service Training Program 1'],
            'capitalised prefix' => ["Fil 101\tKontekstwalisadong Komunikasyon sa Filipino\t3\t0\t3", 'FIL-101', 'Kontekstwalisadong Komunikasyon sa Filipino'],
            'Math prefix with a mixed-case prerequisite' => ['Math 102 Calculus 3 0 3 Math 101', 'MATH-102', 'Calculus'],
            'two-word elective prefix' => ['IT Elec 1   Object-Oriented Programming   2   3   3   IT 112', 'IT-ELEC-1', 'Object-Oriented Programming'],
            'Roman numeral number' => ['PE II   Rhythmic Activities   2   0   2', 'PE-II', 'Rhythmic Activities'],
            'title in capitals ending in a word and number' => ['NSTP 1 CIVIC WELFARE TRAINING SERVICE 1 3 0 3', 'NSTP-1', 'Civic Welfare Training Service 1'],
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
            'grand total' => ['Grand Total 77'],
            'week number' => ['Week 2   Orientation and course overview'],
            'year heading in words' => ['First Year 1'],
            'semester heading' => ['Second Semester 2'],
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
