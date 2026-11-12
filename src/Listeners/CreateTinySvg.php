<?php

namespace Karabin\Fabriq\Listeners;

use Illuminate\Support\Str;
use Karabin\Fabriq\Services\ResponsiveImageGenerator;
use RuntimeException;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\Support\TemporaryDirectory;

class CreateTinySvg
{
    /**
     * Handle the event.
     */
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if (! config('media-library.responsive_images.use_tiny_placeholders')) {
            $media->forgetCustomProperty('tiny_svg');
            $media->save();

            return;
        }

        if (! (new Image)->canConvert($media)) {
            $media->forgetCustomProperty('tiny_svg');
            $media->save();

            return;
        }

        $temporaryDirectory = TemporaryDirectory::create();

        try {
            $path = app(Filesystem::class)->copyFromMediaLibrary(
                $media,
                $temporaryDirectory->path(Str::random(16).'.'.$media->extension)
            );

            app(ResponsiveImageGenerator::class)->generateTinyJpg(
                $media,
                $path,
                'media_library_original',
                $temporaryDirectory
            );

            $placeholder = $media->responsiveImages()->getPlaceholderSvg();

            if ($placeholder === null) {
                throw new RuntimeException("Tiny SVG was not generated for media {$media->id}.");
            }

            $media->setCustomProperty('tiny_svg', $placeholder);
            $media->save();
        } finally {
            $temporaryDirectory->delete();
        }
    }
}
