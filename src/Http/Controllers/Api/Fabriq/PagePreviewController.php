<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Karabin\Fabriq\Data\LivePageData;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Preview;

class PagePreviewController
{
    public function store(Request $request)
    {
        $preview = Preview::updateOrCreate(
            [
                'model_id' => 7,
                'model_type' => 'fabriq_page',
            ],
            [
                'content' => $request->content,
            ]
        );

        if ($preview->wasRecentlyCreated) {
            $preview->update(['uuid' => Str::uuid()]);
        }

        return $preview;
    }

    public function show(Request $request, string $uuid)
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
