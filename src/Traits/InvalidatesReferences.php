<?php

namespace Karabin\Fabriq\Traits;

use InvalidArgumentException;
use Karabin\Fabriq\Services\BlockReferenceInvalidator;
use Karabin\Fabriq\Services\CacheBuster;
use Karabin\Fabriq\Services\CacheWebhookBatcher;
use Karabin\Fabriq\Services\ReferenceRegistry;

trait InvalidatesReferences
{
    /** @var array<int, array{type: string, id: int}> */
    private array $deletedReferenceDependencies = [];

    private array $previousReferenceCacheKeys = [];

    abstract public function referenceType(): string;

    private function fabriqReferenceId(): int
    {
        $id = filter_var($this->getKey(), FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new InvalidArgumentException('Reference IDs must be positive integers.');
        }

        return $id;
    }

    protected static function bootInvalidatesReferences(): void
    {
        static::updating(function ($model) {
            if (config('fabriq.webhooks.enabled')) {
                $original = clone $model;
                $original->setRawAttributes($model->getRawOriginal(), true);
                $model->previousReferenceCacheKeys = app(CacheBuster::class)->getCacheKeys($original)->all();
            }
        });

        static::saved(function ($model) {
            app(CacheWebhookBatcher::class)->defer(function () use ($model) {
                $invalidator = app(BlockReferenceInvalidator::class);
                if (config('fabriq.webhooks.enabled')) {
                    $invalidator->keysChanged([
                        ...$model->previousReferenceCacheKeys,
                        ...app(CacheBuster::class)->getCacheKeys($model)->all(),
                    ]);
                }
                $model->previousReferenceCacheKeys = [];
                $invalidator->resourceChanged($model->referenceType(), $model->fabriqReferenceId());
            });
        });

        static::deleting(function ($model) {
            if (! config('fabriq.webhooks.enabled')) {
                return;
            }
            $model->previousReferenceCacheKeys = app(CacheBuster::class)->getCacheKeys($model)->all();
            $model->deletedReferenceDependencies = iterator_to_array(
                app(ReferenceRegistry::class)->dependencies($model->referenceType(), $model->fabriqReferenceId()),
                false,
            );
        });

        static::deleted(function ($model) {
            app(CacheWebhookBatcher::class)->defer(function () use ($model) {
                $invalidator = app(BlockReferenceInvalidator::class);
                $invalidator->keysChanged($model->previousReferenceCacheKeys);
                $invalidator->resourceChanged($model->referenceType(), $model->fabriqReferenceId());
                foreach ($model->deletedReferenceDependencies as $reference) {
                    $invalidator->resourceChanged($reference['type'], $reference['id']);
                }
                $model->deletedReferenceDependencies = [];
                $model->previousReferenceCacheKeys = [];
            });
        });
    }
}
