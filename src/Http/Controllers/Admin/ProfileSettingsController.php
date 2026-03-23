<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Models\User;

class ProfileSettingsController extends AdminController
{
    public function show(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return Inertia::render('Admin/Profile/Settings', [
            'pageTitle' => 'Din information',
            'profile' => $this->transformProfile($user->loadMissing('image')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProfile(User $user): array
    {
        $media = $user->image?->getFirstMedia('profile_image');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'emailVerifiedAt' => $user->email_verified_at?->toIso8601String(),
            'image' => [
                'id' => $user->image?->id,
                'thumbSrc' => $media?->getUrl('thumb'),
                'src' => $media?->getUrl(),
            ],
        ];
    }
}
