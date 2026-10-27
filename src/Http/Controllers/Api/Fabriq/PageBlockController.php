<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Karabin\Fabriq\ContentGetters\ContentResourceContext;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Requests\UpdateBlockRequest;
use Karabin\Fabriq\Models\Block;

class PageBlockController
{
    public function index(Request $request, int $pageId): Collection
    {
        $blocks = Fabriq::getFqnModel('block')::buildQuery(Fabriq::getFqnModel('block'))
            ->where('model_type', 'fabriq_page')
            ->where('model_id', $pageId)
            ->whereNull('parent_id')
            ->get();

        return ContentResourceContext::withModels(
            $blocks,
            fn () => Fabriq::getDto('block')::collect($blocks),
        );
    }

    public function update(UpdateBlockRequest $request, int $pageId): JsonResponse
    {
        $page = Fabriq::getFqnModel('page')::findOrFail($pageId);
        $blockUuids = [];

        foreach ($request->array('blocks') as $block) {
            $savedBlock = Block::createBlock($block, $page);
            $blockUuids[] = $savedBlock->uuid;
        }

        Fabriq::getFqnModel('block')::where('model_id', $page->id)
            ->where('model_type', $page->getMorphClass())
            ->whereNull('parent_id')
            ->whereNotIn('uuid', $blockUuids)
            ->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Blocks has been updated',
        ]);
    }
}
