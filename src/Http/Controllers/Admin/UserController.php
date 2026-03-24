<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\HandlesAdminInvitations;
use Karabin\Fabriq\Http\Requests\CreateUserRequest;
use Karabin\Fabriq\Http\Requests\UpdateUserRequest;
use Karabin\Fabriq\Models\Role;
use Karabin\Fabriq\Models\User;

class UserController extends AdminController
{
    use HandlesAdminInvitations;

    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->input('search', $request->input('filter.search', '')));
        $sort = (string) $request->string('sort', 'name');
        [$sortColumn, $sortDirection] = $this->normalizeUserSort($sort);

        $users = User::query()
            ->with(['roles', 'invitation'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(15);

        return Inertia::render('Admin/Users/Index', [
            'pageTitle' => 'Users',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'users' => [
                'data' => $this->transformUsers($users),
                'pagination' => $this->paginationMeta($users),
            ],
        ]);
    }

    public function store(CreateUserRequest $request): RedirectResponse
    {
        $roleList = $request->array('role_list');

        $user = new User;
        $user->name = (string) $request->string('name');
        $user->email = (string) $request->string('email');
        $user->password = bcrypt(Str::random(12));
        $user->save();
        $user->syncRoles($roleList);

        if ($request->boolean('send_activation')) {
            $this->createAndSendInvitation($user, $request->user()->id);
        }

        return to_route('admin.users.index')->with('status', 'Användaren skapades.');
    }

    public function show(Request $request, int $userId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $user = Fabriq::getFqnModel('user')::query()
            ->with('roles')
            ->findOrFail($userId);

        abort_unless($user instanceof User, 404);

        $roles = Role::query()
            ->notHidden()
            ->orderBy('display_name')
            ->get();

        return Inertia::render('Admin/Users/Edit', [
            'pageTitle' => 'Redigera användare',
            'user' => $this->transformEditableUser($user),
            'roles' => $this->transformRoleOptions($roles),
        ]);
    }

    public function update(UpdateUserRequest $request, int $userId): RedirectResponse
    {
        $user = Fabriq::getFqnModel('user')::query()->findOrFail($userId);

        abort_unless($user instanceof User, 404);

        $user->fill($request->validated());
        $user->save();

        return to_route('admin.users.edit', ['userId' => $user->id])
            ->with('status', 'Användaren har uppdaterats.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return to_route('admin.users.index')->with('status', 'Du kan inte radera dig själv.');
        }

        $user->delete();

        return to_route('admin.users.index')->with('status', 'Användaren raderades.');
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeUserSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'email', 'updated_at'];

        if (! in_array($column, $allowed, true)) {
            return ['name', 'asc'];
        }

        return [$column, $direction];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformUsers(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $roles = [];

            foreach ($user->roles as $role) {
                $roles[] = [
                    'name' => $role->name,
                    'displayName' => $role->display_name,
                ];
            }

            $items[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'verifiedAt' => $user->email_verified_at?->toIso8601String(),
                'updatedAt' => $user->updated_at?->toIso8601String(),
                'editPath' => '/admin/users/'.$user->id.'/edit',
                'invitationSentAt' => $user->invitation?->created_at?->toIso8601String(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roleList' => $user->roles->pluck('name')->values()->all(),
        ];
    }

    /**
     * @param  iterable<int, Role>  $roles
     * @return array<int, array{name: string, displayName: string}>
     */
    private function transformRoleOptions(iterable $roles): array
    {
        $items = [];

        foreach ($roles as $role) {
            $items[] = [
                'name' => $role->name,
                'displayName' => $role->display_name,
            ];
        }

        return $items;
    }
}
