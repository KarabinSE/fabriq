<?php

namespace Tests\Feature;

use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class ContentTemplateFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_can_get_all_content_templates()
    {
        // Arrange
        ContentTemplate::all()->each(function ($item) {
            $item->delete();
        });
        // ContentTemplate::truncate();
        $templates = ContentTemplate::factory()->count(3)->create([
            'locked' => true,
        ]);

        // Act
        $response = $this->json('GET', '/content-templates');
        $legacyResponse = $this->json('GET', '/templates');

        // Assert
        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'locked',
                    'source_model_id',
                ],
            ],
        ]);
        $legacyResponse->assertOk()->assertJsonCount(3, 'data');
    }
}
