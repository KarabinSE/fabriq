<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class MenuMigrationFeatureTest extends AdminUserTestCase
{
    public function test_it_merges_locale_suffixed_menus_and_localizes_their_items_and_paths(): void
    {
        $migration = require __DIR__.'/../database/migrations/2026_11_10_150000_localize_menu_items_and_merge_locale_menus.php';
        $migration->down();

        $svMenuId = DB::table('menus')->insertGetId([
            'name' => 'Main menu sv',
            'slug' => 'main_sv',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $enMenuId = DB::table('menus')->insertGetId([
            'name' => 'Main menu en',
            'slug' => 'main_en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $svRootId = $this->createMenuItem($svMenuId, null, 1, 4, ['sv' => '/sv']);
        $svItemId = $this->createMenuItem($svMenuId, $svRootId, 2, 3, [
            'sv' => '/sv/page',
            'en' => '/wrong-en/page',
        ]);
        $enRootId = $this->createMenuItem($enMenuId, null, 5, 8, ['en' => '/en']);
        $enItemId = $this->createMenuItem($enMenuId, $enRootId, 6, 7, [
            'en' => '/en/page',
            'sv' => '/wrong-sv/page',
        ]);

        $migration->up();

        $this->assertSame(1, DB::table('menus')->where('slug', 'main')->count());
        $this->assertSame(0, DB::table('menus')->whereIn('slug', ['main_sv', 'main_en'])->count());
        $mainMenuId = DB::table('menus')->where('slug', 'main')->value('id');
        $this->assertDatabaseHas('menus', [
            'id' => $mainMenuId,
            'name' => 'Main menu',
        ]);

        $this->assertDatabaseHas('menu_items', [
            'id' => $svRootId,
            'menu_id' => $mainMenuId,
            'locale' => 'sv',
            'path' => '/sv',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $svItemId,
            'menu_id' => $mainMenuId,
            'locale' => 'sv',
            'path' => '/sv/page',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $enRootId,
            'menu_id' => $mainMenuId,
            'locale' => 'en',
            'path' => '/en',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $enItemId,
            'menu_id' => $mainMenuId,
            'locale' => 'en',
            'path' => '/en/page',
        ]);
        $this->assertSame('text', Schema::getColumnType('menu_items', 'path'));
    }

    /**
     * @param  array<string, string>  $path
     */
    private function createMenuItem(int $menuId, ?int $parentId, int $left, int $right, array $path): int
    {
        return DB::table('menu_items')->insertGetId([
            'menu_id' => $menuId,
            'parent_id' => $parentId,
            '_lft' => $left,
            '_rgt' => $right,
            'type' => 'internal',
            'sortindex' => 0,
            'path' => json_encode($path, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
