<?php

namespace Karabin\Fabriq\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class FileSort implements Sort
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

        $query->select('files.*')
            ->join('media', 'media.model_id', '=', 'files.id')
            ->where('collection_name', 'files')
            ->orderBy('media.'.$keyName, $descending ? 'desc' : 'asc');
    }
}
