<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Page;
use Karabin\TranslatableRevisions\Models\I18nLocale;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

class MenuItemData extends Data
{
    public function __construct(
        public int $id,
        public string $type,
        public ?int $parent_id,
        public bool $is_external,
        public bool $redirect,
        public string $external_url,
        public ?int $page_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|array $content,
        public Lazy|array|null $page,
        public Lazy|array $localizedContent,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['content', 'page', 'localizedContent'];
    }

    public static function fromModel(MenuItem $menuItem): self
    {
        return new self(
            id: (int) $menuItem->id,
            type: (string) $menuItem->type,
            parent_id: $menuItem->parent_id ? (int) $menuItem->parent_id : null,
            is_external: (bool) $menuItem->is_external,
            redirect: (bool) $menuItem->redirect,
            external_url: (string) $menuItem->external_url,
            page_id: $menuItem->page_id ? (int) $menuItem->page_id : null,
            created_at: $menuItem->created_at ? (string) $menuItem->created_at : null,
            updated_at: $menuItem->updated_at ? (string) $menuItem->updated_at : null,
            content: Lazy::create(fn () => ['data' => $menuItem->getFieldContent()->toArray()]),
            page: Lazy::create(fn () => $menuItem->page instanceof Page
                ? ['data' => PageData::fromModel($menuItem->page)->toArray()]
                : null),
            localizedContent: Lazy::create(fn () => ['data' => self::buildLocalizedContent($menuItem)]),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function buildLocalizedContent(MenuItem $menuItem): array
    {
        $enabledLocales = I18nLocale::query()
            ->where('enabled', 1)
            ->select('iso_code')
            ->orderBy('id', 'desc')
            ->get();

        $result = [];

        foreach ($enabledLocales as $locale) {
            $result[(string) $locale->iso_code] = [
                'content' => $menuItem->getSimpleFieldContent($menuItem->revision, (string) $locale->iso_code),
            ];
        }

        return $result;
    }
}
