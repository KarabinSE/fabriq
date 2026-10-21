<?php

namespace Karabin\Fabriq\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class ImageSort implements Sort
{
    /**
     * Sort custom columns.
     *
     * @param  bool  $descending
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $keyName = $property;

        $query->select('images.*')
            ->join('media', 'media.model_id', '=', 'images.id')
            ->where('collection_name', 'images')
            ->orderBy('media.'.$keyName, $descending ? 'desc' : 'asc');
    }
}
