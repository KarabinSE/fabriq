<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\ContentGetters\ButtonGetter;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\ContentGetters\MediaGetter;
use Karabin\Fabriq\ContentGetters\VideoGetter;
use Karabin\Fabriq\Data\ButtonContentData;
use Karabin\Fabriq\Data\ContentResourceIdsData;
use Karabin\Fabriq\Data\FileContentData;
use Karabin\Fabriq\Data\ImageContentData;
use Karabin\Fabriq\Data\MediaContentData;
use Karabin\Fabriq\Data\VideoContentData;
use Karabin\Fabriq\Models\Block;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\ContentValue;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Video;
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

    public function test_it_stores_block_resource_references_as_ids_and_keeps_locale()
    {
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $block = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'en',
        ]);
        $firstImage = Image::factory()->create();
        $secondImage = Image::factory()->create();
        $file = File::factory()->create();
        $video = Video::factory()->create();

        $response = $this->json('PUT', route('blocks.update', $page->id), [
            'blocks' => [[
                'uuid' => $block->uuid,
                'name' => $block->name,
                'locale' => 'en',
                'block_type_id' => $blockType->id,
                'content' => [
                    'image' => ['id' => $firstImage->id, 'file_name' => 'legacy.jpg'],
                    'file' => ['id' => $file->id, 'file_name' => 'legacy.pdf'],
                    'video' => ['id' => $video->id, 'file_name' => 'legacy.mp4'],
                    'buttons' => [
                        ['linkType' => 'file', 'file' => ['id' => $file->id, 'file_name' => 'legacy.pdf']],
                    ],
                    'gallery' => [
                        ['image' => [['id' => $firstImage->id], ['id' => $secondImage->id]]],
                    ],
                ],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'id' => $block->id,
            'locale' => 'en',
            'content->image' => $firstImage->id,
            'content->file' => $file->id,
            'content->video' => $video->id,
            'content->buttons' => json_encode([
                ['linkType' => 'file', 'file' => $file->id],
            ]),
            'content->gallery' => json_encode([
                ['image' => [$firstImage->id, $secondImage->id]],
            ]),
        ]);
    }

    public function test_it_interpolates_block_image_references_when_reading_blocks()
    {
        $blockType = BlockType::factory()->create();
        $page = Page::factory()->create();
        $firstImage = Image::factory()->create(['alt_text' => 'First block image']);
        $secondImage = Image::factory()->create(['alt_text' => 'Second block image']);
        $file = File::factory()->create(['readable_name' => 'Block attachment']);
        $video = Video::factory()->create(['alt_text' => 'Block video']);
        $block = Block::factory()->create([
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $blockType->id,
            'locale' => 'sv',
            'content' => [
                'image' => $firstImage->id,
                'file' => $file->id,
                'video' => $video->id,
                'buttons' => [
                    ['linkType' => 'file', 'file' => $file->id],
                ],
                'gallery' => [
                    ['image' => [$firstImage->id, $secondImage->id]],
                ],
            ],
        ]);

        $contentSelects = 0;
        DB::listen(function ($query) use (&$contentSelects) {
            if (preg_match('/from\s+[`"]?i18n_(terms|definitions)[`"]?/i', $query->sql)) {
                $contentSelects++;
            }
        });

        $response = $this->json('GET', route('pages.blocks.index', $page->id));

        $response->assertOk();
        $response->assertJsonPath('0.locale', 'sv');
        $response->assertJsonPath('0.content.image.id', $firstImage->id);
        $response->assertJsonPath('0.content.image.alt_text', 'First block image');
        $response->assertJsonPath('0.content.file.id', $file->id);
        $response->assertJsonPath('0.content.file.readable_name', 'Block attachment');
        $response->assertJsonPath('0.content.video.id', $video->id);
        $response->assertJsonPath('0.content.video.alt_text', 'Block video');
        $response->assertJsonPath('0.content.buttons.0.file', $file->id);
        $response->assertJsonPath('0.content.buttons.0.path', $file->getFirstMedia('files')->getUrl());
        $response->assertJsonPath('0.content.gallery.0.image.0.id', $firstImage->id);
        $response->assertJsonPath('0.content.gallery.0.image.1.id', $secondImage->id);
        $this->assertSame(0, $contentSelects);
    }

    public function test_resource_getters_return_data_objects()
    {
        $image = Image::factory()->create();
        $secondImage = Image::factory()->create();
        $file = File::factory()->create();
        $video = Video::factory()->create();

        $imageResult = ImageGetter::get(new ContentValue(['value' => $image->id]));
        $imageListResult = ImageGetter::get(new ContentValue(['value' => [$image->id, $secondImage->id]]));
        $fileResult = FileGetter::get(new ContentValue(['value' => $file->id]));
        $videoResult = VideoGetter::get(new ContentValue(['value' => $video->id]));
        $publishingImageResult = ImageGetter::get(new ContentValue(['value' => $image->id]), true);

        $this->assertInstanceOf(ImageContentData::class, $imageResult);
        $this->assertContainsOnlyInstancesOf(ImageContentData::class, $imageListResult);
        $this->assertInstanceOf(FileContentData::class, $fileResult);
        $this->assertInstanceOf(VideoContentData::class, $videoResult);
        $this->assertInstanceOf(ContentResourceIdsData::class, $publishingImageResult);
        $this->assertSame([$image->id], $publishingImageResult->toArray());
    }

    public function test_button_and_media_getters_return_typed_data_objects()
    {
        $button = ButtonGetter::get(new ContentValue([
            'value' => [
                'text' => 'Visit',
                'linkType' => 'external',
                'url' => 'https://example.com',
                'newTab' => true,
            ],
        ]));
        $media = MediaGetter::get(new ContentValue([
            'value' => [
                'type' => 'image',
                'fullscreen' => false,
                'image' => Image::factory()->create()->id,
            ],
        ]));

        $this->assertInstanceOf(ButtonContentData::class, $button);
        $this->assertSame('https://example.com', $button->toArray()['path']);
        $this->assertSame('Visit', $button->toArray()['text']);
        $this->assertArrayNotHasKey('page_id', $button->toArray());
        $this->assertInstanceOf(MediaContentData::class, $media);
        $this->assertSame('image', $media->toArray()['type']);
        $this->assertFalse($media->toArray()['fullscreen']);
        $this->assertArrayHasKey('image', $media->toArray());
    }

    public function test_media_getter_interpolates_optional_image_and_video_references()
    {
        $image = Image::factory()->create();
        $video = Video::factory()->create();

        $imageMedia = MediaGetter::get(new ContentValue([
            'value' => [
                'type' => 'image',
                'image' => $image->id,
                'video' => $video->id,
            ],
        ]));
        $videoMedia = MediaGetter::get(new ContentValue([
            'value' => [
                'type' => 'video',
                'video' => $video->id,
                'image' => $image->id,
            ],
        ]));

        $this->assertInstanceOf(MediaContentData::class, $imageMedia);
        $this->assertInstanceOf(VideoContentData::class, $imageMedia->video);
        $this->assertInstanceOf(MediaContentData::class, $videoMedia);
        $this->assertInstanceOf(ImageContentData::class, $videoMedia->image);
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
