<?php

namespace Karabin\Fabriq\Services;

use Closure;
use Karabin\Fabriq\Actions\BustCacheWithWebhook;

class CacheWebhookBatcher
{
    private int $depth = 0;

    private array $keys = [];

    private array $tags = [];

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function defer(Closure $callback): mixed
    {
        $this->depth++;

        try {
            return $callback();
        } finally {
            $this->depth--;
            if ($this->depth === 0) {
                $keys = $this->keys;
                $tags = $this->tags;
                $this->keys = [];
                $this->tags = [];

                if ($keys !== [] || $tags !== []) {
                    app(BustCacheWithWebhook::class)->handle($keys, $tags, rateLimit: false);
                }
            }
        }
    }

    public function send(array $keys, array $tags = [], bool $rateLimit = true): void
    {
        if ($this->depth > 0) {
            $this->keys = array_values(array_unique([...$this->keys, ...$keys]));
            $this->tags = array_values(array_unique([...$this->tags, ...$tags]));

            return;
        }

        app(BustCacheWithWebhook::class)->handle($keys, $tags, $rateLimit);
    }
}
