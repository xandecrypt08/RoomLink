<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_name',
        'campus_code',
        'address',
        'description',
    ];

    public function buildings()
    {
        return $this->hasMany(Building::class);
    }
}