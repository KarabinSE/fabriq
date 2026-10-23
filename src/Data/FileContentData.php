<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;

class FileContentData extends Data
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $file_name,
        public string $thumb_src,
        public string $extension,
        public string $src,
        public ?string $readable_name,
        public ?string $caption,
        public string $mime_type,
        public int $size,
        public ?string $updated_at,
        public ?string $created_at,
    ) {}
}
