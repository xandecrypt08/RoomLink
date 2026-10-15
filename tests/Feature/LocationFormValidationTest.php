<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocationFormValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_campus_can_be_created_without_a_code_or_address(): void
    {
        $response = $this->actingAs($this->admin)->post(route('campuses.store'), [
            'campus_name' => 'Main Campus',
        ]);

        $response->assertRedirect(route('campuses.index'));
        $this->assertDatabaseHas('campuses', [
            'campus_name' => 'Main Campus',
            'campus_code' => null,
            'address' => null,
        ]);
    }

    public function test_several_campuses_can_have_no_code(): void
    {
        $this->actingAs($this->admin)->post(route('campuses.store'), ['campus_name' => 'First Campus']);
        $this->actingAs($this->admin)->post(route('campuses.store'), ['campus_name' => 'Second Campus']);

        $this->assertDatabaseCount('campuses', 2);
    }

    public function test_campus_code_must_be_unique(): void
    {
        Campus::factory()->create(['campus_code' => 'MC']);

        $response = $this->actingAs($this->admin)->post(route('campuses.store'), [
            'campus_name' => 'Another Campus',
            'campus_code' => 'MC',
        ]);

        $response->assertSessionHasErrors('campus_code');
        $this->assertDatabaseCount('campuses', 1);
    }

    public function test_campus_can_be_updated_keeping_its_own_code(): void
    {
        $campus = Campus::factory()->create(['campus_code' => 'MC']);

        $response = $this->actingAs($this->admin)->put(route('campuses.update', $campus), [
            'campus_name' => 'Renamed Campus',
            'campus_code' => 'MC',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Renamed Campus', $campus->fresh()->campus_name);
    }

    public function test_campus_cannot_take_another_campus_code_when_updated(): void
    {
        Campus::factory()->create(['campus_code' => 'MC']);
        $campus = Campus::factory()->create(['campus_code' => 'OC']);

        $response = $this->actingAs($this->admin)->put(route('campuses.update', $campus), [
            'campus_name' => $campus->campus_name,
            'campus_code' => 'MC',
        ]);

        $response->assertSessionHasErrors('campus_code');
        $this->assertSame('OC', $campus->fresh()->campus_code);
    }

    public function test_building_can_be_created_without_a_code(): void
    {
        $campus = Campus::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('buildings.store'), [
            'campus_id' => $campus->id,
            'building_name' => 'Science Building',
            'number_of_floors' => 3,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('buildings', [
            'building_name' => 'Science Building',
            'building_code' => null,
        ]);
    }

    public function test_floor_number_is_saved_as_a_number(): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('floors.store'), [
            'building_id' => $building->id,
            'floor_number' => '0',
        ]);

        $response->assertRedirect(route('floors.index'));
        $this->assertDatabaseHas('floors', ['building_id' => $building->id, 'floor_number' => 0]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidFloorNumbers(): array
    {
        return [
            'text' => ['Ground'],
            'negative' => ['-1'],
            'too high' => ['256'],
        ];
    }

    #[DataProvider('invalidFloorNumbers')]
    public function test_floor_number_must_be_a_whole_number_from_0_to_255(string $floorNumber): void
    {
        $building = Building::factory()->create();

        $response = $this->actingAs($this->admin)->post(route('floors.store'), [
            'building_id' => $building->id,
            'floor_number' => $floorNumber,
        ]);

        $response->assertSessionHasErrors('floor_number');
        $this->assertDatabaseCount('floors', 0);
    }
}
