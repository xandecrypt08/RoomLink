<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'employee_student_id',
        'email',
        'password',
        'role',
        'department',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Display full name anywhere in the system
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * The faculty record (schedules, department) for a faculty account.
     */
    public function faculty(): HasOne
    {
        return $this->hasOne(Faculty::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFaculty(): bool
    {
        return $this->role === 'faculty';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Name of the dashboard route for this user's role, or null if the role is not recognised.
     */
    public function dashboardRoute(): ?string
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'faculty' => 'faculty.dashboard',
            'student' => 'student.dashboard',
            default => null,
        };
    }
}
