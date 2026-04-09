<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\SmartBlockData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateSmartBlockRequest;
use Karabin\Fabriq\Models\SmartBlock;
use Karabin\Fabriq\QueryBuilders\NoOpInclude;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class SmartBlockController extends Controller
{
    /**
     * Get index of the resource.
     */
    public function index(Request $request): PaginatedDataCollection
    {
        $number = $request->integer('number', 100);
        $allowedIncludes = [
            AllowedInclude::custom('localizedContent', new NoOpInclude),
            AllowedInclude::custom('content', new NoOpInclude),
        ];

        $smartBlocks = QueryBuilder::for(Fabriq::getFqnModel('smartBlock'))
            ->allowedSorts('name', 'updated_at')
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->allowedIncludes(...$allowedIncludes)
            ->paginate($number);

        return SmartBlockData::collect($smartBlocks, PaginatedDataCollection::class);
    }

    public function show(Request $request, int $id): Response
    {
        $allowedIncludes = [
            AllowedInclude::custom('localizedContent', new NoOpInclude),
            AllowedInclude::custom('content', new NoOpInclude),
        ];

        $smartBlock = QueryBuilder::for(Fabriq::getFqnModel('smartBlock'))
            ->allowedIncludes(...$allowedIncludes)
            ->where('id', $id)
            ->firstOrFail();

        /** @var SmartBlock $smartBlock */

        return SmartBlockData::fromModel($smartBlock)->wrap('data')->toResponse($request);
    }

    public function store(CreateSmartBlockRequest $request): Response
    {
        $smartBlock = new SmartBlock;
        $smartBlock->name = $request->name;
        $smartBlock->save();

        return SmartBlockData::fromModel($smartBlock)->wrap('data')->toResponse($request);
    }

    public function update(Request $request, int $id): Response
    {
        $smartBlock = SmartBlock::findOrFail($id);
        $smartBlock->name = $request->name;
        $smartBlock->localizedContent = $request->localizedContent;
        $smartBlock->touch();
        $smartBlock->save();

        return SmartBlockData::fromModel($smartBlock)->wrap('data')->toResponse($request);
    }

    public function destroy(int $id): JsonResponse
    {
        $smartBlock = SmartBlock::findOrFail($id);

        $smartBlock->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Smart block has been deleted successfully',
        ]);
    }
}
