<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use stdClass;

class GlobalSectionData extends Data
{
    public function __construct(
        public string $handle,
        public int $id,
        public string $name,
        public array|Optional $fields,
        public stdClass|Optional $content,
    ) {}
}
