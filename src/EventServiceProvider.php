<?php

namespace Karabin\Fabriq;

use Karabin\Fabriq\Listeners\BustPageCacheListener;
use Karabin\Fabriq\Listeners\CallCacheBustingWebhook;
use Karabin\Fabriq\Listeners\FlushTagCacheListener;
use Karabin\Fabriq\Listeners\UpdateSearchTerms;
use Karabin\Fabriq\Listeners\UpdateSlugListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Karabin\TranslatableRevisions\Events\DefinitionsPublished;
use Karabin\TranslatableRevisions\Events\DefinitionsUpdated;
use Karabin\TranslatableRevisions\Events\TranslatedRevisionDeleted;
use Karabin\TranslatableRevisions\Events\TranslatedRevisionUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        DefinitionsUpdated::class => [
            UpdateSlugListener::class,
            CallCacheBustingWebhook::class,
            UpdateSearchTerms::class,
        ],
        DefinitionsPublished::class => [
            BustPageCacheListener::class,
            CallCacheBustingWebhook::class,
            UpdateSearchTerms::class,
        ],
        TranslatedRevisionDeleted::class => [
            FlushTagCacheListener::class,
        ],
        TranslatedRevisionUpdated::class => [
            FlushTagCacheListener::class,
        ],
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
