<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Requests\CreateArticleRequest;
use Karabin\Fabriq\Http\Requests\UpdateArticleRequest;
use Karabin\Fabriq\Models\Article;

class ArticleController extends AdminController
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->input('search', $request->input('filter.search', '')));
        $sort = (string) $request->string('sort', '-publishes_at');
        [$sortColumn, $sortDirection] = $this->normalizeArticleSort($sort);
        $articleModel = Fabriq::getFqnModel('article');

        $articles = $articleModel::query()
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(20);

        return Inertia::render('Admin/Articles/Index', [
            'pageTitle' => 'Nyheter',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'articles' => [
                'data' => $this->transformArticles($articles),
                'pagination' => $this->paginationMeta($articles),
            ],
        ]);
    }

    public function store(CreateArticleRequest $request): RedirectResponse
    {
        $article = Fabriq::getModelClass('article');
        $article->fill($request->validated());
        $article->template_id = 2;
        $article->save();

        return to_route('admin.articles.index')->with([
            'status' => 'Nyheten skapades.',
            'status_action_label' => 'Gå till nyheten',
            'status_action_href' => '/admin/articles/'.$article->id.'/edit',
        ]);
    }

    public function show(Request $request, int $articleId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $article = Fabriq::getFqnModel('article')::query()->findOrFail($articleId);

        abort_unless($article instanceof Article, 404);

        return Inertia::render('Admin/Articles/Edit', [
            'pageTitle' => 'Redigera nyhet',
            'article' => $this->transformEditableArticle($article),
        ]);
    }

    public function update(UpdateArticleRequest $request, int $articleId): RedirectResponse
    {
        $article = Fabriq::getFqnModel('article')::query()->findOrFail($articleId);

        abort_unless($article instanceof Article, 404);

        $validated = $request->validated();

        $article->fill($validated);
        $article->updateContent($validated['content'] ?? []);
        $article->save();

        return to_route('admin.articles.edit', ['articleId' => $article->id])->with('status', 'Nyheten uppdaterades.');
    }

    public function destroy(int $articleId): RedirectResponse
    {
        $article = Fabriq::getFqnModel('article')::query()->findOrFail($articleId);

        abort_unless($article instanceof Article, 404);

        $article->delete();

        return to_route('admin.articles.index')->with('status', 'Nyheten raderades.');
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeArticleSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'publishes_at', 'updated_at'];

        if (! in_array($column, $allowed, true)) {
            return ['publishes_at', 'desc'];
        }

        return [$column, $direction];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformArticles(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $article) {
            if (! $article instanceof Article) {
                continue;
            }

            $items[] = [
                'id' => $article->id,
                'name' => $article->name,
                'isPublished' => (bool) $article->is_published,
                'publishesAt' => $article->publishes_at?->toIso8601String(),
                'unpublishesAt' => $article->unpublishes_at?->toIso8601String(),
                'hasUnpublishedTime' => (bool) $article->has_unpublished_time,
                'updatedAt' => $article->updated_at?->toIso8601String(),
                'editPath' => '/admin/articles/'.$article->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableArticle(Article $article): array
    {
        $content = $article->getFieldContent($article->revision);
        $publishesAt = $article->publishes_at?->timezone('Europe/Stockholm');
        $unpublishesAt = $article->unpublishes_at?->timezone('Europe/Stockholm');

        return [
            'id' => $article->id,
            'name' => $article->name,
            'publishesAt' => $publishesAt?->format('Y-m-d\TH:i'),
            'unpublishesAt' => $unpublishesAt?->format('Y-m-d\TH:i'),
            'hasUnpublishedTime' => (bool) $article->has_unpublished_time,
            'content' => [
                'title' => (string) $content->get('title', ''),
                'preamble' => (string) $content->get('preamble', ''),
                'body' => (string) $content->get('body', ''),
                'image' => $content->get('image'),
            ],
        ];
    }
}
