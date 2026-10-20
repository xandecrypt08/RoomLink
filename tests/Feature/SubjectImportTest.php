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

    public function test_every_page_of_a_scanned_pdf_is_read_with_ocr(): void
    {
        config(['services.tesseract.path' => $this->fakeTesseract("PATHFIT 1    Movement Competency Training    2    0    2\nNSTP-CWTS 1    Civic Welfare Training Service 1    3    0    3")]);

        $extracted = app(DocumentText::class)->extract($this->scannedPdf(pages: 2));

        $this->assertSame(2, $extracted->pages);
        $this->assertTrue($extracted->usedOcr);
        $this->assertSame(2, substr_count($extracted->text, 'PATHFIT 1'));
    }

    public function test_review_of_a_scanned_pdf_warns_that_it_was_read_from_pictures(): void
    {
        config(['services.tesseract.path' => $this->fakeTesseract("PATHFIT 1    Movement Competency Training    2    0    2\nNSTP-CWTS 1    Civic Welfare Training Service 1    3    0    3")]);

        $this->actingAs($this->admin)
            ->post(route('subjects.import.preview'), [
                'document' => $this->scannedPdf(pages: 2),
                'programs' => [$this->bsit->id],
            ])
            ->assertOk()
            ->assertSee('Found 2 subjects in scanned.pdf (2 pages)')
            ->assertSee('value="PATHFIT-1"', false)
            ->assertSee('value="NSTP-CWTS-1"', false)
            ->assertSee('read from pictures');
    }

    public function test_only_administrators_can_import(): void
    {
        $this->actingAs(User::factory()->faculty()->create())
            ->get(route('subjects.import.create'))
            ->assertForbidden();
    }

    /**
     * A PDF whose pages are only pictures (one small JPEG each), like a scan.
     * Built by hand with a correct cross-reference table.
     */
    private function scannedPdf(int $pages): UploadedFile
    {
        $image = imagecreatetruecolor(40, 20);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();

        $kids = implode(' ', array_map(fn ($page) => (3 + $page * 3).' 0 R', range(0, $pages - 1)));
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 2 => "<< /Type /Pages /Kids [{$kids}] /Count {$pages} >>"];

        foreach (range(0, $pages - 1) as $page) {
            $pageId = 3 + $page * 3;
            $drawing = 'q 200 0 0 100 0 0 cm /Im1 Do Q';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 100] /Resources << /XObject << /Im1 '.($pageId + 1).' 0 R >> >> /Contents '.($pageId + 2).' 0 R >>';
            $objects[$pageId + 1] = '<< /Type /XObject /Subtype /Image /Width 40 /Height 20 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n{$jpeg}\nendstream";
            $objects[$pageId + 2] = '<< /Length '.strlen($drawing)." >>\nstream\n{$drawing}\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(max(array_keys($objects)) + 1)."\n0000000000 65535 f \n";

        foreach (range(1, max(array_keys($objects))) as $id) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }

        $pdf .= 'trailer << /Size '.(max(array_keys($objects)) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return UploadedFile::fake()->createWithContent('scanned.pdf', $pdf);
    }

    /**
     * A stand-in for Tesseract that prints the given text for any image.
     */
    private function fakeTesseract(string $output): string
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fake-tesseract-'.uniqid();
        mkdir($directory);

        if (PHP_OS_FAMILY === 'Windows') {
            $path = $directory.DIRECTORY_SEPARATOR.'tesseract.bat';
            file_put_contents($path, "@echo off\r\n".implode("\r\n", array_map(fn ($line) => 'echo '.$line, explode("\n", $output)))."\r\n");
        } else {
            $path = $directory.'/tesseract';
            file_put_contents($path, "#!/bin/sh\ncat <<'EOF'\n{$output}\nEOF\n");
            chmod($path, 0755);
        }

        return $path;
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
