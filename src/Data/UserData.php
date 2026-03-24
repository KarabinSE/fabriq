<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\User;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class UserData extends Data
{
    /**
     * @param  array<int, string>  $role_list
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public array $role_list,
        public string $timezone,
        public ?string $email_verified_at,
        public ?string $updated_at,
        public array|Lazy $image,
        public array|Lazy $roles,
        public array|Lazy|null $invitation,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['roles', 'invitation', 'image'];
    }

    public static function fromModel(User $user): self
    {
        $roles = $user->roles
            ->map(fn ($role) => RoleData::fromModel($role)->toArray())
            ->values()
            ->all();

        return new self(
            id: (int) $user->id,
            name: (string) $user->name,
            email: (string) $user->email,
            role_list: $user->roles->pluck('name')->values()->all(),
            timezone: 'Europe/Stockholm',
            email_verified_at: $user->email_verified_at ? (string) $user->email_verified_at : null,
            updated_at: $user->updated_at?->toISOString(),
            image: Lazy::create(fn () => ['data' => UserImageData::fromModel($user->image)->toArray()])->defaultIncluded(),
            roles: Lazy::create(fn () => ['data' => $roles]),
            invitation: Lazy::create(fn () => $user->invitation
                ? ['data' => InvitationData::fromModel($user->invitation)->toArray()]
                : null),
        );
    }
}
