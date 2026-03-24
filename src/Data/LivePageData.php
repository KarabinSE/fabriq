<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Models\Page;
use Spatie\LaravelData\Data;

class LivePageData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?int $template_id,
        public ?string $updated_at,
        public array $content,
    ) {}

    public static function fromModel(Page $page): self
    {
        $content = $page->content;

        if ($content instanceof Collection) {
            $content = $content->toArray();
        }

        if (! is_array($content)) {
            $content = [];
        }

        return new self(
            id: (int) $page->id,
            name: (string) $page->name,
            slug: (string) $page->slug,
            template_id: $page->template_id ? (int) $page->template_id : null,
            updated_at: $page->updated_at?->toJSON(),
            content: ['data' => $content],
        );
    }
}
