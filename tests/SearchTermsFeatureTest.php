<?php

namespace Tests\Feature;

use Closure;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\Database\Seeders\DatabaseSeeder;
use Karabin\Fabriq\Database\Seeders\PageTemplateSeeder;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Listeners\UpdateSearchTerms;
use Karabin\Fabriq\Models\Article;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class SearchTermsFeatureTest extends AdminUserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(DatabaseSeeder::class)->call(PageTemplateSeeder::class);
    }

    public function test_it_can_include_content_for_a_single_page()
    {
        // Arrange
        $page = Page::factory()->create([
            'name' => 'En sida som ska publiceras',
            'published' => true,
            'template_id' => ContentTemplate::query()->where('slug', 'startsida')->firstOrFail()->id,
        ]);

        $page->updateContent([
            'page_title' => 'The page title for the page',
            'page_header' => 'The page title for the page',
            'page_content' => '<h1>Wow, a header</h1><p>Ok lets see</p>',
            'meta_title' => 'Meta title',
            'meta_description' => 'Describing the page',
            'boxes' => [
                ['header' => 'EN Box 1 title!', 'url' => 'https://google.com'],
                ['header' => 'EN Box 2 title!', 'url' => 'https://bog.com'],
                ['header' => 'EN Box 3 title!', 'url' => 'http://flank.se'],
            ],
        ], 'en');
        $page->updateContent([
            'page_title' => 'En siee saom skau paublisers',
            'page_header' => 'En siee saom skau paublisers',
            'page_content' => '<h1>Wow</h1><p>Da ska vi se...</p>',
            'meta_title' => 'Meta titel',
            'meta_description' => 'En forklaring av sidan',
            'boxes' => [
                ['header' => 'DK fosta titeln!', 'url' => 'https://google.com'],
                ['header' => 'DK andra titeln!', 'url' => 'https://bog.com'],
            ],
        ], 'dk');

        // Act
        // Assert
        $this->assertDatabaseHas('search_terms', [
            'model_id' => $page->id,
            'model_type' => Fabriq::getModelClass('page')->getMorphClass(),
            'locale' => 'dk',
            'path' => '/en-siee-saom-skau-paublisers',
            'search_string' => 'DK fosta titeln! DK andra titeln! En siee saom skau paublisers',
        ]);

        $this->assertDatabaseHas('search_terms', [
            'model_id' => $page->id,
            'model_type' => 'fabriq_page',
            'locale' => 'en',
            'path' => '/the-page-title-for-the-page',
            'search_string' => 'EN Box 1 title! EN Box 2 title! EN Box 3 title! The page title for the page',
        ]);

        $this->assertDatabaseCount('search_terms', 3);
    }

    public function test_it_uses_the_first_slug_when_no_path_exists_for_the_locale(): void
    {
        $page = Page::factory()->create([
            'published' => true,
            'template_id' => ContentTemplate::query()->where('slug', 'startsida')->firstOrFail()->id,
        ]);
        $page->slugs()->delete();
        Slug::create([
            'model_type' => $page->getMorphClass(),
            'model_id' => $page->id,
            'slug' => 'first-page-slug',
            'source_string' => 'First page slug',
            'source_key' => 'page_title',
            'locale' => 'sv',
        ]);

        app(UpdateSearchTerms::class)->handle(new ContentUpdated(
            $page,
            'en',
            collect(['page_title' => 'English title']),
        ));

        $this->assertDatabaseHas('search_terms', [
            'model_id' => $page->id,
            'model_type' => $page->getMorphClass(),
            'locale' => 'en',
            'path' => '/first-page-slug',
        ]);
    }

    public function test_it_indexes_other_models_using_their_content_options(): void
    {
        $template = ContentTemplate::factory()->create(['slug' => 'article']);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
            'translated' => true,
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'preamble',
            'translated' => true,
        ]);
        $article = Article::factory()->create(['template_id' => $template->id]);

        $article->updateContent([
            'title' => 'Article title',
            'preamble' => 'Article summary',
        ], 'sv');

        $this->assertDatabaseHas('search_terms', [
            'model_id' => $article->id,
            'model_type' => $article->getMorphClass(),
            'locale' => 'sv',
            'title' => 'Article title',
            'search_string' => 'Article summary Article title',
            'path' => '/article-title',
        ]);
    }

    public function test_it_uses_the_configured_callback_for_indexing(): void
    {
        $callbackCalled = false;
        $model = new class extends Page
        {
            public ?Closure $indexCallback = null;

            public function getContentOptions(): ContentOptions
            {
                return ContentOptions::create()->setIndexable(
                    true,
                    [],
                    '',
                    $this->indexCallback,
                );
            }
        };
        $model->indexCallback = function () use (&$callbackCalled): void {
            $callbackCalled = true;
        };
        $model->id = 100;

        app(UpdateSearchTerms::class)->handle(new ContentUpdated($model, 'sv', collect()));

        $this->assertTrue($callbackCalled);
    }
}
