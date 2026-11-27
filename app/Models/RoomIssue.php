<?php

namespace App\Models;

use App\Exceptions\RoomIssueException;
use App\Notifications\RoomIssueUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * A classroom problem reported by a faculty member (RLNK-31, RLNK-32) and
 * followed by the administrator until it is resolved (RLNK-33).
 */
class RoomIssue extends Model
{
    use HasFactory;

    public const CATEGORY_QR = 'qr_code';

    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        'qr_code' => 'Damaged QR Code',
        'electrical' => 'Electrical or Lighting',
        'aircon' => 'Air Conditioning or Fan',
        'equipment' => 'Equipment (projector, computer, TV)',
        'furniture' => 'Furniture',
        'internet' => 'Internet or Network',
        'cleanliness' => 'Cleanliness',
        'structural' => 'Doors, Windows, Walls or Leaks',
        'other' => 'Other',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    /**
     * In the order an issue goes through them.
     *
     * @var array<string, string>
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'acknowledged' => 'Acknowledged',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
    ];

    protected $fillable = [
        'room_id',
        'reported_by',
        'category',
        'description',
        'photo',
        'qr_replacement_requested',
        'status',
        'assigned_to',
        'resolved_at',
    ];

    /**
     * Match the column default so new, unsaved issues are pending.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'qr_replacement_requested' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (RoomIssue $issue): void {
            $issue->updates()->create(['user_id' => $issue->reported_by, 'status' => $issue->status]);
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(RoomIssueUpdate::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(RoomMaintenance::class);
    }

    /**
     * Issues that still need work.
     *
     * @param  Builder<RoomIssue>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_RESOLVED);
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isQrIssue(): bool
    {
        return $this->category === self::CATEGORY_QR;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * The status that comes after the current one, or null when resolved.
     */
    public function nextStatus(): ?string
    {
        $order = array_keys(self::STATUSES);

        return $order[array_search($this->status, $order, true) + 1] ?? null;
    }

    /**
     * Move to the next status, record it in the history and tell the reporter.
     *
     * @throws RoomIssueException when $status is not the next one, including
     *                            when someone else has just moved the issue.
     */
    public function moveTo(string $status, User $user, ?string $note = null, ?string $assignedTo = null): void
    {
        DB::transaction(function () use ($status, $user, $note, $assignedTo): void {
            $current = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($status !== $current->nextStatus()) {
                throw RoomIssueException::notNext($current);
            }

            $current->update([
                'status' => $status,
                'assigned_to' => $assignedTo ?? $current->assigned_to,
                'resolved_at' => $status === self::STATUS_RESOLVED ? now() : null,
            ]);

            $current->updates()->create(['user_id' => $user->id, 'status' => $status, 'note' => $note]);
        });

        $this->refresh();

        if (! $this->reporter->is($user)) {
            $this->reporter->notify(new RoomIssueUpdated($this, $note));
        }
    }
}
