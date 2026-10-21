<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A degree program such as BSIT, BSED or BSBIo, belonging to a college and
 * optionally to a group within it (for example Elementary Education Programs).
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'college_id',
        'program_code',
        'program_name',
        'program_group',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class)->withTimestamps();
    }

    /**
     * Programs in the order they are listed: by college (in the order the
     * colleges were added), group, then code.
     */
    public function scopeInListOrder(Builder $query): void
    {
        // Colleges are listed in the order they were added (by ID), so no join
        // is needed; programs without a college come last.
        $query->orderByRaw('programs.college_id IS NULL')
            ->orderBy('programs.college_id')
            ->orderBy('programs.program_group')
            ->orderBy('programs.program_code');
    }
}
