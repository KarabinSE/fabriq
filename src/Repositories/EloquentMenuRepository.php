<?php

namespace Karabin\Fabriq\Repositories;

use Karabin\Fabriq\ContentGetters\ContentResourceContext;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Repositories\Interfaces\MenuRepositoryInterface;
use Karabin\Fabriq\Support\MenuTreeSerializer;

class EloquentMenuRepository implements MenuRepositoryInterface
{
    /**
     * Find by slug.
     *
     * @return array<string, mixed>
     */
    public function findBySlug(string $slug): array
    {
        $menu = Fabriq::getModelClass('menu')->where('slug', $slug)->firstOrFail();
        $menuItemRoot = MenuItem::rootForMenu($menu->id)->first();

        if ($menuItemRoot === null) {
            return ['data' => []];
        }

        $includes = MenuTreeSerializer::parseIncludes((string) request()->input('include', ''));

        $query = Fabriq::getModelClass('menuItem')->orderBy('sortindex')
            ->with('ancestors', 'ancestors.page', 'ancestors.page.slugs', 'page', 'page.slugs', 'page.template.fields');

        /** @phpstan-ignore-next-line */
        $items = $query->descendantsOf($menuItemRoot->id);
        $contentModels = $items->concat($items->pluck('page')->filter());
        $tree = $items->toTree();

        return ContentResourceContext::withModels(
            $contentModels,
            fn () => MenuTreeSerializer::collection($tree, $includes),
            array_key_exists('content', $includes),
        );
    }
}
