<?php

namespace Karabin\Fabriq\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Traits\InvalidatesReferences;

class BlockReferenceInvalidator
{
    public function resourceChanged(string $type, int $id): void
    {
        ReferenceRegistry::validateType($type);
        if ($id <= 0) {
            throw new InvalidArgumentException('Reference IDs must be positive integers.');
        }
        if (! config('fabriq.webhooks.enabled')) {
            return;
        }

        if (app(CacheWebhookBatcher::class)->deferResource($type, $id)) {
            return;
        }

        if (! Schema::hasTable('block_references')) {
            Log::warning('Cannot invalidate block references: the block_references table does not exist.');

            return;
        }

        $pageClass = Fabriq::getFqnModel('page');
        $smartBlockClass = Fabriq::getFqnModel('smartBlock');
        $hasContentReferences = Schema::hasTable('content_references');
        if (! $hasContentReferences) {
            Log::warning('Cannot invalidate content references: the content_references table does not exist.');
        }

        $pending = [[$type, $id]];
        $visited = [];
        $owners = [];
        while ($pending !== []) {
            [$resourceType, $resourceId] = array_pop($pending);
            $reference = $resourceType.':'.$resourceId;
            if (isset($visited[$reference])) {
                continue;
            }
            $visited[$reference] = true;

            foreach (app(ReferenceRegistry::class)->dependencies($resourceType, $resourceId) as $dependency) {
                $pending[] = [$dependency['type'], $dependency['id']];
            }

            $references = DB::table('block_references')
                ->join('blocks', 'blocks.id', '=', 'block_references.block_id')
                ->where('block_references.resource_type', $resourceType)
                ->where('block_references.resource_id', $resourceId)
                ->distinct()->get(['blocks.model_type', 'blocks.model_id']);

            if ($hasContentReferences) {
                $references = $references->concat(DB::table('content_references')
                    ->join('i18n_definitions', 'i18n_definitions.id', '=', 'content_references.definition_id')
                    ->where('content_references.resource_type', $resourceType)
                    ->where('content_references.resource_id', $resourceId)
                    ->distinct()
                    ->get(['i18n_definitions.model_type', 'i18n_definitions.model_id']));
            }

            $ownerIds = [];
            foreach ($references as $referenceOwner) {
                $modelType = $referenceOwner->model_type;
                $modelId = $referenceOwner->model_id;
                if (! $modelType || ! $modelId) {
                    continue;
                }
                $class = Relation::getMorphedModel($modelType) ?? $modelType;
                if (! is_a($class, Model::class, true)) {
                    Log::warning('Cannot invalidate content reference with unknown model type.', ['model_type' => $modelType]);

                    continue;
                }
                if (! isset($owners[$class][$modelId])) {
                    $ownerIds[$class][$modelId] = $modelId;
                }
            }

            foreach ($ownerIds as $class => $ids) {
                if (! is_string($class) || ! is_a($class, Model::class, true)) {
                    continue;
                }
                foreach (array_chunk(array_values($ids), 500) as $chunk) {
                    foreach ($class::query()->whereKey($chunk)->get() as $owner) {
                        $modelId = $owner->getKey();
                        $owners[$class][$modelId] = $owner;
                        if ($owner instanceof $smartBlockClass) {
                            $pending[] = ['smartBlock', (int) $modelId];
                        } elseif ($owner instanceof $pageClass) {
                            $pending[] = ['page', (int) $modelId];
                        } elseif (in_array(InvalidatesReferences::class, class_uses_recursive($owner), true) && method_exists($owner, 'referenceType')) {
                            $pending[] = [$owner->referenceType(), (int) $modelId];
                        }
                    }
                }
            }
        }

        $keys = [];
        $buster = app(CacheBuster::class);
        foreach ($owners as $models) {
            foreach ($models as $owner) {
                $keys = [...$keys, ...$buster->getCacheKeys($owner)->all()];
            }
        }
        $this->invalidatePages([], $keys);
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

    public function ownerBlocksChanged(string $modelType, int $modelId): void
    {
        if (! config('fabriq.webhooks.enabled')) {
            return;
        }

        $class = Relation::getMorphedModel($modelType) ?? $modelType;
        if (! is_a($class, Model::class, true)) {
            Log::warning('Cannot invalidate block owner with unknown model type.', ['model_type' => $modelType]);

            return;
        }
        $owner = $class::query()->whereKey($modelId)->first();
        if (! $owner) {
            return;
        }
        $pageClass = Fabriq::getFqnModel('page');
        if ($owner instanceof $pageClass) {
            $this->blockChanged($modelId);

            return;
        }
        $smartBlockClass = Fabriq::getFqnModel('smartBlock');
        $type = null;
        if ($owner instanceof $smartBlockClass) {
            $type = 'smartBlock';
        } elseif (in_array(InvalidatesReferences::class, class_uses_recursive($owner), true) && method_exists($owner, 'referenceType')) {
            $type = $owner->referenceType();
        }
        if ($type === null) {
            return;
        }

        app(CacheWebhookBatcher::class)->defer(function () use ($owner, $type, $modelId) {
            $this->keysChanged(app(CacheBuster::class)->getCacheKeys($owner)->all());
            $this->resourceChanged($type, $modelId);
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
