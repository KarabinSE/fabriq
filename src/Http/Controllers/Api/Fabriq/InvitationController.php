<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Karabin\Fabriq\Data\InvitationData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Mail\AccountInvitation;
use Karabin\Fabriq\Models\Invitation;
use Karabin\Fabriq\Models\User;
use Symfony\Component\HttpFoundation\Response;

class InvitationController extends Controller
{
    public function store(Request $request, int $userId): Response
    {
        $user = Fabriq::getFqnModel('user')::where('id', $userId)
            ->firstOrFail();

        $invitedBy = $request->user();

        if (! $invitedBy instanceof User) {
            abort(401);
        }

        $invitation = $user->createInvitation($invitedBy->id);
        $invitation->load('invitedBy', 'user');

        Mail::to($user->email)
            ->queue(new AccountInvitation($invitation));

        return InvitationData::fromModel($invitation)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(201);
    }

    public function destroy(Request $request, int $userId): JsonResponse
    {
        $invitation = Invitation::where('user_id', $userId)
            ->firstOrFail();

        $invitation->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Invitation was deleted successfully',
        ]);
    }
}
