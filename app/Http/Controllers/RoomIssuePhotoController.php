<?php

namespace App\Http\Controllers;

use App\Models\RoomIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoomIssuePhotoController extends Controller
{
    /**
     * The photo of a room issue, for administrators and whoever reported it.
     */
    public function __invoke(Request $request, RoomIssue $roomIssue): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $roomIssue->reported_by === $user->id, 403);
        abort_if(! $roomIssue->photo || ! Storage::disk('local')->exists($roomIssue->photo), 404);

        return Storage::disk('local')->response($roomIssue->photo, null, ['Cache-Control' => 'private, max-age=604800']);
    }
}
