<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\ArticleData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\CreateArticleRequest;
use Karabin\Fabriq\Http\Requests\UpdateArticleRequest;
use Karabin\Fabriq\Models\Article;
use Karabin\Fabriq\QueryBuilders\NoOpInclude;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends Controller
{
    public function index(Request $request): PaginatedDataCollection
    {
        $number = $request->integer('number', 100);
        $allowedIncludes = [
            ...Fabriq::getFqnModel('article')::RELATIONSHIPS,
            AllowedInclude::custom('content', new NoOpInclude),
        ];

        $articles = QueryBuilder::for(Fabriq::getFqnModel('article'))
            ->allowedSorts(['name', 'updated_at', 'publishes_at'])
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('published'),
            ])
            ->allowedIncludes(...$allowedIncludes)
            ->paginate($number);

        return ArticleData::collect($articles, PaginatedDataCollection::class);
    }

    public function show(Request $request, int $id): Response
    {
        $allowedIncludes = [
            ...Fabriq::getFqnModel('article')::RELATIONSHIPS,
            AllowedInclude::custom('content', new NoOpInclude),
        ];

        $article = QueryBuilder::for(Fabriq::getFqnModel('article'))
            ->allowedIncludes(...$allowedIncludes)
            ->where('id', $id)
            ->firstOrFail();

        /** @var Article $article */

        return ArticleData::fromModel($article)->wrap('data')->toResponse($request);
    }

    public function store(CreateArticleRequest $request): Response
    {
        $article = Fabriq::getModelClass('article');
        $article->fill($request->validated());
        $article->template_id = 2;
        $article->save();

        return ArticleData::fromModel($article)->wrap('data')->toResponse($request);
    }

    public function update(UpdateArticleRequest $request, int $id): Response
    {
        $article = Fabriq::getFqnModel('article')::findOrFail($id);
        $article->fill($request->validated());
        $article->updateContent($request->content);
        $article->save();

        return ArticleData::fromModel($article)->wrap('data')->toResponse($request);
    }

    public function destroy(int $id): JsonResponse
    {
        $article = Fabriq::getFqnModel('article')::findOrFail($id);
        $article->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Article deleted successfully',
        ]);
    }
}
