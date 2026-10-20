<?php

namespace Database\Factories;

use App\Models\College;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<College>
 */
class CollegeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'college_code' => 'C'.fake()->unique()->regexify('[A-Z]{2,4}'),
            'college_name' => 'College of '.fake()->words(2, true),
        ];
    }
}
