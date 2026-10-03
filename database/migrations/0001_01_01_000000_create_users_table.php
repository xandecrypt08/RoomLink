<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Personal Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            // University ID (Faculty ID or Student ID)
            $table->string('employee_student_id')->unique();

            // Login Information
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // User Role
            $table->enum('role', ['admin', 'faculty', 'student']);

            // Additional Information
            $table->string('department')->nullable();
            $table->string('profile_photo')->nullable();

            // Laravel Authentication
            $table->rememberToken();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};