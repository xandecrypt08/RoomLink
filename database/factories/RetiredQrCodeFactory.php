<?php

namespace Database\Factories;

use App\Models\RetiredQrCode;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetiredQrCode>
 */
class RetiredQrCodeFactory extends Factory
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
            'qr_code' => 'ROOM-'.fake()->unique()->regexify('[A-Z0-9]{10}'),
            'retired_at' => now()->subDay(),
        ];
    }
}
