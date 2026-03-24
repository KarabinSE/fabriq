<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\QueryBuilders\FileSort;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class FileController extends AdminController
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = $this->normalizeFileSort((string) $request->string('sort', '-created_at'));
        /** @var class-string<File> $fileModel */
        $fileModel = Fabriq::getFqnModel('file');

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => $sort,
        ]);

        $files = QueryBuilder::for($fileModel, $request)
            ->allowedSorts([
                'created_at',
                'updated_at',
                AllowedSort::custom('file_name', new FileSort),
                AllowedSort::custom('size', new FileSort),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->with('tags')
            ->paginate(20);

        return Inertia::render('Admin/Files/Index', [
            'pageTitle' => 'Filer',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'files' => [
                'data' => $this->transformFiles($files),
                'pagination' => $this->paginationMeta($files),
            ],
        ]);
    }

    private function normalizeFileSort(string $sort): string
    {
        $column = ltrim($sort, '-');
        $allowed = ['created_at', 'updated_at', 'file_name', 'size'];

        if (! in_array($column, $allowed, true)) {
            return '-created_at';
        }

        return Str::startsWith($sort, '-') ? '-'.$column : $column;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformFiles(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $file) {
            if (! $file instanceof File) {
                continue;
            }

            $media = $file->getFirstMedia('files');

            if (! $media) {
                continue;
            }

            $items[] = [
                'id' => $file->id,
                'name' => $file->readable_name ?: $media->name,
                'fileName' => $media->file_name,
                'extension' => Str::upper(Str::afterLast($media->file_name, '.')),
                'thumbnailUrl' => $media->hasGeneratedConversion('file_thumb') ? $media->getUrl('file_thumb') : null,
                'sourceUrl' => $media->getUrl(),
                'caption' => $file->caption,
                'size' => $media->size,
                'createdAt' => $file->created_at?->toIso8601String(),
                'updatedAt' => $file->updated_at?->toIso8601String(),
                'tags' => $file->tags->pluck('name')->values()->all(),
            ];
        }

        return $items;
    }
}
