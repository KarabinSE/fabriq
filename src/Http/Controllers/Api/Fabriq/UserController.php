<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Karabin\Fabriq\Data\UserData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateUserRequest;
use Karabin\Fabriq\Http\Requests\UpdateUserRequest;
use Karabin\Fabriq\Models\User;
use Karabin\Fabriq\QueryBuilders\NoOpInclude;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Returns an index of users.
     */
    public function index(Request $request): Response
    {
        $number = $request->integer('number', 100);
        $allowedIncludes = [
            ...User::RELATIONSHIPS,
            AllowedInclude::custom('invitation', new NoOpInclude),
            AllowedInclude::custom('image', new NoOpInclude),
        ];

        $paginator = QueryBuilder::for(Fabriq::getFqnModel('user'))
            ->allowedSorts('name', 'email', 'id', 'updated_at')
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->allowedIncludes(...$allowedIncludes)
            ->with('roles')
            ->paginate($number);

        return UserData::collect($paginator, PaginatedDataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    public function store(CreateUserRequest $request): Response
    {
        $user = Fabriq::getModelClass('user');
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt(Str::random(12));
        $user->save();
        $user->syncRoles($request->role_list);

        return UserData::fromModel($user)->wrap('data')->toResponse($request);
    }

    /**
     * Show a single user.
     */
    public function show(Request $request, int $id): Response
    {
        $allowedIncludes = [
            ...User::RELATIONSHIPS,
            AllowedInclude::custom('invitation', new NoOpInclude),
            AllowedInclude::custom('image', new NoOpInclude),
        ];

        $user = QueryBuilder::for(Fabriq::getFqnModel('user'))
            ->allowedIncludes(...$allowedIncludes)
            ->with('roles')
            ->where('id', $id)
            ->firstOrFail();

        /** @var User $user */

        return UserData::fromModel($user)->wrap('data')->toResponse($request);
    }

    public function update(UpdateUserRequest $request, int $id): Response
    {
        $user = Fabriq::getModelClass('user')->findOrFail($id);
        $user->fill($request->validated());
        $user->save();

        return UserData::fromModel($user)->wrap('data')->toResponse($request);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($id === $request->user()->id) {
            return $this->errorWrongArgs('Du kan inte radera dig själv');
        }

        $user = Fabriq::getModelClass('user')->findOrFail($id);
        $user->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'The user has been deleted',
        ]);
    }

    private function errorWrongArgs(string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => ApiResponseCode::WrongArgs->value,
                'http_code' => 400,
                'message' => $message,
            ],
        ], 400);
    }
}
