<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class RoleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $guard_name,
        public ?string $display_name,
        public ?string $description,
        public ?bool $hidden,
        public ?string $created_at,
        public ?string $updated_at,
    ) {
    }

    public static function fromModel(Model $role): self
    {
        $attributes = $role->getAttributes();
        $hidden = data_get($attributes, 'hidden');

        return new self(
            id: (int) data_get($attributes, 'id'),
            name: (string) data_get($attributes, 'name'),
            guard_name: (string) data_get($attributes, 'guard_name'),
            display_name: data_get($attributes, 'display_name'),
            description: data_get($attributes, 'description'),
            hidden: $hidden !== null ? (bool) $hidden : null,
            created_at: $role->created_at?->toISOString(),
            updated_at: $role->updated_at?->toISOString(),
        );
    }
}