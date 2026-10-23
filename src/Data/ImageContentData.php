<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;

class ImageContentData extends Data
{
    public function __construct(
        public int $id,
        public string $file_name,
        public string $src,
        public string $thumb_src,
        public string $webp_src,
        public string $og_image_src,
        public ?string $srcset,
        public string $alt_text,
        public ?string $caption,
        public string $mime_type,
        public int $size,
        public mixed $width,
        public mixed $height,
        public bool $custom_crop,
        public string $responsive,
        public string $x_position,
        public string $y_position,
        public ?int $meta_id,
    ) {}
}
