<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'faculty_id' => Faculty::factory(),
            'subject_id' => Subject::factory(),
            'section_id' => Section::factory(),
            'day' => 'M',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'status' => 'active',
        ];
    }
}
