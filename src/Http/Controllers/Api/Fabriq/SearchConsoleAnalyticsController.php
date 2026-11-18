<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Services\SearchConsoleService;

class SearchConsoleAnalyticsController extends Controller
{
    public function __invoke(
        Request $request,
        SearchConsoleService $searchConsole
    ): JsonResponse {
        $period = $request->string('period', '30d')->toString();
        $siteUrl = config('google.config.site_url');

        $data = Cache::remember(
            "search-console-analytics:{$period}{$siteUrl}",
            now()->addMinutes(10),
            fn (): object => $searchConsole->getTopSearchTerms(config('google.config.site_url'), $period)
        );

        return response()->json($data);
    }
}
