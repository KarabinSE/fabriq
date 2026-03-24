<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\QueryBuilders\ImageSort;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ImageController extends AdminController
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = $this->normalizeImageSort((string) $request->string('sort', '-created_at'));
        /** @var class-string<Image> $imageModel */
        $imageModel = Fabriq::getFqnModel('image');

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => $sort,
        ]);

        $images = QueryBuilder::for($imageModel, $request)
            ->allowedSorts([
                'created_at',
                'updated_at',
                'alt_text',
                AllowedSort::custom('c_name', new ImageSort, 'name'),
                AllowedSort::custom('size', new ImageSort),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->has('mediaImages')
            ->with('tags')
            ->paginate(20);

        return Inertia::render('Admin/Images/Index', [
            'pageTitle' => 'Bilder',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'images' => [
                'data' => $this->transformImages($images),
                'pagination' => $this->paginationMeta($images),
            ],
        ]);
    }

    private function normalizeImageSort(string $sort): string
    {
        $column = ltrim($sort, '-');
        $allowed = ['created_at', 'updated_at', 'alt_text', 'c_name', 'size'];

        if (! in_array($column, $allowed, true)) {
            return '-created_at';
        }

        return Str::startsWith($sort, '-') ? '-'.$column : $column;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformImages(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $image) {
            if (! $image instanceof Image) {
                continue;
            }

            $media = $image->getFirstMedia('images');

            if (! $media) {
                continue;
            }

            $items[] = [
                'id' => $image->id,
                'name' => $media->name,
                'fileName' => $media->file_name,
                'extension' => Str::upper(Str::afterLast($media->file_name, '.')),
                'thumbnailUrl' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                'sourceUrl' => $media->getUrl(),
                'altText' => $image->alt_text,
                'caption' => $image->caption,
                'size' => $media->size,
                'width' => $media->getCustomProperty('width'),
                'height' => $media->getCustomProperty('height'),
                'processing' => (bool) $media->getCustomProperty('processing'),
                'processingFailed' => (bool) $media->getCustomProperty('processing_failed'),
                'createdAt' => $image->created_at?->toIso8601String(),
                'updatedAt' => $image->updated_at?->toIso8601String(),
                'tags' => $image->tags->pluck('name')->values()->all(),
            ];
        }

        return $items;
    }
}
