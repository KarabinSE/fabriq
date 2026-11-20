<?php

namespace Karabin\Fabriq\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Fabriq;

class BlockReferenceInvalidator
{
    private array $deferredResources = [];

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function deferResourceChanges(string $type, int $id, Closure $callback): mixed
    {
        $key = $type.':'.$id;
        if (! isset($this->deferredResources[$key])) {
            $this->deferredResources[$key] = ['depth' => 0, 'changed' => false];
        }
        $this->deferredResources[$key]['depth']++;

        try {
            return $callback();
        } finally {
            $this->deferredResources[$key]['depth']--;
            if ($this->deferredResources[$key]['depth'] === 0) {
                $changed = $this->deferredResources[$key]['changed'];
                unset($this->deferredResources[$key]);
                if ($changed) {
                    $this->resourceChanged($type, $id);
                }
            }
        }
    }

    public function resourceChanged(string $type, int $id): void
    {
        if (! config('fabriq.webhooks.enabled')) {
            return;
        }

        $key = $type.':'.$id;
        if (isset($this->deferredResources[$key])) {
            $this->deferredResources[$key]['changed'] = true;

            return;
        }

        if (! Schema::hasTable('block_references')) {
            Log::warning('Cannot invalidate block references: the block_references table does not exist.');

            return;
        }

        $pageClass = Fabriq::getFqnModel('page');
        $smartBlockClass = Fabriq::getFqnModel('smartBlock');
        $pageMorph = (new $pageClass)->getMorphClass();
        $smartBlockMorph = (new $smartBlockClass)->getMorphClass();
        $hasContentReferences = Schema::hasTable('content_references');
        if (! $hasContentReferences) {
            Log::warning('Cannot invalidate content references: the content_references table does not exist.');
        }

        $pending = [[$type, $id]];
        $visited = [];
        $pages = [];
        $owners = [];
        while ($pending !== []) {
            [$resourceType, $resourceId] = array_pop($pending);
            $reference = $resourceType.':'.$resourceId;
            if (isset($visited[$reference])) {
                continue;
            }
            $visited[$reference] = true;

            foreach (DB::table('block_references')
                ->join('blocks', 'blocks.id', '=', 'block_references.block_id')
                ->where('block_references.resource_type', $resourceType)
                ->where('block_references.resource_id', $resourceId)
                ->whereIn('blocks.model_type', [$pageMorph, $pageClass])
                ->distinct()->pluck('blocks.model_id') as $pageId) {
                $pages[] = $pageId;
            }

            if (! $hasContentReferences) {
                continue;
            }

            $definitions = DB::table('content_references')
                ->join('i18n_definitions', 'i18n_definitions.id', '=', 'content_references.definition_id')
                ->where('content_references.resource_type', $resourceType)
                ->where('content_references.resource_id', $resourceId)
                ->distinct()
                ->get(['i18n_definitions.model_type', 'i18n_definitions.model_id']);
            foreach ($definitions as $definition) {
                $modelType = $definition->model_type;
                $modelId = $definition->model_id;
                if (! $modelType || ! $modelId) {
                    continue;
                }
                $class = Relation::getMorphedModel($modelType) ?? $modelType;
                if (! is_a($class, Model::class, true)) {
                    Log::warning('Cannot invalidate content reference with unknown model type.', ['model_type' => $modelType]);

                    continue;
                }
                $owners[$class][] = $modelId;
                if ($modelType === $smartBlockMorph || $class === $smartBlockClass) {
                    $pending[] = ['smartBlock', (int) $modelId];
                }
            }
        }

        $keys = [];
        $buster = app(CacheBuster::class);
        foreach ($owners as $class => $ids) {
            if (! is_string($class) || ! is_a($class, Model::class, true)) {
                continue;
            }
            foreach (array_chunk(array_values(array_unique($ids)), 500) as $chunk) {
                foreach ($class::query()->whereIn('id', $chunk)->get() as $owner) {
                    $keys = [...$keys, ...$buster->getCacheKeys($owner)->all()];
                }
            }
        }
        $this->invalidatePages($pages, $keys);
    }

    public function pageChanged(int $id, array $additionalKeys = []): void
    {
        if (! config('fabriq.webhooks.enabled')) {
            return;
        }

        $this->invalidatePages([$id], $additionalKeys);
    }

    public function blockChanged(int $pageId): void
    {
        app(CacheWebhookBatcher::class)->defer(function () use ($pageId) {
            $this->pageChanged($pageId);
            $this->resourceChanged('page', $pageId);
        });
    }

    public function keysChanged(array $keys): void
    {
        if (config('fabriq.webhooks.enabled') && $keys !== []) {
            $this->invalidatePages([], $keys);
        }
    }

    public function mediaChanged(Model $media): void
    {
        $owners = [[$media->model_type, $media->model_id]];
        if ($media->wasChanged(['model_type', 'model_id'])) {
            $owners[] = [$media->getOriginal('model_type'), $media->getOriginal('model_id')];
        }

        foreach (['image', 'file', 'video'] as $type) {
            $class = Fabriq::getFqnModel($type);
            $morph = (new $class)->getMorphClass();
            foreach ($owners as [$modelType, $modelId]) {
                if ($modelId && ($modelType === $morph || $modelType === $class)) {
                    $this->resourceChanged($type, (int) $modelId);
                }
            }
        }
    }

    private function invalidatePages(array $ids, array $keys = []): void
    {
        if ($ids !== []) {
            $pageClass = Fabriq::getFqnModel('page');
            $buster = app(CacheBuster::class);
            foreach ($pageClass::query()->with('slugs')->whereIn('id', array_unique($ids))->get() as $page) {
                $keys = [...$keys, ...$buster->getCacheKeys($page)->all()];
            }
        }

        $keys = array_values(array_unique($keys));
        if ($keys !== []) {
            app(CacheWebhookBatcher::class)->send($keys, rateLimit: false);
        }
    }
}
