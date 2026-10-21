<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\Models\I18nDefinition;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\SmartBlock;
use Karabin\Fabriq\Models\Video;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class SmartBlocksFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_store_a_new_smart_block()
    {
        // Arrange
        $this->withoutExceptionHandling();

        // Act
        $response = $this->json('POST', '/smart-blocks', [
            'name' => 'Find store smart block',
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('smart_blocks', [
            'name' => 'Find store smart block',
        ]);
    }

    /** @test **/
    public function it_can_update_a_smart_block()
    {
        // Arrange
        $smartBlock = SmartBlock::factory()->create();
        $image = Image::factory()->create();
        $video = Video::factory()->create();
        $file = File::factory()->create();
        $file2 = File::factory()->create();

        // Act
        $response = $this->json('PATCH', '/smart-blocks/'.$smartBlock->id, [
            'name' => 'Another name',
            'localizedContent' => [
                'sv' => [
                    'boxes' => [
                        [
                            'name' => 'Superblocket',
                            'image' => $image->id,
                            'video' => $video->id,
                            'file' => $file2->id,
                        ],
                    ],
                ],
                'en' => [
                    'boxes' => [
                        [
                            'name' => 'The super block',
                        ],
                    ],
                ],
            ],
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('smart_blocks', [
            'id' => $smartBlock->id,
            'name' => 'Another name',
        ]);
        $this->assertSame([['name' => 'The super block']], $smartBlock->getFieldContent('en')->get('boxes'));

        $boxesDefinition = I18nDefinition::query()
            ->where('model_id', $smartBlock->id)
            ->where('model_type', $smartBlock->getMorphClass())
            ->where('field_key', 'boxes')
            ->firstOrFail();
        $this->assertSame(
            [
                'sv' => [['name' => 'Superblocket', 'image' => $image->id, 'video' => $video->id, 'file' => $file2->id]],
                'en' => [['name' => 'The super block']],
            ],
            $boxesDefinition->getTranslations('content')
        );
    }

    /** @test **/
    public function it_can_get_all_smart_blocks()
    {
        // Arrange
        $smartBlocks = SmartBlock::factory()->count(3)->create();

        // Act
        $response = $this->json('GET', '/smart-blocks');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /** @test **/
    public function it_can_sort_smart_blocks()
    {
        // Arrange
        $smartBlocks = SmartBlock::factory()->count(3)->create();
        $smartBlock = SmartBlock::factory()->create([
            'name' => '1st :)',
        ]);

        // Act
        $response = $this->json('GET', '/smart-blocks?sort=name');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(4, 'data');
        $this->assertEquals('1st :)', $response->json()['data'][0]['name']);
    }

    /** @test **/
    public function it_can_search_for_a_smart_block()
    {
        // Arrange
        $smartBlocks = SmartBlock::factory()->count(3)->create();
        $smartBlock = SmartBlock::factory()->create([
            'name' => 'Find me',
        ]);

        // Act
        $response = $this->json('GET', '/smart-blocks?filter[search]=Find me');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    /** @test **/
    public function it_can_get_a_single_smart_block()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $smartBlock = SmartBlock::factory()->create();
        $image = Image::factory()->create([
            'alt_text' => 'Image alt text',
        ]);
        $video = Video::factory()->create([
            'alt_text' => 'Video alt text',
        ]);
        $file = File::factory()->create([
            'caption' => 'File caption',
        ]);
        $smartBlock->localizedContent = [
            'sv' => [
                'boxes' => [
                    [
                        'name' => 'Superblocket',
                        'image' => $image->id,
                        'video' => $video->id,
                        'file' => $file->id,
                    ],
                ],
            ],
            'en' => [
                'boxes' => [
                    [
                        'name' => 'The super block',
                    ],
                ],
            ],
        ];
        $smartBlock->save();

        // Act
        app()->setLocale('sv');
        $response = $this->json('GET', '/smart-blocks/'.$smartBlock->id.'?include=localizedContent,content');

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Superblocket',
        ]);
        $response->assertJsonFragment([
            'alt_text' => 'Image alt text',
        ]);
        $response->assertJsonFragment([
            'alt_text' => 'Video alt text',
        ]);
        $response->assertJsonFragment([
            'caption' => 'File caption',
        ]);
    }

    /** @test **/
    public function it_batches_image_lookups_and_resolves_multiple_image_ids()
    {
        $firstBlock = SmartBlock::factory()->create();
        $secondBlock = SmartBlock::factory()->create();
        $images = Image::factory()->count(3)->create();

        $firstBlock->updateContent([
            'boxes' => [
                ['image' => [$images[0]->id, $images[1]->id]],
            ],
        ], 'sv');
        $secondBlock->updateContent([
            'boxes' => [
                ['image' => $images[2]->id],
            ],
        ], 'sv');

        app()->setLocale('sv');
        $imageSelects = 0;
        DB::listen(function ($query) use (&$imageSelects) {
            if (preg_match('/from\s+[`"]?images[`"]?/i', $query->sql)) {
                $imageSelects++;
            }
        });

        $response = $this->json('GET', '/smart-blocks?include=content');

        $response->assertOk();
        $response->assertJsonFragment(['alt_text' => $images[0]->alt_text]);
        $response->assertJsonFragment(['alt_text' => $images[1]->alt_text]);
        $response->assertJsonFragment(['alt_text' => $images[2]->alt_text]);
        $this->assertSame(1, $imageSelects);
        $this->assertSame(
            [['image' => [$images[0]->id, $images[1]->id]]],
            I18nDefinition::query()
                ->where('model_id', $firstBlock->id)
                ->where('model_type', $firstBlock->getMorphClass())
                ->where('field_key', 'boxes')
                ->firstOrFail()
                ->getTranslation('content', 'sv')
        );
    }

    /** @test **/
    public function it_can_destroy_a_smart_block()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $smartBlock = SmartBlock::factory()->create();

        // Act
        $response = $this->json('DELETE', '/smart-blocks/'.$smartBlock->id);

        // Assert
        $response->assertOk();
        $this->assertDatabaseMissing('smart_blocks', [
            'id' => $smartBlock->id,
        ]);
    }
}
