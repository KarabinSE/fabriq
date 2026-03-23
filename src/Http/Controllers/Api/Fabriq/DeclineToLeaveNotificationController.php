<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\User;
use Karabin\Fabriq\Notifications\LeaveDeclinedNotification;

class DeclineToLeaveNotificationController extends Controller
{
    public function __invoke(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'path' => 'required|max:255',
        ]);
        $causer = $request->user();
        $recipient = User::findOrFail($userId);

        $recipient->notify(new LeaveDeclinedNotification($causer, $request->path));

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Editing rights was declined',
        ]);
    }
}
