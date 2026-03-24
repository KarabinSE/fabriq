<?php

namespace Karabin\Fabriq\Repositories;

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
        $tree = $query->descendantsOf($menuItemRoot->id)->toTree();

        return MenuTreeSerializer::collection($tree, $includes);
    }
}
