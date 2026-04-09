<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\VideoData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\Video;
use Karabin\Fabriq\QueryBuilders\TagSort;
use Karabin\Fabriq\QueryBuilders\VideoSort;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class VideoController extends Controller
{
    public function index(Request $request): PaginatedDataCollection
    {
        $number = $request->integer('number', 100);

        $videos = QueryBuilder::for(Fabriq::getFqnModel('video'))
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts([
                'id', 'created_at', 'updated_at', 'alt_text',
                AllowedSort::custom('file_name', new VideoSort),
                AllowedSort::custom('size', new VideoSort),
                AllowedSort::custom('tags', new TagSort, 'videos'),
            ])
            ->allowedIncludes(...Video::RELATIONSHIPS)
            ->paginate($number);

        return VideoData::collect($videos, PaginatedDataCollection::class);
    }

    public function show(Request $request, int $id): Response
    {
        $video = QueryBuilder::for(Fabriq::getFqnModel('video'))
            ->allowedIncludes(...Video::RELATIONSHIPS)
            ->where('id', $id)
            ->firstOrFail();

        /** @var Video $video */

        return VideoData::fromModel($video)->wrap('data')->toResponse($request);
    }

    public function update(Request $request, int $id): Response
    {
        $video = Fabriq::getFqnModel('video')::findOrFail($id);
        $video->alt_text = $request->alt_text;
        $video->caption = $request->caption;

        $video->videoTags = $request->tags;

        $media = $video->getFirstmedia('videos');
        $media->name = $request->name;
        $media->save();

        $video->save();

        return VideoData::fromModel($video)->wrap('data')->toResponse($request);
    }

    public function destroy(int $id): JsonResponse
    {
        $video = Fabriq::getFqnModel('video')::findOrFail($id);
        $video->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Video has been deleted',
        ]);
    }
}
