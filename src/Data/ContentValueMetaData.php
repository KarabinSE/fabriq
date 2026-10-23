<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;

class ContentValueMetaData extends Data
{
    public function __construct(
        public ?int $meta_id,
    ) {}
}
