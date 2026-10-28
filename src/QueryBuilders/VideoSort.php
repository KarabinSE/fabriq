<?php

namespace Karabin\Fabriq\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class VideoSort implements Sort
{
    /**
     * Sort custom columns.
     *
     * @param  Builder  $query
     * @param  bool  $descending
     * @param  string  $property
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $keyName = $property;

        $query->select('videos.*')
            ->join('media', 'media.model_id', '=', 'videos.id')
            ->where('collection_name', 'videos')
            ->orderBy('media.'.$keyName, $descending ? 'desc' : 'asc');
    }
}
