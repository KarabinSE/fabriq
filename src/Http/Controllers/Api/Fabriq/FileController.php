<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Karabin\Fabriq\Data\FileData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\QueryBuilders\FileSort;
use Karabin\Fabriq\QueryBuilders\TagSort;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    /**
     * Get index of the resource.
     */
    public function index(Request $request): PaginatedDataCollection
    {
        $number = $request->integer('number', 100);

        $files = QueryBuilder::for(Fabriq::getFqnModel('file'))
            ->allowedSorts([
                'id', 'created_at', 'updated_at', 'alt_text',
                AllowedSort::custom('file_name', new FileSort),
                AllowedSort::custom('size', new FileSort),
                AllowedSort::custom('tags', new TagSort, 'files'),
            ])
            ->allowedFilters([
                AllowedFilter::scope('search'),
            ])
            ->allowedIncludes(...File::RELATIONSHIPS)
            ->paginate($number);

        return FileData::collect($files, PaginatedDataCollection::class);
    }

    public function show(Request $request, int $id): Response
    {
        $file = QueryBuilder::for(Fabriq::getFqnModel('file'))
            ->allowedIncludes(...File::RELATIONSHIPS)
            ->where('id', $id)
            ->firstOrFail();

        /** @var File $file */

        return FileData::fromModel($file)->wrap('data')->toResponse($request);
    }

    public function update(Request $request, int $id): Response
    {
        $file = Fabriq::getFqnModel('file')::findOrFail($id);

        $file->readable_name = $request->readable_name;
        $file->caption = $request->caption;
        $file->fileTags = $request->tags;
        $media = $file->getFirstmedia('files');
        $media->name = $request->name;
        $media->save();
        $file->save();

        return FileData::fromModel($file)->wrap('data')->toResponse($request);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $file = Fabriq::getFqnModel('file')::findOrFail($id);
        $file->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'The file has been deleted',
        ]);
    }
}
