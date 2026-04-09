<?php

namespace Karabin\Fabriq\Support;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Page;
use Karabin\TranslatableRevisions\Models\I18nLocale;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;

class MenuTreeSerializer
{
    /**
     * @param  iterable<MenuItem>  $menuItems
     * @param  array<string, array>  $includes
     * @return array{data: array<int, array<string, mixed>>}
     */
    public static function collection(iterable $menuItems, array $includes = []): array
    {
        $serialized = [];

        foreach ($menuItems as $menuItem) {
            $serialized[] = self::item($menuItem, $includes);
        }

        return ['data' => $serialized];
    }

    /**
     * @return array<string, array>
     */
    public static function parseIncludes(?string $includeParameter): array
    {
        $includes = [];

        foreach (explode(',', (string) $includeParameter) as $path) {
            $segments = array_values(array_filter(explode('.', trim($path))));

            if ($segments === []) {
                continue;
            }

            $includes = self::addIncludePath($includes, $segments);
        }

        return $includes;
    }

    /**
     * @param  array<string, array>  $includes
     * @return array<string, mixed>
     */
    private static function item(MenuItem $menuItem, array $includes): array
    {
        $data = [
            'id' => (int) $menuItem->id,
            'title' => (string) $menuItem->title,
            'slug' => (string) $menuItem->getSlugString(),
            'path' => (string) $menuItem->relativePath,
            'localized_path' => '/'.app()->getLocale().$menuItem->relativePath,
            'parent_id' => $menuItem->parent_id ? (int) $menuItem->parent_id : null,
            'type' => (string) $menuItem->type,
        ];

        if (array_key_exists('page', $includes)) {
            $data['page'] = [
                'data' => $menuItem->page instanceof Page ? self::page($menuItem->page, $includes['page']) : null,
            ];
        }

        if (array_key_exists('content', $includes)) {
            $data['content'] = [
                'data' => self::normalize($menuItem->getFieldContent($menuItem->revision)),
            ];
        }

        if (array_key_exists('children', $includes)) {
            $childIncludes = self::recursiveIncludes($includes['children']);
            /** @var \Illuminate\Database\Eloquent\Collection<int, MenuItem> $children */
            $children = $menuItem->children;
            $data['children'] = self::collection($children, $childIncludes);
        }

        return $data;
    }

    /**
     * @param  array<string, array>  $includes
     * @return array<string, mixed>
     */
    private static function page(Page $page, array $includes): array
    {
        $data = $page->toArray();

        if (array_key_exists('content', $includes)) {
            $data['content'] = [
                'data' => self::normalize($page->getSimpleFieldContent($page->revision)),
            ];
        }

        if (array_key_exists('template', $includes)) {
            /** @var RevisionTemplate|null $template */
            $template = $page->template;
            $data['template'] = [
                'data' => $template ? self::template($template, $includes['template']) : null,
            ];
        }

        if (array_key_exists('slugs', $includes)) {
            $data['slugs'] = [
                'data' => $page->slugs->toArray(),
            ];
        }

        if (array_key_exists('localizedContent', $includes)) {
            $data['localizedContent'] = [
                'data' => self::localizedContent($page),
            ];
        }

        if (array_key_exists('children', $includes)) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Page> $pageChildren */
            $pageChildren = $page->children;
            $data['children'] = [
                'data' => $pageChildren
                    ->map(fn (Page $child) => self::page($child, self::recursiveIncludes($includes['children'])))
                    ->values()
                    ->all(),
            ];
        }

        return $data;
    }

    /**
     * @param  array<string, array>  $includes
     * @return array<string, mixed>
     */
    private static function template(RevisionTemplate $template, array $includes): array
    {
        $data = $template->toArray();

        if (array_key_exists('fields', $includes)) {
            $data['fields'] = [
                'data' => $template->fields->map(fn ($field) => $field->toArray())->values()->all(),
            ];
        }

        if (array_key_exists('groupedFields', $includes)) {
            $data['groupedFields'] = [
                'data' => $template->fields->groupBy('group')->toArray(),
            ];
        }

        return $data;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function localizedContent(Page $page): array
    {
        $enabledLocales = I18nLocale::query()
            ->where('enabled', 1)
            ->select('iso_code')
            ->orderBy('id', 'desc')
            ->get();

        return $enabledLocales
            ->mapWithKeys(fn ($locale) => [
                (string) $locale->iso_code => [
                    'content' => self::normalize($page->getSimpleFieldContent($page->revision, (string) $locale->iso_code)),
                ],
            ])
            ->toArray();
    }

    /**
     * @param  array<string, array>  $includes
     * @return array<string, array>
     */
    private static function recursiveIncludes(array $includes): array
    {
        if (! array_key_exists('children', $includes)) {
            $includes['children'] = [];
        }

        return $includes;
    }

    /**
     * @param  array<string, array>  $includes
     * @param  array<int, string>  $segments
     * @return array<string, array>
     */
    private static function addIncludePath(array $includes, array $segments): array
    {
        $segment = array_shift($segments);

        if ($segment === null) {
            return $includes;
        }

        $existing = $includes[$segment] ?? [];
        $includes[$segment] = self::addIncludePath($existing, $segments);

        return $includes;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private static function normalize($value)
    {
        if ($value instanceof Collection) {
            return $value->toArray();
        }

        return $value;
    }
}
