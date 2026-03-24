<?php

namespace Karabin\Fabriq\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Karabin\Fabriq\Fabriq;

class PermalinksRedirectController extends Controller
{
    /**
     * Return redirect or paths.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function __invoke(Request $request, string $hash, string $locale = 'sv')
    {
        App::setLocale($locale);
        $page = Fabriq::getModelClass('page')->whereHash($hash)
            ->with('slugs', 'menuItems', 'latestSlug')
            ->firstOrFail();

        $paths = $page->transformPaths();

        if (request()->wantsJson()) {
            return response()->json([
                'data' => $paths,
            ]);
        }

        return Response()->redirectTo($paths['absolute_path']);
    }
}
