<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomIssue;
use App\Models\User;
use App\Notifications\RoomIssueReported;
use App\Support\IssuePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/**
 * Faculty report classroom problems (RLNK-31) and damaged QR codes (RLNK-32)
 * and follow what happens to their reports.
 */
class RoomIssueController extends Controller
{
    public function index(Request $request): View
    {
        $issues = RoomIssue::with(['room.floor.building', 'updates' => fn ($query) => $query->latest('id')])
            ->where('reported_by', $request->user()->id)
            ->latest('id')
            ->paginate(10);

        return view('faculty.issues.index', compact('issues'));
    }

    public function create(Request $request): View
    {
        return view('faculty.issues.create', [
            'rooms' => Room::with('floor.building')->orderBy('room_name')->get(),
            'roomId' => $request->integer('room') ?: null,
            'category' => array_key_exists((string) $request->input('category'), RoomIssue::CATEGORIES) ? $request->input('category') : null,
        ]);
    }

    public function store(Request $request, IssuePhoto $issuePhoto): RedirectResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'category' => ['required', Rule::in(array_keys(RoomIssue::CATEGORIES))],
            // A damaged QR code explains itself; anything else needs describing.
            'description' => [Rule::requiredIf($request->input('category') !== RoomIssue::CATEGORY_QR), 'nullable', 'string', 'max:1000'],
            'qr_replacement_requested' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:max_width=9000,max_height=9000'],
        ], [
            'photo.image' => 'Upload a JPG, PNG or WEBP photo.',
            'photo.mimes' => 'Upload a JPG, PNG or WEBP photo.',
            'photo.max' => 'The photo must be 10 MB or smaller.',
            'photo.dimensions' => 'The photo is too large. Use one up to 9000 pixels wide or tall.',
            'description.required' => 'Describe the issue so it can be fixed.',
        ]);

        $isQrIssue = $validated['category'] === RoomIssue::CATEGORY_QR;

        if ($isQrIssue) {
            $reported = RoomIssue::open()->where('room_id', $validated['room_id'])->where('category', RoomIssue::CATEGORY_QR)->first();

            if ($reported !== null) {
                throw ValidationException::withMessages([
                    'category' => "This room's QR code was already reported and is {$reported->status_label}.",
                ]);
            }
        }

        $photo = null;

        if ($request->hasFile('photo')) {
            try {
                $photo = $issuePhoto->store($request->file('photo'));
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages(['photo' => $exception->getMessage()]);
            }
        }

        $issue = RoomIssue::create([
            'room_id' => $validated['room_id'],
            'reported_by' => $request->user()->id,
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'photo' => $photo,
            'qr_replacement_requested' => $isQrIssue && $request->boolean('qr_replacement_requested'),
        ]);

        Notification::send(User::where('role', 'admin')->where('is_active', true)->get(), new RoomIssueReported($issue));

        return redirect()
            ->route('faculty.issues.index')
            ->with('success', "Your report about {$issue->room->room_name} was sent to the administrator.");
    }
}
