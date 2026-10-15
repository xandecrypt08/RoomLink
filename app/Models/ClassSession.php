<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Full day names keyed by the codes stored in the day column.
     *
     * @var array<string, string>
     */
    public const DAY_NAMES = [
        'M' => 'Monday',
        'T' => 'Tuesday',
        'W' => 'Wednesday',
        'Th' => 'Thursday',
        'F' => 'Friday',
        'S' => 'Saturday',
        'Su' => 'Sunday',
    ];

    /**
     * The full day name for display, e.g. "Monday" for "M".
     */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day] ?? (string) $this->day;
    }

    /**
     * Order sessions Monday through Sunday, then by start time.
     *
     * Uses a CASE expression so it works on both MySQL and SQLite.
     */
    public function scopeOrderedByWeek(Builder $query): void
    {
        $cases = collect(array_keys(self::DAY_NAMES))
            ->map(fn (string $code, int $position): string => "WHEN '{$code}' THEN {$position}")
            ->implode(' ');

        $query->orderByRaw("CASE day {$cases} ELSE 99 END")
            ->orderBy('start_time');
    }

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
