<?php

namespace Tests\Feature;

use Karabin\Fabriq\Database\Seeders\DatabaseSeeder;
use Karabin\Fabriq\Database\Seeders\PageTemplateSeeder;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class ClonePageFeatureTest extends AdminUserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(DatabaseSeeder::class)->call(PageTemplateSeeder::class);
    }

    /** @test **/
    public function it_can_clone_another_page()
    {
        // Arrange
        $root = Page::factory()->create([
            'name' => 'root',
            'template_id' => ContentTemplate::all()->first()->id,
        ]);
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => ContentTemplate::all()->first()->id,
        ]);
        $image = Image::factory()->create([
            'id' => 12,
        ]);
        $page->updateContent([
            'page_title' => 'Att klona',
            'page_header' => 'The page title for the page',
            'page_content' => '<h1>Wow, a header</h1><p>Ok lets see</p>',
            'meta_title' => 'Meta title',
            'meta_description' => 'Describing the page',
            'boxes' => [
                [
                    'name' => 'El blocko',
                    'block_type' => [
                        'name' => 'Demo-block',
                        'has_children' => true,
                    ],
                    'children' => [
                        [
                            'id' => 'if0i8d5',
                            'hasImage' => true,
                            'image' => [
                                'id' => 12,
                            ],
                        ],
                    ],
                ],
            ],
        ], 'sv', 1);

        $image->addMediaFromString('A nice media')
            ->toMediaCollection('profile_image');
        $image->save();
        $page->save();

        // Act
        $response = $this->json('POST', route('pages.clone.store', $page->id).'?include=localizedContent');

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('pages', [
            'name' => 'Kopia av Den första startsidan',
            'template_id' => 1,
        ]);
        $response->assertJson([
            'data' => [
                'localizedContent' => [
                    'data' => [
                        'sv' => [
                            'content' => [
                                'page_title' => 'Att klona',
                                'boxes' => [
                                    [
                                        'name' => 'El blocko',
                                        'block_type' => [
                                            'name' => 'Demo-block',
                                            'has_children' => true,
                                        ],
                                        'children' => [
                                            [
                                                'id' => 'if0i8d5',
                                                'hasImage' => true,
                                                'image' => [
                                                    'id' => 12,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
