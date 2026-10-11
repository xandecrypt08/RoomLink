<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'department',
    ];

    protected $appends = [
        'full_name',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(
            $this->first_name.' '.
            ($this->middle_name ? $this->middle_name.' ' : '').
            $this->last_name
        );
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * The login account of this faculty member. The User account and
     * Faculty record use different tables, so they are matched using
     * employee_id and employee_student_id.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'employee_student_id');
    }

    public function temporaryClassroomRequests()
    {
        return $this->hasMany(TemporaryClassroomRequest::class, 'requester_id');
    }

    public function incomingTemporaryClassroomRequests()
    {
        return $this->hasMany(TemporaryClassroomRequest::class, 'scheduled_faculty_id');
    }
}
