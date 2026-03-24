<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\PageData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreatePageRequest;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\QueryBuilders\NoOpInclude;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    /**
     * Return index of pages.
     */
    public function index(Request $request): Response
    {
        $number = $request->integer('number', 100);
        $allowedIncludes = [
            ...Fabriq::getModelClass('page')::RELATIONSHIPS,
            AllowedInclude::relationship('slugs'),
            AllowedInclude::relationship('children'),
            AllowedInclude::custom('content', new NoOpInclude),
            AllowedInclude::custom('localizedContent', new NoOpInclude),
            AllowedInclude::custom('template.groupedFields', new NoOpInclude),
        ];

        $pages = QueryBuilder::for(Fabriq::getFqnModel('page'))
            ->allowedSorts('name', 'slug', 'id', 'created_at', 'updated_at')
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::exact('template_id'),
            ])
            ->allowedIncludes(...$allowedIncludes)
            ->paginate($number);

        return PageData::collect($pages, PaginatedDataCollection::class)
            ->wrap('data')
            ->toResponse($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id): Response
    {
        $allowedIncludes = [
            ...Fabriq::getModelClass('page')::RELATIONSHIPS,
            AllowedInclude::relationship('slugs'),
            AllowedInclude::relationship('children'),
            AllowedInclude::custom('content', new NoOpInclude),
            AllowedInclude::custom('localizedContent', new NoOpInclude),
            AllowedInclude::custom('template.groupedFields', new NoOpInclude),
        ];

        $page = QueryBuilder::for(Fabriq::getFqnModel('page'))
            ->allowedIncludes(...$allowedIncludes)
            ->where('id', $id)
            ->firstOrFail();

        /** @var Page $page */

        return PageData::fromModel($page)->wrap('data')->toResponse($request);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, int $id): Response
    {
        $allowedIncludes = [
            ...Fabriq::getModelClass('page')::RELATIONSHIPS,
            AllowedInclude::relationship('slugs'),
            AllowedInclude::relationship('children'),
            AllowedInclude::custom('content', new NoOpInclude),
            AllowedInclude::custom('localizedContent', new NoOpInclude),
            AllowedInclude::custom('template.groupedFields', new NoOpInclude),
        ];

        $page = QueryBuilder::for(Fabriq::getFqnModel('page'))
            ->allowedIncludes(...$allowedIncludes)
            ->where('id', $id)
            ->firstOrFail();

        /** @var Page $page */
        $page->name = $request->name;
        $page->touch();
        $page->localizedContent = $request->localizedContent;
        $page->updated_by = $request->user()->id;

        $page->save();

        return PageData::fromModel($page)->wrap('data')->toResponse($request);
    }

    /**
     * Create a new resource.
     */
    public function store(CreatePageRequest $request): Response
    {
        $pageRoot = Fabriq::getModelClass('page')->whereNull('parent_id')
            ->select('id')
            ->firstOrFail();

        $page = new Page;
        $page->name = $request->name;
        $page->template_id = $request->template_id;
        $page->parent_id = $pageRoot->id;
        $page->updated_by = $request->user()->id;
        $page->save();

        return PageData::fromModel($page)
            ->wrap('data')
            ->toResponse($request)
            ->setStatusCode(201);
    }

    /**
     * Create a new resource.
     */
    public function destroy(int $id): JsonResponse
    {
        $page = Fabriq::getModelClass('page')->where('id', $id)->firstOrFail();

        $page->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Page has been deleted',
        ]);
    }
}
