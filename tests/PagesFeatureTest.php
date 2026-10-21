<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Karabin\Fabriq\Database\Seeders\DatabaseSeeder;
use Karabin\Fabriq\Database\Seeders\PageTemplateSeeder;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\RevisionTemplate;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class PagesFeatureTest extends AdminUserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(DatabaseSeeder::class)->call(PageTemplateSeeder::class);
    }

    /** @test **/
    public function it_can_get_a_single_page()
    {
        // Arrange
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);

        // Act
        $response = $this->json('GET', '/pages/'.$page->id);

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $page->id,
            // 'locked' => true,
        ]);
    }

    /** @test **/
    public function it_can_include_content_for_a_single_page()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);
        $arr = ['page_title' => 'The page title for the page',
            'page_header' => 'The page title for the page',
            'page_content' => '<h1>Wow, a header</h1><p>Ok lets see</p>',
            'meta_title' => 'Meta title',
            'meta_description' => 'Describing the page',
            'meta_og_image' => 'https://placehold.it/40',
            'boxes' => [
                ['title' => 'Box 1 title!', 'url' => 'https://google.com'],
                ['title' => 'Box 2 title!', 'url' => 'https://bog.com'],
                ['title' => 'Box 3 title!', 'url' => 'http://flank.se'],
            ], ];
        $page->updateContent([
            'page_title' => 'The page title for the page',
            'page_header' => 'The page title for the page',
            'page_content' => '<h1>Wow, a header</h1><p>Ok lets see</p>',
            'meta_title' => 'Meta title',
            'meta_description' => 'Describing the page',
            'boxes' => [
                ['title' => 'Box 1 title!', 'url' => 'https://google.com'],
                ['title' => 'Box 2 title!', 'url' => 'https://bog.com'],
                ['title' => 'Box 3 title!', 'url' => 'http://flank.se'],
            ],
        ], 'en');

        // Act
        $response = $this->json('GET', '/pages/'.$page->id.'?include=content');

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'page_title' => 'The page title for the page',
            'page_header' => 'The page title for the page',
            'page_content' => '<h1>Wow, a header</h1><p>Ok lets see</p>',
            'meta_title' => 'Meta title',
            'meta_description' => 'Describing the page',
        ]);
        $response->assertJsonCount(3, 'data.content.data.boxes');
    }

    /** @test **/
    public function it_can_include_all_template_fields()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::where('slug', 'startsida')->first()->id,
        ]);

        // Act
        $response = $this->json('GET', '/pages/'.$page->id.'?include=template.fields');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(8, 'data.template.data.fields.data');
        $response->assertJsonStructure([
            'data' => [
                'template' => [
                    'data' => [
                        'fields' => [],
                    ],
                ],
            ],
        ]);
    }

    /** @test **/
    public function it_can_get_all_pages()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);
        $otherPage = Page::factory()->create([
            'name' => 'Den andra startsidan',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);

        // Act
        $response = $this->json('GET', '/pages');

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $page->id,
        ]);
        $response->assertJsonFragment([
            'id' => $otherPage->id,
        ]);
    }

    /** @test **/
    public function it_can_update_page_content()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);
        DB::table('slugs')->insert([
            'model_id' => Page::factory()->create()->id,
            'model_type' => 'fabriq_page',
            'slug' => 'en-mycket-bra-titel',
            'locale' => app()->getLocale(),
            'source_string' => 'En mycket bra titel',
            'source_key' => 'page_1_1_page_title',
        ]);

        // Act
        $response = $this->json('PATCH', '/pages/'.$page->id.'?include=content', [
            'name' => 'Same as before',
            'published' => true,
            'localizedContent' => [
                'sv' => [
                    'page_title' => 'En mycket bra titel',
                    'boxes' => [['title' => 'One box']],
                ],
                'en' => [
                    'page_title' => 'English title',
                    'page_header' => 'The new header',
                    'page_content' => '<p>sweet</p>',
                    'boxes' => [['title' => 'One box']],
                ],
            ],
        ]);

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'page_title' => 'English title',
            'page_header' => 'The new header',
            'page_content' => '<p>sweet</p>',
            'published' => true,
            'boxes' => [['title' => 'One box']],
        ]);
        $this->assertDatabaseHas('pages', [
            'updated_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('slugs', [
            'model_id' => $page->id,
            'model_type' => 'fabriq_page',
            'locale' => 'sv',
            'slug' => 'en-mycket-bra-titel-1',
        ]);
        $definition = $page->contentTerms()->where('field_key', 'page_title')->first()->definition;
        $this->assertSame('English title', $definition->getTranslation('content', 'en'));
        $this->assertSame('En mycket bra titel', $definition->getTranslation('content', 'sv'));
    }

    /** @test **/
    public function it_can_store_a_new_a_page()
    {
        // Act
        $root = Page::factory()->create(['name' => 'root']);
        $response = $this->json('POST', '/pages', [
            'name' => 'Ny sida',
            'template_id' => 1,
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('pages', [
            'name' => 'Ny sida',
            'template_id' => 1,
            'published' => false,
            'parent_id' => $root->id,
            'updated_by' => $this->user->id,
        ]);
    }

    /** @test **/
    public function it_can_delete_a_page()
    {
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);

        // Act
        $response = $this->json('delete', '/pages/'.$page->id);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('pages', [
            'id' => $page->id,
        ]);
    }

    /** @test **/
    public function it_can_get_a_page_via_slug()
    {
        // Arrange
        $page = Page::factory()->create();
        $page->updateContent([
            'page_title' => 'The page title for the page',
        ], 'sv');
        $otherPage = Page::factory()->create();
        $otherPage->updateContent([
            'page_title' => 'The page title for the page',
        ], 'sv');

        // Act
        $foundPage = Page::whereSlug('the-page-title-for-the-page')->first();

        // Assert
        $this->assertEquals($page->id, $foundPage->id);
    }

    /** @test **/
    public function it_can_include_slugs()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);
        $page->updateContent([
            'page_title' => 'The page title for the page',
        ], 'sv');

        // Act
        $response = $this->json('GET', '/pages/'.$page->id.'?include=slugs');

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'slug' => 'the-page-title-for-the-page',
            'source_key' => 'page_title',
        ]);
    }

    public function test_include_grouped_fields()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => RevisionTemplate::where('slug', 'startsida')->first()->id,
        ]);

        // Act
        $response = $this->json('GET', '/pages/'.$page->id.'?include=template.groupedFields');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'template' => [
                    'data' => [
                        'groupedFields' => [
                            'data' => [
                                'meta' => [],
                                'main_content' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test **/
    public function it_can_update_page_published_status()
    {
        // Arrange
        $page = Page::factory()->create([
            'name' => 'En sida som ska publiceras',
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);
        $page->updateContent([
            'page_title' => 'The page title for the page',
            'page_header' => 'The page title for the page',
            'page_content' => '<h1>Wow, a header</h1><p>Ok lets see</p>',
            'meta_title' => 'Meta title',
            'meta_description' => 'Describing the page',
            'meta_og_image' => 'https://placehold.it/40',
            'boxes' => [
                ['title' => 'Box 1 title!', 'url' => 'https://google.com'],
                ['title' => 'Box 2 title!', 'url' => 'https://bog.com'],
                ['title' => 'Box 3 title!', 'url' => 'http://flank.se'],
            ],
        ]);

        // Act
        $response = $this->json('PATCH', "/pages/{$page->id}", [
            'published' => true,
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'published' => true,
        ]);
    }

    /** @test **/
    public function it_can_search_for_a_page()
    {
        // Arrange
        $page = Page::factory()
            ->create([
                'name' => 'Landningssida',
            ]);
        $otherPage = Page::factory()
            ->create([
                'name' => 'Fooll',
            ]);
        $template = RevisionTemplate::factory()->create([
            'name' => 'Landningssida',
        ]);
        $page = Page::factory()
            ->create([
                'name' => 'Första sidan',
                'template_id' => $template->id,
            ]);

        // Act
        $response = $this->json('GET', '/pages?filter[search]=landningssida');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    /** @test **/
    public function it_can_include_content_for_all_enabled_locales()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $page = Page::factory()->create([
            'template_id' => RevisionTemplate::all()->first()->id,
        ]);
        $page->updateContent([
            'page_title' => 'Svensk titel',
        ], 'sv');
        $page->updateContent([
            'page_title' => 'English title',
        ], 'en');

        // Act
        $response = $this->json('GET', '/pages/'.$page->id.'?include=localizedContent');

        // Assert
        $response->assertOk();
        $response->assertJsonPath('data.localizedContent.data.en.content.page_title', 'English title');
        $response->assertJsonPath('data.localizedContent.data.sv.content.page_title', 'Svensk titel');
    }

    /** @test **/
    public function it_can_have_a_tree_structure()
    {
        // Arrange
        $parentPage = Page::factory()->create();
        $page = Page::factory()->create();
        $page->parent_id = $parentPage->id;
        $page->save();

        // Act
        $response = $this->json('GET', '/pages?include=children');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'children' => [
                        'data' => [],
                    ],
                ],
            ],
        ]);
    }

    public function test_it_can_be_published_or_not()
    {
        $page = Page::factory()->create(['published' => true]);

        $this->assertTrue((bool) $page->published);

        $page->published = false;
        $page->save();

        $this->assertFalse((bool) $page->fresh()->published);
    }
}
