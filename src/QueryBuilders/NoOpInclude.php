<?php

namespace Karabin\Fabriq\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Includes\IncludeInterface;

class NoOpInclude implements IncludeInterface
{
    public function __invoke(Builder $query, string $include)
    {
        return null;
    }
}
