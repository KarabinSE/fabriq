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

        foreach ($request->blocks as $block) {
            $savedBlock = Block::createBlock($block, $page);
            $blockUuids[] = $savedBlock->uuid;
        }

        Fabriq::getFqnModel('block')::where('model_id', $page->id)
            ->where('model_type', $page->getMorphClass())
            ->whereNotNull('parent_id')
            ->whereNotIn('uuid', $blockUuids)
            ->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Blocks has been updated',
        ]);
    }

    private function saveBlocks($blocks, $page)
    {
        $savedBlocks = [];
        foreach ($blocks as $block) {
            $savedBlock = $this->saveBlock($block, $page, $block);
            $savedBlocks[] = $savedBlock->uuid;

            $children = data_get($block, 'content.children', []);
            $ids = $this->saveBlocks($children, $page);
            $savedBlocks = [
                ...$savedBlocks,
                ...$ids,
            ];
        }

        return [
            ...$savedBlocks,
        ];
    }

    private function saveBlock($data, $page, $parent)
    {
        $savedBlock = Fabriq::getFqnModel('block')::updateOrCreate([
            'uuid' => $data['uuid'] ?? null,
        ], [
            'name' => $data['name'],
            'content' => (object) ($data['content'] ?? []),
            'model_id' => $page->id,
            'locale' => $data['locale'] ?? $parent['locale'],
            'model_type' => $page->getMorphClass(),
            'block_type_id' => $data['block_type_id'] ?? $parent['block_type_id'],
        ]);

        return $savedBlock;
    }

    // public function store(Request $request)
    // {
    // }

    // public function show($id)
    // {
    // }

    // public function update(Request $request, $id)
    // {
    // }

    // public function destroy($id)
    // {
    // }
}
