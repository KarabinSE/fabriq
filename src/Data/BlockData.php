<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\ContentGetters\ButtonsGetter;
use Karabin\Fabriq\Models\ContentValue;
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
        public ?object $content,
        public int $sortindex,
        public Optional|BlockTypeData $blockType,
        #[DataCollectionOf(BlockData::class)]
        public array|DataCollection $children = [],
    ) {
        if (isset($content->buttons)) {
            $metaFake = ContentValue::make(['meta_value' => $content->buttons]);
            $nyaButtons = ButtonsGetter::get($metaFake);
            $content->buttons = $nyaButtons;
            // dd($content, $this->uuid);
            // dd('apa');
        }
    }
}
