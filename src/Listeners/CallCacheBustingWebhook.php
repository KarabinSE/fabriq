<?php

namespace Karabin\Fabriq\Listeners;

use Karabin\Fabriq\Actions\BustCacheWithWebhook;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Services\CacheBuster;
use Karabin\TranslatableRevisions\Events\DefinitionsUpdated;

class CallCacheBustingWebhook
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

        $model = $event->model;

        if (! config('fabriq.webhooks.enabled')) {
            return;
        }

        // Case for pages, skip busting on update
        if (get_class($event) === DefinitionsUpdated::class && Fabriq::getFqnModel('page') === get_class($model)) {
            return;
        }

        $keysToForget = (new CacheBuster)->getCacheKeys($model);

        if (! $keysToForget->count()) {
            return;
        }

        (new BustCacheWithWebhook)->handle($keysToForget->toArray());
    }
}
