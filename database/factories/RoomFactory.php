<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'floor_id' => Floor::factory(),
            'room_name' => fake()->unique()->bothify('Room ###'),
            'capacity' => 40,
            'status' => 'available',
        ];
    }
}
