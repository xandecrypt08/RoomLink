<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Leyte Normal University's colleges and their degree programs.
 *
 * Safe to run more than once. Existing programs are matched by code
 * regardless of capitals (BACOMM is BAComm), so they keep their subjects and
 * are updated with the official spelling, name, college and group.
 */
class ProgramSeeder extends Seeder
{
    /**
     * College code => [college name, [group => [program code => program name]]].
     * A null group means the program isn't in a group within its college.
     *
     * @var array<string, array{0: string, 1: array<string, array<string, string>>}>
     */
    private const COLLEGES = [
        'COE' => ['College of Education', [
            'Elementary Education Programs' => [
                'BEED' => 'Bachelor of Elementary Education',
                'BECEd' => 'Bachelor of Early Childhood Education',
                'BSNEd' => 'Bachelor of Special Needs Education - Generalist',
                'BTLEd' => 'Bachelor of Technology and Livelihood Education - Major in Home Economics',
            ],
            'Secondary Education Programs' => [
                'BSED' => 'Bachelor of Secondary Education',
                'BPEd' => 'Bachelor of Physical Education',
                'TCP' => 'Teacher Certificate Program',
            ],
        ]],
        'CAS' => ['College of Arts and Sciences', [
            '' => [
                'BAEL' => 'Bachelor of Arts in English Language',
                'BAComm' => 'Bachelor of Arts in Communication',
                'BAPoS' => 'Bachelor of Arts in Political Science',
                'BSSW' => 'Bachelor of Science in Social Work',
                'BSIT' => 'Bachelor of Science in Information Technology',
                'BSBIo' => 'Bachelor of Science in Biology',
                'BLIS' => 'Bachelor of Library and Information Science',
                'BMME' => 'Bachelor of Music in Music Education',
            ],
        ]],
        'CME' => ['College of Management and Entrepreneurship', [
            '' => [
                'BSTM' => 'Bachelor of Science in Tourism Management',
                'BSHM' => 'Bachelor of Science in Hospitality Management',
                'BSEntrep' => 'Bachelor of Science in Entrepreneurship',
            ],
        ]],
    ];

    public function run(): void
    {
        foreach (self::COLLEGES as $collegeCode => [$collegeName, $groups]) {
            $college = College::updateOrCreate(
                ['college_code' => $collegeCode],
                ['college_name' => $collegeName],
            );

            foreach ($groups as $group => $programs) {
                foreach ($programs as $code => $name) {
                    $program = Program::query()
                        ->whereRaw('LOWER(program_code) = ?', [strtolower($code)])
                        ->first() ?? new Program;

                    $program->fill([
                        'college_id' => $college->id,
                        'program_code' => $code,
                        'program_name' => $name,
                        'program_group' => $group !== '' ? $group : null,
                    ])->save();
                }
            }
        }
    }
}
