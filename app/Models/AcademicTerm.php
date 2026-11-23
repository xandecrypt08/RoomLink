<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A semester of a school year. Class sessions belong to a term; rooms,
 * faculty, subjects and sections are shared by all of them.
 *
 * One term is current: the one running now, which QR scanning, reminders and
 * faculty dashboards follow. Which term someone is looking at is separate
 * (see App\Support\SelectedTerm).
 */
class AcademicTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_year',
        'semester',
        'is_current',
    ];

    protected $casts = [
        'school_year' => 'integer',
        'semester' => 'integer',
        'is_current' => 'boolean',
    ];

    /**
     * @var array<int, string>
     */
    public const SEMESTERS = [
        1 => 'First Semester',
        2 => 'Second Semester',
        3 => 'Summer',
    ];

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * The running term. Until an administrator sets one, it follows the date:
     * first semester from August, second from January, summer in June and July.
     */
    public static function current(): self
    {
        return self::where('is_current', true)->first() ?? self::forDate(now())->makeCurrent();
    }

    private static function forDate(CarbonInterface $date): self
    {
        return self::firstOrCreate([
            'school_year' => $date->month >= 8 ? $date->year : $date->year - 1,
            'semester' => match (true) {
                $date->month >= 8 => 1,
                $date->month <= 5 => 2,
                default => 3,
            },
        ]);
    }

    public function makeCurrent(): self
    {
        DB::transaction(function (): void {
            self::whereKeyNot($this->id)->where('is_current', true)->update(['is_current' => false]);
            $this->update(['is_current' => true]);
        });

        return $this;
    }

    /**
     * e.g. "2026–2027".
     */
    public function getSchoolYearLabelAttribute(): string
    {
        return self::schoolYearLabel($this->school_year);
    }

    public static function schoolYearLabel(int $schoolYear): string
    {
        return $schoolYear.'–'.($schoolYear + 1);
    }

    /**
     * e.g. "First Semester, S.Y. 2026–2027".
     */
    public function getLabelAttribute(): string
    {
        return self::SEMESTERS[$this->semester].', S.Y. '.$this->school_year_label;
    }
}
