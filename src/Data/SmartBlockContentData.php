<?php

namespace Karabin\Fabriq\Data;

use Spatie\LaravelData\Data;

class SmartBlockContentData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public mixed $content,
    ) {}
}
