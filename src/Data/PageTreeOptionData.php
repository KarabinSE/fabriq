<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Karabin\Fabriq\Models\Page;
use Spatie\LaravelData\Data;

class PageTreeOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $prefixed_name,
        public int $depth,
        public ?string $page_title,
    ) {}

    /**
     * @param  iterable<Page>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function collectTree(iterable $items, string $prefix = '-'): array
    {
        $result = [];

        foreach ($items as $item) {
            $option = self::fromModel($item, $prefix)->toArray();
            $option['children'] = [];
            $result[] = array_merge(Arr::except($item->toArray(), ['children']), $option);

            if ($item->children->count() > 0) {
                /** @var Collection<int, Page> $children */
                $children = $item->children;
                $result = [
                    ...$result,
                    ...self::collectTree($children, $prefix.'-'),
                ];
            }
        }

        return $result;
    }

    public static function fromModel(Page $page, string $prefix = '-'): self
    {
        $pageTitle = $page->getSimpleFieldContent(app()->getLocale())->get('page_title');
        $name = (string) $page->name;

        return new self(
            id: (int) $page->id,
            name: $name,
            prefixed_name: $prefix.' '.$name,
            depth: strlen($prefix),
            page_title: $pageTitle !== null ? (string) $pageTitle : null,
        );
    }
}
