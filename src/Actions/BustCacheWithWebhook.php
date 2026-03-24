<?php

namespace Karabin\Fabriq\Actions;

use Illuminate\Support\Facades\RateLimiter;
use Spatie\WebhookServer\WebhookCall;

class BustCacheWithWebhook
{
    public function handle(array $keysToForget, array $tagsToFlush = []): void
    {
        $urls = collect(explode(',', (string) config('fabriq.webhooks.endpoint')))
            ->map(static fn (string $url): string => trim($url))
            ->filter();

        if (! config('fabriq.webhooks.enabled') || $urls->isEmpty()) {
            return;
        }

        // 1 per 5 seconds for the same key
        RateLimiter::attempt(
            key: hash('adler32', json_encode([$keysToForget, $tagsToFlush])),
            maxAttempts: 1,
            callback: function () use ($keysToForget, $tagsToFlush, $urls) {
                foreach ($urls as $url) {
                    WebhookCall::create()
                        ->url($url)
                        ->payload([
                            'type' => 'cache_expiration',
                            'invalid_cache_keys' => $keysToForget,
                            'invalid_cache_tags' => $tagsToFlush,
                        ])
                        ->useSecret(config('fabriq.webhooks.secret'))
                        ->dispatch();
                }
            },
            decaySeconds: 1
        );
    }
}
