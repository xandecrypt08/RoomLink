<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProgramManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_seeder_adds_the_colleges_and_their_programs_once(): void
    {
        $this->seed(ProgramSeeder::class);
        $this->seed(ProgramSeeder::class);

        $this->assertSame(['COE', 'CAS', 'CME'], College::orderBy('id')->pluck('college_code')->all());
        $this->assertSame(18, Program::count());

        $coe = College::where('college_code', 'COE')->first();
        $this->assertSame(7, $coe->programs()->count());
        $this->assertSame('Elementary Education Programs', Program::where('program_code', 'BECEd')->value('program_group'));
        $this->assertSame('Secondary Education Programs', Program::where('program_code', 'BPEd')->value('program_group'));
        $this->assertSame('CAS', Program::where('program_code', 'BSIT')->first()->college->college_code);
        $this->assertSame('CME', Program::where('program_code', 'BSEntrep')->first()->college->college_code);
    }

    public function test_seeder_updates_existing_programs_whatever_their_capitals(): void
    {
        $existing = Program::factory()->create(['program_code' => 'BACOMM', 'program_name' => 'Comm']);
        $existing->subjects()->attach(Subject::create(['subject_code' => 'COMM-101', 'subject_name' => 'Media and Society']));

        $this->seed(ProgramSeeder::class);

        $existing->refresh();

        $this->assertSame('BAComm', $existing->program_code);
        $this->assertSame('Bachelor of Arts in Communication', $existing->program_name);
        $this->assertSame('CAS', $existing->college->college_code);
        $this->assertSame(1, $existing->subjects()->count());
        $this->assertSame(18, Program::count());
    }

    public function test_admin_can_add_a_program_to_a_college_keeping_the_code_as_written(): void
    {
        $college = College::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('programs.store'), [
                'college_id' => $college->id,
                'program_code' => ' BAComm ',
                'program_name' => 'Bachelor of Arts in Communication',
                'program_group' => '',
            ])
            ->assertRedirect(route('programs.index'));

        $this->assertDatabaseHas('programs', ['program_code' => 'BAComm', 'college_id' => $college->id, 'program_group' => null]);
    }

    public function test_programs_are_listed_under_their_college_and_group(): void
    {
        $this->seed(ProgramSeeder::class);

        $this->actingAs($this->admin)
            ->get(route('programs.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'College of Education', 'Elementary Education Programs', 'Bachelor of Elementary Education',
                'Secondary Education Programs', 'Bachelor of Secondary Education',
                'College of Arts and Sciences', 'Bachelor of Science in Information Technology',
                'College of Management and Entrepreneurship', 'Bachelor of Science in Tourism Management',
            ]);
    }

    public function test_program_list_shows_how_many_subjects_each_program_has(): void
    {
        $this->seed(ProgramSeeder::class);
        $bsit = Program::where('program_code', 'BSIT')->first();
        $bsit->subjects()->attach([
            Subject::create(['subject_code' => 'IT-110', 'subject_name' => 'Introduction to Computing'])->id,
            Subject::create(['subject_code' => 'IT-111', 'subject_name' => 'Computer Programming 1'])->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('programs.index'))
            ->assertOk()
            ->assertSeeInOrder(['Bachelor of Science in Information Technology', 'BSIT', '2']);

        $this->assertSame(2, Program::withCount('subjects')->inListOrder()->get()->firstWhere('id', $bsit->id)->subjects_count);
    }

    public function test_program_checkboxes_are_grouped_by_college(): void
    {
        $this->seed(ProgramSeeder::class);

        $this->actingAs($this->admin)
            ->get(route('subjects.create'))
            ->assertOk()
            ->assertSeeInOrder(['College of Education (COE)', 'BEED', 'College of Arts and Sciences (CAS)', 'BSIT', 'College of Management and Entrepreneurship (CME)', 'BSTM']);
    }

    public function test_college_with_programs_cannot_be_deleted(): void
    {
        $program = Program::factory()->for(College::factory())->create();

        $this->actingAs($this->admin)
            ->delete(route('colleges.destroy', $program->college))
            ->assertSessionHas('error');

        $this->assertModelExists($program->college);
    }

    public function test_admin_can_add_rename_and_delete_an_empty_college(): void
    {
        $this->actingAs($this->admin)
            ->post(route('colleges.store'), ['college_code' => 'CTE', 'college_name' => 'College of Teacher Education'])
            ->assertRedirect(route('colleges.index'));

        $college = College::where('college_code', 'CTE')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('colleges.update', $college), ['college_code' => 'CTE', 'college_name' => 'College of Technology'])
            ->assertRedirect(route('colleges.index'));

        $this->assertSame('College of Technology', $college->fresh()->college_name);

        $this->actingAs($this->admin)
            ->post(route('colleges.store'), ['college_code' => 'cte', 'college_name' => 'Duplicate'])
            ->assertSessionHasErrors('college_code');

        $this->actingAs($this->admin)
            ->delete(route('colleges.destroy', $college))
            ->assertSessionHas('success');

        $this->assertModelMissing($college);
    }

    public function test_program_codes_are_unique(): void
    {
        Program::factory()->create(['program_code' => 'BSIT']);

        $this->actingAs($this->admin)
            ->post(route('programs.store'), ['program_code' => 'bsit', 'program_name' => 'Duplicate'])
            ->assertSessionHasErrors('program_code');
    }

    public function test_program_with_subjects_cannot_be_deleted(): void
    {
        $program = Program::factory()->create();
        Subject::create(['subject_code' => 'IT-110', 'subject_name' => 'Introduction to Computing'])->programs()->attach($program);

        $this->actingAs($this->admin)
            ->delete(route('programs.destroy', $program))
            ->assertSessionHas('error');

        $this->assertModelExists($program);
    }

    public function test_empty_program_can_be_deleted(): void
    {
        $program = Program::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('programs.destroy', $program))
            ->assertSessionHas('success');

        $this->assertModelMissing($program);
    }

    public function test_subjects_are_saved_with_their_programs_and_can_be_filtered(): void
    {
        $bsit = Program::factory()->create(['program_code' => 'BSIT']);
        $bsed = Program::factory()->create(['program_code' => 'BSED']);

        $this->actingAs($this->admin)
            ->post(route('subjects.store'), [
                'subject_code' => 'GE-109',
                'subject_name' => 'Purposive Communication',
                'programs' => [$bsit->id, $bsed->id],
            ])
            ->assertRedirect(route('subjects.index'));

        $this->actingAs($this->admin)
            ->post(route('subjects.store'), [
                'subject_code' => 'IT-110',
                'subject_name' => 'Introduction to Computing',
                'programs' => [$bsit->id],
            ]);

        $this->assertSame(2, Subject::where('subject_code', 'GE-109')->first()->programs()->count());

        $this->actingAs($this->admin)
            ->get(route('subjects.index', ['program' => $bsed->id]))
            ->assertOk()
            ->assertSee('Purposive Communication')
            ->assertDontSee('Introduction to Computing');
    }

    public function test_editing_a_subject_replaces_its_programs(): void
    {
        $bsit = Program::factory()->create();
        $bsed = Program::factory()->create();
        $subject = Subject::create(['subject_code' => 'GE-109', 'subject_name' => 'Purposive Communication']);
        $subject->programs()->attach($bsit);

        $this->actingAs($this->admin)
            ->put(route('subjects.update', $subject), [
                'subject_code' => 'GE-109',
                'subject_name' => 'Purposive Communication',
                'programs' => [$bsed->id],
            ]);

        $this->assertSame([$bsed->id], $subject->fresh()->programs->pluck('id')->all());
    }
}
