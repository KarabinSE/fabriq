<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Page;

class PagePreviewUrlController extends AdminController
{
    public function show(Request $request, int $pageId): JsonResponse
    {
        $page = Fabriq::getFqnModel('page')::query()
            ->with('slugs')
            ->findOrFail($pageId);

        abort_unless($page instanceof Page, 404);

        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $locale = (string) $request->string('locale', (string) data_get($supportedLocales->first(), 'iso_code', app()->getLocale()));
        $slug = $page->slugs->firstWhere('locale', $locale)?->slug ?? $page->slugs->first()?->slug;

        abort_if(! is_string($slug) || $slug === '', 404, 'Could not build preview URL for page.');

        $signedUrl = URL::signedRoute('pages.show.preview', ['slug' => $slug]);
        $prefix = $supportedLocales->count() > 1 ? '/'.$locale : '';

        return response()->json([
            'data' => [
                'url' => rtrim((string) config('fabriq.front_end_domain'), '/')
                    .$prefix
                    .'/'.$slug
                    .'?preview='
                    .base64_encode($signedUrl),
            ],
        ]);
    }
}
