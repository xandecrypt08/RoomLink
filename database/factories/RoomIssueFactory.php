<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomIssue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomIssue>
 */
class RoomIssueFactory extends Factory
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
            'reported_by' => User::factory()->faculty(),
            'category' => 'furniture',
            'description' => fake()->sentence(),
        ];
    }
}
