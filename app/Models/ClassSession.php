<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Convert a date to the day code stored in the day column
     * (M, T, W, Th, F, S, Su).
     */
    public static function dayCodeFor(CarbonInterface $date): string
    {
        return [
            'Mon' => 'M',
            'Tue' => 'T',
            'Wed' => 'W',
            'Thu' => 'Th',
            'Fri' => 'F',
            'Sat' => 'S',
            'Sun' => 'Su',
        ][$date->format('D')];
    }

    /**
     * Determine if the session is running at the given moment's time of day.
     */
    public function isOngoingAt(CarbonInterface $moment): bool
    {
        $time = $moment->format('H:i:s');

        return $this->start_time->format('H:i:s') <= $time
            && $this->end_time->format('H:i:s') > $time;
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
