<?php

namespace Tests\Feature;

use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class PagePreviewFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_get_a_preview_with_a_signed_link()
    {
        // Arrange
        $this->markTestSkipped();
        $template = ContentTemplate::factory()->create([
            'name' => 'Sidmall',
        ]);
        $titleField = ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Titel',
            'key' => 'page_title',
            'group' => 'main_content',
            'options' => ['classes' => 'hidden'],
            'type' => 'text',
            'sort_index' => 40,
            'translated' => true,
        ]);
        $page = Page::factory()->create([
            'name' => 'Den första startsidan',
            'template_id' => $template->id,
        ]);
        $page->updateContent([
            'page_title' => 'the unborn title',
        ]);

        $page->updateContent([
            'page_title' => 'the first title',
        ]);

        $signedUrl = $this->json('GET', '/pages/'.$page->id.'/signed-url');

        // Act
        $response = $this->json('GET', url($signedUrl->json()['signed_url']));

        // Assert
        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Den första startsidan',
        ]);
        $response->assertJsonFragment([
            'page_title' => 'the first title',
        ]);
    }
}
