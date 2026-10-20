<?php

namespace Tests\Feature;

use Karabin\Fabriq\Models\Block;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class BlockFeatureTest extends AdminUserTestCase
{
    public function test_it_can_get_a_list_of_blocks_attached_to_a_page()
    {
        // Arrange
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $blocks = Block::factory(4)->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
        ]);
        Block::setNewOrder($blocks->pluck('id'));
        // $blocks->setNewOrder();

        // Act
        $response = $this->json('GET', route('pages.blocks.index', $page->id));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(4);
        $this->assertEquals($response->json()[0]['sortindex'], 1);
        $this->assertEquals(2, $response->json()[1]['sortindex']);
        $this->assertEquals(4, $response->json()[3]['sortindex']);
    }

    public function test_it_can_include_its_block_type()
    {
        // Arrange
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $blocks = Block::factory(4)->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
        ]);

        // Act
        $response = $this->json('GET', route('pages.blocks.index', ['id' => $page->id, 'include' => 'blockType']));

        // Assert
        $response->assertOk();
        $this->assertEquals($response->json()[0]['blockType']['name'], $blockType->name);
    }

    public function test_it_can_filter_by_locale()
    {
        // Arrange
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $blocks = Block::factory(4)->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'sv',
        ]);
        $enBlocks = Block::factory(2)->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'en',
        ]);

        // Act
        $response = $this->json('GET', route('pages.blocks.index', ['id' => $page->id, 'filter[locale]' => 'en']));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_it_can_get_a_single_block()
    {
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $block = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
        ]);

        // Act
        $response = $this->json('GET', route('blocks.show', ['id' => $block->id]));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'uuid',
            'name',
            'locale',
            'hidden',
            'parent_id',
            'block_type_id',
            'sortindex',
        ]);
    }

    public function test_it_can_update_multiple_blocks_at_once()
    {
        // Arrange
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $block1 = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'sv',
            'content' => ['title' => 'En titel'],
        ]);
        $block2 = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'en',
            'content' => ['title' => 'A title'],
        ]);
        $block2->content = ['title' => 'Changed title'];

        // Act
        $response = $this->json('PUT', route('blocks.update', $page->id), [
            'blocks' => [$block1, $block2],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'id' => $block2->id,
            'content->title' => 'Changed title',
            'locale' => 'en',
        ]);
    }

    public function test_it_can_update_or_create_multiple_blocks_at_once()
    {
        // Arrange
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $block1 = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'sv',
            'content' => ['title' => 'En titel'],
        ]);
        $block2 = [
            'name' => 'Demo-block',
            'locale' => 'en',
            'block_type_id' => $blockType->id,
            'content' => ['title' => 'A very new title'],
        ];

        // Act
        $response = $this->json('PUT', route('blocks.update', $page->id), [
            'blocks' => [$block1->toArray(), $block2],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'content->title' => 'A very new title',
            'locale' => 'en',
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
        ]);
        $this->assertDatabaseMissing('blocks', [
            'uuid' => null,
            'content->title' => 'A very new title',
            'locale' => 'en',
        ]);
    }

    public function test_it_will_destroy_blocks_that_are_not_present_in_the_update()
    {
        // Arrange
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $block1 = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'sv',
            'content' => ['title' => 'En titel'],
        ]);

        $block2 = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'sv',
            'content' => ['title' => 'En titel'],
        ]);
        $blockForAnotherModelType = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => 'fabriq_article',
            'block_type_id' => $blockType->id,
        ]);
        $anotherPage = Page::factory()->create();
        $blockForAnotherPage = Block::factory()->create([
            'model_id' => $anotherPage->id,
            'model_type' => $anotherPage->getMorphClass(),
            'block_type_id' => $blockType->id,
        ]);

        // Act
        $response = $this->json('PUT', route('blocks.update', $page->id), [
            'blocks' => [$block1->toArray()],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'id' => $block1->id,
        ]);
        $this->assertDatabaseMissing('blocks', [
            'id' => $block2->id,
        ]);
        $this->assertDatabaseHas('blocks', [
            'id' => $blockForAnotherModelType->id,
        ]);
        $this->assertDatabaseHas('blocks', [
            'id' => $blockForAnotherPage->id,
        ]);
    }

    public function test_it_will_destroy_all_page_blocks_when_the_update_is_empty()
    {
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $block = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
        ]);

        $response = $this->json('PUT', route('blocks.update', $page->id), [
            'blocks' => [],
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('blocks', [
            'id' => $block->id,
        ]);
    }
}
