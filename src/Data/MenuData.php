<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Menu;
use Spatie\LaravelData\Data;

class MenuData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Menu $menu): self
    {
        return new self(
            id: (int) $menu->id,
            name: (string) $menu->name,
            slug: (string) $menu->slug,
            created_at: $menu->created_at ? (string) $menu->created_at : null,
            updated_at: $menu->updated_at ? (string) $menu->updated_at : null,
        );
    }
}
