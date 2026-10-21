<?php

namespace Karabin\Fabriq\Listeners;

use Illuminate\Support\Collection;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Helpers\RecursiveArrayValues;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\SearchTerm;

class UpdateSearchTerms
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(ContentUpdated $event): void
    {
        if (! $event->model instanceof Page || ! $event->model->published) {
            return;
        }

        $content = $event->model->getSimpleFieldContent($event->locale);
        $title = (string) ($content->get('page_title') ?? '');
        /** @var Collection<int, array<string, mixed>> $paths */
        $paths = $event->model->paths;
        $pathGroup = $paths->first(fn ($group) => array_key_exists($event->locale, $group));
        $localePaths = $pathGroup[$event->locale] ?? [];
        $path = $localePaths instanceof Collection ? $localePaths->flatten()->first() : null;
        $indexedKeys = RecursiveArrayValues::fromCollection($content, ['page_title', 'page_header', 'header']);
        $data = [
            'model_id' => $event->model->id,
            'model_type' => $event->model->getMorphClass(),
            'title' => $title,
            'locale' => $event->locale,
            'path' => (string) $path,
            'search_string' => implode(' ', $indexedKeys),
        ];

        SearchTerm::updateOrCreate([
            'model_id' => $data['model_id'],
            'model_type' => $data['model_type'],
            'locale' => $data['locale'],
        ], $data);
    }
}
