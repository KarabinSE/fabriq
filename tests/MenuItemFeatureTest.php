<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Data\PageTreeOptionData;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class MenuItemFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_get_a_tree_representation_of_the_menu_items()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $first = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
        ]);
        $second = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $first->id,
        ]);
        $third = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $second->id,
        ]);
        $standAlone = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'sortindex' => 20,
            'parent_id' => $root->id,
        ]);

        // Act
        $response = $this->json('GET', '/menus/'.$menu->id.'/items/tree');

        // Assert
        $response->assertOk();

        // Expect 2 roots
        $response->assertJsonCount(2, 'data');
    }

    /** @test **/
    public function it_can_load_menu_items_with_title_content(): void
    {
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'type' => 'external',
        ]);
        $item->updateContent(['title' => 'External item'], app()->getLocale());

        $response = $this->json('GET', '/menu-items/'.$item->id.'?include=content');

        $response->assertOk();
        $response->assertJsonPath('data.content.data.title', 'External item');

        $this->assertEqualsCanonicalizing(
            ['title', 'url', 'body', 'file', 'external_url'],
            ContentTemplateField::query()
                ->whereHas('template', fn ($query) => $query->where('slug', 'menu-item'))
                ->whereIn('key', ['title', 'url', 'body', 'file', 'external_url'])
                ->pluck('key')
                ->all()
        );
    }

    /** @test **/
    public function it_uses_the_linked_page_title_for_the_menu_item_locale_unless_a_custom_title_is_enabled(): void
    {
        ContentTemplateField::query()->updateOrCreate(
            ['template_id' => 2, 'key' => 'page_title'],
            ['name' => 'Page title', 'type' => 'text', 'translated' => true],
        );
        $menuItemTemplate = ContentTemplate::query()->where('slug', 'menu-item')->firstOrFail();
        ContentTemplateField::query()->updateOrCreate(
            ['template_id' => $menuItemTemplate->id, 'key' => 'title'],
            ['name' => 'Title', 'type' => 'text', 'translated' => true],
        );

        $page = Page::factory()->create([
            'template_id' => 2,
            'name' => 'internt sidnamn',
        ]);
        $page->updateContent(['page_title' => 'Svensk sidtitel'], 'sv');
        $page->updateContent(['page_title' => 'English page title'], 'en');

        app()->setLocale('sv');
        $pageOption = PageTreeOptionData::fromModel($page)->toArray();
        $this->assertSame('internt sidnamn', $pageOption['name']);
        $this->assertSame('Svensk sidtitel', $pageOption['page_title']);

        $menu = Menu::factory()->create();
        $menuItem = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'page_id' => $page->id,
            'type' => 'internal',
            'locale' => 'sv',
            'has_custom_title' => false,
        ]);
        $this->assertSame('Svensk sidtitel', $menuItem->title);

        $menuItem->has_custom_title = true;
        $menuItem->updateContent(['title' => 'Egen svensk titel'], 'sv');
        $menuItem->save();

        $this->assertSame('Egen svensk titel', $menuItem->title);
    }

    /** @test **/
    public function it_includes_file_content_with_filename_in_the_menu_item_tree(): void
    {
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'type' => 'file',
        ]);
        $file = File::factory()->create();
        $item->updateContent(['file' => $file->id], app()->getLocale());

        $response = $this->json('GET', '/menus/'.$menu->id.'/items/tree');

        $response->assertOk();
        $response->assertJsonPath(
            'data.0.content.data.file.file_name',
            $file->getFirstMedia('files')->file_name,
        );
    }

    /** @test **/
    public function it_can_update_a_tree()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $first = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
        ]);
        $second = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $first->id,
        ]);

        $treeData = MenuItem::descendantsOf($root->id)->toTree();

        // Act
        $response = $this->json('PATCH', '/menus/'.$menu->id.'/items/tree', [
            'tree' => $treeData->toArray(),
        ]);

        // Assert
        $response->assertOk();
    }

    /** @test **/
    public function it_removes_content_from_menu_items_at_every_tree_depth_before_updating(): void
    {
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create(['menu_id' => $menu->id]);
        $first = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
        ]);
        $second = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $first->id,
        ]);
        $third = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $second->id,
        ]);

        $response = $this->json('PATCH', '/menus/'.$menu->id.'/items/tree', [
            'tree' => [[
                'id' => $first->id,
                'content' => ['title' => 'First'],
                'children' => [[
                    'id' => $second->id,
                    'content' => ['title' => 'Second'],
                    'children' => [[
                        'id' => $third->id,
                        'content' => ['title' => 'Third'],
                        'children' => [],
                    ]],
                ]],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('menu_items', [
            'id' => $first->id,
            'parent_id' => $root->id,
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $second->id,
            'parent_id' => $first->id,
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $third->id,
            'parent_id' => $second->id,
        ]);
    }

    /** @test **/
    public function it_updates_menu_item_paths_for_the_menu_item_locale_after_tree_update()
    {
        $menu = Menu::factory()->create();
        $rootPage = Page::factory()->create();
        $parentPage = Page::factory()->create();
        $childPage = Page::factory()->create();
        $menuItemTemplate = ContentTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $menuItemTemplate->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => true,
        ]);
        foreach ([
            [$rootPage, 'sv', 'rot-sv'],
            [$rootPage, 'en', 'root-en'],
            [$parentPage, 'sv', 'foralder-sv'],
            [$parentPage, 'en', 'parent-en-old'],
            [$parentPage, 'en', 'parent-en'],
            [$childPage, 'sv', 'barn-sv'],
            [$childPage, 'en', 'child-en'],
        ] as [$page, $locale, $slug]) {
            Slug::create([
                'model_id' => $page->id,
                'model_type' => $page->getMorphClass(),
                'source_string' => $slug,
                'source_key' => 'page_title',
                'slug' => $slug,
                'locale' => $locale,
            ]);
        }

        foreach ([
            ['sv', 'rot-sv', 'foralder-sv', 'barn-sv', 'https://example.se'],
            ['en', 'root-en', 'parent-en', 'child-en', 'https://example.com'],
        ] as [$locale, $rootSlug, $parentSlug, $childSlug, $externalUrl]) {
            $root = MenuItem::factory()->create([
                'menu_id' => $menu->id,
                'page_id' => $rootPage->id,
                'locale' => $locale,
            ]);
            $parentItem = MenuItem::factory()->create([
                'menu_id' => $menu->id,
                'parent_id' => $root->id,
                'page_id' => $parentPage->id,
                'locale' => $locale,
            ]);
            $childItem = MenuItem::factory()->create([
                'menu_id' => $menu->id,
                'parent_id' => $parentItem->id,
                'page_id' => $childPage->id,
                'locale' => $locale,
            ]);
            $externalItem = MenuItem::factory()->create([
                'menu_id' => $menu->id,
                'parent_id' => $root->id,
                'type' => 'external',
                'locale' => $locale,
            ]);
            $externalItem->updateContent(['external_url' => $externalUrl], $locale);

            $treeData = MenuItem::descendantsOf($root->id)->toTree()->toArray();
            $this->withHeaders(['X-LOCALE' => $locale])
                ->json('PATCH', '/menus/'.$menu->id.'/items/tree', ['tree' => $treeData])
                ->assertOk();

            $this->assertDatabaseHas('menu_items', [
                'id' => $root->id,
                'path' => '/'.$rootSlug,
            ]);
            $this->assertDatabaseHas('menu_items', [
                'id' => $parentItem->id,
                'path' => '/'.$rootSlug.'/'.$parentSlug,
            ]);
            $this->assertDatabaseHas('menu_items', [
                'id' => $childItem->id,
                'path' => '/'.$rootSlug.'/'.$parentSlug.'/'.$childSlug,
            ]);
            $this->assertDatabaseHas('menu_items', [
                'id' => $externalItem->id,
                'path' => $externalUrl,
            ]);
        }
    }

    /** @test **/
    public function it_can_update_a_single_menu_item()
    {
        // Arrange
        $page = Page::factory()->create();
        Event::fake(ContentUpdated::class);
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $first = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
        ]);

        // Act
        $response = $this->json('PATCH', '/menu-items/'.$first->id, [
            'item' => [
                'type' => 'internal',
                'page_id' => $page->id,
            ],
            'has_custom_title' => false,
            'content' => [],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('menu_items', [
            'page_id' => $page->id,
            'type' => 'internal',
        ]);
        Event::assertDispatchedTimes(ContentUpdated::class, 1);
        Event::assertDispatched(function (ContentUpdated $event) {
            if (get_class($event->model) === Fabriq::getFqnModel('menuItem')) {
                return $event->model->getContentCacheKeys()[0] === 'fabriq_menu';
            }

            return false;
        });
    }

    /** @test **/
    public function it_can_get_single_menu_item()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $page = Page::factory()->create();
        $first = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
            'page_id' => $page->id,
        ]);

        // Act
        $response = $this->json('GET', '/menu-items/'.$first->id.'?include=page');

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'name' => $page->name,
        ]);
    }

    /** @test **/
    public function it_can_store_a_new_menu_item()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $page = Page::factory()->create();

        // Act
        $response = $this->json('POST', '/menus/'.$menu->id.'/items', [
            'item' => [
                'page_id' => $page->id,
                'type' => 'internal',
            ],
            'has_custom_title' => false,
            'content' => [],
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('menu_items', [
            'page_id' => $page->id,
            'type' => 'internal',
        ]);
    }

    /** @test **/
    public function it_can_create_an_item_with_external_url()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);

        $template = ContentTemplate::query()->where('slug', 'menu-item')->firstOrFail();
        ContentTemplateField::query()
            ->where('template_id', $template->id)
            ->where('key', 'title')
            ->update(['translated' => false]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => false,
        ]);

        // Act
        $response = $this->json('POST', '/menus/'.$menu->id.'/items', [
            'item' => [
                'page_id' => null,
                'type' => 'external',
            ],
            'has_custom_title' => false,
            'content' => [
                'title' => 'Sök på google',
                'external_url' => 'https://google.se',
            ],
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('menu_items', [
            'page_id' => null,
            'type' => 'external',
        ]);
        $this->assertDatabaseHas('i18n_definitions', [
            'content' => json_encode(['*' => 'Sök på google'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $this->assertDatabaseHas('i18n_definitions', [
            'content' => json_encode(['en' => 'https://google.se'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    /** @test **/
    public function it_can_delete_an_item()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
        ]);
        $subItem = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $item->id,
        ]);

        // Act
        $response = $this->json('DELETE', '/menu-items/'.$item->id);

        // Assert
        $response->assertOk();
        $this->assertDatabaseMissing('menu_items', [
            'id' => $item->id,
        ]);
        // The childeren shall also be deleted
        $this->assertDatabaseMissing('menu_items', [
            'id' => $subItem->id,
        ]);
    }

    /** @test **/
    public function it_can_include_localized_content()
    {
        // Arrange
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);

        $template = ContentTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        $field = ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
            'type' => 'text',
            'translated' => true,
        ]);
        $field = ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => true,
        ]);
        $menuItem = MenuItem::factory()->create([
            'menu_id' => $menu,
            'type' => 'external',
        ]);
        $menuItem->updateContent([
            'title' => 'Hello',
        ], 'en');
        $menuItem->updateContent([
            'title' => 'Hej',
        ], 'sv');

        // Act
        $response = $this->json('GET', '/menu-items/'.$menuItem->id.'?include=localizedContent');

        //
        $response->assertOk();
        // dd(I18nDefinition::all()->toArray());
        $response->assertJsonFragment([
            'sv' => [
                'content' => [
                    'title' => 'Hej',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'en' => [
                'content' => [
                    'title' => 'Hello',
                ],
            ],
        ]);
    }

    /** @test **/
    public function it_can_update_a_menu_item()
    {
        // Arrange
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);

        $template = ContentTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        $field = ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
            'type' => 'text',
            'translated' => false,
        ]);
        $field = ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => false,
        ]);
        $menuItem = MenuItem::factory()->create([
            'menu_id' => $menu,
            'type' => 'external',
        ]);
        $menuItem->updateContent([
            'title' => 'Hello',
        ], 'en');
        $menuItem->updateContent([
            'title' => 'Hej',
        ], 'sv');

        $this->withoutExceptionHandling();

        // Act
        $response = $this->json('PATCH', '/menu-items/'.$menuItem->id.'?include=content', [
            'item' => [
                'type' => 'external',
            ],
            'has_custom_title' => false,
            'content' => [
                'title' => 'En extern',
                'external_url' => 'https://google.se',
            ],
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'content' => [
                'data' => [
                    'title' => 'En extern',
                    'external_url' => 'https://google.se',
                ],
            ],
        ]);
    }

    /** @test **/
    public function it_will_prepend_a_slash_to_the_relative_url()
    {
        // Arrange
        Schema::disableForeignKeyConstraints();
        Page::truncate();
        Schema::enableForeignKeyConstraints();
        $menu = Menu::factory()->create(['slug' => 'main_menu']);
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $page = Page::factory()->create([
            'template_id' => 3,
        ]);

        ContentTemplateField::factory()->create([
            'key' => 'page_title',
            'translated' => true,
            'template_id' => 3,
        ]);
        $page->updateContent(['page_title' => 'The root page'], 'en');
        $subPage = Page::factory()->create([
            'template_id' => 3,
            'parent_id' => $page->id,
        ]);
        $subPage->updateContent(['page_title' => 'The child page'], 'en');
        $menuItem = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'page_id' => $page->id,
        ]);
        $subMenuItem = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $menuItem->id,
            'page_id' => $subPage->id,
        ]);
        $this->json('PATCH', '/menus/'.$menu->id.'/items/tree', [
            'tree' => [[
                'id' => $menuItem->id,
                'children' => [[
                    'id' => $subMenuItem->id,
                    'children' => [],
                ]],
            ]],
        ])->assertOk();

        // Act
        $response = $this->withHeaders(['X-LOCALE' => 'en'])
            ->json('GET', '/menus/'.$menu->slug.'/public/'.'?include=children');

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'path' => '/the-root-page/the-child-page',
        ]);
        $response->assertJsonFragment([
            'localized_path' => '/en/the-root-page/the-child-page',
        ]);
    }
}
