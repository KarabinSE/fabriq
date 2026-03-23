<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class TagData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $value,
        public ?string $type,
    ) {}

    public static function fromModel(Model $tag): self
    {
        return new self(
            id: (int) $tag->id,
            name: (string) $tag->name,
            value: (int) $tag->id,
            type: $tag->type,
        );
    }
}
