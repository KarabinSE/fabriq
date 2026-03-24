<?php

namespace Karabin\Fabriq\Http\Controllers\Admin\Concerns;

use Karabin\Fabriq\Http\Requests\UpdateMenuItemRequest;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;

trait InteractsWithAdminMenus
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function menuTree(Menu $menu): array
    {
        $root = $this->menuRoot($menu);

        /** @var mixed $treeQuery */
        $treeQuery = $root->descendants();

        $tree = $treeQuery
            ->with('page')
            ->defaultOrder()
            ->get()
            ->toTree();

        return $this->transformMenuTreeItems($tree);
    }

    /**
     * @param  iterable<int, MenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function transformMenuTreeItems(iterable $items): array
    {
        $tree = [];

        foreach ($items as $item) {
            $content = $this->menuItemContent($item);
            $file = data_get($content, 'file');

            $tree[] = [
                'id' => $item->id,
                'title' => $item->type === 'internal'
                    ? ($item->page?->name ?? $item->title)
                    : ((string) data_get($content, 'title', $item->title)),
                'type' => (string) $item->type,
                'pageId' => $item->page_id ? (int) $item->page_id : null,
                'page' => $item->page ? [
                    'id' => $item->page->id,
                    'name' => $item->page->name,
                ] : null,
                'sortindex' => $item->sortindex !== null ? (int) $item->sortindex : null,
                'content' => [
                    'title' => (string) data_get($content, 'title', ''),
                    'external_url' => (string) data_get($content, 'external_url', ''),
                    'body' => (string) data_get($content, 'body', ''),
                    'file' => is_array($file) && data_get($file, 'id') ? $file : null,
                ],
                'children' => $this->transformMenuTreeItems($item->children ?? []),
            ];
        }

        return $tree;
    }

    protected function menuRoot(Menu $menu): MenuItem
    {
        /** @var MenuItem $root */
        $root = MenuItem::query()->firstOrCreate([
            'menu_id' => $menu->id,
            'parent_id' => null,
        ], [
            'type' => 'internal',
        ]);

        return $root;
    }

    /**
     * @return array<string, mixed>
     */
    protected function menuItemContent(MenuItem $menuItem): array
    {
        /** @var mixed $content */
        $content = $menuItem->getFieldContent($menuItem->revision);

        if (is_object($content) && method_exists($content, 'toArray')) {
            return $content->toArray();
        }

        if (is_array($content)) {
            return $content;
        }

        return [];
    }

    protected function menuItemPageId(UpdateMenuItemRequest $request): ?int
    {
        if ((string) $request->input('item.type') !== 'internal') {
            return null;
        }

        $pageId = $request->input('item.page_id');

        if ($pageId === null || $pageId === '') {
            return null;
        }

        return (int) $pageId;
    }
}
