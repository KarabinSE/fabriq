<?php

namespace Karabin\Fabriq;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Models\I18nDefinition;
use Karabin\Fabriq\Observers\BlockReferenceMenuItemObserver;
use Karabin\Fabriq\Services\BlockReferenceInvalidator;
use Karabin\Fabriq\Services\CacheWebhookBatcher;
use Karabin\Fabriq\Services\DefinitionReferenceIndex;

class ReferenceEventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(BlockReferenceInvalidator::class);
        $this->app->scoped(CacheWebhookBatcher::class);
    }

    public function boot(): void
    {
        I18nDefinition::saved(fn (I18nDefinition $definition) => app(DefinitionReferenceIndex::class)->sync($definition));
        Event::listen(ContentUpdated::class, function (ContentUpdated $event) {
            $smartBlockClass = Fabriq::getFqnModel('smartBlock');
            $pageClass = Fabriq::getFqnModel('page');
            if ($event->model instanceof $pageClass) {
                app(BlockReferenceInvalidator::class)->resourceChanged('page', (int) $event->model->getKey());
            } elseif ($event->model instanceof $smartBlockClass) {
                app(BlockReferenceInvalidator::class)->resourceChanged('smartBlock', (int) $event->model->getKey());
            }
        });

        $pageClass = Fabriq::getFqnModel('page');
        $pageClass::updated(fn (Model $page) => app(BlockReferenceInvalidator::class)->resourceChanged('page', (int) $page->getKey()));

        foreach (['image', 'file', 'video'] as $type) {
            $class = Fabriq::getFqnModel($type);
            $class::saved(fn (Model $model) => app(BlockReferenceInvalidator::class)->resourceChanged($type, (int) $model->getKey()));
            $class::deleted(fn (Model $model) => app(BlockReferenceInvalidator::class)->resourceChanged($type, (int) $model->getKey()));
        }

        $mediaClass = config('media-library.media_model');
        $mediaClass::saved(fn (Model $media) => app(BlockReferenceInvalidator::class)->mediaChanged($media));
        $mediaClass::deleted(fn (Model $media) => app(BlockReferenceInvalidator::class)->mediaChanged($media));

        $menuItemClass = Fabriq::getFqnModel('menuItem');
        $menuItemClass::observe(BlockReferenceMenuItemObserver::class);

        $slugClass = Fabriq::getFqnModel('slug');
        $slugClass::saved(function (Model $slug) {
            $pageClass = Fabriq::getFqnModel('page');
            if ($slug->model_type !== (new $pageClass)->getMorphClass() || (! $slug->wasRecentlyCreated && ! $slug->wasChanged('slug'))) {
                return;
            }

            $invalidator = app(BlockReferenceInvalidator::class);
            $invalidator->resourceChanged('page', (int) $slug->model_id);
            $oldSlug = $slug->getOriginal('slug');
            if ($oldSlug && $oldSlug !== $slug->slug) {
                $invalidator->pageChanged((int) $slug->model_id, ['fabriq_pages_slug_'.$oldSlug]);
            }
        });
        $slugClass::deleted(function (Model $slug) {
            $pageClass = Fabriq::getFqnModel('page');
            if ($slug->model_type === (new $pageClass)->getMorphClass()) {
                $invalidator = app(BlockReferenceInvalidator::class);
                $invalidator->resourceChanged('page', (int) $slug->model_id);
                $invalidator->pageChanged((int) $slug->model_id, ['fabriq_pages_slug_'.$slug->slug]);
            }
        });
    }
}
