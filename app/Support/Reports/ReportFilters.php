<?php

namespace App\Support\Reports;

use App\Models\RoomIssue;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Throwable;

/**
 * What a report covers: a campus, building or room and a range of dates (the
 * last 30 days unless chosen). Values that make no sense are ignored, so an
 * edited address still shows a report.
 */
class ReportFilters
{
    private function __construct(
        public readonly ?int $campusId,
        public readonly ?int $buildingId,
        public readonly ?int $roomId,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly ?string $category,
        public readonly ?string $status,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $to = self::date($request->query('to')) ?? CarbonImmutable::today();
        $from = self::date($request->query('from')) ?? $to->subDays(29);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $category = $request->query('category');
        $status = $request->query('status');

        return new self(
            $request->integer('campus') ?: null,
            $request->integer('building') ?: null,
            $request->integer('room') ?: null,
            $from,
            $to,
            is_string($category) && array_key_exists($category, RoomIssue::CATEGORIES) ? $category : null,
            is_string($status) && array_key_exists($status, RoomIssue::STATUSES) ? $status : null,
        );
    }

    /**
     * Limit a query on rooms to the chosen place.
     */
    public function applyToRooms(mixed $rooms): void
    {
        $rooms
            ->when($this->roomId, fn ($query) => $query->whereKey($this->roomId))
            ->when($this->buildingId, fn ($query) => $query->whereHas('floor', fn ($floor) => $floor->where('building_id', $this->buildingId)))
            ->when($this->campusId, fn ($query) => $query->whereHas('floor.building', fn ($building) => $building->where('campus_id', $this->campusId)));
    }

    /**
     * For file names, e.g. "2026-11-01-to-2026-11-30".
     */
    public function period(): string
    {
        return $this->from->format('Y-m-d').'-to-'.$this->to->format('Y-m-d');
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
