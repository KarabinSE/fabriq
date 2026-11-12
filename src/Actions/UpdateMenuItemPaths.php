<?php

namespace Karabin\Fabriq\Actions;

use Karabin\Fabriq\Models\MenuItem;

class UpdateMenuItemPaths
{
    public function __invoke(MenuItem $menuItemRoot): void
    {
        $menuItemRoot->load('page.slugs');
        $locale = (string) $menuItemRoot->locale;
        $slug = $menuItemRoot->page?->slugs
            ->where('locale', $locale)
            ->sortByDesc('id')
            ->first()
            ?->slug;
        $path = $slug ? '/'.$slug : '';
        $menuItemRoot->path = $path;

        $menuItemRoot->save();
        $pathsByItemId = [$menuItemRoot->id => $path];

        $menuItems = MenuItem::whereDescendantOf($menuItemRoot)
            ->orderBy($menuItemRoot->getLftName())
            ->with('page.slugs')
            ->get();

        foreach ($menuItems as $menuItem) {
            $parentPath = $pathsByItemId[$menuItem->parent_id] ?? $path;

            if ($menuItem->type === 'external') {
                $itemPath = (string) $menuItem
                    ->getFieldContent($locale)
                    ->get('external_url', '');
            } else {
                $slug = $menuItem->page?->slugs
                    ->where('locale', $locale)
                    ->sortByDesc('id')
                    ->first()
                    ?->slug;
                $itemPath = $parentPath.($slug ? '/'.$slug : '');
            }

            $menuItem->path = $itemPath;
            $menuItem->save();
            $pathsByItemId[$menuItem->id] = $itemPath;
        }
    }
}
