<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\AcceptInvitationRequest;
use Karabin\Fabriq\Models\Invitation;

class AcceptInvitationController extends Controller
{
    /**
     * Show invitation view.
     *
     * @return JsonResponse|View
     */
    public function show(Request $request, string $invitationUuid)
    {
        if (! $request->hasValidSignature()) {
            return $this->errorUnauthorized();
        }

        $invitation = Invitation::where('uuid', $invitationUuid)
            ->with('user')
            ->firstOrFail();

        /** @var view-string $viewString * */
        $viewString = 'vendor.fabriq.auth.activate';

        return view($viewString, ['invitation' => $invitation]);
    }

    /**
     * Undocumented function.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store(AcceptInvitationRequest $request, string $invitationUuid)
    {
        if (! $request->hasValidSignature()) {
            return $this->errorUnauthorized();
        }

        $invitation = Invitation::where('uuid', $invitationUuid)
            ->with('user')
            ->firstOrFail();

        $user = Fabriq::getFqnModel('user')::findOrFail($invitation->user_id);
        $user->email_verified_at = now();
        $user->password = bcrypt($request->password);
        $user->save();
        Auth::login($user);

        $invitation->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'code' => ApiResponseCode::Success->value,
                'http_code' => 200,
                'message' => 'The user has accepted the invitation successfully',
            ]);
        }

        return response()->redirectTo('/');
    }

    private function errorUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => ApiResponseCode::Unauthorized->value,
                'http_code' => 401,
                'message' => $message,
            ],
        ], 401);
    }
}
