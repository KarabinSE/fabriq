<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\BlockTypeData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateBlockTypeRequest;
use Karabin\Fabriq\Http\Requests\UpdateBlockTypeRequest;
use Karabin\Fabriq\Models\BlockType;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class BlockTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $blockTypes = QueryBuilder::for(BlockType::where('active', 1))
            ->allowedSorts('name', 'id')
            ->defaultSort('name')
            ->get();

        return BlockTypeData::collect($blockTypes, DataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    public function store(CreateBlockTypeRequest $request): Response
    {
        $blockType = new BlockType;
        $blockType->fill($request->validated());
        $blockType->active = true;
        $blockType->type = 'block';
        $blockType->options = [
            'recommended_for' => [],
            'visible_for' => [],
            'hidden_for' => [],
        ];

        $blockType->save();

        return BlockTypeData::fromModel($blockType)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(201);
    }

    public function update(UpdateBlockTypeRequest $request, int $id): Response
    {
        $blockType = BlockType::findOrFail($id);
        $blockType->fill($request->validated());
        $blockType->save();

        return BlockTypeData::fromModel($blockType)
            ->wrap('data')
            ->toResponse($request);
    }

    public function destroy(int $id): JsonResponse
    {
        $blockType = BlockType::findOrFail($id);
        $blockType->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Block type has been deleted',
        ]);
    }
}
