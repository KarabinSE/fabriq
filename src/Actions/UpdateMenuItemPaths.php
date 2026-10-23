<?php

namespace Karabin\Fabriq\Actions;

use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Locale as I18nLocale;

class UpdateMenuItemPaths
{
    public function __invoke(MenuItem $menuItemRoot): void
    {
        $locales = I18nLocale::query()
            ->where('enabled', 1)
            ->pluck('iso_code')
            ->all();

        $menuItemRoot->load('page.slugs');
        $paths = [];

        foreach ($locales as $locale) {
            $slug = $menuItemRoot->page?->slugs
                ->where('locale', $locale)
                ->sortByDesc('id')
                ->first()
                ?->slug;
            $paths[$locale] = $slug ? '/'.$slug : '';
            $menuItemRoot->setTranslation('path', $locale, $paths[$locale]);
        }

        $menuItemRoot->save();
        $pathsByItemId = [$menuItemRoot->id => $paths];

        $menuItems = MenuItem::whereDescendantOf($menuItemRoot)
            ->orderBy($menuItemRoot->getLftName())
            ->with('page.slugs')
            ->get();

        foreach ($menuItems as $menuItem) {
            $parentPaths = $pathsByItemId[$menuItem->parent_id] ?? $paths;
            $itemPaths = [];

            foreach ($locales as $locale) {
                if ($menuItem->type === 'external') {
                    $itemPaths[$locale] = (string) $menuItem
                        ->getFieldContent($locale)
                        ->get('external_url', '');
                } else {
                    $slug = $menuItem->page?->slugs
                        ->where('locale', $locale)
                        ->sortByDesc('id')
                        ->first()
                        ?->slug;

                    $itemPaths[$locale] = $parentPaths[$locale].($slug ? '/'.$slug : '');
                }

                $menuItem->setTranslation('path', $locale, $itemPaths[$locale]);
            }

            $menuItem->save();
            $pathsByItemId[$menuItem->id] = $itemPaths;
        }
    }
}
