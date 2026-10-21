<?php

namespace Tests\Feature;

use Karabin\Fabriq\Database\Seeders\DatabaseSeeder;
use Karabin\Fabriq\Database\Seeders\PageTemplateSeeder;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class PageSlugFeatureTest extends AdminUserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(DatabaseSeeder::class)->call(PageTemplateSeeder::class);
    }

    /** @test **/
    public function it_can_find_a_page_via_its_slugs()
    {
        // Arrange
        // $this->withoutExceptionHandling();
        $page = Page::factory()->create(['published' => true]);
        $otherPage = Page::factory()->create();
        $slug = Slug::create([
            'model_type' => 'fabriq_page',
            'model_id' => $page->id,
            'slug' => 'a-slug',
            'source_string' => 'none',
            'source_key' => 'source_key',
            'locale' => 'sv',
        ]);
        $otherSlug = Slug::create([
            'model_type' => 'fabriq_page',
            'model_id' => $otherPage->id,
            'slug' => 'a-slug',
            'source_string' => 'none',
            'source_key' => 'source_key',
            'locale' => 'sv',
        ]);

        // Act
        $response = $this->json('GET', route('pages.live.show', $slug->slug));

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'slug' => $slug->slug,
        ]);
    }

    /** @test **/
    public function it_interpolates_image_content_when_finding_a_page_via_its_slug()
    {
        $page = Page::factory()->create([
            'published' => true,
            'template_id' => ContentTemplate::query()->where('slug', 'startsida')->firstOrFail()->id,
        ]);
        $image = Image::factory()->create([
            'alt_text' => 'Page social image',
        ]);
        $page->updateContent([
            'meta_og_image' => $image->id,
        ], app()->getLocale());
        $slug = Slug::create([
            'model_type' => 'fabriq_page',
            'model_id' => $page->id,
            'slug' => 'page-with-image',
            'source_string' => 'Page with image',
            'source_key' => 'page_title',
            'locale' => app()->getLocale(),
        ]);

        $response = $this->json('GET', route('pages.live.show', $slug->slug));

        $response->assertOk();
        $response->assertJsonPath('data.content.data.meta_og_image.id', $image->id);
        $response->assertJsonPath('data.content.data.meta_og_image.alt_text', 'Page social image');
    }
}
