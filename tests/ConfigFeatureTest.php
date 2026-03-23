<?php

namespace Tests\Feature;

use Karabin\Fabriq\Tests\AdminUserTestCase;

class ConfigFeatureTest extends AdminUserTestCase
{
    public function testGetConfigData()
    {
        // Arrange
        $this->withoutExceptionHandling();

        // Act
        $response = $this->json('GET', '/config');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'modules' => [],
                'supported_locales' => [],
            ],
        ]);
    }
}
