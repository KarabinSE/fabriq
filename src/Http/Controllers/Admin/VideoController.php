<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Models\Video;
use Karabin\Fabriq\QueryBuilders\VideoSort;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class VideoController extends AdminController
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        $search = trim((string) $request->string('search'));
        $sort = $this->normalizeVideoSort((string) $request->string('sort', '-created_at'));
        /** @var class-string<Video> $videoModel */
        $videoModel = Fabriq::getFqnModel('video');

        $request->merge([
            'filter' => [
                'search' => $search,
            ],
            'sort' => $sort,
        ]);

        $videos = QueryBuilder::for($videoModel, $request)
            ->allowedSorts([
                'created_at',
                'updated_at',
                'alt_text',
                AllowedSort::custom('file_name', new VideoSort),
                AllowedSort::custom('size', new VideoSort),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->with('tags')
            ->paginate(20);

        return Inertia::render('Admin/Videos/Index', [
            'pageTitle' => 'Videos',
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
            'videos' => [
                'data' => $this->transformVideos($videos),
                'pagination' => $this->paginationMeta($videos),
            ],
        ]);
    }

    private function normalizeVideoSort(string $sort): string
    {
        $column = ltrim($sort, '-');
        $allowed = ['created_at', 'updated_at', 'alt_text', 'file_name', 'size'];

        if (! in_array($column, $allowed, true)) {
            return '-created_at';
        }

        return Str::startsWith($sort, '-') ? '-'.$column : $column;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function transformVideos(LengthAwarePaginator $paginator): array
    {
        $items = [];

        foreach ($paginator->items() as $video) {
            if (! $video instanceof Video) {
                continue;
            }

            $media = $video->getFirstMedia('videos');

            if (! $media) {
                continue;
            }

            $thumbnailUrl = null;

            if ($media->hasGeneratedConversion('thumb')) {
                $thumbnailUrl = $media->getUrl('thumb');
            } elseif ($media->hasGeneratedConversion('poster')) {
                $thumbnailUrl = $media->getUrl('poster');
            }

            $items[] = [
                'id' => $video->id,
                'name' => $media->name,
                'fileName' => $media->file_name,
                'extension' => Str::upper(Str::afterLast($media->file_name, '.')),
                'thumbnailUrl' => $thumbnailUrl,
                'sourceUrl' => $media->getUrl(),
                'altText' => $video->alt_text,
                'caption' => $video->caption,
                'size' => $media->size,
                'createdAt' => $video->created_at?->toIso8601String(),
                'updatedAt' => $video->updated_at?->toIso8601String(),
                'tags' => $video->tags->pluck('name')->values()->all(),
            ];
        }

        return $items;
    }
}
