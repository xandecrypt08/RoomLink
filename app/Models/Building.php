<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'building_name',
        'building_code',
        'number_of_floors',
        'description',
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    public function rooms()
    {
        return $this->hasManyThrough(Room::class, Floor::class);
    }

    /**
     * Floor numbers from 1 to the building's number of floors that have not
     * been added yet.
     *
     * @return list<int>
     */
    public function missingFloorNumbers(): array
    {
        return array_values(array_diff(
            range(1, max(1, (int) $this->number_of_floors)),
            $this->floors->pluck('floor_number')->map(fn ($number) => (int) $number)->all(),
        ));
    }

    /**
     * Add the missing floors and return how many were added.
     */
    public function createMissingFloors(): int
    {
        $missing = $this->missingFloorNumbers();

        $this->floors()->createMany(array_map(fn (int $number) => ['floor_number' => $number], $missing));
        $this->unsetRelation('floors');

        return count($missing);
    }
}
