<?php

namespace Karabin\Fabriq\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ContentUpdated
{
    public function __construct(
        public Model $model,
        public string $locale,
        public Collection $content,
    ) {}
}
