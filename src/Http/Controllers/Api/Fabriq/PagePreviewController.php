<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Karabin\Fabriq\Data\LivePageData;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Preview;

class PagePreviewController
{
    public function store(Request $request): JsonResponse
    {
        $page = Fabriq::getModelClass('page')->findOrFail($request->id);

        $preview = Preview::firstOrNew([
            'model_id' => $page->id,
            'model_type' => 'fabriq_page',
        ], [
            'content' => $request->content,
        ]);

        $previewUrl = config('fabriq.front_end_domain').'/'.App::currentLocale().$page->localizedPaths->flatten()->first().'?preview='.$preview->uuid;

        return response()->json([
            'preview_url' => $previewUrl,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $preview = Preview::updateOrCreate(
            [
                'model_id' => $request->id,
                'model_type' => 'fabriq_page',
            ],
            [
                'content' => $request->content,
            ]
        );

        return response()->json(['message' => 'Preview updated'], 200);
    }

    public function show(Request $request, string $uuid): LivePageData
    {
        $locale = $request->input('locale', 'sv');
        $preview = Preview::whereUuid($uuid)
            ->firstOrFail();
        $page = Page::where('id', $preview->model_id)
            ->firstOrFail();
        $page->content = $preview['content'][$locale];

        return LivePageData::from($page)->wrap('data');
    }
}
