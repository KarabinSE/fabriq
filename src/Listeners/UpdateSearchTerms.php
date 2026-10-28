<?php

namespace Karabin\Fabriq\Listeners;

use Illuminate\Support\Collection;
use Karabin\Fabriq\ContentGetters\ContentOptions;
use Karabin\Fabriq\Events\ContentUpdated;
use Karabin\Fabriq\Helpers\RecursiveArrayValues;
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
        if (! method_exists($event->model, 'getContentOptions')) {
            return;
        }

        $options = $event->model->getContentOptions();
        if (! $options instanceof ContentOptions || ! $options->isIndexable()) {
            return;
        }

        if ($callback = $options->indexFunction()) {
            $callback($event);

            return;
        }

        if (! method_exists($event->model, 'getSimpleFieldContent')) {
            return;
        }

        $content = $event->model->getSimpleFieldContent($event->locale);
        $titleKey = $options->titleKey();
        $title = (string) ($content->get($titleKey) ?? $event->model->getAttribute($titleKey) ?? '');
        $indexableKeys = $options->indexableKeys();
        $indexedKeys = RecursiveArrayValues::fromCollection($content, $indexableKeys);
        $indexedAttributes = collect($event->model->only($indexableKeys))
            ->filter()
            ->map(fn ($value) => is_scalar($value) || $value instanceof \Stringable ? (string) $value : null)
            ->filter()
            ->values()
            ->all();
        $path = $this->pathFor($event);
        $data = [
            'model_id' => $event->model->id,
            'model_type' => $event->model->getMorphClass(),
            'title' => $title,
            'locale' => $event->locale,
            'path' => (string) $path,
            'search_string' => implode(' ', [...$indexedKeys, ...$indexedAttributes]),
        ];

        SearchTerm::updateOrCreate([
            'model_id' => $data['model_id'],
            'model_type' => $data['model_type'],
            'locale' => $data['locale'],
        ], $data);
    }

    private function pathFor(ContentUpdated $event): ?string
    {
        if (method_exists($event->model, 'getPathsAttribute')) {
            /** @var Collection<int, array<string, mixed>> $paths */
            $paths = $event->model->paths;
            $pathGroup = $paths->first(fn ($group) => array_key_exists($event->locale, $group));
            $localePaths = $pathGroup[$event->locale] ?? [];
            $path = $localePaths instanceof Collection ? $localePaths->flatten()->first() : null;
            if ($path) {
                return $path;
            }
        }

        if (! method_exists($event->model, 'slugs')) {
            return null;
        }

        $firstSlug = $event->model->slugs->first()?->slug;

        return $firstSlug ? '/'.$firstSlug : null;
    }
}
