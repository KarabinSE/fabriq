<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Actions\Fortify\UpdateUserPassword;
use Karabin\Fabriq\Actions\Fortify\UpdateUserProfileInformation;
use Karabin\Fabriq\Data\UserData;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\User;
use Karabin\Fabriq\QueryBuilders\NoOpInclude;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedUserController extends Controller
{
    public function index(Request $request): Response
    {
        $authenticatedUser = $request->user();

        abort_unless($authenticatedUser instanceof User, 401);

        $allowedIncludes = [
            ...User::RELATIONSHIPS,
            AllowedInclude::custom('invitation', new NoOpInclude),
            AllowedInclude::custom('image', new NoOpInclude),
        ];

        $user = QueryBuilder::for(Fabriq::getFqnModel('user'))
            ->allowedIncludes(...$allowedIncludes)
            ->with('roles')
            ->where('id', $authenticatedUser->id)
            ->firstOrFail();

        /** @var User $user */

        return UserData::fromModel($user)->wrap('data')->toResponse($request);
    }

    public function update(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        if ($request->input('password', false)) {
            $updateUserPassword = new UpdateUserPassword;
            $updateUserPassword->update($user, $request->all());
        }

        // Update the profile
        (new UpdateUserProfileInformation)->update($user, $request->all());

        $allowedIncludes = [
            ...User::RELATIONSHIPS,
            AllowedInclude::custom('invitation', new NoOpInclude),
            AllowedInclude::custom('image', new NoOpInclude),
        ];

        $refreshedUser = QueryBuilder::for(Fabriq::getFqnModel('user'))
            ->allowedIncludes(...$allowedIncludes)
            ->with('roles')
            ->where('id', $user->id)
            ->firstOrFail();

        /** @var User $refreshedUser */

        return UserData::fromModel($refreshedUser)->wrap('data')->toResponse($request);
    }
}
