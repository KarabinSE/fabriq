<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\I18nDefinition;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Tests\AdminUserTestCase;
use Karabin\TranslatableRevisions\Events\TranslatedRevisionUpdated;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;
use Karabin\TranslatableRevisions\Models\RevisionTemplateField;

class MenuItemFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_get_a_tree_representation_of_the_menu_items()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $first = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
        ]);
        $second = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $first->id,
        ]);
        $third = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $second->id,
        ]);
        $standAlone = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'sortindex' => 20,
            'parent_id' => $root->id,
        ]);

        // Act
        $response = $this->json('GET', '/menus/'.$menu->id.'/items/tree');

        // Assert
        $response->assertOk();

        //Expect 2 roots
        $response->assertJsonCount(2, 'data');
    }

    /** @test **/
    public function it_can_update_a_tree()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $first = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
        ]);
        $second = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $first->id,
        ]);

        $treeData = \Karabin\Fabriq\Models\MenuItem::descendantsOf($root->id)->toTree();

        // Act
        $response = $this->json('PATCH', '/menus/'.$menu->id.'/items/tree', [
            'tree' => $treeData->toArray(),
        ]);

        // Assert
        $response->assertOk();
    }

    /** @test **/
    public function it_updates_menu_item_paths_for_each_locale_after_tree_update()
    {
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $rootPage = Page::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'page_id' => $rootPage->id,
        ]);
        $parentPage = Page::factory()->create();
        $childPage = Page::factory()->create();
        $parentItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'page_id' => $parentPage->id,
        ]);
        $childItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parentItem->id,
            'page_id' => $childPage->id,
        ]);
        $menuItemTemplate = RevisionTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        RevisionTemplateField::factory()->create([
            'template_id' => $menuItemTemplate->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => true,
        ]);
        $externalItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'type' => 'external',
        ]);
        $externalItem->updateContent(['external_url' => 'https://example.se'], 'sv');
        $externalItem->updateContent(['external_url' => 'https://example.com'], 'en');

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

        $treeData = \Karabin\Fabriq\Models\MenuItem::descendantsOf($root->id)
            ->toTree()
            ->toArray();

        $response = $this->json('PATCH', '/menus/'.$menu->id.'/items/tree', [
            'tree' => $treeData,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('menu_items', [
            'id' => $root->id,
            'path->sv' => '/rot-sv',
            'path->en' => '/root-en',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $parentItem->id,
            'path->sv' => '/rot-sv/foralder-sv',
            'path->en' => '/root-en/parent-en',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $childItem->id,
            'path->sv' => '/rot-sv/foralder-sv/barn-sv',
            'path->en' => '/root-en/parent-en/child-en',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'id' => $externalItem->id,
            'path->sv' => 'https://example.se',
            'path->en' => 'https://example.com',
        ]);
    }

    /** @test **/
    public function it_can_update_a_single_menu_item()
    {
        // Arrange
        Event::fake(TranslatedRevisionUpdated::class);

        $page = \Karabin\Fabriq\Models\Page::factory()->create();
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $first = \Karabin\Fabriq\Models\MenuItem::factory()->create([
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
            'content' => [],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('menu_items', [
            'page_id' => $page->id,
            'type' => 'internal',
        ]);
        Event::assertDispatchedTimes(TranslatedRevisionUpdated::class, 2);
        Event::assertDispatched(function (TranslatedRevisionUpdated $event) {
            if (get_class($event->model) === Fabriq::getFqnModel('menuItem')) {
                return $event->model->getRevisionOptions()->cacheKeysToFlush[0] === 'fabriq_menu';
            }
        });
    }

    /** @test **/
    public function it_can_get_single_menu_item()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $page = \Karabin\Fabriq\Models\Page::factory()->create();
        $first = \Karabin\Fabriq\Models\MenuItem::factory()->create([
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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $page = \Karabin\Fabriq\Models\Page::factory()->create();

        // Act
        $response = $this->json('POST', '/menus/'.$menu->id.'/items', [
            'item' => [
                'page_id' => $page->id,
                'type' => 'internal',
            ],
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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);

        $template = RevisionTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        $field = RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
            'type' => 'text',
            'translated' => false,
        ]);
        $field = RevisionTemplateField::factory()->create([
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
        $this->assertDatabaseHas('revision_meta', [
            'meta_value' => json_encode('Sök på google'),
        ]);
        $this->assertDatabaseHas('revision_meta', [
            'meta_value' => json_encode('https://google.se'),
        ]);
    }

    /** @test **/
    public function it_can_delete_an_item()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $item = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
        ]);
        $subItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);

        $template = RevisionTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        $field = RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
            'type' => 'text',
            'translated' => true,
        ]);
        $field = RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => true,
        ]);
        $menuItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);

        $template = RevisionTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        $field = RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
            'type' => 'text',
            'translated' => false,
        ]);
        $field = RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => false,
        ]);
        $menuItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create(['slug' => 'main_menu']);
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $page = \Karabin\Fabriq\Models\Page::factory()->create([
            'template_id' => 3,
        ]);

        RevisionTemplateField::factory()->create([
            'key' => 'page_title',
            'translated' => true,
            'template_id' => 3,
        ]);
        $page->updateContent(['page_title' => 'The root page'], 'en');
        $subPage = \Karabin\Fabriq\Models\Page::factory()->create([
            'template_id' => 3,
            'parent_id' => $page->id,
        ]);
        $subPage->updateContent(['page_title' => 'The child page'], 'en');
        $menuItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'page_id' => $page->id,
        ]);
        $subMenuItem = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $menuItem->id,
            'page_id' => $subPage->id,
        ]);

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
