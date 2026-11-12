<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('locale')->after('type')->nullable();
        });

        $locales = DB::table('i18n_locales')
            ->pluck('iso_code')
            ->map(fn ($locale) => (string) $locale)
            ->all();
        // Menus without a locale suffix keep their items in the primary enabled locale.
        $defaultLocale = DB::table('i18n_locales')
            ->where('enabled', 1)
            ->orderBy('sort_index')
            ->value('iso_code') ?? config('app.locale', 'en');

        $locales = array_unique([...$locales, (string) $defaultLocale]);

        $menuMappings = [];
        $localizedMenus = [];
        $localizedPaths = [];

        $menus = DB::table('menus')->orderBy('id')->get(['id', 'slug', 'name']);

        foreach ($menus as $menu) {
            // Legacy menu slugs use the final locale suffix, e.g. "main_sv".
            $locale = $this->localeFromSlug($menu->slug, $locales);

            if ($locale === null) {
                $menuMappings[$menu->id] = [
                    'base_slug' => (string) $menu->slug,
                    'locale' => (string) $defaultLocale,
                ];

                continue;
            }

            $baseSlug = substr($menu->slug, 0, -(strlen($locale) + 1));
            $menuMappings[$menu->id] = [
                'base_slug' => $baseSlug,
                'locale' => $locale,
            ];
            $localizedMenus[$baseSlug][] = $menu;
        }

        $targetMenuIds = [];
        foreach ($localizedMenus as $baseSlug => $sourceMenus) {
            // Reuse an existing unsuffixed menu, or promote the first localized menu.
            $targetMenu = $menus->firstWhere('slug', $baseSlug) ?? $sourceMenus[0];
            $targetMenuIds[$baseSlug] = $targetMenu->id;

            if ($targetMenu->slug !== $baseSlug) {
                $locale = $menuMappings[$targetMenu->id]['locale'];
                $name = preg_replace(
                    '/[\s_-]+'.preg_quote($locale, '/').'$/i',
                    '',
                    (string) $targetMenu->name,
                );
                DB::table('menus')
                    ->where('id', $targetMenu->id)
                    ->update(['slug' => $baseSlug, 'name' => $name]);
            }
        }

        foreach ($menus as $menu) {
            $mapping = $menuMappings[$menu->id];
            $targetId = $targetMenuIds[$mapping['base_slug']] ?? $menu->id;

            // Keep the locale-specific path in memory before changing the column type.
            foreach (DB::table('menu_items')->where('menu_id', $menu->id)->get(['id', 'path']) as $item) {
                $path = json_decode((string) $item->path, true);
                $localizedPaths[$item->id] = is_array($path)
                    ? ($path[$mapping['locale']] ?? null)
                    : $item->path;

                DB::table('menu_items')
                    ->where('id', $item->id)
                    ->update([
                        'menu_id' => $targetId,
                        'locale' => $mapping['locale'],
                    ]);
            }

            if ($targetId !== $menu->id) {
                DB::table('menus')->where('id', $menu->id)->delete();
            }
        }

        foreach (DB::table('menus')->get(['id']) as $menu) {
            foreach ($locales as $locale) {
                // Every menu needs a root node for each locale so its tree can be edited independently.
                $hasRoot = DB::table('menu_items')
                    ->where('menu_id', $menu->id)
                    ->where('locale', $locale)
                    ->whereNull('parent_id')
                    ->exists();

                if ($hasRoot) {
                    continue;
                }

                $right = (int) (DB::table('menu_items')->max('_rgt') ?? 0);
                DB::table('menu_items')->insert([
                    'menu_id' => $menu->id,
                    'locale' => $locale,
                    'parent_id' => null,
                    '_lft' => $right + 1,
                    '_rgt' => $right + 2,
                    'type' => 'internal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('menu_items', function (Blueprint $table) {
            $table->text('path')->nullable()->change();
        });

        // Store the scalar path selected for each item's locale.
        foreach ($localizedPaths as $menuItemId => $path) {
            DB::table('menu_items')
                ->where('id', $menuItemId)
                ->update(['path' => $path]);
        }
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->json('path_translations')->nullable();
        });

        foreach (DB::table('menu_items')->get(['id', 'locale', 'path']) as $item) {
            DB::table('menu_items')
                ->where('id', $item->id)
                ->update([
                    'path_translations' => json_encode([
                        (string) $item->locale => $item->path,
                    ]),
                ]);
        }

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['path', 'locale']);
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->renameColumn('path_translations', 'path');
        });
    }

    /**
     * @param  array<int, string>  $locales
     */
    private function localeFromSlug(?string $slug, array $locales): ?string
    {
        if ($slug === null) {
            return null;
        }

        // Check longer locale codes first in case one locale code ends with another.
        usort($locales, fn (string $left, string $right) => strlen($right) <=> strlen($left));

        foreach ($locales as $locale) {
            if (str_ends_with($slug, '_'.$locale)) {
                return $locale;
            }
        }

        return null;
    }
};
