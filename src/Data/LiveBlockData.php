<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\ContentGetters\ContentReferenceTransformer;
use Karabin\Fabriq\Fabriq;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class LiveBlockData extends Data
{
    public function __construct(
        public string $uuid,
        public string $locale,
        public ?array $content,
        public int $sortindex,
        public string|Optional $component_name,
        public null|Optional|BlockTypeData $block_type,
        #[DataCollectionOf(self::class)]
        public array|DataCollection $children = [],
    ) {
        if ($content !== null) {
            $this->content = ContentReferenceTransformer::resolve($content, Fabriq::getFqnModel('block')::contentOptions());
        }

        $this->component_name = $this->block_type->component_name ?? Optional::create();
        $this->block_type = Optional::create();
    }
}
