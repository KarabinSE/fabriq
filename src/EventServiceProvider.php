<?php

namespace Karabin\Fabriq;

use Karabin\Fabriq\Listeners\CallCacheBustingWebhook;
use Karabin\Fabriq\Listeners\FlushTagCacheListener;
use Karabin\Fabriq\Listeners\UpdateSearchTerms;
use Karabin\Fabriq\Listeners\UpdateSlugListener;
use Karabin\Fabriq\Events\ContentUpdated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ContentUpdated::class => [
            UpdateSlugListener::class,
            CallCacheBustingWebhook::class,
            UpdateSearchTerms::class,
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
