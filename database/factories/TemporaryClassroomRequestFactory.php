<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\TemporaryClassroomRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemporaryClassroomRequest>
 */
class TemporaryClassroomRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => Faculty::factory(),
            'room_id' => Room::factory(),
            'reason' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}
