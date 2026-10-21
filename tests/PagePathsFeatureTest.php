<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class PagePathsFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_get_page_paths()
    {
        // Arrange
        $menu = Menu::factory()->create();
        $parent = Page::factory()->create([
            'name' => 'parent 1',
        ]);
        $parentItem = MenuItem::create([
            'page_id' => $parent->id,
            'menu_id' => $menu->id,
        ]);
        $childOne = Page::factory()->create([
            'name' => 'child 2',
        ]);
        $subItem = MenuItem::create([
            'page_id' => $childOne->id,
            'menu_id' => $menu->id,
            'parent_id' => $parentItem->id,
        ]);
        $childChildOne = Page::factory()->create([
            'name' => 'child 1 of child 1',
        ]);
        $leafItem = MenuItem::create([
            'page_id' => $childChildOne->id,
            'menu_id' => $menu->id,
            'parent_id' => $subItem->id,
        ]);
        $this->json('PATCH', '/menus/'.$menu->id.'/items/tree', [
            'tree' => [[
                'id' => $subItem->id,
                'children' => [[
                    'id' => $leafItem->id,
                    'children' => [],
                ]],
            ]],
        ])->assertOk();

        $response = $this
            ->withHeaders(['X-LOCALE' => 'sv'])
            ->json('GET', route('pages.paths.index', [$childChildOne]));

        $response->assertOk();
        $response->assertJsonFragment([
            'absolute_path' => config('fabriq.front_end_domain').'/'.App::currentLocale().'/parent-1/child-2/child-1-of-child-1',
            'permalink' => config('fabriq.front_end_domain').'/permalink/'.hash('md5', $childChildOne->id).'/'.App::currentLocale(),
        ]);
    }

    /** @test **/
    public function it_uses_stored_localized_paths_for_multiple_menu_items_without_loading_linked_pages_or_slugs()
    {
        $page = Page::factory()->create();
        $firstMenu = Menu::factory()->create();
        $secondMenu = Menu::factory()->create();
        $firstMenuItem = MenuItem::factory()->create([
            'menu_id' => $firstMenu->id,
            'page_id' => $page->id,
        ]);
        $firstMenuItem->setTranslation('path', 'sv', '/sv/first');
        $firstMenuItem->setTranslation('path', 'en', '/en/first');
        $firstMenuItem->save();
        $secondMenuItem = MenuItem::factory()->create([
            'menu_id' => $secondMenu->id,
            'page_id' => $page->id,
        ]);
        $secondMenuItem->setTranslation('path', 'sv', '/sv/second');
        $secondMenuItem->setTranslation('path', 'en', '/en/second');
        $secondMenuItem->save();
        $page = Page::query()
            ->with(['menuItems' => fn ($query) => $query->without('page')])
            ->findOrFail($page->id);
        App::setLocale('sv');

        $pathsByLocale = $page->paths->mapWithKeys(fn ($paths) => $paths);
        $this->assertSame(['/sv/first', '/sv/second'], $pathsByLocale['sv']->all());
        $this->assertSame(['/en/first', '/en/second'], $pathsByLocale['en']->all());
        $this->assertSame(['/sv/first', '/sv/second'], $page->localizedPaths->flatten()->all());

        $pageSelects = 0;
        $menuItemSelects = 0;
        $slugSelects = 0;
        DB::listen(function ($query) use (&$pageSelects, &$menuItemSelects, &$slugSelects) {
            if (preg_match('/from\s+[`"]?pages[`"]?/i', $query->sql)) {
                $pageSelects++;
            }
            if (preg_match('/from\s+[`"]?menu_items[`"]?/i', $query->sql)) {
                $menuItemSelects++;
            }
            if (preg_match('/from\s+[`"]?slugs[`"]?/i', $query->sql)) {
                $slugSelects++;
            }
        });

        $response = $this->withHeaders(['X-LOCALE' => 'sv'])
            ->getJson(route('pages.paths.index', [$page]));

        $response->assertOk();
        $this->assertSame(1, $pageSelects);
        $this->assertSame(1, $menuItemSelects);
        $this->assertSame(0, $slugSelects);
    }

    /** @test **/
    public function it_uses_page_slugs_when_no_menu_items_exist()
    {
        $page = Page::factory()->create();
        $page->slugs()->delete();
        Slug::create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'source_string' => 'fallback page',
            'source_key' => 'page_title',
            'slug' => 'fallback-page-sv',
            'locale' => 'sv',
        ]);
        Slug::create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'source_string' => 'fallback page',
            'source_key' => 'page_title',
            'slug' => 'fallback-page-en',
            'locale' => 'en',
        ]);
        App::setLocale('sv');

        $pathsByLocale = $page->paths->mapWithKeys(fn ($paths) => $paths);

        $this->assertSame('/fallback-page-sv', $pathsByLocale['sv']->first());
        $this->assertSame('/fallback-page-en', $pathsByLocale['en']->first());
        $this->assertSame('/fallback-page-sv', $page->localizedPaths->flatten()->first());
    }

    /** @test **/
    public function it_can_figure_out_a_path_given_an_sha_hash()
    {
        // Arrange
        // Page::truncate();
        $this->markTestSkipped('Refactor this to uuids');
        $menu = Menu::factory()->create();
        $parent = Page::factory()->create([
            'name' => 'parent 1',
        ]);
        $parentItem = MenuItem::create([
            'page_id' => $parent->id,
            'menu_id' => $menu->id,
        ]);
        $childOne = Page::factory()->create([
            'name' => 'child 2',
        ]);
        $subItem = MenuItem::create([
            'page_id' => $childOne->id,
            'menu_id' => $menu->id,
            'parent_id' => $parentItem->id,
        ]);
        $childChildOne = Page::factory()->create([
            'name' => 'child 1 of child 1',
        ]);
        MenuItem::create([
            'page_id' => $childChildOne->id,
            'menu_id' => $menu->id,
            'parent_id' => $subItem->id,
        ]);
        App::setLocale('sv');
        $paths = $childChildOne->transformPaths();

        // Act
        $response = $this->get($paths['permalink']);

        // Assert
        $response->assertRedirect(config('fabriq.front_end_domain').'/'.App::currentLocale().'/parent-1/child-2/child-1-of-child-1');
    }
}
