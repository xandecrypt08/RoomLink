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
        'contact_number',
        'password',
        'role',
        'is_active',
        'department',
        'profile_photo',
    ];

    /**
     * Roles an account can have, keyed by the stored value.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'admin' => 'Administrator',
        'faculty' => 'Faculty',
        'student' => 'Student',
    ];

    /**
     * Match the column default so new, unsaved users are active.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
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
            'is_active' => 'boolean',
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
     * Profile fields this user may change on their own profile page.
     *
     * A faculty member's name and employee number identify them in schedules
     * and records, so only the administrator changes those; faculty keep their
     * contact details up to date. Students and administrators can change all
     * of their own details. The role and account status are never self-edited.
     *
     * @return list<string>
     */
    public function editableProfileFields(): array
    {
        if ($this->isFaculty()) {
            return ['email', 'contact_number'];
        }

        return ['first_name', 'middle_name', 'last_name', 'employee_student_id', 'email', 'contact_number', 'department'];
    }

    /**
     * Address of the profile photo, or null when there is none. The photo's
     * file name changes with every upload, so browsers never show an old one.
     */
    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo ? route('profile.photo', ['user' => $this, 'v' => md5($this->profile_photo)]) : null;
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
