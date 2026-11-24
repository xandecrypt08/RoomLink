<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Campus;
use App\Models\Room;
use App\Models\RoomIssue;
use App\Models\RoomMaintenance;
use App\Models\RoomSession;
use App\Support\Reports\CsvExport;
use App\Support\Reports\ReportFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Classroom utilization (RLNK-35) and room issue and maintenance reports
 * (RLNK-36), on screen and as CSV files that open in Excel.
 */
class ReportController extends Controller
{
    public function utilization(Request $request): View|StreamedResponse
    {
        $filters = ReportFilters::fromRequest($request);

        $sessions = $this->inPeriod(RoomSession::query(), $filters, 'started_at')
            ->where('status', '!=', RoomSession::STATUS_NOT_HELD);

        if ($request->boolean('export')) {
            return CsvExport::download(
                "classroom-utilization-{$filters->period()}.csv",
                ['Campus', 'Building', 'Classroom', 'Faculty', 'Subject', 'Descriptive Title', 'Section', 'Started', 'Ended', 'Duration (minutes)'],
                (clone $sessions)->with(self::SESSION_DETAILS)->orderBy('started_at')->lazy()->map(fn (RoomSession $session): array => [
                    $session->room->floor?->building?->campus?->campus_name,
                    $session->room->floor?->building?->building_name,
                    $session->room->room_name,
                    $session->faculty->full_name,
                    $session->classSession?->subject?->subject_code ?? 'Temporary session',
                    $session->classSession?->subject?->subject_name,
                    $session->classSession?->section?->section_name,
                    $session->started_at->format('Y-m-d H:i'),
                    $session->ended_at?->format('Y-m-d H:i') ?? 'In session',
                    $session->durationMinutes(),
                ]),
            );
        }

        $minutesByRoom = (clone $sessions)->get(['id', 'room_id', 'status', 'started_at', 'ended_at'])
            ->groupBy('room_id')
            ->map(fn ($roomSessions) => ['sessions' => $roomSessions->count(), 'minutes' => $roomSessions->sum(fn (RoomSession $session) => $session->durationMinutes())])
            ->sortByDesc('minutes');

        return view('admin.reports.utilization', [
            ...$this->filterOptions($filters),
            'sessions' => $sessions->with(self::SESSION_DETAILS)->latest('started_at')->paginate($this->rowsPerPage($request))->withQueryString(),
            'minutesByRoom' => $minutesByRoom,
            'roomsById' => Room::with('floor.building')->findMany($minutesByRoom->keys())->keyBy('id'),
        ]);
    }

    public function maintenance(Request $request): View|StreamedResponse
    {
        $filters = ReportFilters::fromRequest($request);
        $type = $request->input('type') === 'maintenance' ? 'maintenance' : 'issues';

        $records = $type === 'issues' ? $this->issues($filters) : $this->maintenances($filters);

        if ($request->boolean('export')) {
            return $type === 'issues' ? $this->exportIssues($records, $filters) : $this->exportMaintenances($records, $filters);
        }

        return view('admin.reports.maintenance', [
            ...$this->filterOptions($filters),
            'type' => $type,
            'records' => $records->paginate($this->rowsPerPage($request))->withQueryString(),
        ]);
    }

    private const SESSION_DETAILS = ['room.floor.building.campus', 'faculty', 'classSession.subject', 'classSession.section'];

    private function issues(ReportFilters $filters): Builder
    {
        return $this->inPeriod(RoomIssue::query(), $filters, 'created_at')
            ->with(['room.floor.building.campus', 'reporter'])
            ->when($filters->category, fn ($query) => $query->where('category', $filters->category))
            ->when($filters->status, fn ($query) => $query->where('status', $filters->status))
            ->latest('id');
    }

    /**
     * Maintenance periods that were going on at any time in the date range.
     */
    private function maintenances(ReportFilters $filters): Builder
    {
        return RoomMaintenance::query()
            ->with(['room.floor.building.campus', 'roomIssue', 'starter', 'ender'])
            ->where('started_at', '<=', $filters->to->endOfDay())
            ->where(fn ($query) => $query->whereNull('ended_at')->orWhere('ended_at', '>=', $filters->from->startOfDay()))
            ->whereHas('room', fn ($query) => $filters->applyToRooms($query))
            ->latest('started_at')
            ->latest('id');
    }

    private function exportIssues(Builder $issues, ReportFilters $filters): StreamedResponse
    {
        return CsvExport::download(
            "room-issues-{$filters->period()}.csv",
            ['Campus', 'Building', 'Classroom', 'Issue Category', 'Description', 'Reported By', 'Date Reported', 'Status', 'Assigned To', 'Resolution Date'],
            $issues->lazy()->map(fn (RoomIssue $issue): array => [
                $issue->room->floor?->building?->campus?->campus_name,
                $issue->room->floor?->building?->building_name,
                $issue->room->room_name,
                $issue->category_label,
                $issue->description,
                $issue->reporter->full_name,
                $issue->created_at->format('Y-m-d H:i'),
                $issue->status_label,
                $issue->assigned_to,
                $issue->resolved_at?->format('Y-m-d H:i'),
            ]),
        );
    }

    private function exportMaintenances(Builder $maintenances, ReportFilters $filters): StreamedResponse
    {
        return CsvExport::download(
            "room-maintenance-{$filters->period()}.csv",
            ['Campus', 'Building', 'Classroom', 'Reason', 'Issue Category', 'Status', 'Started', 'Started By', 'Restored', 'Restored By'],
            $maintenances->lazy()->map(fn (RoomMaintenance $maintenance): array => [
                $maintenance->room->floor?->building?->campus?->campus_name,
                $maintenance->room->floor?->building?->building_name,
                $maintenance->room->room_name,
                $maintenance->reason,
                $maintenance->roomIssue?->category_label,
                $maintenance->ended_at ? 'Restored' : 'Under maintenance',
                $maintenance->started_at->format('Y-m-d H:i'),
                $maintenance->starter?->full_name,
                $maintenance->ended_at?->format('Y-m-d H:i'),
                $maintenance->ender?->full_name,
            ]),
        );
    }

    /**
     * A printed report has every row; on screen it is paged.
     */
    private function rowsPerPage(Request $request): int
    {
        return $request->boolean('print') ? 10000 : 20;
    }

    private function inPeriod(Builder $query, ReportFilters $filters, string $column): Builder
    {
        return $query
            ->whereBetween($column, [$filters->from->startOfDay(), $filters->to->endOfDay()])
            ->whereHas('room', fn ($rooms) => $filters->applyToRooms($rooms));
    }

    /**
     * @return array<string, mixed>
     */
    private function filterOptions(ReportFilters $filters): array
    {
        return [
            'filters' => $filters,
            'campuses' => Campus::orderBy('campus_name')->get(),
            'buildings' => Building::orderBy('building_name')->get(),
            'rooms' => Room::with('floor.building')->orderBy('room_name')->get(),
        ];
    }
}
