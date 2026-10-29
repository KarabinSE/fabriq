<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class PreviewPageData extends Data
{
    public function __construct(
        public ?string $uuid,
        public int $id,
        public string $name,
        public ?int $template_id,
        public ?string $updated_at,
        public array $content = [],
        // public array $blocks = [],
        #[DataCollectionOf(LiveBlockData::class)]
        public Optional|DataCollection $blocks = new Optional,
    ) {
        if (isset($this->content['blocks'])) {
            $newBlocks = collect($this->content['blocks'])->transform(function ($block) {
                if (isset($block['children']) && count($block['children'])) {
                    foreach ($block['children'] as $index => $child) {
                        $child['block_type'] = Optional::create();
                    }
                    $block['children'] = LiveBlockData::collect($block['children'], DataCollection::class);
                }

                return $block;
            });
            $this->blocks = LiveBlockData::collect($this->content['blocks'], DataCollection::class);
            unset($this->content['blocks']);
        }
    }
}
