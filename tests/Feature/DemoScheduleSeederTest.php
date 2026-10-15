<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\User;
use Database\Seeders\DemoScheduleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoScheduleSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_creates_five_classes_on_each_weekday(): void
    {
        $this->seed(DemoScheduleSeeder::class);

        $this->assertSame(25, ClassSession::count());

        foreach (['M', 'T', 'W', 'Th', 'F'] as $day) {
            $this->assertSame(5, ClassSession::where('day', $day)->count(), "Expected 5 classes on {$day}.");
        }
    }

    public function test_running_it_again_does_not_duplicate_anything(): void
    {
        $this->seed(DemoScheduleSeeder::class);
        $this->seed(DemoScheduleSeeder::class);

        $this->assertSame(25, ClassSession::count());
        $this->assertSame(5, User::where('email', 'like', 'demo.faculty%')->count());
    }

    public function test_no_room_faculty_or_section_is_double_booked(): void
    {
        $this->seed(DemoScheduleSeeder::class);

        $sessions = ClassSession::all();

        foreach ($sessions as $session) {
            $overlapping = $sessions->filter(fn (ClassSession $other) => $other->isNot($session)
                && $other->day === $session->day
                && $other->start_time->format('H:i') < $session->end_time->format('H:i')
                && $other->end_time->format('H:i') > $session->start_time->format('H:i'));

            foreach (['room_id', 'faculty_id', 'section_id'] as $column) {
                $this->assertFalse(
                    $overlapping->contains($column, $session->{$column}),
                    "Class session {$session->id} overlaps another with the same {$column}."
                );
            }
        }
    }

    public function test_demo_faculty_can_log_in_and_are_linked_to_their_faculty_record(): void
    {
        $this->seed(DemoScheduleSeeder::class);

        $user = User::where('email', 'demo.faculty1@roomlink.edu.ph')->firstOrFail();

        $this->assertTrue(Hash::check(DemoScheduleSeeder::PASSWORD, $user->password));
        $this->assertNotNull($user->faculty);
        $this->assertSame(5, $user->faculty->classSessions()->count());
    }
}
