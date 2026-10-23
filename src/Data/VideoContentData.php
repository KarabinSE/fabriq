<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;

class VideoContentData extends Data
{
    public function __construct(
        public int $id,
        public string $file_name,
        public string $src,
        public string $thumb_src,
        public string $poster_src,
        public ?string $alt_text,
        public ?string $caption,
        public string $mime_type,
        public int $size,
        public ?int $meta_id,
    ) {}
}
