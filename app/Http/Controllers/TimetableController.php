<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Support\SelectedTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The week of a room, section or faculty member as a timetable. Students see
 * rooms and sections only.
 */
class TimetableController extends Controller
{
    public function __invoke(Request $request): View
    {
        $types = ['room' => 'Room', 'section' => 'Section'];

        if (! $request->user()->isStudent()) {
            $types['faculty'] = 'Faculty';
        }

        $type = array_key_exists((string) $request->query('type'), $types) ? $request->query('type') : 'room';
        $options = $this->options($type);
        $id = $request->integer('id') ?: null;
        $name = $options->get($id);

        $classes = $name === null ? collect() : ClassSession::with(['room', 'faculty', 'subject', 'section'])
            ->inTerm(SelectedTerm::get())
            ->where("{$type}_id", $id)
            ->where('status', 'active')
            ->orderedByWeek()
            ->get();

        return view('timetable.index', [
            'types' => $types,
            'type' => $type,
            'options' => $options,
            'id' => $name === null ? null : $id,
            'name' => $name,
            'classes' => $classes,
            'term' => SelectedTerm::get(),
        ]);
    }

    /**
     * What can be chosen, by ID.
     *
     * @return Collection<int, string>
     */
    private function options(string $type): Collection
    {
        return match ($type) {
            'room' => Room::with('floor.building')->orderBy('room_name')->get()
                ->mapWithKeys(fn (Room $room) => [$room->id => $room->room_name.($room->floor?->building ? ' · '.$room->floor->building->building_name : '')]),
            'section' => Section::orderBy('section_name')->pluck('section_name', 'id'),
            'faculty' => Faculty::orderBy('last_name')->orderBy('first_name')->get()
                ->mapWithKeys(fn (Faculty $faculty) => [$faculty->id => "{$faculty->last_name}, {$faculty->first_name}"]),
        };
    }
}
