<?php

namespace Karabin\Fabriq\Listeners;

use Illuminate\Support\Facades\Cache;

class FlushTagCacheListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $tagsToFlush = method_exists($event->model, 'getContentCacheKeys')
            ? $event->model->getContentCacheKeys()
            : [];

        if (! $tagsToFlush) {
            return;
        }

        Cache::tags($tagsToFlush)->flush();
    }
}
