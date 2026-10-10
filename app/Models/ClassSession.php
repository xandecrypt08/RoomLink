<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use HasFactory;

    protected $table = 'class_sessions';

    protected $fillable = [
        'room_id',
        'faculty_id',
        'subject_id',
        'section_id',
        'day',
        'start_time',
        'end_time',
        'status',
        'description',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function roomSessions(): HasMany
    {
        return $this->hasMany(RoomSession::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
