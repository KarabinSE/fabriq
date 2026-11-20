<?php

namespace Karabin\Fabriq\Observers;

use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Services\BlockReferenceInvalidator;

class BlockReferenceMenuItemObserver
{
    private static ?\WeakMap $deletedPageIds = null;

    public function saved(MenuItem $item): void
    {
        if (! $item->wasRecentlyCreated && ! $item->wasChanged(['page_id', 'path', 'locale', 'type'])) {
            return;
        }

        foreach (array_unique(array_filter([$item->getOriginal('page_id'), $item->page_id])) as $id) {
            app(BlockReferenceInvalidator::class)->resourceChanged('page', (int) $id);
        }
    }

    public function deleting(MenuItem $item): void
    {
        self::$deletedPageIds ??= new \WeakMap;
        self::$deletedPageIds[$item] = $item->descendants()->getQuery()
            ->whereNotNull('page_id')->pluck('page_id')
            ->push($item->page_id)->filter()->unique()->all();
    }

    public function deleted(MenuItem $item): void
    {
        foreach (self::$deletedPageIds[$item] ?? [] as $id) {
            app(BlockReferenceInvalidator::class)->resourceChanged('page', (int) $id);
        }
        unset(self::$deletedPageIds[$item]);
    }
}
