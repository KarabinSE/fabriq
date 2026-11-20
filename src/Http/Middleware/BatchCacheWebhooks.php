<?php

namespace Karabin\Fabriq\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Karabin\Fabriq\Services\CacheWebhookBatcher;
use Symfony\Component\HttpFoundation\Response;

class BatchCacheWebhooks
{
    public function handle(Request $request, Closure $next): Response
    {
        return app(CacheWebhookBatcher::class)->defer(fn () => $next($request));
    }
}
