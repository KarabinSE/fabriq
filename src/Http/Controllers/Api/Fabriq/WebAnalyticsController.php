<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Karabin\Fabriq\Services\CloudflareAnalytics;

class WebAnalyticsController
{
    public function __invoke(
        Request $request,
        CloudflareAnalytics $analytics
    ): JsonResponse {
        $period = $request->string('period', '30d')->toString();

        $data = Cache::remember(
            "cloudflare-analytics:{$period}",
            now()->addMinutes(10),
            fn () => $analytics->getAnalytics($period)
        );

        return response()->json($data);
    }
}
