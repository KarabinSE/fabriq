<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;

class ConfigController extends Controller
{
    /**
     * Return config.
     */
    public function index(): JsonResponse
    {
        $config = new Collection(config('fabriq'));
        $fabriqConfig = $config->only([
            'models',
            'modules',
            'front_end_domain',
            'extras',
            'ui',
        ])->toArray();

        $supportedLocales = Fabriq::getModelClass('locale')->cachedLocales();
        $config = array_merge($fabriqConfig, ['supported_locales' => $supportedLocales]);

        return response()->json([
            'data' => collect($config)
                ->reject(fn ($item, $key) => Str::contains((string) $key, 'key'))
                ->all(),
        ]);
    }
}
