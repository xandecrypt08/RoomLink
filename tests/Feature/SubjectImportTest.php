<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use App\Support\SubjectImport\DocumentText;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class SubjectImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PROSPECTUS = "LEYTE NORMAL UNIVERSITY\n"
        ."Course No.\tDescriptive Title\tLec\tLab\tUnits\n"
        ."IT-110\tIntroduction to Computing\t2\t3\t3\n"
        ."IT 111\tComputer Programming 1\t2\t3\t3\n"
        ."GE-109\tPurposive Communication\t3\t0\t3\n"
        ."TOTAL\t9\n";

    private User $admin;

    private Program $bsit;

    private Program $bsed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->bsit = Program::factory()->create(['program_code' => 'BSIT']);
        $this->bsed = Program::factory()->create(['program_code' => 'BSED']);
    }

    public function test_upload_page_lists_the_programs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('subjects.import.create'))
            ->assertOk()
            ->assertSee('BSIT')
            ->assertSee('BSED')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_review_shows_the_subjects_found_and_flags_existing_ones(): void
    {
        // Stored with a space; the document writes it with a dash.
        Subject::create(['subject_code' => 'GE 109', 'subject_name' => 'Purposive Communication']);

        $this->actingAs($this->admin)
            ->post(route('subjects.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('prospectus.txt', self::PROSPECTUS),
                'programs' => [$this->bsit->id],
            ])
            ->assertOk()
            ->assertSee('Found 3 subjects in prospectus.txt')
            ->assertSee('value="IT-110"', false)
            ->assertSee('value="Introduction to Computing"', false)
            ->assertSee('value="IT-111"', false)
            ->assertSee('2 new')
            ->assertSee('1 already in RoomLink')
            ->assertDontSee('value="TOTAL"', false);

        $this->assertSame(1, Subject::count());
    }

    public function test_word_documents_are_read(): void
    {
        $this->actingAs($this->admin)
            ->post(route('subjects.import.preview'), [
                'document' => $this->docx([['IT-110', 'Introduction to Computing', '3'], ['GE-109', 'Purposive Communication', '3']]),
                'programs' => [$this->bsit->id],
            ])
            ->assertOk()
            ->assertSee('Found 2 subjects')
            ->assertSee('value="Purposive Communication"', false);
    }

    public function test_importing_creates_new_subjects_and_adds_programs_to_existing_ones(): void
    {
        $existing = Subject::create(['subject_code' => 'GE-109', 'subject_name' => 'Purposive Communication']);
        $existing->programs()->attach($this->bsed);

        $this->actingAs($this->admin)
            ->post(route('subjects.import.store'), [
                'programs' => [$this->bsit->id],
                'rows' => [
                    ['import' => '1', 'code' => 'it 110', 'title' => 'Introduction to Computing'],
                    ['import' => '1', 'code' => 'GE-109', 'title' => 'Purposive Communication'],
                    ['code' => 'XX-999', 'title' => 'Unticked row'],
                ],
            ])
            ->assertRedirect(route('subjects.index', ['program' => $this->bsit->id]))
            ->assertSessionHas('success', 'Imported 1 new subject for BSIT; added BSIT to 1 existing subject.');

        $created = Subject::where('subject_code', 'IT-110')->firstOrFail();

        $this->assertSame(['BSIT'], $created->programs->pluck('program_code')->all());
        $this->assertSame(['BSED', 'BSIT'], $existing->fresh()->programs->pluck('program_code')->sort()->values()->all());
        $this->assertDatabaseMissing('subjects', ['subject_code' => 'XX-999']);
        $this->assertSame(2, Subject::count());
    }

    public function test_a_selected_row_without_a_code_shows_the_review_again_with_the_error(): void
    {
        $this->actingAs($this->admin)
            ->post(route('subjects.import.store'), [
                'programs' => [$this->bsit->id],
                'rows' => [
                    ['import' => '1', 'code' => '', 'title' => 'Introduction to Computing'],
                ],
            ])
            ->assertOk()
            ->assertSee('Every selected row needs a Course No.')
            ->assertSee('value="Introduction to Computing"', false);

        $this->assertSame(0, Subject::count());
    }

    public function test_a_program_must_be_chosen(): void
    {
        $this->actingAs($this->admin)
            ->post(route('subjects.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('prospectus.txt', self::PROSPECTUS),
            ])
            ->assertSessionHasErrors(['programs' => 'Choose at least one program for these subjects.']);
    }

    public function test_unsupported_files_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('subjects.import.preview'), [
                'document' => UploadedFile::fake()->create('prospectus.xlsx', 10),
                'programs' => [$this->bsit->id],
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_a_document_without_course_codes_explains_what_was_expected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('subjects.import.preview'), [
                'document' => UploadedFile::fake()->createWithContent('letter.txt', "Dear students,\nWelcome to the new semester."),
                'programs' => [$this->bsit->id],
            ])
            ->assertSessionHasErrors('document');

        $this->assertStringContainsString('No course codes were found', session('errors')->first('document'));
    }

    public function test_photos_explain_when_tesseract_is_not_installed(): void
    {
        config(['services.tesseract.path' => 'C:/no-such-folder/tesseract.exe']);

        $this->actingAs($this->admin)
            ->post(route('subjects.import.preview'), [
                'document' => UploadedFile::fake()->image('page.png', 400, 300),
                'programs' => [$this->bsit->id],
            ])
            ->assertSessionHasErrors(['document' => DocumentText::tesseractMissingMessage()]);
    }

    public function test_only_administrators_can_import(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('subjects.import.create'))
            ->assertForbidden();
    }

    /**
     * A minimal Word document containing a table with the given rows.
     *
     * @param  list<list<string>>  $rows
     */
    private function docx(array $rows): UploadedFile
    {
        $cells = fn (array $row) => implode('', array_map(fn ($cell) => '<w:tc><w:p><w:r><w:t>'.htmlspecialchars($cell).'</w:t></w:r></w:p></w:tc>', $row));
        $table = '<w:tbl>'.implode('', array_map(fn ($row) => '<w:tr>'.$cells($row).'</w:tr>', $rows)).'</w:tbl>';

        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$table.'</w:body></w:document>');
        $zip->close();

        return new UploadedFile($path, 'prospectus.docx', null, null, true);
    }
}
