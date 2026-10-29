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
        #[DataCollectionOf(LiveBlockData::class)]
        public Optional|DataCollection $blocks = new Optional,
    ) {
        if (isset($this->content['blocks'])) {
            $this->blocks = LiveBlockData::collect($this->content['blocks'], DataCollection::class);
            unset($this->content['blocks']);
        }
    }
}
