<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    /**
     * Link a new or edited faculty record to the faculty login account
     * with the same employee ID, if it is not linked yet.
     */
    protected static function booted(): void
    {
        static::saving(function (Faculty $faculty): void {
            if ($faculty->user_id !== null) {
                return;
            }

            $faculty->user_id = User::query()
                ->where('role', 'faculty')
                ->where('employee_student_id', $faculty->employee_id)
                ->whereDoesntHave('faculty')
                ->value('id');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
}
