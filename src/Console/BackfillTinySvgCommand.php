<?php

namespace Karabin\Fabriq\Console;

use Illuminate\Console\Command;
use Karabin\Fabriq\Listeners\CreateTinySvg;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Image;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Throwable;

class BackfillTinySvgCommand extends Command
{
    protected $signature = 'fabriq:backfill-tiny-svg';

    protected $description = 'Generate missing tiny SVG placeholders for existing media';

    public function handle(CreateTinySvg $listener): int
    {
        if (! config('media-library.responsive_images.use_tiny_placeholders')) {
            $this->error('Tiny placeholders are disabled.');

            return self::FAILURE;
        }

        $mediaClass = config('media-library.media_model');
        $imageGenerator = new Image;
        $processed = 0;
        $failed = 0;

        $mediaClass::query()->chunkById(100, function ($items) use ($listener, $imageGenerator, &$processed, &$failed) {
            foreach ($items as $media) {
                if ($media->getCustomProperty('tiny_svg')) {
                    continue;
                }

                try {
                    if (! $imageGenerator->canConvert($media)) {
                        continue;
                    }

                    $listener->handle(new MediaHasBeenAddedEvent($media));
                    $processed++;
                } catch (Throwable $exception) {
                    report($exception);
                    $this->error("Media {$media->id}: {$exception->getMessage()}");
                    $failed++;
                }
            }
        });

        $this->info("Generated: {$processed}; failed: {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
