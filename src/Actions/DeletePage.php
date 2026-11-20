<?php

namespace Karabin\Fabriq\Actions;

use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Services\BlockReferenceInvalidator;
use Karabin\Fabriq\Services\CacheBuster;

class DeletePage
{
    public function handle(int $id): void
    {
        $query = Fabriq::getModelClass('page')->where('id', $id);
        $page = $query->first();
        if ($page === null) {
            return;
        }

        $keys = app(CacheBuster::class)->getCacheKeys($page)->all();
        $query->delete();
        $invalidator = app(BlockReferenceInvalidator::class);
        $invalidator->keysChanged($keys);
        $invalidator->resourceChanged('page', $id);
    }
}
