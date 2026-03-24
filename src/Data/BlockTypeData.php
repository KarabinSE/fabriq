<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\BlockType;
use Spatie\LaravelData\Data;

class BlockTypeData extends Data
{
    /**
     * @param  array<string, mixed>|null  $options
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $component_name,
        public ?string $base_64_svg,
        public bool $has_children,
        public ?string $type,
        public ?bool $active,
        public ?array $options,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(BlockType $blockType): self
    {
        return new self(
            id: (int) $blockType->id,
            name: (string) $blockType->name,
            component_name: (string) $blockType->component_name,
            base_64_svg: $blockType->base_64_svg,
            has_children: (bool) $blockType->has_children,
            type: $blockType->type,
            active: $blockType->active !== null ? (bool) $blockType->active : null,
            options: $blockType->options,
            created_at: $blockType->created_at?->toISOString(),
            updated_at: $blockType->updated_at?->toISOString(),
        );
    }
}
