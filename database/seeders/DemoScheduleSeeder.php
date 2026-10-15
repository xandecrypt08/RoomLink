<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Campus;
use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Floor;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo timetable for local testing: 5 faculty members, 5 rooms, 5 subjects
 * and 5 sections, with 5 classes on every weekday (25 class sessions).
 *
 * Each room holds one class a day and each faculty member and section has
 * one class a day, so the schedule never conflicts. Classes overlap in time
 * so several rooms are in use at once between 7:30 AM and 5:00 PM.
 *
 * Safe to run more than once: records are matched and updated, not duplicated.
 *
 * Run with: php artisan db:seed --class=DemoScheduleSeeder
 */
class DemoScheduleSeeder extends Seeder
{
    /**
     * Password for every demo faculty account.
     */
    public const PASSWORD = 'password123';

    /**
     * @var list<array{first_name: string, last_name: string, employee_id: string, email: string}>
     */
    private const FACULTY = [
        ['first_name' => 'Maria', 'last_name' => 'Santos', 'employee_id' => 'DEMO-F01', 'email' => 'demo.faculty1@roomlink.edu.ph'],
        ['first_name' => 'Jose', 'last_name' => 'Reyes', 'employee_id' => 'DEMO-F02', 'email' => 'demo.faculty2@roomlink.edu.ph'],
        ['first_name' => 'Ana', 'last_name' => 'Cruz', 'employee_id' => 'DEMO-F03', 'email' => 'demo.faculty3@roomlink.edu.ph'],
        ['first_name' => 'Ramon', 'last_name' => 'Garcia', 'employee_id' => 'DEMO-F04', 'email' => 'demo.faculty4@roomlink.edu.ph'],
        ['first_name' => 'Liza', 'last_name' => 'Mendoza', 'employee_id' => 'DEMO-F05', 'email' => 'demo.faculty5@roomlink.edu.ph'],
    ];

    /**
     * Faculty member N teaches subject N.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const SUBJECTS = [
        ['IT 101', 'Introduction to Computing'],
        ['IT 102', 'Computer Programming 1'],
        ['IT 201', 'Data Structures and Algorithms'],
        ['IT 202', 'Web Development'],
        ['IT 301', 'Database Systems'],
    ];

    /**
     * @var list<string>
     */
    private const SECTIONS = ['BSIT 1-A', 'BSIT 1-B', 'BSIT 2-A', 'BSIT 2-B', 'BSIT 3-A'];

    /**
     * Daily time slots; slot N is always held in room N.
     *
     * @var list<array{0: string, 1: string}>
     */
    private const SLOTS = [
        ['07:30', '10:00'],
        ['08:30', '11:00'],
        ['10:00', '12:30'],
        ['13:00', '15:30'],
        ['14:30', '17:00'],
    ];

    /**
     * @var list<string>
     */
    private const WEEKDAYS = ['M', 'T', 'W', 'Th', 'F'];

    public function run(): void
    {
        $faculty = $this->seedFaculty();
        $rooms = $this->seedRooms();

        $subjects = collect(self::SUBJECTS)->map(fn (array $subject): Subject => Subject::updateOrCreate(
            ['subject_code' => $subject[0]],
            ['subject_name' => $subject[1]],
        ));

        $sections = collect(self::SECTIONS)->map(fn (string $name): Section => Section::firstOrCreate(
            ['section_name' => $name],
        ));

        foreach (self::WEEKDAYS as $dayIndex => $day) {
            foreach (self::SLOTS as $slot => [$startTime, $endTime]) {
                // Rotate who teaches which slot each day, so every faculty
                // member and section has exactly one class per day.
                $teacher = ($slot + $dayIndex) % 5;
                $section = ($slot + 2 * $dayIndex) % 5;

                ClassSession::updateOrCreate(
                    [
                        'room_id' => $rooms[$slot]->id,
                        'day' => $day,
                        'start_time' => $startTime,
                    ],
                    [
                        'faculty_id' => $faculty[$teacher]->id,
                        'subject_id' => $subjects[$teacher]->id,
                        'section_id' => $sections[$section]->id,
                        'end_time' => $endTime,
                        'status' => 'active',
                        'description' => 'Demo class',
                    ],
                );
            }
        }
    }

    /**
     * Faculty records, each with a login.
     *
     * @return list<Faculty>
     */
    private function seedFaculty(): array
    {
        return collect(self::FACULTY)->map(function (array $person): Faculty {
            $user = User::updateOrCreate(
                ['email' => $person['email']],
                [
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'employee_student_id' => $person['employee_id'],
                    'department' => 'College of Computing',
                    'role' => 'faculty',
                    'is_active' => true,
                    'password' => Hash::make(self::PASSWORD),
                ],
            );

            return Faculty::updateOrCreate(
                ['employee_id' => $person['employee_id']],
                [
                    'user_id' => $user->id,
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'email' => $person['email'],
                    'department' => 'College of Computing',
                ],
            );
        })->all();
    }

    /**
     * Five rooms on the third floor of a demo building.
     *
     * @return list<Room>
     */
    private function seedRooms(): array
    {
        $campus = Campus::query()->first()
            ?? Campus::create(['campus_name' => 'Main Campus', 'campus_code' => 'MC']);

        $building = Building::firstOrCreate(
            ['building_name' => 'Academic Building C'],
            ['campus_id' => $campus->id, 'building_code' => 'ABC', 'number_of_floors' => 3],
        );

        $floor = Floor::firstOrCreate(
            ['building_id' => $building->id, 'floor_number' => 3],
        );

        return collect(range(1, 5))->map(fn (int $number): Room => Room::firstOrCreate(
            ['floor_id' => $floor->id, 'room_name' => 'ACAD 30'.$number],
            ['capacity' => 40, 'status' => 'available'],
        ))->all();
    }
}
