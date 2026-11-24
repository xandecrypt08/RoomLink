<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step in the history of a room issue.
 */
class RoomIssueUpdate extends Model
{
    protected $fillable = [
        'room_issue_id',
        'user_id',
        'status',
        'note',
    ];

    public function roomIssue(): BelongsTo
    {
        return $this->belongsTo(RoomIssue::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
