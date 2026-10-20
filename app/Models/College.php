<?php

namespace App\Models;

use Database\Factories\CollegeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A college such as the College of Education (COE), grouping degree programs.
 */
class College extends Model
{
    /** @use HasFactory<CollegeFactory> */
    use HasFactory;

    protected $fillable = [
        'college_code',
        'college_name',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
