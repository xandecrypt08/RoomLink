<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_code' => 'BS'.fake()->unique()->regexify('[A-Z]{2,4}'),
            'program_name' => 'Bachelor of Science in '.fake()->words(2, true),
        ];
    }
}
