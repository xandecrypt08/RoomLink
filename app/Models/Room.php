<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'floor_id',
        'room_name',
        'qr_code',
        'capacity',
        'status',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            if (empty($room->qr_code)) {
                do {
                    $code = 'ROOM-' . strtoupper(Str::random(10));
                } while (self::where('qr_code', $code)->exists());

                $room->qr_code = $code;
            }
        });
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class);
    }

    public function classSessions()
    {
        return $this->hasMany(ClassSession::class);
    }
}