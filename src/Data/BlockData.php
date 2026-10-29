<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\ContentGetters\ContentReferenceTransformer;
use Karabin\Fabriq\Models\Block;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class BlockData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $locale,
        public bool $hidden,
        public ?int $parent_id,
        public int $block_type_id,
        public ?array $content,
        public int $sortindex,
        public Optional|BlockTypeData $block_type,
        #[DataCollectionOf(BlockData::class)]
        public array|DataCollection $children = [],
    ) {
        if ($content !== null) {
            $this->content = ContentReferenceTransformer::resolve($content, Block::contentOptions());
        }
    }
}
