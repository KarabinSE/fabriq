<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class UploadFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_stores_the_tiny_svg_as_a_custom_property_before_responsive_images_are_processed()
    {
        Queue::fake();
        $image = Image::factory()->create();

        $media = $image->addMedia(__DIR__.'/_fixtures/image.jpeg')
            ->preservingOriginal()
            ->withCustomProperties(['width' => 123])
            ->withResponsiveImages()
            ->toMediaCollection('images', '__test')
            ->fresh();

        $placeholder = $media->getCustomProperty('tiny_svg');
        $this->assertIsString($placeholder);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $placeholder);
        $this->assertStringContainsString('<svg', base64_decode(substr($placeholder, strlen('data:image/svg+xml;base64,')), true));
        $this->assertSame($media->responsiveImages()->getPlaceholderSvg(), $placeholder);
        $this->assertSame(123, $media->getCustomProperty('width'));
    }

    /** @test **/
    public function it_does_not_create_a_tiny_svg_for_non_image_media()
    {
        $image = Image::factory()->create();
        $media = $image->getFirstMedia('images')->fresh();

        $this->assertFalse($media->hasCustomProperty('tiny_svg'));
    }

    /** @test **/
    public function it_can_upload_an_image_and_persist_it()
    {
        // Arrange

        // Act
        $response = $this->json('POST', '/uploads/images', [
            'image' => UploadedFile::fake()->image('new-image.png', 230, 120)->size(300),
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('media', [
            'file_name' => 'new-image.png',
            'model_type' => 'fabriq_image',
        ]);
    }

    /** @test **/
    public function it_can_upload_a_file_and_persist_it()
    {
        // Arrange

        // Act
        $response = $this->json('POST', '/uploads/files', [
            'file' => UploadedFile::fake()->create('document.txt', 240),
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('media', [
            'file_name' => 'document.txt',
            'model_type' => 'fabriq_file',
        ]);
    }

    /** @test **/
    public function it_can_upload_a_video_and_persist_it()
    {
        // Arrange
        $this->withoutExceptionHandling();
        $fixturePath = __DIR__.'/_fixtures/';
        copy($fixturePath.'fixture-video.mov', $fixturePath.'video.mov');

        // Act
        $response = $this->json('POST', '/uploads/videos', [
            'video' => new UploadedFile($fixturePath.'video.mov', 'video.mov'),
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('media', [
            'file_name' => 'video.mov',
            'model_type' => 'fabriq_video',
        ]);
    }
}
