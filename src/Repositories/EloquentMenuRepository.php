<?php

namespace Karabin\Fabriq\Repositories;

use Karabin\Fabriq\ContentGetters\ContentResourceContext;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Repositories\Interfaces\MenuRepositoryInterface;
use Karabin\Fabriq\Support\MenuTreeSerializer;

class EloquentMenuRepository implements MenuRepositoryInterface
{
    public function __construct(private MenuItem $model, private Menu $menuModel) {}

    /**
     * Find by slug.
     *
     * @return array<string, mixed>
     */
    public function findBySlug(string $slug): array
    {
        $menu = $this->menuModel->where('slug', $slug)->firstOrFail();
        $menuItemRoot = MenuItem::where('menu_id', $menu->id)
            ->whereNull('parent_id')
            ->first();

        $includes = MenuTreeSerializer::parseIncludes((string) request()->input('include', ''));

        $query = $this->model->orderBy('sortindex')
            ->with('ancestors', 'ancestors.page', 'ancestors.page.slugs', 'page', 'page.slugs', 'page.template.fields');

        /** @phpstan-ignore-next-line */
        $items = $query->descendantsOf($menuItemRoot->id);
        $tree = $items->toTree();

        return ContentResourceContext::withModels(
            $items,
            fn () => MenuTreeSerializer::collection($tree, $includes),
            array_key_exists('content', $includes),
        );
    }
}
