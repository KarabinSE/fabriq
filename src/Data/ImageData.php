<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Support\Str;
use Karabin\Fabriq\Models\Image;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImageData extends Data
{
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $name,
        public ?string $c_name,
        public ?string $extension,
        public ?string $file_name,
        public ?string $thumb_src,
        public ?string $og_image_src,
        public ?string $webp_src,
        public ?string $src,
        public ?string $srcset,
        public ?string $responsive,
        public ?string $alt_text,
        public ?string $caption,
        public ?string $mime_type,
        public bool $custom_crop,
        public ?string $x_position,
        public ?string $y_position,
        public ?int $size,
        public mixed $width,
        public mixed $height,
        public bool $processing,
        public bool $processing_failed,
        public ?string $updated_at,
        public ?string $created_at,
        public Lazy|array $tags,
    ) {}

    public static function allowedRequestIncludes(): ?array
    {
        return ['tags'];
    }

    public static function fromModel(Image $image): self
    {
        $media = $image->getFirstMedia('images');

        if (! $media) {
            return new self(
                id: (int) $image->id,
                uuid: null,
                name: null,
                c_name: null,
                extension: null,
                file_name: null,
                thumb_src: null,
                og_image_src: null,
                webp_src: null,
                src: null,
                srcset: null,
                responsive: null,
                alt_text: null,
                caption: null,
                mime_type: null,
                custom_crop: false,
                x_position: null,
                y_position: null,
                size: null,
                width: null,
                height: null,
                processing: false,
                processing_failed: false,
                updated_at: $image->updated_at?->toISOString(),
                created_at: $image->created_at?->toISOString(),
                tags: Lazy::create(fn () => self::buildTags($image)),
            );
        }

        return new self(
            id: (int) $image->id,
            uuid: (string) $media->uuid,
            name: (string) $media->name,
            c_name: (string) ($media->name.'.'.Str::afterLast($media->file_name, '.')),
            extension: (string) Str::afterLast($media->file_name, '.'),
            file_name: (string) $media->file_name,
            thumb_src: (string) $media->getUrl('thumb'),
            og_image_src: $media->hasGeneratedConversion('og_image') ? (string) $media->getUrl('og_image') : null,
            webp_src: $media->hasGeneratedConversion('webp') ? (string) $media->getUrl('webp') : '',
            src: (string) $media->getUrl(),
            srcset: (string) $media->getSrcSet(),
            responsive: (string) $media->toHtml(),
            alt_text: $image->alt_text,
            caption: $image->caption,
            mime_type: $media->mime_type,
            custom_crop: (bool) $image->custom_crop,
            x_position: $image->x_position,
            y_position: $image->y_position,
            size: $media->size,
            width: self::getWidth($media),
            height: self::getHeight($media),
            processing: (bool) $media->getCustomProperty('processing'),
            processing_failed: (bool) $media->getCustomProperty('processing_failed'),
            updated_at: $image->updated_at?->toISOString(),
            created_at: $image->created_at?->toISOString(),
            tags: Lazy::create(fn () => self::buildTags($image)),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildTags(Image $image): array
    {
        $tags = [];

        foreach ($image->tags as $tag) {
            $tags[] = [
                'id' => $tag->id,
                'name' => $tag->name,
                'value' => $tag->id,
                'type' => $tag->type,
            ];
        }

        return $tags;
    }

    private static function getWidth(Media $media): mixed
    {
        if ($media->getCustomProperty('width')) {
            return $media->getCustomProperty('width');
        }

        if ($media->responsiveImages()->files->first()) {
            return $media->responsiveImages()->files->first()->width();
        }

        return null;
    }

    private static function getHeight(Media $media): mixed
    {
        if ($media->getCustomProperty('height')) {
            return $media->getCustomProperty('height');
        }

        if ($media->responsiveImages()->files->first()) {
            return $media->responsiveImages()->files->first()->height();
        }

        return null;
    }
}
