<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;

class PreviewPageData extends Data
{
    public function __construct(
        public ?string $uuid,
        public int $id,
        public string $name,
        public ?int $template_id,
        public ?string $updated_at,
        public array $content = [],
        public array $blocks = [],
    ) {
        if (isset($this->content['blocks'])) {
            $this->blocks = $this->content['blocks'];
            unset($this->content['blocks']);
        }
    }
}
