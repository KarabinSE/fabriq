<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class MenuFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_get_all_menus()
    {
        // Arrange
        $menus = Menu::factory()->count(2)->create();

        // Act
        $response = $this->json('GET', '/menus');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /** @test **/
    public function it_can_get_a_single_menu()
    {
        // Arrange
        $menus = Menu::factory()->count(2)->create();

        // Act
        $response = $this->json('GET', '/menus/'.$menus->first()->id);

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'name' => $menus->first()->name,
            'slug' => (string) $menus->first()->slug,
        ]);
    }

    /** @test **/
    public function it_can_store_a_new_menu()
    {
        // Arrange
        $this->withoutExceptionHandling();

        // Act
        $response = $this->json('POST', '/menus', [
            'name' => 'Ny meny',
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('menus', [
            'name' => 'Ny meny',
        ]);
        $this->assertDatabaseHas('menu_items', [
            '_lft' => 1,
            '_rgt' => 2,
        ]);
    }

    /** @test **/
    public function it_can_store_a_new_menu_when_webhooks_are_enabled_without_an_endpoint()
    {
        // Arrange
        $this->withoutExceptionHandling();
        config()->set('fabriq.webhooks.enabled', true);
        config()->set('fabriq.webhooks.endpoint', null);

        // Act
        $response = $this->json('POST', '/menus', [
            'name' => 'Webhookless menu',
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('menus', [
            'name' => 'Webhookless menu',
        ]);
    }

    /** @test **/
    public function it_can_update_a_menu()
    {
        // Arrange
        $menu = Menu::factory()->create();

        // Act
        $response = $this->json('PATCH', '/menus/'.$menu->id, [
            'name' => 'Nytt namn',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('menus', [
            'name' => 'Nytt namn',
        ]);
    }

    /** @test **/
    public function it_can_delete_a_menu()
    {
        // Arrange
        $menu = Menu::factory()->create();

        // Act
        $response = $this->json('DELETE', '/menus/'.$menu->id);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('menus', [
            'id' => $menu->id,
        ]);
    }

    /** @test **/
    public function it_can_get_a_public_menu()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $menu = Menu::factory()->create([
            'slug' => 'main_menu',
            'name' => 'Huvudmeny',
        ]);
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $field = ContentTemplateField::factory()->create([
            'template_id' => 2,
            'key' => 'page_title',
            'translated' => true,
        ]);
        $page = Page::factory()->create(['template_id' => 2]);
        $page->updateContent(['page_title' => 'En titel'], 'en');
        $page->save();

        $page2 = Page::factory()->create(['template_id' => 2]);
        $page2->updateContent(['page_title' => 'En annan titel'], 'en');
        $page2->save();
        $first = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
            'type' => 'internal',
            'page_id' => $page->id,
        ]);
        $second = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $first->id,
            'type' => 'internal',
            'page_id' => $page2->id,
        ]);
        $this->json('PATCH', '/menus/'.$menu->id.'/items/tree', [
            'tree' => [[
                'id' => $first->id,
                'children' => [[
                    'id' => $second->id,
                    'children' => [],
                ]],
            ]],
        ])->assertOk();

        // Act
        $response = $this->json('GET', '/menus/'.$menu->slug.'/public/'.'?include=children');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'path' => '/en-titel',
        ]);
        $response->assertJsonFragment([
            'path' => '/en-titel/en-annan-titel',
        ]);
    }

    /** @test **/
    public function it_batches_menu_item_content_reads_for_the_public_menu()
    {
        $menu = Menu::factory()->create(['slug' => 'batched_menu']);
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'type' => 'internal',
        ]);

        foreach (['First item', 'Second item'] as $title) {
            $item = MenuItem::factory()->create([
                'menu_id' => $menu->id,
                'parent_id' => $root->id,
                'type' => 'external',
            ]);
            $item->updateContent(['title' => $title], 'sv');
        }

        $definitionSelects = 0;
        DB::listen(function ($query) use (&$definitionSelects) {
            if (preg_match('/from\s+[`"]?i18n_definitions[`"]?/i', $query->sql)) {
                $definitionSelects++;
            }
        });

        $response = $this->json('GET', '/menus/'.$menu->slug.'/public/?include=children');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'First item']);
        $response->assertJsonFragment(['title' => 'Second item']);
        $this->assertSame(1, $definitionSelects);
    }
}
