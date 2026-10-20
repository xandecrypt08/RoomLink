<?php

namespace Tests\Feature;

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

    public function test_seeder_adds_bsit_bsed_and_bsbio_once(): void
    {
        $this->seed(ProgramSeeder::class);
        $this->seed(ProgramSeeder::class);

        $this->assertSame(['BSBIO', 'BSED', 'BSIT'], Program::orderBy('program_code')->pluck('program_code')->all());
    }

    public function test_admin_can_add_a_program_and_the_code_is_capitalised(): void
    {
        $this->actingAs($this->admin)
            ->post(route('programs.store'), ['program_code' => ' bsit ', 'program_name' => 'Bachelor of Science in Information Technology'])
            ->assertRedirect(route('programs.index'));

        $this->assertDatabaseHas('programs', ['program_code' => 'BSIT']);
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
