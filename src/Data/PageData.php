<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Page;
use Karabin\TranslatableRevisions\Models\I18nLocale;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class PageData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?int $template_id,
        public ?int $parent_id,
        public ?int $revision,
        public ?int $published_version,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|array $content,
        public Lazy|array $localizedContent,
        public Lazy|array|null $template,
        public Lazy|array $slugs,
        public Lazy|array $children,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['content', 'template', 'slugs', 'localizedContent', 'children'];
    }

    public static function fromModel(Page $page): self
    {
        return new self(
            id: (int) $page->id,
            name: (string) $page->name,
            template_id: $page->template_id ? (int) $page->template_id : null,
            parent_id: $page->parent_id ? (int) $page->parent_id : null,
            revision: $page->revision ? (int) $page->revision : null,
            published_version: $page->published_version ? (int) $page->published_version : null,
            created_at: $page->created_at?->toISOString(),
            updated_at: $page->updated_at?->toISOString(),
            content: Lazy::create(fn () => ['data' => $page->getSimpleFieldContent($page->revision)->toArray()]),
            localizedContent: Lazy::create(fn () => ['data' => self::buildLocalizedContent($page)]),
            template: Lazy::create(fn () => $page->template ? ['data' => self::buildTemplate($page)] : null)->defaultIncluded(),
            slugs: Lazy::create(fn () => ['data' => $page->slugs->toArray()]),
            children: Lazy::create(fn () => ['data' => $page->children->map(fn (Page $child) => self::fromModel($child)->toArray())->values()->all()]),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function buildLocalizedContent(Page $page): array
    {
        $enabledLocales = I18nLocale::query()
            ->where('enabled', 1)
            ->select('iso_code')
            ->orderBy('id', 'desc')
            ->get();

        $result = [];

        foreach ($enabledLocales as $locale) {
            $result[(string) $locale->iso_code] = [
                'content' => $page->getSimpleFieldContent($page->revision, (string) $locale->iso_code),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildTemplate(Page $page): array
    {
        $template = $page->template;

        if (! $template) {
            return [];
        }

        $templateArray = $template->toArray();
        $fields = $template->fields;

        $templateArray['fields'] = [
            'data' => $fields->map(fn ($field) => $field->toArray())->values()->all(),
        ];

        $templateArray['groupedFields'] = [
            'data' => $fields->groupBy('group')->toArray(),
        ];

        return $templateArray;
    }
}
