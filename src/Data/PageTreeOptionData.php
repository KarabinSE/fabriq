<?php

namespace Karabin\Fabriq\Data;

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
                $result = [
                    ...$result,
                    ...self::collectTree($item->children, $prefix.'-'),
                ];
            }
        }

        return $result;
    }

    public static function fromModel(Page $page, string $prefix = '-'): self
    {
        return new self(
            id: (int) $page->id,
            name: (string) $page->name,
            prefixed_name: $prefix.' '.$page->name,
            depth: strlen($prefix),
        );
    }
}
