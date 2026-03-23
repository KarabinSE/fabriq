<?php

namespace Karabin\Fabriq\Data;

use Karabin\Fabriq\Models\Image;
use Spatie\LaravelData\Data;

class UserImageData extends Data
{
    public function __construct(
        public ?int $id,
        public ?string $file_name,
        public ?string $thumb_src,
        public ?string $webp_src,
        public ?string $src,
        public ?string $mime_type,
        public ?string $srcset,
    ) {}

    public static function fromModel(?Image $image): self
    {
        if (! $image) {
            return new self(
                id: null,
                file_name: null,
                thumb_src: null,
                webp_src: null,
                src: null,
                mime_type: null,
                srcset: null,
            );
        }

        $media = $image->getFirstMedia('profile_image');

        if (! $media) {
            return new self(
                id: (int) $image->id,
                file_name: null,
                thumb_src: null,
                webp_src: null,
                src: null,
                mime_type: null,
                srcset: null,
            );
        }

        return new self(
            id: (int) $image->id,
            file_name: $media->file_name,
            thumb_src: $media->getUrl('thumb'),
            webp_src: $media->hasGeneratedConversion('webp') ? $media->getUrl('webp') : '',
            src: $media->getUrl(),
            mime_type: $media->mime_type,
            srcset: $media->getSrcSet(),
        );
    }
}
