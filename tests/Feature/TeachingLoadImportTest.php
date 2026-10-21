<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Support\ScheduleImport\TeachingLoadReview;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class TeachingLoadImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const CLASS_LIST = "Code\tSection\tSubject\tDescription\tUnits\tInstructor\tSchedule\n"
        ."A86\tAI21\tIT-109\tIT Elective I - Platform Tech\t2.0\tMemoracion, Janine Salve\t10:30 am-12:30 pm S COMLAB2A\n"
        ."A189\tAI34\tIT-119\tIT Elective IV - Web System\t2.0\tMemoracion, Janine Salve\t5-7 pm W CISCOLAB\n"
        ."A188\tAI34\tIT-119L\tIT Elective IV - Web System\t1.0\tMemoracion, Janine Salve\t5:30-7 pm MTh COMLAB1A\n";

    private User $user;

    private Faculty $faculty;

    private Room $comlab2a;

    private Room $ciscolab;

    private Room $comlab1a;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->faculty()->create();
        $this->faculty = Faculty::factory()->for($this->user)->create(['first_name' => 'Janine', 'last_name' => 'Memoracion']);
        $this->comlab2a = Room::factory()->create(['room_name' => 'COMLAB 2A']);
        $this->ciscolab = Room::factory()->create(['room_name' => 'CISCO LAB']);
        $this->comlab1a = Room::factory()->create(['room_name' => 'COMLAB 1A']);
    }

    public function test_my_schedule_lists_the_faculty_members_classes_and_offers_the_upload(): void
    {
        ClassSession::factory()->create([
            'faculty_id' => $this->faculty->id,
            'subject_id' => Subject::factory()->create(['subject_code' => 'IT-109']),
            'room_id' => $this->comlab2a->id,
            'day' => 'S',
        ]);
        ClassSession::factory()->create(['subject_id' => Subject::factory()->create(['subject_code' => 'XX-999'])]);

        $this->actingAs($this->user)
            ->get(route('faculty.schedule.index'))
            ->assertOk()
            ->assertSee('Upload My Teaching Load')
            ->assertSee('IT-109')
            ->assertSee('Saturday')
            ->assertDontSee('XX-999');
    }

    public function test_the_review_shows_each_class_once_per_day_with_rooms_matched(): void
    {
        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('load.txt', self::CLASS_LIST),
                'mode' => 'add',
            ])
            ->assertOk()
            ->assertSee('Found 4 classes in load.txt')
            ->assertSee('Janine Memoracion')
            ->assertSee('value="IT-119L"', false)
            ->assertSee('value="AI34"', false)
            ->assertSee('value="17:30"', false)
            ->assertSee('<option value="'.$this->comlab1a->id.'" selected>', false)
            ->assertSee('<option value="'.$this->ciscolab->id.'" selected>', false)
            ->assertSee('4 ready');

        $this->assertSame(0, ClassSession::count());
    }

    public function test_saving_assigns_every_class_to_the_signed_in_faculty_member(): void
    {
        $someoneElse = Faculty::factory()->create();

        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.store'), [
                'mode' => 'add',
                'faculty_id' => $someoneElse->id,
                'rows' => [
                    $this->row(['subject_code' => 'IT-109', 'title' => 'IT Elective I - Platform Tech', 'section' => 'AI21', 'day' => 'S', 'start_time' => '10:30', 'end_time' => '12:30', 'room_id' => $this->comlab2a->id]),
                    $this->row(['subject_code' => 'IT-119L', 'title' => 'IT Elective IV - Web System', 'section' => 'AI34', 'day' => 'Th', 'start_time' => '17:30', 'end_time' => '19:00', 'room_id' => $this->comlab1a->id]),
                    $this->row(['subject_code' => 'XX-1', 'title' => 'Unticked', 'import' => null]),
                ],
            ])
            ->assertRedirect(route('faculty.schedule.index'))
            ->assertSessionHas('success', 'Added 2 classes to your schedule (2 new subjects; 2 new sections).');

        $this->assertSame([$this->faculty->id], ClassSession::pluck('faculty_id')->unique()->values()->all());
        $this->assertDatabaseHas('class_sessions', ['day' => 'Th', 'start_time' => '17:30:00', 'end_time' => '19:00:00', 'room_id' => $this->comlab1a->id, 'status' => 'active']);
        $this->assertSame('IT Elective I - Platform Tech', Subject::where('subject_code', 'IT-109')->value('subject_name'));
        $this->assertDatabaseMissing('subjects', ['subject_code' => 'XX-1']);
    }

    public function test_existing_subjects_and_sections_are_reused(): void
    {
        $subject = Subject::factory()->create(['subject_code' => 'IT 109', 'subject_name' => 'Platform Technologies']);
        $section = Section::factory()->create(['section_name' => 'AI21']);

        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.store'), [
                'mode' => 'add',
                'rows' => [$this->row(['subject_code' => 'IT-109', 'title' => '', 'section' => 'ai21'])],
            ])
            ->assertSessionHas('success', 'Added 1 class to your schedule.');

        $this->assertSame(1, Subject::count());
        $this->assertSame(1, Section::count());
        $this->assertDatabaseHas('class_sessions', ['subject_id' => $subject->id, 'section_id' => $section->id]);
    }

    public function test_a_conflicting_class_shows_the_review_again_and_nothing_is_saved(): void
    {
        ClassSession::factory()->create([
            'room_id' => $this->comlab2a->id,
            'day' => 'S',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.store'), [
                'mode' => 'add',
                'rows' => [
                    $this->row(['room_id' => $this->ciscolab->id, 'day' => 'W']),
                    $this->row(['room_id' => $this->comlab2a->id, 'day' => 'S', 'start_time' => '10:30', 'end_time' => '12:30']),
                ],
            ])
            ->assertOk()
            ->assertSee('Some ticked classes still have problems')
            ->assertSee('This room is already booked at that time');

        $this->assertSame(1, ClassSession::count());
    }

    public function test_two_classes_of_the_same_upload_cannot_overlap(): void
    {
        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.store'), [
                'mode' => 'add',
                'rows' => [
                    $this->row(['day' => 'M', 'start_time' => '08:00', 'end_time' => '10:00', 'room_id' => $this->comlab2a->id]),
                    $this->row(['subject_code' => 'IT-110', 'section' => 'AI22', 'day' => 'M', 'start_time' => '09:00', 'end_time' => '11:00', 'room_id' => $this->ciscolab->id]),
                ],
            ])
            ->assertOk()
            ->assertSee('Overlaps row 1');

        $this->assertSame(0, ClassSession::count());
    }

    public function test_a_room_that_was_not_recognised_must_be_chosen(): void
    {
        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('load.txt', "AI21  IT-109  Platform Tech  10:30 am-12:30 pm S NEWLAB9\n"),
                'mode' => 'add',
            ])
            ->assertOk()
            ->assertSee('Choose the room (the load says &quot;NEWLAB9&quot;).', false)
            ->assertSee('1 needs attention');

        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.store'), [
                'mode' => 'add',
                'rows' => [$this->row(['room_id' => null, 'room' => 'NEWLAB9'])],
            ])
            ->assertOk()
            ->assertSee('Some ticked classes still have problems');

        $this->assertSame(0, ClassSession::count());
        $this->assertSame(3, Room::count());
    }

    public function test_classes_already_in_the_schedule_are_unticked_and_skipped(): void
    {
        ClassSession::factory()->create([
            'faculty_id' => $this->faculty->id,
            'subject_id' => Subject::factory()->create(['subject_code' => 'IT-119']),
            'section_id' => Section::factory()->create(['section_name' => 'AI34']),
            'room_id' => $this->ciscolab->id,
            'day' => 'W',
            'start_time' => '17:00:00',
            'end_time' => '19:00:00',
        ]);

        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('load.txt', self::CLASS_LIST),
                'mode' => 'add',
            ])
            ->assertOk()
            ->assertSee('3 ready')
            ->assertSee('1 already in the schedule')
            ->assertSee('Already in schedule');
    }

    public function test_replacing_sets_the_current_classes_to_inactive(): void
    {
        $oldClass = ClassSession::factory()->create([
            'faculty_id' => $this->faculty->id,
            'room_id' => $this->comlab2a->id,
            'day' => 'S',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.store'), [
                'mode' => 'replace',
                'rows' => [$this->row(['room_id' => $this->comlab2a->id, 'day' => 'S', 'start_time' => '10:30', 'end_time' => '12:30'])],
            ])
            ->assertRedirect(route('faculty.schedule.index'));

        $this->assertSame('inactive', $oldClass->fresh()->status);
        $this->assertSame(1, ClassSession::where('status', 'active')->count());
    }

    public function test_excel_files_are_read(): void
    {
        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.preview'), [
                'document' => $this->xlsx([
                    ['Section', 'Subject', 'Description', 'Day', 'Start', 'End', 'Room'],
                    ['AI21', 'IT-109', 'IT Elective I - Platform Tech', 'S', 0.4375, 0.520833333, 'COMLAB2A'],
                ]),
                'mode' => 'add',
            ])
            ->assertOk()
            ->assertSee('Found 1 class in load.xlsx')
            ->assertSee('value="10:30"', false)
            ->assertSee('value="12:30"', false)
            ->assertSee('<option value="'.$this->comlab2a->id.'" selected>', false);
    }

    public function test_csv_files_are_read(): void
    {
        $csv = "Section,Subject,Description,Schedule\nAI34,IT-119,IT Elective IV - Web System,\"5-7 pm W CISCOLAB\"\n";

        $this->actingAs($this->user)
            ->post(route('faculty.schedule.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('load.csv', $csv),
                'mode' => 'add',
            ])
            ->assertOk()
            ->assertSee('value="IT-119"', false)
            ->assertSee('<option value="'.$this->ciscolab->id.'" selected>', false);
    }

    public function test_rooms_misread_by_ocr_are_still_matched(): void
    {
        $review = app(TeachingLoadReview::class);
        $rooms = $review->rooms();

        $this->assertTrue($review->matchRoom('COMLABZA', $rooms)->is($this->comlab2a));
        $this->assertTrue($review->matchRoom('COMLAB?A', $rooms) === null, 'COMLAB?A could be 1A or 2A');
        $this->assertTrue($review->matchRoom('COMLABIA', $rooms)->is($this->comlab1a));
        $this->assertTrue($review->matchRoom('cisco-lab', $rooms)->is($this->ciscolab));
        $this->assertNull($review->matchRoom('TBA', $rooms));
    }

    public function test_an_administrator_uploads_a_load_for_a_chosen_faculty_member(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('schedules.index'))
            ->assertSee('Import Teaching Load');

        $this->actingAs($admin)
            ->post(route('schedules.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('load.txt', self::CLASS_LIST),
                'mode' => 'add',
            ])
            ->assertSessionHasErrors(['faculty_id' => 'Choose whose teaching load this is.']);

        $this->actingAs($admin)
            ->post(route('schedules.import.store'), [
                'mode' => 'add',
                'faculty_id' => $this->faculty->id,
                'rows' => [$this->row()],
            ])
            ->assertRedirect(route('schedules.index', ['search' => 'Memoracion']))
            ->assertSessionHas('success', "Added 1 class to Janine Memoracion's schedule (1 new subject; 1 new section).");

        $this->assertSame($this->faculty->id, ClassSession::sole()->faculty_id);
    }

    public function test_a_faculty_account_without_a_faculty_record_is_told_to_ask_the_administrator(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('faculty.schedule.import.create'))
            ->assertOk()
            ->assertSee("Your account isn't linked to a faculty record yet", false)
            ->assertDontSee('enctype="multipart/form-data"', false);
    }

    public function test_students_and_faculty_cannot_use_each_others_upload(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('faculty.schedule.import.create'))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('schedules.import.create'))
            ->assertForbidden();
    }

    /**
     * A reviewed row as the form submits it, ticked unless overridden.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'import' => '1',
            'subject_code' => 'IT-109',
            'title' => 'IT Elective I - Platform Tech',
            'section' => 'AI21',
            'day' => 'S',
            'start_time' => '10:30',
            'end_time' => '12:30',
            'room_id' => $this->comlab2a->id,
            'room' => 'COMLAB2A',
        ], $overrides);
    }

    /**
     * A minimal Excel workbook: text in shared strings, numbers in cells, and
     * numbers in the Start and End columns formatted as times (style 1).
     *
     * @param  list<list<string|float>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $strings = [];
        $sheetRows = '';

        foreach ($rows as $rowIndex => $row) {
            $cells = '';

            foreach ($row as $columnIndex => $value) {
                $reference = chr(65 + $columnIndex).($rowIndex + 1);

                if (is_string($value)) {
                    $strings[] = $value;
                    $cells .= '<c r="'.$reference.'" t="s"><v>'.(count($strings) - 1).'</v></c>';
                } else {
                    $cells .= '<c r="'.$reference.'" s="1"><v>'.$value.'</v></c>';
                }
            }

            $sheetRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        $namespace = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst '.$namespace.'>'.implode('', array_map(fn ($text) => '<si><t>'.htmlspecialchars($text).'</t></si>', $strings)).'</sst>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0"?><styleSheet '.$namespace.'><numFmts count="1"><numFmt numFmtId="164" formatCode="h:mm AM/PM"/></numFmts><cellXfs count="2"><xf numFmtId="0"/><xf numFmtId="164" applyNumberFormat="1"/></cellXfs></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet '.$namespace.'><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $zip->close();

        return new UploadedFile($path, 'load.xlsx', null, null, true);
    }
}
