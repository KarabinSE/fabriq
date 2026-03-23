<?php

namespace Tests\Feature;

use Karabin\Fabriq\Tests\AdminUserTestCase;
use Karabin\TranslatableRevisions\Models\RevisionTemplateField;

class MenuFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_get_all_menus()
    {
        // Arrange
        $menus = \Karabin\Fabriq\Models\Menu::factory()->count(2)->create();

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
        $menus = \Karabin\Fabriq\Models\Menu::factory()->count(2)->create();

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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();

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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create();

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
        $menu = \Karabin\Fabriq\Models\Menu::factory()->create([
            'slug' => 'main_menu',
            'name' => 'Huvudmeny',
        ]);
        $root = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $field = RevisionTemplateField::factory()->create([
            'template_id' => 2,
            'key' => 'page_title',
            'translated' => true,
        ]);
        $page = \Karabin\Fabriq\Models\Page::factory()->create(['template_id' => 2]);
        $page->updateContent(['page_title' => 'En titel'], 'en');
        $page->save();

        $page2 = \Karabin\Fabriq\Models\Page::factory()->create(['template_id' => 2]);
        $page2->updateContent(['page_title' => 'En annan titel'], 'en');
        $page2->save();
        $first = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $root->id,
            'type' => 'internal',
            'page_id' => $page->id,
        ]);
        $second = \Karabin\Fabriq\Models\MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'sortindex' => 10,
            'parent_id' => $first->id,
            'type' => 'internal',
            'page_id' => $page2->id,
        ]);

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
}
