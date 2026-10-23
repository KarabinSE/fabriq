<?php

namespace Karabin\Fabriq\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class MediaContentData extends Data
{
    public function __construct(
        public string $type,
        public bool $fullscreen,
        public Optional|ImageContentData|Collection|null $image,
        public Optional|VideoContentData|Collection|null $video,
    ) {}
}
