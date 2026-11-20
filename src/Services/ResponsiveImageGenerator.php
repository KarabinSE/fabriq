<?php

namespace Karabin\Fabriq\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\ResponsiveImages\Events\ResponsiveImagesGeneratedEvent;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImageGenerator as SpatieResponsiveImageGenerator;
use Spatie\MediaLibrary\Support\TemporaryDirectory;

class ResponsiveImageGenerator extends SpatieResponsiveImageGenerator
{
    protected string $fileType;

    public function generateResponsiveImagesWithWebP(Media $media): void
    {
        $this->fileType = 'webp';

        $temporaryDirectory = TemporaryDirectory::create();

        $baseImage = app(Filesystem::class)->copyFromMediaLibrary(
            $media,
            $temporaryDirectory->path(Str::random(16).'.'.$this->fileType)
        );

        $media = $this->cleanResponsiveImages($media);

        foreach ($this->widthCalculator->calculateWidthsFromFile($baseImage) as $width) {
            $this->generateResponsiveImage($media, $baseImage, 'media_library_original', $width, $temporaryDirectory);
        }

        event(new ResponsiveImagesGeneratedEvent($media));

        $this->generateTinyJpg($media, $baseImage, 'media_library_original', $temporaryDirectory);

        $temporaryDirectory->delete();
    }

    protected function buildPayload(Collection $widths, Media $media): Collection
    {
        $generatorClass = config('fabriq.media-library.path_generator');
        $mediaPathGenerator = new $generatorClass;
        $responsiveImagePath = $this->fileNamer->temporaryFileName($media, $this->fileType);

        return $widths->map(function ($width) use ($media, $responsiveImagePath, $mediaPathGenerator) {
            return [
                'format' => $this->fileType,
                'path' => $mediaPathGenerator->getPathForResponsiveImages($media).$width.'_'.$responsiveImagePath,
                'maxWidth' => $width,
                'acl' => 'public-read',
            ];
        });
    }
}
