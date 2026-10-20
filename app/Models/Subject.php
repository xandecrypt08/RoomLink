<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_code',
        'subject_name',
        'description',
    ];

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * Programs that take this subject. A GE subject usually belongs to all of them.
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withTimestamps();
    }

    /**
     * A course code in one standard form, with its parts joined by dashes, so
     * "it 110", "IT110" and "IT-110" are the same subject, as are "PathFit 1"
     * and "PATHFIT-1", or "NSTP CWTS 1" and "NSTP-CWTS-1".
     */
    public static function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));

        if (preg_match('/^([A-Z]{2,8}(?:[\s\-–—]+[A-Z]{2,8})?)\s*[-–—.]?\s*(\d{1,4}[A-Z]?|IV|V?I{1,3})$/u', $code, $match)) {
            return preg_replace('/[\s\-–—]+/u', '-', $match[1]).'-'.$match[2];
        }

        return preg_replace('/\s+/', ' ', $code);
    }
}
