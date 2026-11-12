<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Tests\AdminUserTestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BackfillTinySvgFeatureTest extends AdminUserTestCase
{
    /** @test */
    public function it_backfills_missing_placeholders_and_skips_existing_and_non_image_media(): void
    {
        Queue::fake();
        $image = Image::factory()->create();
        $this->assertInstanceOf(Image::class, $image);
        $nonImage = $image->getFirstMedia('images');
        $this->assertNotNull($nonImage);
        $missing = $this->addImage($image);
        $existing = $this->addImage($image);
        $missing->forgetCustomProperty('tiny_svg')->save();
        $existing->setCustomProperty('tiny_svg', 'existing-placeholder')->save();

        $this->artisan('fabriq:backfill-tiny-svg')
            ->expectsOutput('Generated: 1; failed: 0.')
            ->assertExitCode(0);

        $placeholder = $missing->fresh()->getCustomProperty('tiny_svg');
        $this->assertIsString($placeholder);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $placeholder);
        $this->assertSame($missing->fresh()->responsiveImages()->getPlaceholderSvg(), $placeholder);
        $this->assertSame('existing-placeholder', $existing->fresh()->getCustomProperty('tiny_svg'));
        $this->assertNull($nonImage->fresh()->getCustomProperty('tiny_svg'));

        $this->artisan('fabriq:backfill-tiny-svg')
            ->expectsOutput('Generated: 0; failed: 0.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_reports_failures_and_continues_processing(): void
    {
        Queue::fake();
        $image = Image::factory()->create();
        $this->assertInstanceOf(Image::class, $image);
        $missingFile = $this->addImage($image);
        $valid = $this->addImage($image);
        $missingFile->forgetCustomProperty('tiny_svg')->save();
        $valid->forgetCustomProperty('tiny_svg')->save();
        Storage::disk($missingFile->disk)->delete($missingFile->getPathRelativeToRoot());

        $this->artisan('fabriq:backfill-tiny-svg')->assertExitCode(1);

        $this->assertNull($missingFile->fresh()->getCustomProperty('tiny_svg'));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $valid->fresh()->getCustomProperty('tiny_svg'));
    }

    /** @test */
    public function it_fails_when_tiny_placeholders_are_disabled(): void
    {
        config()->set('media-library.responsive_images.use_tiny_placeholders', false);

        $this->artisan('fabriq:backfill-tiny-svg')
            ->expectsOutput('Tiny placeholders are disabled.')
            ->assertExitCode(1);
    }

    private function addImage(Image $image): Media
    {
        return $image->addMedia(__DIR__.'/_fixtures/image.jpeg')
            ->preservingOriginal()
            ->toMediaCollection('images', '__test');
    }
}
