<?php

namespace Database\Factories;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campus>
 */
class CampusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campus_name' => fake()->city().' Campus',
            'campus_code' => fake()->unique()->bothify('CMP-###'),
            'address' => fake()->address(),
        ];
    }
}
