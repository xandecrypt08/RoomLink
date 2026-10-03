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
}