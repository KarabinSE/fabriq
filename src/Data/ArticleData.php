<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Article;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class ArticleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $is_published,
        public string $publishes_at,
        public string $publishes_at_date,
        public string $unpublishes_at,
        public bool $has_unpublished_time,
        public string $updated_at,
        public string $created_at,
        public Lazy|array $content,
        public Lazy|array|null $template,
        public Lazy|array $slugs,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['content', 'template', 'slugs'];
    }

    public static function fromModel(Article $article): self
    {

        return new self(
            id: (int) $article->id,
            name: (string) $article->name,
            slug: (string) $article->slug,
            is_published: (bool) $article->is_published,
            publishes_at: $article->publishes_at?->toISOString() ?? '',
            publishes_at_date: $article->publishes_at?->toDateString() ?? '',
            unpublishes_at: $article->unpublishes_at?->toISOString() ?? '',
            has_unpublished_time: (bool) $article->has_unpublished_time,
            updated_at: (string) $article->updated_at->toISOString(),
            created_at: (string) $article->created_at->toISOString(),
            content: Lazy::create(fn () => $article->getFieldContent(1)->toArray()),
            template: Lazy::create(fn () => $article->template?->toArray()),
            slugs: Lazy::create(fn () => $article->slugs->toArray()),
        );
    }
}
