<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'System',
            'middle_name' => null,
            'last_name' => 'Administrator',
            'employee_student_id' => 'ADM001',
            'email' => 'admin@roomlink.edu.ph',
            'department' => 'ICT Office',
            'role' => 'admin',
            'profile_photo' => null,
            'password' => Hash::make('admin123'),
        ]);

        User::create([
            'first_name' => 'Minho',
            'middle_name' => null,
            'last_name' => 'Batumbakal',
            'employee_student_id' => 'FAC001',
            'email' => 'faculty@roomlink.edu.ph',
            'department' => 'College of Computing',
            'role' => 'faculty',
            'profile_photo' => null,
            'password' => Hash::make('faculty123'),
        ]);

        User::create([
            'first_name' => 'Alexander',
            'middle_name' => null,
            'last_name' => 'Diocton',
            'employee_student_id' => '2024-0001',
            'email' => 'student@roomlink.edu.ph',
            'department' => 'BSIT',
            'role' => 'student',
            'profile_photo' => null,
            'password' => Hash::make('student123'),
        ]);
    }
}