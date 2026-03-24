<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\InteractsWithAdminPages;
use Karabin\Fabriq\Http\Controllers\Admin\Concerns\TransformsBlockTypes;
use Karabin\Fabriq\Http\Requests\CreateSmartBlockRequest;
use Karabin\Fabriq\Http\Requests\UpdateSmartBlockRequest;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\SmartBlock;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SmartBlockController extends AdminController
{
    use InteractsWithAdminPages;
    use TransformsBlockTypes;

    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->input('search', $request->input('filter.search', '')));
        $sort = (string) $request->string('sort', 'name');
        [$sortColumn, $sortDirection] = $this->normalizeSmartBlockSort($sort);

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => ($sortDirection === 'desc' ? '-' : '').$sortColumn,
        ]);

        $smartBlocks = QueryBuilder::for(SmartBlock::query(), $request)
            ->allowedSorts([
                'name',
                'updated_at',
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->paginate(25);

        return Inertia::render('Admin/SmartBlocks/Index', [
            'pageTitle' => 'Smarta block',
            'filters' => [
                'search' => $search,
                'sort' => ($sortDirection === 'desc' ? '-' : '').$sortColumn,
            ],
            'smartBlocks' => [
                'data' => $this->transformSmartBlocks($smartBlocks),
                'pagination' => $this->paginationMeta($smartBlocks),
            ],
        ]);
    }

    public function store(CreateSmartBlockRequest $request): RedirectResponse
    {
        $smartBlock = new SmartBlock;
        $smartBlock->name = (string) $request->string('name');
        $smartBlock->save();

        return to_route('admin.smart-blocks.index')->with([
            'status' => 'Det smarta blocket skapades.',
            'status_action_label' => 'Gå till blocket',
            'status_action_href' => '/admin/smart-blocks/'.$smartBlock->id.'/edit',
        ]);
    }

    public function show(Request $request, int $smartBlockId): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $smartBlock = SmartBlock::query()->findOrFail($smartBlockId);

        return Inertia::render('Admin/SmartBlocks/Edit', [
            'pageTitle' => 'Redigera smart block',
            'smartBlock' => $this->transformEditableSmartBlock($smartBlock),
            'blockTypes' => $this->transformBlockTypes(
                BlockType::query()
                    ->where('active', 1)
                    ->orderBy('name')
                    ->get()
            ),
            'pageOptions' => $this->pageTreeOptions(),
        ]);
    }

    public function update(UpdateSmartBlockRequest $request, int $smartBlockId): RedirectResponse
    {
        $smartBlock = SmartBlock::query()->findOrFail($smartBlockId);
        $validated = $request->validated();

        $smartBlock->name = (string) ($validated['name'] ?? $smartBlock->name);
        $smartBlock->localizedContent = $validated['localizedContent'] ?? [];
        $smartBlock->touch();
        $smartBlock->save();

        return to_route('admin.smart-blocks.edit', ['smartBlockId' => $smartBlock->id])
            ->with('status', 'Det smarta blocket uppdaterades.');
    }

    public function destroy(int $smartBlockId): RedirectResponse
    {
        $smartBlock = SmartBlock::query()->findOrFail($smartBlockId);
        $smartBlock->delete();

        return to_route('admin.smart-blocks.index')->with('status', 'Det smarta blocket raderades.');
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function normalizeSmartBlockSort(string $sort): array
    {
        $direction = Str::startsWith($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        $allowed = ['name', 'updated_at'];

        if (! in_array($column, $allowed, true)) {
            return ['name', 'asc'];
        }

        return [$column, $direction];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformSmartBlocks(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $smartBlock) {
            if (! $smartBlock instanceof SmartBlock) {
                continue;
            }

            $items[] = [
                'id' => $smartBlock->id,
                'name' => $smartBlock->name,
                'updatedAt' => $smartBlock->updated_at?->toIso8601String(),
                'editPath' => '/admin/smart-blocks/'.$smartBlock->id.'/edit',
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEditableSmartBlock(SmartBlock $smartBlock): array
    {
        $supportedLocales = app(config('fabriq.models.locale'))->cachedLocales()->values();
        $localizedContent = [];

        foreach ($supportedLocales as $locale) {
            $isoCode = (string) data_get($locale, 'iso_code');

            if ($isoCode === '') {
                continue;
            }

            $content = $smartBlock->getSimpleFieldContent($smartBlock->revision, $isoCode)->toArray();
            $localizedContent[$isoCode] = [
                ...$content,
                'boxes' => is_array(data_get($content, 'boxes'))
                    ? array_values((array) data_get($content, 'boxes', []))
                    : [],
            ];
        }

        return [
            'id' => $smartBlock->id,
            'name' => $smartBlock->name,
            'localizedContent' => $localizedContent,
        ];
    }
}
