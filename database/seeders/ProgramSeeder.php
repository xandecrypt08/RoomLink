<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * The degree programs subjects are grouped under. Safe to run more than once.
 */
class ProgramSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const PROGRAMS = [
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSED' => 'Bachelor of Secondary Education',
        'BSBIO' => 'Bachelor of Science in Biology',
    ];

    public function run(): void
    {
        foreach (self::PROGRAMS as $code => $name) {
            Program::firstOrCreate(['program_code' => $code], ['program_name' => $name]);
        }
    }
}
