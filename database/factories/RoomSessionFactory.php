<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\Room;
use App\Models\RoomSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomSession>
 */
class RoomSessionFactory extends Factory
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
            'status' => RoomSession::STATUS_ACTIVE,
            'started_at' => now(),
        ];
    }

    /**
     * A session that has already finished.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RoomSession::STATUS_ENDED,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);
    }
}
