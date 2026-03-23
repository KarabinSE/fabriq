<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\UserData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateUserImageRequest;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\User;
use Symfony\Component\HttpFoundation\Response;

class UserImageController extends Controller
{
    public function store(CreateUserImageRequest $request): Response
    {
        $image = new Image;
        $image->save();
        try {
            $image->saveMedia(false, 'profile_image');

            $user = $request->user();
            $user->image_id = $image->id;
            $user->save();
        } catch (\Throwable $exception) {
            $image->delete();

            return response()->json([
                'error' => [
                    'code' => ApiResponseCode::InternalError->value,
                    'http_code' => 500,
                    'message' => 'Kunde inte ladda upp bilden',
                ],
            ], 500);
        }

        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $user = Fabriq::getModelClass('user')::with('roles')->findOrFail($user->id);

        return UserData::fromModel($user)->wrap('data')->toResponse($request);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $user->image->delete();
        $user->image_id = null;
        $user->save();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Image was deleted successfully',
        ]);
    }
}
